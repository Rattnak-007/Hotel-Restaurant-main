<?php
require_once '../config/connect.php';
session_start();
$user_id = $_SESSION['user_id'] ?? null;

if (!$user_id) {
    header('Location: /Hotel-Restaurant/auth/login.php');
    exit;
}

$success = false;
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['cart_json'])) {
    $cart = json_decode($_POST['cart_json'], true);
    $allowed_methods = ['Cash', 'Stripe', 'PayPal'];
    $method = $_POST['method'] ?? 'Cash';
    if (!in_array($method, $allowed_methods, true)) {
        $method = 'Cash';
    }
    // Validate cart
    if (!is_array($cart) || empty($cart)) {
        $error = "Invalid cart data. Please refresh and try again.";
    } else {
        $total = 0;
        $validated_cart = [];
        $menu_ids = array_column($cart, 'id');
        $binds = [];
        foreach ($menu_ids as $idx => $mid) {
            $binds[] = ':mid' . $idx;
        }
        $in_clause = implode(',', $binds);
        $menu_sql = "SELECT menu_id, price, name FROM restaurant_menu WHERE menu_id IN ($in_clause)";
        $menu_stmt = oci_parse($connection, $menu_sql);
        foreach ($menu_ids as $idx => $mid) {
            oci_bind_by_name($menu_stmt, $binds[$idx], $menu_ids[$idx]);
        }
        oci_execute($menu_stmt);
        $menu_rows = [];
        while ($row = oci_fetch_assoc($menu_stmt)) {
            $menu_rows[$row['MENU_ID']] = $row;
        }
        foreach ($cart as $item) {
            if (!isset($menu_rows[$item['id']])) {
                $error = "Invalid menu item detected.";
                break;
            }
            $menu_row = $menu_rows[$item['id']];
            if (floatval($menu_row['PRICE']) != floatval($item['price'])) {
                $error = "Price mismatch for item: " . htmlspecialchars($menu_row['NAME']);
                break;
            }
            $total += $menu_row['PRICE'] * $item['quantity'];
            $validated_cart[] = [
                'id' => $menu_row['MENU_ID'],
                'name' => $menu_row['NAME'],
                'price' => $menu_row['PRICE'],
                'quantity' => $item['quantity']
            ];
        }
        if (!$error) {
            $cart = $validated_cart;

            // Only proceed if payment is successful
            $payment_success = false;
            // Simulate payment gateway logic here (Stripe/PayPal/Cash)
            // For demonstration, assume payment is successful for Stripe/PayPal, not for Cash
            if ($method === 'Stripe' || $method === 'PayPal') {
                $payment_success = true;
            } else {
                // Cash payment is not considered paid until confirmed at property
                $payment_success = false;
            }

            if ($payment_success) {
                // Insert into restaurant_orders and get order_id
                $order_id = null;
                $order_sql = "DECLARE new_order_id NUMBER; BEGIN INSERT INTO restaurant_orders (order_id, user_id, total_amount, status) 
                                  VALUES (restaurant_orders_seq.NEXTVAL, :uid, :total, 'Confirmed') RETURNING order_id INTO :out_order_id; END;";
                $stmt = oci_parse($connection, $order_sql);
                oci_bind_by_name($stmt, ':uid', $user_id);
                oci_bind_by_name($stmt, ':total', $total);
                oci_bind_by_name($stmt, ':out_order_id', $order_id, 32);

                if (!oci_execute($stmt)) {
                    $error = 'Order creation failed: ' . htmlspecialchars(oci_error($stmt)['message']);
                } else {
                    // Insert order items
                    foreach ($cart as $item) {
                        $sql_item = "INSERT INTO order_items (order_item_id, order_id, menu_id, quantity, price) VALUES (order_items_seq.NEXTVAL, :oid, :mid, :qty, :price)";
                        $stmt_item = oci_parse($connection, $sql_item);
                        oci_bind_by_name($stmt_item, ':oid', $order_id);
                        oci_bind_by_name($stmt_item, ':mid', $item['id']);
                        oci_bind_by_name($stmt_item, ':qty', $item['quantity']);
                        oci_bind_by_name($stmt_item, ':price', $item['price']);
                        oci_execute($stmt_item);
                    }
                    // Insert payment record
                    $sql_pay = "INSERT INTO order_payments (payment_id, order_id, user_id, amount, method, status, payment_date)
                                VALUES (order_payments_seq.NEXTVAL, :oid, :uid, :amt, :method, 'Paid', SYSDATE)";
                    $stmt_pay = oci_parse($connection, $sql_pay);
                    oci_bind_by_name($stmt_pay, ':oid', $order_id);
                    oci_bind_by_name($stmt_pay, ':uid', $user_id);
                    oci_bind_by_name($stmt_pay, ':amt', $total);
                    oci_bind_by_name($stmt_pay, ':method', $method);
                    oci_execute($stmt_pay);

                    $success = true;
                }
            } else {
                $error = "Payment not completed. Your order was not stored.";
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payment - RoyalNest</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary: #4361ee;
            --secondary: #3f37c9;
            --royal-gold: #d4af37;
            --royal-purple: #7851a9;
            --light: #f8f9fa;
            --dark: #212529;
            --gray: #6c757d;
            --light-purple: #e6e1f7;
            --transition: all 0.3s ease;
            --success: #2ecc71;
            --warning: #f39c12;
            --danger: #e74c3c;
            --info: #3498db;
            --shadow: 0 10px 30px rgba(0, 0, 0, 0.08);
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        body {
            background: linear-gradient(135deg, #f5f7fa 0%, #e4e8f0 100%);
            color: var(--dark);
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 20px;
        }

        .payment-container {
            width: 100%;
            max-width: 500px;
            background: white;
            border-radius: 20px;
            box-shadow: var(--shadow);
            overflow: hidden;
        }

        .payment-header {
            background: linear-gradient(135deg, var(--royal-purple), var(--primary));
            color: white;
            padding: 30px;
            text-align: center;
            position: relative;
        }

        .payment-header::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 50%;
            transform: translateX(-50%);
            width: 80%;
            height: 4px;
            background: var(--royal-gold);
            border-radius: 2px;
        }

        .payment-title {
            font-size: 2.2rem;
            font-weight: 700;
            margin-bottom: 10px;
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 15px;
        }

        .payment-subtitle {
            font-size: 1rem;
            opacity: 0.9;
            font-weight: 300;
        }

        .payment-body {
            padding: 30px;
        }

        .order-summary {
            background: var(--light);
            border-radius: 12px;
            padding: 20px;
            margin-bottom: 25px;
        }

        .summary-title {
            font-size: 1.3rem;
            color: var(--royal-purple);
            margin-bottom: 15px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .order-items {
            margin-bottom: 15px;
            max-height: 300px;
            overflow-y: auto;
            padding-right: 10px;
        }

        .order-item {
            display: flex;
            justify-content: space-between;
            padding: 12px 0;
            border-bottom: 1px solid rgba(0, 0, 0, 0.05);
        }

        .item-name {
            font-weight: 500;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .item-price {
            font-weight: 600;
        }

        .order-total {
            display: flex;
            justify-content: space-between;
            font-weight: bold;
            color: var(--royal-purple);
            font-size: 1.4rem;
            margin-top: 15px;
            padding-top: 15px;
            border-top: 2px dashed var(--light-purple);
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-label {
            display: block;
            margin-bottom: 10px;
            font-weight: 600;
            color: var(--royal-purple);
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .payment-methods {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(120px, 1fr));
            gap: 15px;
            margin-bottom: 25px;
        }

        .method-card {
            background: var(--light);
            border-radius: 10px;
            padding: 15px;
            text-align: center;
            cursor: pointer;
            transition: var(--transition);
            border: 2px solid transparent;
        }

        .method-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
        }

        .method-card.active {
            border-color: var(--royal-gold);
            background: rgba(212, 175, 55, 0.1);
        }

        .method-icon {
            font-size: 2.5rem;
            margin-bottom: 10px;
            color: var(--royal-purple);
        }

        .method-name {
            font-weight: 600;
            color: var(--royal-purple);
        }

        .pay-btn {
            width: 100%;
            background: linear-gradient(135deg, var(--royal-purple), var(--primary));
            color: white;
            border: none;
            padding: 16px;
            border-radius: 12px;
            font-size: 1.2rem;
            font-weight: 600;
            cursor: pointer;
            transition: var(--transition);
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 12px;
            box-shadow: 0 4px 15px rgba(120, 81, 169, 0.3);
        }

        .pay-btn:hover {
            transform: translateY(-3px);
            box-shadow: 0 6px 20px rgba(120, 81, 169, 0.4);
        }

        .pay-btn:active {
            transform: translateY(0);
        }

        .payment-status {
            text-align: center;
            padding: 30px;
        }

        .success-msg {
            color: var(--success);
            font-size: 1.4rem;
            font-weight: 700;
            margin-bottom: 20px;
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 20px;
        }

        .success-icon {
            font-size: 4rem;
            color: var(--success);
            animation: pulse 1.5s infinite;
        }

        .error-msg {
            color: var(--danger);
            font-size: 1.2rem;
            font-weight: 600;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
            justify-content: center;
        }

        .back-link {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            color: var(--royal-purple);
            text-decoration: none;
            font-weight: 600;
            margin-top: 20px;
            transition: var(--transition);
            padding: 10px 20px;
            border-radius: 8px;
            background: var(--light);
        }

        .back-link:hover {
            background: var(--light-purple);
            transform: translateX(-5px);
        }

        @keyframes pulse {
            0% {
                transform: scale(1);
            }

            50% {
                transform: scale(1.1);
            }

            100% {
                transform: scale(1);
            }
        }

        /* Responsive */
        @media (max-width: 576px) {
            .payment-header {
                padding: 20px;
            }

            .payment-title {
                font-size: 1.8rem;
            }

            .payment-body {
                padding: 20px;
            }
        }

        .empty-cart {
            text-align: center;
            padding: 30px;
            color: var(--gray);
        }

        .empty-cart i {
            font-size: 3rem;
            margin-bottom: 15px;
            color: #ccc;
        }
    </style>
</head>

<body>
    <div class="payment-container">
        <div class="payment-header">
            <h1 class="payment-title">
                <i class="fas fa-credit-card"></i> Payment
            </h1>
            <p class="payment-subtitle">Complete your RoyalNest dining experience</p>
        </div>

        <?php if ($success): ?>
            <div class="payment-status">
                <div class="success-msg">
                    <i class="fas fa-check-circle success-icon"></i>
                    <div>Payment successful! Thank you for your order.</div>
                </div>
                <a href="/Hotel-Restaurant/user/dining.php" class="back-link">
                    <i class="fas fa-arrow-left"></i> Back to Menu
                </a>
            </div>
            <script>
                localStorage.removeItem('restaurant_cart');
            </script>
        <?php elseif ($error): ?>
            <div class="payment-status">
                <div class="error-msg">
                    <i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($error); ?>
                </div>
                <a href="/Hotel-Restaurant/user/dining.php" class="back-link">
                    <i class="fas fa-arrow-left"></i> Back to Menu
                </a>
            </div>
        <?php else: ?>
            <form method="post" id="payment-form" autocomplete="off">
                <div class="payment-body">
                    <div class="order-summary">
                        <h3 class="summary-title">
                            <i class="fas fa-receipt"></i> Order Summary
                        </h3>
                        <div class="order-items" id="order-items">
                            <!-- Items will be populated by JavaScript -->
                        </div>
                        <div class="order-total">
                            <span>Total:</span>
                            <span id="order-total">$0.00</span>
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="form-label"><i class="fas fa-wallet"></i> Payment Method</label>
                        <div class="payment-methods">
                            <div class="method-card active" data-method="Cash">
                                <div class="method-icon">
                                    <i class="fas fa-money-bill-wave"></i>
                                </div>
                                <div class="method-name">Cash</div>
                            </div>
                            <div class="method-card" data-method="Stripe">
                                <div class="method-icon">
                                    <i class="fab fa-cc-stripe"></i>
                                </div>
                                <div class="method-name">Stripe</div>
                            </div>
                            <div class="method-card" data-method="PayPal">
                                <div class="method-icon">
                                    <i class="fab fa-cc-paypal"></i>
                                </div>
                                <div class="method-name">PayPal</div>
                            </div>
                        </div>
                        <input type="hidden" name="method" id="method" value="Cash" required>
                    </div>

                    <input type="hidden" name="cart_json" id="cart_json" />
                    <button type="submit" class="pay-btn">
                        <i class="fas fa-lock"></i> Pay Now
                    </button>
                </div>
            </form>

            <script>
                // Load cart from localStorage
                let cart = [];
                try {
                    cart = JSON.parse(localStorage.getItem('restaurant_cart') || '[]');
                } catch (e) {}

                const orderItems = document.getElementById('order-items');
                const orderTotal = document.getElementById('order-total');
                const cartJson = document.getElementById('cart_json');

                if (!Array.isArray(cart) || cart.length === 0) {
                    orderItems.innerHTML = `
                        <div class="empty-cart">
                            <i class="fas fa-shopping-cart"></i>
                            <h3>Your cart is empty</h3>
                            <p>Please add items from our menu</p>
                        </div>
                    `;
                    document.querySelector('.pay-btn').disabled = true;
                } else {
                    let itemsHtml = '';
                    let total = 0;

                    cart.forEach(item => {
                        const itemTotal = item.price * item.quantity;
                        total += itemTotal;

                        itemsHtml += `
                            <div class="order-item">
                                <div class="item-name">
                                    <i class="fas fa-utensils"></i>
                                    <span>${item.name} x${item.quantity}</span>
                                </div>
                                <div class="item-price">$${itemTotal.toFixed(2)}</div>
                            </div>
                        `;
                    });

                    orderItems.innerHTML = itemsHtml;
                    orderTotal.textContent = `$${total.toFixed(2)}`;
                    cartJson.value = JSON.stringify(cart);
                }

                // Payment method selection
                const methodCards = document.querySelectorAll('.method-card');
                const methodInput = document.getElementById('method');

                methodCards.forEach(card => {
                    card.addEventListener('click', () => {
                        methodCards.forEach(c => c.classList.remove('active'));
                        card.classList.add('active');
                        methodInput.value = card.dataset.method;
                    });
                });
            </script>
        <?php endif; ?>
    </div>
</body>

</html>