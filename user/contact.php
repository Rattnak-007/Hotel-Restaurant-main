<?php require_once("../include/Header.php"); ?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Contact Us | RoyalNest</title>
    <link rel="stylesheet" href="../assets/Css/styles.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .contact-container {
            max-width: 900px;
            margin: 40px auto;
            background: #fff;
            border-radius: 18px;
            box-shadow: 0 8px 32px rgba(67, 97, 238, 0.08);
            padding: 40px;
        }

        .contact-title {
            font-size: 2.2rem;
            color: #4361ee;
            margin-bottom: 18px;
            font-weight: 700;
            text-align: center;
        }

        .contact-info {
            display: flex;
            gap: 40px;
            margin-bottom: 30px;
            flex-wrap: wrap;
            justify-content: center;
        }

        .info-block {
            flex: 1;
            min-width: 220px;
            background: #f8f6f2;
            border-radius: 12px;
            padding: 18px;
            text-align: center;
        }

        .info-block i {
            font-size: 1.6rem;
            color: #d4af37;
            margin-bottom: 8px;
        }

        .info-block h4 {
            margin: 8px 0 4px 0;
            font-size: 1.1rem;
            color: #3f37c9;
        }

        .contact-form {
            margin-top: 20px;
        }

        .form-group {
            margin-bottom: 18px;
        }

        .form-label {
            display: block;
            margin-bottom: 7px;
            font-weight: 600;
            color: #3f37c9;
        }

        .form-input,
        .form-textarea {
            width: 100%;
            padding: 14px;
            border-radius: 8px;
            border: 1px solid #e4e0d7;
            font-size: 1rem;
        }

        .form-textarea {
            min-height: 120px;
            resize: vertical;
        }

        .submit-btn {
            background: linear-gradient(135deg, #4361ee, #3f37c9);
            color: white;
            padding: 14px 38px;
            border: none;
            border-radius: 10px;
            font-size: 1.1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            box-shadow: 0 4px 15px rgba(67, 97, 238, 0.15);
        }

        .submit-btn:hover {
            background: linear-gradient(135deg, #d4af37, #b89735);
            color: #222;
        }

        .msg-success {
            background: #e6f7ee;
            color: #2e8b57;
            border-left: 4px solid #2e8b57;
            padding: 12px 18px;
            border-radius: 8px;
            margin-bottom: 18px;
        }

        .msg-error {
            background: #fce8e6;
            color: #d93025;
            border-left: 4px solid #d93025;
            padding: 12px 18px;
            border-radius: 8px;
            margin-bottom: 18px;
        }

        @media (max-width: 600px) {
            .contact-container {
                padding: 18px;
            }

            .contact-info {
                flex-direction: column;
                gap: 18px;
            }
        }
    </style>
</head>

<body>
    <div class="contact-container">
        <div class="contact-title">
            <i class="fas fa-envelope"></i> Contact Us
        </div>
        <div class="contact-info">
            <div class="info-block">
                <i class="fas fa-map-marker-alt"></i>
                <h4>Address</h4>
                <div>123 RoyalNest Avenue, Luxury City, Country</div>
            </div>
            <div class="info-block">
                <i class="fas fa-phone"></i>
                <h4>Phone</h4>
                <div>+1 (555) 123-4567</div>
            </div>
            <div class="info-block">
                <i class="fas fa-envelope"></i>
                <h4>Email</h4>
                <div>contact@royalnest.com</div>
            </div>
        </div>
        <?php
        require_once("../config/connect.php");
        $msg = '';
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['contact_submit'])) {
            $name = trim($_POST['name'] ?? '');
            $email = trim($_POST['email'] ?? '');
            $phone = trim($_POST['phone'] ?? ''); // new
            $message = trim($_POST['message'] ?? '');
            if (!$name || !$email || !$message) {
                $msg = '<div class="msg-error">Please fill in all fields.</div>';
            } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $msg = '<div class="msg-error">Please enter a valid email address.</div>';
            } else {
                // Insert into contact table
                $sql = "INSERT INTO contact (id, name, email, phone, message, created_at) VALUES (contact_seq.NEXTVAL, :name, :email, :phone, :message, SYSDATE)";
                $stmt = oci_parse($connection, $sql);
                oci_bind_by_name($stmt, ":name", $name);
                oci_bind_by_name($stmt, ":email", $email);
                oci_bind_by_name($stmt, ":phone", $phone);
                oci_bind_by_name($stmt, ":message", $message);
                $result = oci_execute($stmt);
                if ($result) {
                    $msg = '<div class="msg-success">Thank you for contacting us! We will get back to you soon.</div>';
                } else {
                    $msg = '<div class="msg-error">Failed to submit your message. Please try again later.</div>';
                }
                oci_free_statement($stmt);
            }
        }
        echo $msg;
        ?>
        <form method="post" class="contact-form">
            <div class="form-group">
                <label class="form-label" for="name">Your Name</label>
                <input type="text" name="name" id="name" class="form-input" required>
            </div>
            <div class="form-group">
                <label class="form-label" for="email">Your Email</label>
                <input type="email" name="email" id="email" class="form-input" required>
            </div>
            <div class="form-group">
                <label class="form-label" for="phone">Your Phone</label>
                <input type="text" name="phone" id="phone" class="form-input">
            </div>
            <div class="form-group">
                <label class="form-label" for="message">Message</label>
                <textarea name="message" id="message" class="form-textarea" required></textarea>
            </div>
            <button type="submit" name="contact_submit" class="submit-btn">
                <i class="fas fa-paper-plane"></i> Send Message
            </button>
        </form>
    </div>
    <?php require_once("../include/Footer.php"); ?>
</body>

</html>