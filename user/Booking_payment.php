<?php
session_start();
require_once '../config/connect.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'user') {
    header("Location: ../auth/login.php");
    exit;
}

$user_id = $_SESSION['user_id'];
$cancel_msg = '';

// Handle cancel payment request
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['cancel_payment_id'])) {
    $payment_id = intval($_POST['cancel_payment_id']);
    // Only allow cancel if payment is pending and belongs to user
    $sql = "UPDATE booking_payments SET status = 'Cancelled' WHERE payment_id = :pid AND user_id = :uid AND status = 'Pending'";
    $stmt = oci_parse($connection, $sql);
    oci_bind_by_name($stmt, ':pid', $payment_id);
    oci_bind_by_name($stmt, ':uid', $user_id);
    if (oci_execute($stmt)) {
        $cancel_msg = "Payment cancelled successfully.";
    } else {
        $cancel_msg = "Failed to cancel payment.";
    }
    oci_free_statement($stmt);
}

// Fetch user's booking payments
$sql = "SELECT bp.payment_id, bp.amount, bp.status, bp.method, bp.payment_date, bp.booking_id, b.status AS booking_status
        FROM booking_payments bp
        JOIN bookings b ON bp.booking_id = b.booking_id
        WHERE bp.user_id = :uid
        ORDER BY bp.payment_date DESC";
$stmt = oci_parse($connection, $sql);
oci_bind_by_name($stmt, ':uid', $user_id);
oci_execute($stmt);

$payments = [];
while ($row = oci_fetch_assoc($stmt)) {
    $payments[] = $row;
}
oci_free_statement($stmt);
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>My Booking Payments - RoyalNest</title>
    <link rel="stylesheet" href="../assets/Css/styles.css">
</head>

<body>
    <?php require_once '../include/Header.php'; ?>
    <div class="container">
        <h2>My Booking Payments</h2>
        <?php if ($cancel_msg): ?>
            <div style="color:green; margin-bottom:12px;"><?= htmlspecialchars($cancel_msg) ?></div>
        <?php endif; ?>
        <table border="1" cellpadding="8" cellspacing="0" style="width:100%;background:#fff;">
            <thead>
                <tr>
                    <th>Payment ID</th>
                    <th>Booking ID</th>
                    <th>Amount</th>
                    <th>Method</th>
                    <th>Status</th>
                    <th>Booking Status</th>
                    <th>Payment Date</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($payments as $p): ?>
                    <tr>
                        <td><?= htmlspecialchars($p['PAYMENT_ID']) ?></td>
                        <td><?= htmlspecialchars($p['BOOKING_ID']) ?></td>
                        <td>$<?= number_format($p['AMOUNT'], 2) ?></td>
                        <td><?= htmlspecialchars($p['METHOD']) ?></td>
                        <td><?= htmlspecialchars($p['STATUS']) ?></td>
                        <td><?= htmlspecialchars($p['BOOKING_STATUS']) ?></td>
                        <td><?= htmlspecialchars(date('Y-m-d', strtotime($p['PAYMENT_DATE']))) ?></td>
                        <td>
                            <?php if (strtolower($p['STATUS']) === 'pending'): ?>
                                <form method="post" style="display:inline;">
                                    <input type="hidden" name="cancel_payment_id" value="<?= $p['PAYMENT_ID'] ?>">
                                    <button type="submit" onclick="return confirm('Cancel this payment?');" style="color:#fff;background:#e74c3c;border:none;padding:6px 14px;border-radius:5px;cursor:pointer;">Cancel Payment</button>
                                </form>
                            <?php else: ?>
                                <span style="color:#888;">-</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
                <?php if (empty($payments)): ?>
                    <tr>
                        <td colspan="8" style="text-align:center;">No booking payments found.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
    <?php require_once '../include/footer.php'; ?>
</body>

</html>