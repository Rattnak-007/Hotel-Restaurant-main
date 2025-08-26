<?php
require_once("../../config/connect.php");
?>
<style>
    :root {
        --primary: #2c3e50;
        --primary-light: #34495e;
        --primary-dark: #1a252f;
        --secondary: #3498db;
        --accent: #e74c3c;
        --success: #27ae60;
        --warning: #f39c12;
        --light: #ecf0f1;
        --dark: #2c3e50;
        --gray: #7f8c8d;
        --light-gray: #e9ecef;
        --card-shadow: 0 10px 30px rgba(0, 0, 0, 0.08);
        --transition: all 0.3s ease;
    }

    body {
        background-color: #f5f7fa;
        font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        color: var(--dark);
        line-height: 1.6;
    }

    .content {
        margin-left: 240px;
        padding: 30px;
        transition: all 0.3s ease;
        box-sizing: border-box;
    }

    .contact-table-container {
        background: #fff;
        border-radius: 16px;
        box-shadow: var(--card-shadow);
        overflow: hidden;
        margin-bottom: 50px;
        transition: box-shadow 0.3s;
    }

    .contact-table-container:hover {
        box-shadow: 0 12px 35px rgba(0, 0, 0, 0.12);
    }

    .contact-title {
        padding: 25px 30px;
        background: linear-gradient(135deg, var(--primary), var(--primary-dark));
        color: #fff;
        font-size: 1.8rem;
        font-weight: 600;
        display: flex;
        align-items: center;
        gap: 15px;
        border-top-left-radius: 16px;
        border-top-right-radius: 16px;
    }

    .contact-title i {
        font-size: 1.5rem;
        background: rgba(255, 255, 255, 0.2);
        width: 50px;
        height: 50px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
    }

    .table-responsive {
        overflow-x: auto;
        width: 100%;
    }

    .contact-table {
        width: 100%;
        border-collapse: collapse;
        min-width: 900px;
    }

    .contact-table th {
        padding: 18px 20px;
        text-align: left;
        font-weight: 600;
        color: var(--primary);
        background: #f4f7fa;
        border-bottom: 2px solid var(--light-gray);
        font-size: 1rem;
        position: sticky;
        top: 0;
        z-index: 1;
    }

    .contact-table td {
        padding: 18px 20px;
        border-bottom: 1px solid var(--light-gray);
        font-size: 0.98rem;
        vertical-align: top;
        background: #fff;
    }

    .contact-table tbody tr {
        transition: background 0.2s;
    }

    .contact-table tbody tr:hover {
        background: #f0f6fa;
    }

    .contact-table tbody tr:nth-child(even) {
        background: #f9fafb;
    }

    .contact-table tbody tr:nth-child(even):hover {
        background: #f0f6fa;
    }

    .message-cell {
        max-width: 350px;
        word-break: break-word;
        line-height: 1.6;
        color: #222;
    }

    .timestamp {
        font-size: 0.92rem;
        color: var(--gray);
        white-space: nowrap;
    }

    .user-info {
        display: flex;
        flex-direction: column;
        gap: 5px;
    }

    .user-name {
        font-weight: 600;
        color: var(--primary);
        font-size: 1.05rem;
    }

    .user-email {
        color: var(--secondary);
        font-size: 0.93rem;
        word-break: break-all;
    }

    .user-phone {
        color: var(--accent);
        font-size: 0.93rem;
        font-weight: 500;
        letter-spacing: 0.5px;
    }

    .no-messages {
        text-align: center;
        padding: 50px 20px;
        color: var(--gray);
        background: #f9fafb;
    }

    .no-messages i {
        font-size: 3rem;
        margin-bottom: 18px;
        color: #d1d5db;
    }

    .no-messages h3 {
        font-weight: 600;
        margin-bottom: 10px;
        color: var(--dark);
    }

    .no-messages p {
        max-width: 500px;
        margin: 0 auto 20px;
        line-height: 1.6;
    }

    .status-badge {
        padding: 6px 15px;
        border-radius: 20px;
        font-size: 0.85rem;
        font-weight: 600;
        display: inline-block;
        min-width: 80px;
        text-align: center;
        box-shadow: 0 1px 4px rgba(0, 0, 0, 0.07);
    }

    .status-new {
        background: rgba(46, 204, 113, 0.15);
        color: #27ae60;
        border: 1px solid rgba(46, 204, 113, 0.25);
    }

    .status-read {
        background: rgba(149, 165, 166, 0.15);
        color: #7f8c8d;
        border: 1px solid rgba(149, 165, 166, 0.25);
    }

    .status-replied {
        background: rgba(52, 152, 219, 0.15);
        color: #2980b9;
        border: 1px solid rgba(52, 152, 219, 0.25);
    }

    .status-urgent {
        background: rgba(231, 76, 60, 0.15);
        color: #c0392b;
        border: 1px solid rgba(231, 76, 60, 0.25);
    }

    /* Column width adjustments */
    .contact-table th:nth-child(1),
    .contact-table td:nth-child(1) {
        width: 20%;
    }

    .contact-table th:nth-child(2),
    .contact-table td:nth-child(2) {
        width: 15%;
    }

    .contact-table th:nth-child(3),
    .contact-table td:nth-child(3) {
        width: 35%;
    }

    .contact-table th:nth-child(4),
    .contact-table td:nth-child(4) {
        width: 15%;
    }

    .contact-table th:nth-child(5),
    .contact-table td:nth-child(5) {
        width: 15%;
    }

    @media (max-width: 1200px) {
        .content {
            padding: 20px;
            margin-left: 0;
        }

        .contact-table th,
        .contact-table td {
            padding: 14px 16px;
        }

        .contact-table {
            min-width: 800px;
        }

        .message-cell {
            max-width: 300px;
        }
    }

    @media (max-width: 992px) {
        .content {
            padding: 15px;
        }

        .contact-title {
            font-size: 1.5rem;
            padding: 20px;
        }

        .contact-table th,
        .contact-table td {
            padding: 12px 14px;
        }

        .contact-table {
            min-width: 700px;
        }

        .message-cell {
            max-width: 250px;
        }
    }

    @media (max-width: 768px) {
        .content {
            padding: 10px;
        }

        .contact-title {
            font-size: 1.3rem;
            padding: 15px;
        }

        .contact-title i {
            width: 40px;
            height: 40px;
            font-size: 1.2rem;
        }

        .contact-table th,
        .contact-table td {
            padding: 10px 12px;
            font-size: 0.9rem;
        }

        .contact-table {
            min-width: 600px;
        }

        .user-name {
            font-size: 0.95rem;
        }

        .user-email,
        .user-phone {
            font-size: 0.85rem;
        }

        .message-cell {
            max-width: 200px;
            font-size: 0.9rem;
        }

        .timestamp {
            font-size: 0.8rem;
        }

        .status-badge {
            font-size: 0.8rem;
            min-width: 70px;
            padding: 5px 10px;
        }
    }

    @media (max-width: 576px) {
        .contact-title {
            font-size: 1.1rem;
            flex-direction: row;
            text-align: left;
            gap: 10px;
            padding: 12px;
        }

        .contact-title i {
            width: 35px;
            height: 35px;
            font-size: 1rem;
        }

        .no-messages {
            padding: 30px 15px;
        }

        .no-messages i {
            font-size: 2.5rem;
        }

        .no-messages h3 {
            font-size: 1.1rem;
        }

        .no-messages p {
            font-size: 0.9rem;
        }

        .contact-table {
            min-width: 500px;
        }

        .contact-table th,
        .contact-table td {
            padding: 8px 10px;
        }

        .message-cell {
            max-width: 150px;
        }
    }
</style>

<div class="dashboard-container">
    <?php require_once("../include/Header.php"); ?>
    <div class="content">
        <div class="contact-table-container">
            <div class="contact-title">
                <i class="fas fa-envelope"></i> Contact Messages
            </div>
            <table class="contact-table">
                <thead>
                    <tr>
                        <th>Contact</th>
                        <th>Phone</th>
                        <th>Message</th>
                        <th>Status</th>
                        <th>Timestamp</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $sql = "SELECT name, email, phone, message, status, TO_CHAR(created_at, 'YYYY-MM-DD HH24:MI:SS') AS created_at FROM contact ORDER BY created_at DESC";
                    $stmt = oci_parse($connection, $sql);
                    oci_execute($stmt);

                    $hasMessages = false;

                    while ($row = oci_fetch_assoc($stmt)) {
                        $hasMessages = true;
                        $messageText = '';
                        if (is_object($row['MESSAGE']) && method_exists($row['MESSAGE'], 'load')) {
                            $messageText = $row['MESSAGE']->load();
                        } else {
                            $messageText = (string) $row['MESSAGE'];
                        }
                        $status = htmlspecialchars($row['STATUS'] ?? 'New');
                        echo "<tr>
                            <td>
                                <div class='user-info'>
                                    <div class='user-name'>" . htmlspecialchars($row['NAME']) . "</div>
                                    <div class='user-email'>" . htmlspecialchars($row['EMAIL']) . "</div>
                                </div>
                            </td>
                            <td><span class='user-phone'>" . htmlspecialchars($row['PHONE']) . "</span></td>
                            <td class='message-cell'>" . nl2br(htmlspecialchars($messageText)) . "</td>
                            <td>
                                <span class='status-badge'>$status</span>
                            </td>
                            <td class='timestamp'>" . $row['CREATED_AT'] . "</td>
                        </tr>";
                    }

                    oci_free_statement($stmt);

                    if (!$hasMessages) {
                        echo '<tr>
                            <td colspan="5">
                                <div class="no-messages">
                                    <i class="fas fa-inbox"></i>
                                    <h3>No Contact Messages</h3>
                                    <p>You haven\'t received any contact messages yet. All new messages will appear here.</p>
                                </div>
                            </td>
                        </tr>';
                    }
                    ?>
                </tbody>
            </table>
        </div>
    </div>
</div>