<?php
require_once '../config/connect.php';
session_start();

$room_id = $_GET['id'] ?? null;
if (!$room_id) {
    echo "<h2>Room not found.</h2>";
    exit;
}

$sql = "SELECT * FROM rooms WHERE room_id = :room_id";
$stmt = oci_parse($connection, $sql);
oci_bind_by_name($stmt, ':room_id', $room_id);
oci_execute($stmt);
$room = oci_fetch_assoc($stmt);

if (!$room) {
    echo "<h2>Room not found.</h2>";
    exit;
}

// Handle CLOB for description
if (isset($room['DESCRIPTION']) && is_object($room['DESCRIPTION']) && $room['DESCRIPTION'] instanceof OCILob) {
    $room['DESCRIPTION'] = $room['DESCRIPTION']->load();
}

$img = trim($room['IMAGE_URL'] ?? '');
$filename = $img ? basename($img) : '';
$local_url = '/Hotel-Restaurant/uploads/rooms/' . $filename;
$file_path = $_SERVER['DOCUMENT_ROOT'] . $local_url;
$default_img = '/Hotel-Restaurant/assets/img/default-room.jpg';

if ($img && preg_match('/^https?:\/\//', $img)) {
    $img_url_display = $img;
} elseif ($filename && file_exists($file_path)) {
    $img_url_display = $local_url;
} else {
    $img_url_display = $default_img;
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($room['ROOM_NAME']) ?> | RoyalNest Room Details</title>
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
            padding: 20px;
            min-height: 100vh;
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
        }

        .header {
            text-align: center;
            margin-bottom: 30px;
            padding: 20px 0;
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
            position: absolute;
            top: 30px;
            left: 30px;
            background: #8c6d46;
            color: white;
            border: none;
            padding: 12px 20px;
            border-radius: 30px;
            cursor: pointer;
            font-size: 1rem;
            display: flex;
            align-items: center;
            gap: 8px;
            transition: all 0.3s ease;
            box-shadow: 0 4px 15px rgba(140, 109, 70, 0.2);
        }

        .back-btn:hover {
            background: #d4af37;
            transform: translateX(-5px);
        }

        .detail-container {
            display: flex;
            flex-wrap: wrap;
            background: white;
            border-radius: 20px;
            overflow: hidden;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.15);
            margin-bottom: 40px;
        }

        .image-section {
            flex: 1;
            min-width: 300px;
            position: relative;
            overflow: hidden;
        }

        .main-image {
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: transform 0.5s ease;
        }

        .image-section:hover .main-image {
            transform: scale(1.03);
        }

        .image-overlay {
            position: absolute;
            bottom: 0;
            left: 0;
            right: 0;
            background: linear-gradient(to top, rgba(0, 0, 0, 0.7), transparent);
            padding: 30px 20px;
            color: white;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .price-tag {
            background: #d4af37;
            color: #222;
            font-weight: 700;
            padding: 10px 25px;
            border-radius: 30px;
            font-size: 1.4rem;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.2);
        }

        .price-tag span {
            font-size: 1rem;
            font-weight: 400;
        }

        .info-section {
            flex: 1;
            min-width: 300px;
            padding: 40px;
            display: flex;
            flex-direction: column;
            justify-content: center;
        }

        .room-title {
            font-family: 'Playfair Display', serif;
            font-size: 2.8rem;
            color: #222;
            margin-bottom: 15px;
            line-height: 1.2;
        }

        .room-meta {
            display: flex;
            gap: 15px;
            margin-bottom: 25px;
            flex-wrap: wrap;
        }

        .room-status {
            background: #f0f7ff;
            color: #4361ee;
            padding: 8px 20px;
            border-radius: 30px;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .room-size {
            background: #f8f6f2;
            color: #8c6d46;
            padding: 8px 20px;
            border-radius: 30px;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .room-features {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin: 30px 0;
        }

        .feature-card {
            display: flex;
            flex-direction: column;
            align-items: center;
            padding: 20px;
            background: #faf9f7;
            border-radius: 15px;
            transition: all 0.3s ease;
            text-align: center;
        }

        .feature-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.05);
            background: #f8f6f2;
        }

        .feature-icon {
            font-size: 2.5rem;
            color: #d4af37;
            margin-bottom: 15px;
        }

        .feature-title {
            font-weight: 600;
            margin-bottom: 5px;
            color: #333;
        }

        .feature-value {
            font-size: 1.4rem;
            font-weight: 700;
            color: #8c6d46;
        }

        .room-desc {
            color: #555;
            font-size: 1.1rem;
            margin: 25px 0;
            line-height: 1.8;
        }

        .booking-section {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-top: 30px;
            padding-top: 25px;
            border-top: 1px solid #eee;
        }

        .book-btn {
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
        }

        .book-btn:hover {
            background: linear-gradient(135deg, #d4af37, #b89735);
            transform: translateY(-3px);
            box-shadow: 0 15px 30px rgba(212, 175, 55, 0.4);
        }

        .amenities {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
        }

        .amenity {
            background: #f0f7ff;
            color: #4361ee;
            padding: 8px 15px;
            border-radius: 30px;
            font-size: 0.9rem;
            display: flex;
            align-items: center;
            gap: 5px;
        }

        .testimonials {
            margin-top: 50px;
        }

        .section-title {
            font-family: 'Playfair Display', serif;
            font-size: 2rem;
            text-align: center;
            margin-bottom: 40px;
            color: #222;
            position: relative;
        }

        .section-title:after {
            content: '';
            display: block;
            width: 80px;
            height: 3px;
            background: #d4af37;
            margin: 15px auto;
        }

        .testimonial-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 30px;
        }

        .testimonial-card {
            background: white;
            padding: 30px;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.08);
        }

        .testimonial-text {
            font-style: italic;
            color: #555;
            margin-bottom: 20px;
            position: relative;
            padding-left: 25px;
        }

        .testimonial-text:before {
            content: "";
            font-family: Georgia, serif;
            font-size: 5rem;
            position: absolute;
            left: -15px;
            top: -25px;
            color: #f0f0f0;
            line-height: 1;
        }

        .testimonial-author {
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .author-avatar {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            background: #8c6d46;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: bold;
            font-size: 1.2rem;
        }

        .author-info {
            display: flex;
            flex-direction: column;
        }

        .author-name {
            font-weight: 600;
            color: #222;
        }

        .author-stars {
            color: #d4af37;
            font-size: 0.9rem;
        }

        @media (max-width: 900px) {
            .detail-container {
                flex-direction: column;
            }

            .image-section {
                height: 400px;
            }

            .room-title {
                font-size: 2.2rem;
            }

            .back-btn {
                position: relative;
                top: auto;
                left: auto;
                margin-bottom: 20px;
            }
        }

        @media (max-width: 600px) {
            .room-title {
                font-size: 1.8rem;
            }

            .price-tag {
                font-size: 1.2rem;
                padding: 8px 15px;
            }

            .info-section {
                padding: 25px;
            }

            .book-btn {
                padding: 15px 30px;
                font-size: 1rem;
            }
        }
    </style>
</head>

<body>
    <div class="container">
        <button class="back-btn" onclick="history.back()">
            <i class="fas fa-arrow-left"></i> Back to Rooms
        </button>

        <div class="header">
            <div class="logo">Royal<span>Nest</span></div>
            <div class="subtitle">LUXURY ACCOMMODATIONS</div>
        </div>

        <div class="detail-container">
            <div class="image-section">
                <img src="<?= htmlspecialchars($img_url_display) ?>" alt="<?= htmlspecialchars($room['ROOM_NAME']) ?>" class="main-image">
                <div class="image-overlay">
                    <div class="price-tag">$<?= number_format($room['PRICE_PER_NIGHT'], 2) ?> <span>/night</span></div>
                    <div class="room-status">
                        <i class="fas fa-circle"></i> <?= htmlspecialchars($room['STATUS']) ?>
                    </div>
                </div>
            </div>

            <div class="info-section">
                <h1 class="room-title"><?= htmlspecialchars($room['ROOM_NAME']) ?></h1>

                <div class="room-meta">
                    <div class="room-size">
                        <i class="fas fa-ruler-combined"></i>
                        <?= isset($room['ROOM_SIZE']) && $room['ROOM_SIZE'] ? htmlspecialchars($room['ROOM_SIZE']) . ' sq.ft.' : 'N/A' ?>
                    </div>
                    <div class="room-status">
                        <i class="fas fa-bed"></i> Sleeps <?= htmlspecialchars($room['SLEEPS']) ?>
                    </div>
                </div>

                <p class="room-desc">
                    <?= nl2br(htmlspecialchars($room['DESCRIPTION'])) ?>
                </p>

                <div class="room-features">
                    <div class="feature-card">
                        <div class="feature-icon">
                            <i class="fas fa-bed"></i>
                        </div>
                        <div class="feature-title">Bed Type</div>
                        <div class="feature-value">King Size</div>
                    </div>

                    <div class="feature-card">
                        <div class="feature-icon">
                            <i class="fas fa-user-friends"></i>
                        </div>
                        <div class="feature-title">Capacity</div>
                        <div class="feature-value"><?= htmlspecialchars($room['SLEEPS']) ?> Guests</div>
                    </div>

                    <div class="feature-card">
                        <div class="feature-icon">
                            <i class="fas fa-door-open"></i>
                        </div>
                        <div class="feature-title">Room Type</div>
                        <div class="feature-value">Deluxe</div>
                    </div>

                    <div class="feature-card">
                        <div class="feature-icon">
                            <i class="fas fa-wind"></i>
                        </div>
                        <div class="feature-title">View</div>
                        <div class="feature-value">Ocean</div>
                    </div>
                </div>

                <div class="amenities">
                    <div class="amenity"><i class="fas fa-wifi"></i> Free Wi-Fi</div>
                    <div class="amenity"><i class="fas fa-tv"></i> Smart TV</div>
                    <div class="amenity"><i class="fas fa-coffee"></i> Coffee Maker</div>
                    <div class="amenity"><i class="fas fa-wine-bottle"></i> Mini Bar</div>
                    <div class="amenity"><i class="fas fa-shower"></i> Rain Shower</div>
                </div>

                <div class="booking-section">
                    <div class="amenities">
                        <div class="amenity"><i class="fas fa-parking"></i> Parking</div>
                        <div class="amenity"><i class="fas fa-snowflake"></i> A/C</div>
                        <div class="amenity"><i class="fas fa-lock"></i> Safe</div>
                    </div>
                    <a href="booking.php?room_id=<?= $room['ROOM_ID'] ?>" class="book-btn">
                        <i class="fas fa-calendar-check"></i> Book Now
                    </a>
                </div>
            </div>
        </div>

        <div class="testimonials">
            <h2 class="section-title">Guest Experiences</h2>
            <div class="testimonial-grid">
                <div class="testimonial-card">
                    <p class="testimonial-text">
                        The room was absolutely stunning! The ocean view took our breath away every morning.
                        Service was impeccable and the bed was incredibly comfortable.
                    </p>
                    <div class="testimonial-author">
                        <div class="author-avatar">S</div>
                        <div class="author-info">
                            <div class="author-name">Sarah Johnson</div>
                            <div class="author-stars">
                                <i class="fas fa-star"></i>
                                <i class="fas fa-star"></i>
                                <i class="fas fa-star"></i>
                                <i class="fas fa-star"></i>
                                <i class="fas fa-star"></i>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="testimonial-card">
                    <p class="testimonial-text">
                        Best hotel experience we've ever had. The attention to detail in the room design
                        and the quality of amenities were exceptional. Will definitely return!
                    </p>
                    <div class="testimonial-author">
                        <div class="author-avatar">M</div>
                        <div class="author-info">
                            <div class="author-name">Michael Chen</div>
                            <div class="author-stars">
                                <i class="fas fa-star"></i>
                                <i class="fas fa-star"></i>
                                <i class="fas fa-star"></i>
                                <i class="fas fa-star"></i>
                                <i class="fas fa-star-half-alt"></i>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="testimonial-card">
                    <p class="testimonial-text">
                        Perfect anniversary getaway! The room was spacious and luxurious.
                        The balcony with the ocean view was our favorite spot for evening drinks.
                    </p>
                    <div class="testimonial-author">
                        <div class="author-avatar">A</div>
                        <div class="author-info">
                            <div class="author-name">Amanda Roberts</div>
                            <div class="author-stars">
                                <i class="fas fa-star"></i>
                                <i class="fas fa-star"></i>
                                <i class="fas fa-star"></i>
                                <i class="fas fa-star"></i>
                                <i class="fas fa-star"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <script>
            // Simple animation for cards when they come into view
            document.addEventListener('DOMContentLoaded', function() {
                const featureCards = document.querySelectorAll('.feature-card');
                featureCards.forEach((card, index) => {
                    setTimeout(() => {
                        card.style.opacity = '0';
                        card.style.transform = 'translateY(20px)';
                        card.style.transition = 'all 0.5s ease';

                        setTimeout(() => {
                            card.style.opacity = '1';
                            card.style.transform = 'translateY(0)';
                        }, 100);
                    }, index * 100);
                });

                // Button hover effects
                const bookBtn = document.querySelector('.book-btn');
                bookBtn.addEventListener('mouseover', function() {
                    this.style.transform = 'scale(1.05)';
                });

                bookBtn.addEventListener('mouseout', function() {
                    this.style.transform = 'scale(1)';
                });
            });
        </script>
</body>

</html>