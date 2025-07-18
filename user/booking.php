<?php
require_once '../config/connect.php';
session_start();
$user_id = $_SESSION['user_id'] ?? null;
if (!$user_id) {
    header('Location: /Hotel-Restaurant/auth/login.php');
    exit;
}

$room_id = $_GET['room_id'] ?? null;
$booking_msg = '';
$booking_id = null;

// Fetch room info for booking form
$room = null;
if ($room_id) {
    $sql = "SELECT * FROM rooms WHERE room_id = :room_id";
    $stmt = oci_parse($connection, $sql);
    oci_bind_by_name($stmt, ':room_id', $room_id);
    if (oci_execute($stmt)) {
        $room = oci_fetch_assoc($stmt);
        if ($room && isset($room['DESCRIPTION']) && is_object($room['DESCRIPTION']) && $room['DESCRIPTION'] instanceof OCILob) {
            $room['DESCRIPTION'] = $room['DESCRIPTION']->load();
        }
        // Image handling
        $default_img = '/Hotel-Restaurant/assets/img/default-room.jpg';
        $img = trim($room['IMAGE_URL'] ?? '');
        $filename = $img ? basename($img) : '';
        $local_url = '/Hotel-Restaurant/uploads/rooms/' . $filename;
        $file_path = $_SERVER['DOCUMENT_ROOT'] . $local_url;
        if ($img && preg_match('/^https?:\/\//', $img)) {
            $room['IMAGE_URL_DISPLAY'] = $img;
        } elseif ($filename && file_exists($file_path)) {
            $room['IMAGE_URL_DISPLAY'] = $local_url;
        } else {
            $room['IMAGE_URL_DISPLAY'] = $default_img;
        }
    } else {
        $room = null;
    }
}

// Handle booking form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['book_room'])) {
    $room_id = $_POST['room_id'];
    $check_in = $_POST['check_in'];
    $check_out = $_POST['check_out'];
    $guest_name = $_POST['guest_name'] ?? ($_SESSION['name'] ?? 'Guest');
    $guest_email = $_POST['guest_email'] ?? ($_SESSION['email'] ?? 'guest@example.com');
    $guest_phone = $_POST['guest_phone'] ?? ($_SESSION['phone'] ?? ''); // <-- FIXED: get phone from POST
    // Basic validation
    if (!$room_id || !$check_in || !$check_out) {
        $booking_msg = "Please fill all fields.";
    } elseif (strtotime($check_in) >= strtotime($check_out)) {
        $booking_msg = "Check-out must be after check-in.";
    } else {
        $sql = "INSERT INTO bookings (booking_id, user_id, room_id, check_in_date, check_out_date, status, created_at)
                VALUES (bookings_seq.NEXTVAL, :p_uid, :p_rid, TO_DATE(:p_cin, 'YYYY-MM-DD'), TO_DATE(:p_cout, 'YYYY-MM-DD'), 'Pending', SYSDATE)
                RETURNING booking_id INTO :new_booking_id";
        $stmt = oci_parse($connection, $sql);
        oci_bind_by_name($stmt, ':p_uid', $user_id);
        oci_bind_by_name($stmt, ':p_rid', $room_id);
        oci_bind_by_name($stmt, ':p_cin', $check_in);
        oci_bind_by_name($stmt, ':p_cout', $check_out);
        oci_bind_by_name($stmt, ':new_booking_id', $booking_id, 32);
        if (oci_execute($stmt)) {
            // Insert guest record
            $sql_guest = "INSERT INTO guests (guest_id, first_name, email, phone, booking_id, room_id, created_at)
                          VALUES (guests_seq.NEXTVAL, :gname, :gemail, :gphone, :bid, :rid, SYSDATE)";
            $stmt_guest = oci_parse($connection, $sql_guest);
            oci_bind_by_name($stmt_guest, ':gname', $guest_name);
            oci_bind_by_name($stmt_guest, ':gemail', $guest_email);
            oci_bind_by_name($stmt_guest, ':gphone', $guest_phone); // <-- FIXED: phone now set
            oci_bind_by_name($stmt_guest, ':bid', $booking_id);
            oci_bind_by_name($stmt_guest, ':rid', $room_id);
            oci_execute($stmt_guest);
            // Redirect to booking payment page
            header("Location: Booking_payment.php?booking_id=" . $booking_id);
            exit;
        } else {
            $e = oci_error($stmt);
            $booking_msg = "Booking failed: " . htmlspecialchars($e['message']);
        }
    }
}

// Fetch user's bookings
$bookings = [];
$sql = "SELECT b.*, r.room_name, r.price_per_night, r.image_url FROM bookings b JOIN rooms r ON b.room_id = r.room_id WHERE b.user_id = :p_uid ORDER BY b.booking_id DESC";
$stmt = oci_parse($connection, $sql);
oci_bind_by_name($stmt, ':p_uid', $user_id);
oci_execute($stmt);
while ($row = oci_fetch_assoc($stmt)) {
    // Image handling for bookings table
    $default_img = '/Hotel-Restaurant/assets/img/default-room.jpg';
    $img = trim($row['IMAGE_URL'] ?? '');
    $filename = $img ? basename($img) : '';
    $local_url = '/Hotel-Restaurant/uploads/rooms/' . $filename;
    $file_path = $_SERVER['DOCUMENT_ROOT'] . $local_url;
    if ($img && preg_match('/^https?:\/\//', $img)) {
        $row['IMAGE_URL_DISPLAY'] = $img;
    } elseif ($filename && file_exists($file_path)) {
        $row['IMAGE_URL_DISPLAY'] = $local_url;
    } else {
        $row['IMAGE_URL_DISPLAY'] = $default_img;
    }
    $bookings[] = $row;
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Room Booking | RoyalNest</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;500;600;700&family=Montserrat:wght@300;400;500;600&display=swap" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Montserrat', sans-serif;
            background: linear-gradient(135deg, #f8f6f2 0%, #f0ede8 100%);
            color: #333;
            line-height: 1.6;
            min-height: 100vh;
            padding: 20px;
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
        }

        .header {
            text-align: center;
            margin-bottom: 40px;
            padding: 30px 0;
        }

        .logo {
            font-family: 'Playfair Display', serif;
            font-size: 2.8rem;
            color: #8c6d46;
            margin-bottom: 10px;
            letter-spacing: 2px;
        }

        .logo span {
            color: #d4af37;
        }

        .subtitle {
            font-size: 1.1rem;
            color: #777;
            letter-spacing: 3px;
            text-transform: uppercase;
            margin-top: -10px;
        }

        .back-btn {
            background: #8c6d46;
            color: white;
            border: none;
            padding: 12px 25px;
            border-radius: 30px;
            cursor: pointer;
            font-size: 1rem;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            transition: all 0.3s ease;
            box-shadow: 0 4px 15px rgba(140, 109, 70, 0.2);
            margin-bottom: 20px;
            text-decoration: none;
        }

        .back-btn:hover {
            background: #d4af37;
            transform: translateX(-5px);
        }

        .booking-container {
            background: white;
            border-radius: 20px;
            overflow: hidden;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.15);
            padding: 40px;
            margin-bottom: 40px;
        }

        .booking-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
            border-bottom: 2px solid #f0ede8;
            padding-bottom: 20px;
        }

        .booking-title {
            font-family: 'Playfair Display', serif;
            font-size: 2.5rem;
            color: #222;
        }

        .booking-icon {
            font-size: 2.5rem;
            color: #d4af37;
        }

        .room-card {
            display: flex;
            gap: 30px;
            margin-bottom: 40px;
            background: #faf9f7;
            border-radius: 15px;
            padding: 25px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.05);
        }

        .room-image {
            width: 250px;
            height: 180px;
            border-radius: 15px;
            overflow: hidden;
            flex-shrink: 0;
        }

        .room-image img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .room-info {
            flex-grow: 1;
        }

        .room-name {
            font-family: 'Playfair Display', serif;
            font-size: 1.8rem;
            margin-bottom: 10px;
            color: #222;
        }

        .room-price {
            color: #d4af37;
            font-size: 1.4rem;
            font-weight: 600;
            margin-bottom: 15px;
        }

        .room-desc {
            color: #555;
            margin-bottom: 15px;
            font-size: 0.95rem;
        }

        .booking-form {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 25px;
            margin-top: 30px;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-label {
            display: block;
            margin-bottom: 10px;
            font-weight: 600;
            color: #555;
            font-size: 1.1rem;
        }

        .form-input {
            width: 100%;
            padding: 15px 20px;
            border: 2px solid #e4e0d7;
            border-radius: 12px;
            font-size: 1rem;
            transition: all 0.3s ease;
        }

        .form-input:focus {
            border-color: #d4af37;
            outline: none;
            box-shadow: 0 0 0 3px rgba(212, 175, 55, 0.2);
        }

        .submit-btn {
            background: linear-gradient(135deg, #8c6d46, #6b5435);
            color: white;
            padding: 18px 45px;
            border: none;
            border-radius: 50px;
            font-size: 1.1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            gap: 10px;
            box-shadow: 0 10px 25px rgba(140, 109, 70, 0.3);
            margin-top: 20px;
        }

        .submit-btn:hover {
            background: linear-gradient(135deg, #d4af37, #b89735);
            transform: translateY(-3px);
            box-shadow: 0 15px 30px rgba(212, 175, 55, 0.4);
        }

        .msg {
            padding: 15px 20px;
            border-radius: 12px;
            margin: 20px 0;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .success {
            background: #e6f7ee;
            color: #2e8b57;
            border-left: 4px solid #2e8b57;
        }

        .error {
            background: #fce8e6;
            color: #d93025;
            border-left: 4px solid #d93025;
        }

        .footer {
            text-align: center;
            padding: 30px;
            color: #777;
            font-size: 0.9rem;
            margin-top: 50px;
        }

        @media (max-width: 900px) {
            .room-card {
                flex-direction: column;
            }

            .room-image {
                width: 100%;
                height: 200px;
            }

            .booking-header {
                flex-direction: column;
                align-items: flex-start;
                gap: 20px;
            }
        }

        @media (max-width: 600px) {

            .booking-container,
            .bookings-section {
                padding: 25px;
            }

            .booking-title {
                font-size: 2rem;
            }

            .bookings-table {
                display: block;
                overflow-x: auto;
            }
        }
    </style>
</head>

<body>
    <div class="container">
        <a href="/Hotel-Restaurant/user/index.php" class="back-btn">
            <i class="fas fa-arrow-left"></i> Back to Home
        </a>

        <div class="header">
            <div class="logo">Royal<span>Nest</span></div>
            <div class="subtitle">RESERVE YOUR LUXURY EXPERIENCE</div>
        </div>

        <div class="booking-container">
            <div class="booking-header">
                <h1 class="booking-title">Book Your Stay</h1>
                <div class="booking-icon">
                    <i class="fas fa-calendar-check"></i>
                </div>
            </div>

            <?php if ($room): ?>
                <div class="room-card">
                    <div class="room-image">
                        <img src="<?= htmlspecialchars($room['IMAGE_URL_DISPLAY']) ?>" alt="<?= htmlspecialchars($room['ROOM_NAME']) ?>">
                    </div>
                    <div class="room-info">
                        <h2 class="room-name"><?= htmlspecialchars($room['ROOM_NAME']) ?></h2>
                        <div class="room-price">$<?= number_format($room['PRICE_PER_NIGHT'], 2) ?> <span style="font-size:1rem;color:#888;">per night</span></div>
                        <p class="room-desc"><?= substr(htmlspecialchars($room['DESCRIPTION']), 0, 200) ?>...</p>
                    </div>
                </div>

                <form method="post" class="booking-form">
                    <input type="hidden" name="room_id" value="<?= $room['ROOM_ID'] ?>">
                    <div class="form-group">
                        <label class="form-label"><i class="fas fa-sign-in-alt"></i> Check-in Date</label>
                        <input type="date" name="check_in" class="form-input" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label"><i class="fas fa-sign-out-alt"></i> Check-out Date</label>
                        <input type="date" name="check_out" class="form-input" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label"><i class="fas fa-user"></i> Guest Name</label>
                        <input type="text" name="guest_name" class="form-input" value="<?= htmlspecialchars($_SESSION['name'] ?? '') ?>" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label"><i class="fas fa-envelope"></i> Email</label>
                        <input type="email" name="guest_email" class="form-input" value="<?= htmlspecialchars($_SESSION['email'] ?? '') ?>" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label"><i class="fas fa-phone"></i> Phone</label>
                        <input type="tel" name="guest_phone" class="form-input" value="<?= htmlspecialchars($_SESSION['phone'] ?? '') ?>" required>
                    </div>
                    <button type="submit" name="book_room" class="submit-btn">
                        <i class="fas fa-check-circle"></i> Confirm Booking
                    </button>
                </form>
            <?php else: ?>
                <div class="no-room">
                    <p>Please select a room to book from our <a href="/Hotel-Restaurant/rooms.php" style="color:#8c6d46;text-decoration:none;">Rooms page</a>.</p>
                </div>
            <?php endif; ?>

            <?php if ($booking_msg): ?>
                <div class="msg <?= strpos($booking_msg, 'failed') ? 'error' : 'success' ?>">
                    <i class="fas <?= strpos($booking_msg, 'failed') ? 'fa-exclamation-triangle' : 'fa-check-circle' ?>"></i>
                    <?= htmlspecialchars($booking_msg) ?>
                </div>
            <?php endif; ?>
        </div>

        <div class="footer">
            <p>RoyalNest Hotel &copy; 2023 | Luxury Redefined</p>
            <p>Contact: reservations@royalnest.com | +1 (555) 123-4567</p>
        </div>
    </div>

    <script>
        // Set minimum date to today for check-in
        const today = new Date().toISOString().split('T')[0];
        document.querySelector('input[name="check_in"]').min = today;

        // Set check-out min to next day
        document.querySelector('input[name="check_in"]').addEventListener('change', function() {
            const checkInDate = new Date(this.value);
            checkInDate.setDate(checkInDate.getDate() + 1);
            const nextDay = checkInDate.toISOString().split('T')[0];
            document.querySelector('input[name="check_out"]').min = nextDay;
        });

        // Initialize check-out min based on check-in if already set
        const checkInValue = document.querySelector('input[name="check_in"]').value;
        if (checkInValue) {
            const checkInDate = new Date(checkInValue);
            checkInDate.setDate(checkInDate.getDate() + 1);
            const nextDay = checkInDate.toISOString().split('T')[0];
            document.querySelector('input[name="check_out"]').min = nextDay;
        }
    </script>
</body>

</html>