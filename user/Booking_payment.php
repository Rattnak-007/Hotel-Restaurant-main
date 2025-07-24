<?php
require_once '../config/connect.php';
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: ../auth/login.php");
    exit();
}
$user_id = $_SESSION['user_id'];

$booking_id = $_GET['booking_id'] ?? null;
$success = false;
$error = '';
$booking = null;
$nights = 0;
$amount = 0;

if ($booking_id) {
    $sql = "SELECT b.*, r.room_name, r.price_per_night FROM bookings b JOIN rooms r ON b.room_id = r.room_id WHERE b.booking_id = :p_bid AND b.user_id = :p_uid";
    $stmt = oci_parse($connection, $sql);
    oci_bind_by_name($stmt, ':p_bid', $booking_id);
    oci_bind_by_name($stmt, ':p_uid', $user_id);
    oci_execute($stmt);
    $booking = oci_fetch_assoc($stmt);

    if ($booking) {
        $check_in = new DateTime($booking['CHECK_IN_DATE']);
        $check_out = new DateTime($booking['CHECK_OUT_DATE']);
        $nights = $check_in->diff($check_out)->days;
        $amount = $nights * $booking['PRICE_PER_NIGHT'];
    }
}

// Handle payment submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['pay_room'])) {
    $method = $_POST['method'] ?? 'Phone';
    $allowed_methods = ['Phone', 'Cash', 'Stripe', 'PayPal'];
    if (!in_array($method, $allowed_methods, true)) $method = 'Phone';

    if ($booking) {
        // Only store payment if successful
        $payment_success = false;
        if ($method === 'Stripe' || $method === 'PayPal' || $method === 'Cash') {
            $payment_success = true;
        } else {
            // Phone payment is not considered paid until confirmed
            $payment_success = false;
        }

        if ($payment_success) {
            $status = 'Paid';
            $sql = "INSERT INTO booking_payments (payment_id, booking_id, user_id, amount, method, status, payment_date)
                    VALUES (booking_payments_seq.NEXTVAL, :p_bid, :p_uid, :amt, :method, :status, SYSDATE)
                    RETURNING payment_id INTO :new_payment_id";
            $stmt = oci_parse($connection, $sql);
            oci_bind_by_name($stmt, ':p_bid', $booking_id);
            oci_bind_by_name($stmt, ':p_uid', $user_id);
            oci_bind_by_name($stmt, ':amt', $amount);
            oci_bind_by_name($stmt, ':method', $method);
            oci_bind_by_name($stmt, ':status', $status);
            $new_payment_id = null;
            oci_bind_by_name($stmt, ':new_payment_id', $new_payment_id, 32);
            if (oci_execute($stmt)) {
                // Update guest record with payment_id
                $sql_guest_update = "UPDATE guests SET payment_id = :pid WHERE booking_id = :bid";
                $stmt_guest_update = oci_parse($connection, $sql_guest_update);
                oci_bind_by_name($stmt_guest_update, ':pid', $new_payment_id);
                oci_bind_by_name($stmt_guest_update, ':bid', $booking_id);
                oci_execute($stmt_guest_update);
                $success = true;
            } else {
                $e = oci_error($stmt);
                $error = 'Payment failed: ' . htmlspecialchars($e['message']);
            }
        } else {
            $error = "Payment not completed. Your booking payment was not stored.";
        }
    } else {
        $error = "Invalid booking.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Booking Payment - RoyalNest</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Poppins', sans-serif;
        }

        body {
            background: linear-gradient(135deg, #f5f7fa 0%, #e4edf5 100%);
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            align-items: center;
            padding: 20px;
            color: #333;
        }

        .header {
            width: 100%;
            max-width: 1200px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 20px 0;
            margin-bottom: 30px;
        }

        .logo {
            display: flex;
            align-items: center;
            gap: 12px;
            color: #2c3e50;
            text-decoration: none;
            font-size: 1.8rem;
            font-weight: 700;
        }

        .logo i {
            color: #d4af37;
        }

        .user-info {
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .user-icon {
            width: 40px;
            height: 40px;
            background: #4361ee;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 1.2rem;
        }

        .user-name {
            font-weight: 500;
            color: #2c3e50;
        }

        .payment-container {
            width: 100%;
            max-width: 850px;
            background: #fff;
            border-radius: 20px;
            box-shadow: 0 15px 50px rgba(67, 97, 238, 0.15);
            overflow: hidden;
            display: flex;
            flex-direction: column;
        }

        .payment-header {
            background: linear-gradient(90deg, #4361ee 0%, #3f37c9 100%);
            color: white;
            padding: 25px 40px;
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .payment-header i {
            font-size: 2.2rem;
        }

        .payment-title {
            font-size: 1.8rem;
            font-weight: 600;
        }

        .payment-content {
            display: flex;
            padding: 0;
        }

        .booking-summary {
            flex: 1;
            padding: 35px;
            background: #f9fafc;
            border-right: 1px solid #eaeef5;
        }

        .section-title {
            font-size: 1.4rem;
            font-weight: 600;
            color: #2c3e50;
            margin-bottom: 25px;
            padding-bottom: 15px;
            border-bottom: 2px solid #eaeef5;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .section-title i {
            color: #4361ee;
        }

        .summary-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
        }

        .summary-card {
            background: white;
            border-radius: 12px;
            padding: 20px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.03);
            border: 1px solid #eaeef5;
        }

        .summary-row {
            display: flex;
            justify-content: space-between;
            padding: 10px 0;
            border-bottom: 1px solid #f0f2f5;
        }

        .summary-row:last-child {
            border-bottom: none;
        }

        .summary-label {
            font-weight: 500;
            color: #6c757d;
        }

        .summary-value {
            font-weight: 600;
            color: #2c3e50;
        }

        .highlight {
            color: #d4af37;
            font-weight: 700;
        }

        .total-amount {
            background: linear-gradient(135deg, #4361ee 0%, #3f37c9 100%);
            color: white;
            padding: 25px;
            border-radius: 15px;
            margin-top: 25px;
            text-align: center;
        }

        .total-label {
            font-size: 1.1rem;
            margin-bottom: 10px;
            opacity: 0.9;
        }

        .total-value {
            font-size: 2.5rem;
            font-weight: 700;
        }

        .payment-form-section {
            flex: 1;
            padding: 35px;
        }

        .form-group {
            margin-bottom: 25px;
        }

        .form-label {
            display: block;
            font-weight: 500;
            margin-bottom: 10px;
            color: #2c3e50;
            font-size: 1.1rem;
        }

        .method-select {
            width: 100%;
            padding: 15px;
            border-radius: 12px;
            border: 2px solid #eaeef5;
            font-size: 1rem;
            background: white;
            transition: all 0.3s;
            appearance: none;
            background-image: url("data:image/svg+xml;charset=UTF-8,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='currentColor' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'%3e%3cpolyline points='6 9 12 15 18 9'%3e%3c/polyline%3e%3c/svg%3e");
            background-repeat: no-repeat;
            background-position: right 15px center;
            background-size: 16px;
        }

        .method-select:focus {
            outline: none;
            border-color: #4361ee;
            box-shadow: 0 0 0 3px rgba(67, 97, 238, 0.2);
        }

        .method-options {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 15px;
            margin-top: 15px;
        }

        .method-option {
            border: 2px solid #eaeef5;
            border-radius: 12px;
            padding: 20px;
            display: flex;
            flex-direction: column;
            align-items: center;
            cursor: pointer;
            transition: all 0.3s;
            background: white;
        }

        .method-option:hover {
            transform: translateY(-5px);
            border-color: #4361ee;
            box-shadow: 0 10px 25px rgba(67, 97, 238, 0.15);
        }

        .method-option.active {
            border-color: #4361ee;
            background: rgba(67, 97, 238, 0.05);
        }

        .method-icon {
            width: 50px;
            height: 50px;
            background: #f0f4ff;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 15px;
            color: #4361ee;
            font-size: 1.4rem;
        }

        .method-name {
            font-weight: 600;
            color: #2c3e50;
            margin-bottom: 5px;
        }

        .method-desc {
            font-size: 0.85rem;
            color: #6c757d;
            text-align: center;
        }

        .pay-btn {
            width: 100%;
            padding: 18px;
            background: linear-gradient(135deg, #d4af37 0%, #b8860b 100%);
            color: #2c3e50;
            border: none;
            border-radius: 12px;
            font-size: 1.2rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            box-shadow: 0 5px 15px rgba(212, 175, 55, 0.3);
        }

        .pay-btn:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 20px rgba(212, 175, 55, 0.4);
        }

        .pay-btn:active {
            transform: translateY(1px);
        }

        .msg {
            padding: 20px;
            border-radius: 12px;
            margin: 25px 0;
            display: flex;
            align-items: center;
            gap: 15px;
            font-weight: 500;
            font-size: 1.1rem;
        }

        .success {
            background: rgba(46, 204, 113, 0.1);
            color: #27ae60;
            border: 1px solid rgba(46, 204, 113, 0.3);
        }

        .error {
            background: rgba(231, 76, 60, 0.1);
            color: #c0392b;
            border: 1px solid rgba(231, 76, 60, 0.3);
        }

        .pending {
            background: rgba(243, 156, 18, 0.1);
            color: #d35400;
            border: 1px solid rgba(243, 156, 18, 0.3);
            margin-top: 15px;
        }

        .back-btn {
            display: inline-block;
            padding: 15px 30px;
            background: #4361ee;
            color: white;
            border-radius: 12px;
            text-decoration: none;
            font-weight: 500;
            transition: all 0.3s;
            text-align: center;
            margin-top: 20px;
        }

        .back-btn:hover {
            background: #3f37c9;
            transform: translateY(-2px);
        }

        @media (max-width: 768px) {
            .payment-content {
                flex-direction: column;
            }

            .booking-summary {
                border-right: none;
                border-bottom: 1px solid #eaeef5;
            }

            .summary-grid {
                grid-template-columns: 1fr;
            }

            .method-options {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>

<body>
    <div class="header">
        <a href="/Hotel-Restaurant/user/booking.php" class="logo">
            <i class="fas fa-crown"></i>
            <span>RoyalNest</span>
        </a>
        <div class="user-info">
            <div class="user-icon">
                <i class="fas fa-user"></i>
            </div>
            <div class="user-name">
                <?= htmlspecialchars($_SESSION['user_name'] ?? 'Guest') ?>
            </div>
        </div>
    </div>

    <div class="payment-container">
        <div class="payment-header">
            <i class="fas fa-credit-card"></i>
            <div class="payment-title">Complete Your Booking Payment</div>
        </div>

        <div class="payment-content">
            <div class="booking-summary">
                <div class="section-title">
                    <i class="fas fa-receipt"></i>
                    Booking Summary
                </div>

                <?php if ($booking_id && $booking): ?>
                    <div class="summary-grid">
                        <div class="summary-card">
                            <div class="summary-row">
                                <div class="summary-label">Booking ID</div>
                                <div class="summary-value">#<?= htmlspecialchars($booking_id) ?></div>
                            </div>
                            <div class="summary-row">
                                <div class="summary-label">Room Type</div>
                                <div class="summary-value highlight"><?= htmlspecialchars($booking['ROOM_NAME']) ?></div>
                            </div>
                            <div class="summary-row">
                                <div class="summary-label">Price per Night</div>
                                <div class="summary-value">$<?= number_format($booking['PRICE_PER_NIGHT'], 2) ?></div>
                            </div>
                        </div>

                        <div class="summary-card">
                            <div class="summary-row">
                                <div class="summary-label">Check-in Date</div>
                                <div class="summary-value"><?= htmlspecialchars(date('M d, Y', strtotime($booking['CHECK_IN_DATE']))) ?></div>
                            </div>
                            <div class="summary-row">
                                <div class="summary-label">Check-out Date</div>
                                <div class="summary-value"><?= htmlspecialchars(date('M d, Y', strtotime($booking['CHECK_OUT_DATE']))) ?></div>
                            </div>
                            <div class="summary-row">
                                <div class="summary-label">Total Nights</div>
                                <div class="summary-value"><?= $nights ?></div>
                            </div>
                        </div>
                    </div>

                    <div class="total-amount">
                        <div class="total-label">Total Amount Due</div>
                        <div class="total-value">$<?= number_format($amount, 2) ?></div>
                    </div>
                <?php else: ?>
                    <div class="msg error">
                        <i class="fas fa-exclamation-circle"></i>
                        <div>Invalid booking or no booking selected.</div>
                    </div>
                <?php endif; ?>
            </div>

            <div class="payment-form-section">
                <?php if ($booking_id && $booking): ?>
                    <?php if ($success): ?>
                        <div class="msg success">
                            <i class="fas fa-check-circle"></i>
                            <div>
                                <strong>Payment submitted successfully!</strong>
                                <?php if ($_POST['method'] === 'Phone'): ?>
                                    <div class="pending">
                                        <i class="fas fa-info-circle"></i>
                                        Please wait for staff to contact you by phone to complete payment.
                                    </div>
                                <?php else: ?>
                                    <div>Thank you for your payment. Your booking is confirmed!</div>
                                <?php endif; ?>
                            </div>
                        </div>
                        <a href="booking.php" class="back-btn">
                            <i class="fas fa-arrow-left"></i> Back to My Bookings
                        </a>
                    <?php else: ?>
                        <div class="section-title">
                            <i class="fas fa-credit-card"></i>
                            Payment Method
                        </div>

                        <?php if ($error): ?>
                            <div class="msg error">
                                <i class="fas fa-exclamation-triangle"></i>
                                <div><?= $error ?></div>
                            </div>
                        <?php endif; ?>

                        <form method="post">
                            <div class="form-group">
                                <label class="form-label">Select Payment Method</label>
                                <select name="method" id="method" class="method-select">
                                    <option value="Phone">Phone Payment</option>
                                    <option value="Cash">Cash at Property</option>
                                    <option value="Stripe">Credit/Debit Card</option>
                                    <option value="PayPal">PayPal</option>
                                </select>
                            </div>

                            <div class="form-group">
                                <label class="form-label">Or choose an option below:</label>
                                <div class="method-options">
                                    <div class="method-option" data-value="Phone">
                                        <div class="method-icon">
                                            <i class="fas fa-phone"></i>
                                        </div>
                                        <div class="method-name">Phone</div>
                                        <div class="method-desc">Pay later by phone</div>
                                    </div>
                                    <div class="method-option" data-value="Cash">
                                        <div class="method-icon">
                                            <i class="fas fa-money-bill-wave"></i>
                                        </div>
                                        <div class="method-name">Cash</div>
                                        <div class="method-desc">Pay at the property</div>
                                    </div>
                                    <div class="method-option" data-value="Stripe">
                                        <div class="method-icon">
                                            <i class="fab fa-cc-stripe"></i>
                                        </div>
                                        <div class="method-name">Card</div>
                                        <div class="method-desc">Credit/Debit Card</div>
                                    </div>
                                    <div class="method-option" data-value="PayPal">
                                        <div class="method-icon">
                                            <i class="fab fa-paypal"></i>
                                        </div>
                                        <div class="method-name">PayPal</div>
                                        <div class="method-desc">Online Payment</div>
                                    </div>
                                </div>
                            </div>

                            <button type="submit" name="pay_room" class="pay-btn">
                                <i class="fas fa-lock"></i> Pay $<?= number_format($amount, 2) ?>
                            </button>
                        </form>
                    <?php endif; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script>
        // Method selection interaction
        document.querySelectorAll('.method-option').forEach(option => {
            option.addEventListener('click', function() {
                // Remove active class from all options
                document.querySelectorAll('.method-option').forEach(opt => {
                    opt.classList.remove('active');
                });

                // Add active class to clicked option
                this.classList.add('active');

                // Update the select element
                const method = this.getAttribute('data-value');
                document.getElementById('method').value = method;
            });
        });

        // Initialize active option based on select value
        document.addEventListener('DOMContentLoaded', function() {
            const initialMethod = document.getElementById('method').value;
            document.querySelector(`.method-option[data-value="${initialMethod}"]`).classList.add('active');
        });
    </script>
</body>

</html>