-- 1. INNER JOIN: List all restaurant orders with user name and total amount
SELECT ro.order_id, u.name AS user_name, ro.total_amount, ro.status
FROM restaurant_orders ro
INNER JOIN users u ON ro.user_id = u.user_id
ORDER BY ro.order_id DESC;

-- 2. LEFT JOIN: List all users and their latest booking (if any)
SELECT u.user_id, u.name, b.booking_id, b.check_in_date, b.status
FROM users u
LEFT JOIN bookings b ON u.user_id = b.user_id
ORDER BY u.user_id;

-- 3. RIGHT JOIN: List all rooms and the user who booked them (if any)
SELECT r.room_id, r.room_name, b.user_id, b.booking_id
FROM rooms r
RIGHT JOIN bookings b ON r.room_id = b.room_id
ORDER BY r.room_id;

-- 4. GROUP BY: Count number of bookings per room
SELECT r.room_name, COUNT(b.booking_id) AS total_bookings
FROM rooms r
LEFT JOIN bookings b ON r.room_id = b.room_id
GROUP BY r.room_name
ORDER BY total_bookings DESC;

-- 5. HAVING: Rooms with more than 2 bookings
SELECT r.room_name, COUNT(b.booking_id) AS total_bookings
FROM rooms r
LEFT JOIN bookings b ON r.room_id = b.room_id
GROUP BY r.room_name
HAVING COUNT(b.booking_id) > 2
ORDER BY total_bookings DESC;

-- 6. ORDER BY: List all menu items ordered by price descending
SELECT menu_id, name, price, category
FROM restaurant_menu
ORDER BY price DESC;

-- 7. INNER JOIN with GROUP BY: Top users by total restaurant order amount
SELECT u.user_id, u.name, SUM(ro.total_amount) AS total_spent
FROM users u
INNER JOIN restaurant_orders ro ON u.user_id = ro.user_id
GROUP BY u.user_id, u.name
ORDER BY total_spent DESC;

-- 8. LEFT JOIN with GROUP BY: Number of payments per user (including users with no payments)
SELECT u.user_id, u.name, COUNT(op.payment_id) AS payment_count
FROM users u
LEFT JOIN order_payments op ON u.user_id = op.user_id
GROUP BY u.user_id, u.name
ORDER BY payment_count DESC;

-- 9. RIGHT JOIN with GROUP BY: Number of orders per menu item (including menu items never ordered)
SELECT m.menu_id, m.name, COUNT(oi.order_item_id) AS times_ordered
FROM restaurant_menu m
RIGHT JOIN order_items oi ON m.menu_id = oi.menu_id
GROUP BY m.menu_id, m.name
ORDER BY times_ordered DESC;

-- 10. INNER JOIN with HAVING: Users who have spent more than $500 in total
SELECT u.user_id, u.name, SUM(ro.total_amount) AS total_spent
FROM users u
INNER JOIN restaurant_orders ro ON u.user_id = ro.user_id
GROUP BY u.user_id, u.name
HAVING SUM(ro.total_amount) > 500
ORDER BY total_spent DESC;
