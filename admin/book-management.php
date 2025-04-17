<?php
// Start session at the very beginning of the file
session_start();

// At the top of the file, after session_start()
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: ../admin/admin-login.php');
    exit();
}

// Get admin name from session
$adminFirstName = $_SESSION['admin_first_name'] ?? 'Admin';
$adminLastName = $_SESSION['admin_last_name'] ?? '';

// Include the database connection
$pdo = require '../database/db_connection.php';

// Function to get random cover URL
function getRandomCover($title, $author) {
    // List of background colors (pastel colors)
    $colors = [
        'F8B195', 'F67280', 'C06C84', '6C5B7B', '355C7D', // warm to cool
        'A8E6CF', 'DCEDC1', 'FFD3B6', 'FFAAA5', 'FF8B94', // nature
        'B5EAD7', 'C7CEEA', 'E2F0CB', 'FFDAC1', 'FFB7B2', // soft
        'E7D3EA', 'DCD3FF', 'B5D8EB', 'BBE1FA', 'D6E5FA'  // pastel
    ];
    
    // Get random color
    $bgColor = $colors[array_rand($colors)];
    
    // Format title and author for URL
    $text = urlencode($title . "\nby " . $author);
    
    return "https://placehold.co/400x600/${bgColor}/333333/png?text=${text}";
}

// Fetch books from database outside of the HTML
try {
    // Fetch books with their status
    $stmt = $pdo->query('
        SELECT b.*,
            (SELECT COUNT(*) FROM borrowed_books bb 
             WHERE bb.book_id = b.book_id AND bb.return_date IS NULL) as is_borrowed
        FROM books b
        ORDER BY b.book_id DESC
    ');
    $books = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Update books with cover images if they don't have one
    foreach ($books as &$book) {
        if (empty($book['cover_image_url'])) {
            $book['cover_image_url'] = getRandomCover($book['title'], $book['author']);
        }
    }
} catch (PDOException $e) {
    error_log("Error: " . $e->getMessage());
    $books = [];
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>Book Management - Admin Dashboard</title>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/admin-dashboard.css">
    <style>
        .book-management-container {
            padding: 20px;
            background: #FEF3E8;
        }

        .controls-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
            flex-wrap: wrap;
            gap: 20px;
        }

        .page-title {
            color: #B07154;
            font-size: 24px;
            font-weight: 600;
        }

        .controls-container {
            display: flex;
            gap: 15px;
            flex-wrap: wrap;
        }

        .filter-group {
            display: flex;
            gap: 10px;
        }

        select.filter-select {
            padding: 10px 15px;
            border: 2px solid #B07154;
            border-radius: 8px;
            font-family: 'Montserrat', sans-serif;
            font-size: 14px;
            color: #495057;
            background: white;
            cursor: pointer;
        }

        .search-box {
            position: relative;
        }

        .search-input {
            width: 300px;
            padding: 10px 15px;
            border: 2px solid #B07154;
            border-radius: 8px;
            font-family: 'Montserrat', sans-serif;
            font-size: 14px;
        }

        .add-book-btn {
            padding: 10px 20px;
            background: #B07154;
            color: white;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-family: 'Montserrat', sans-serif;
            font-weight: 600;
            transition: background-color 0.3s;
        }

        .add-book-btn:hover {
            background: #8B5B43;
        }

        .books-table {
            width: 100%;
            background: white;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }

        .books-table table {
            width: 100%;
            border-collapse: collapse;
        }

        .books-table th {
            background: #F4DECB;
            color: #B07154;
            font-weight: 600;
            text-align: left;
            padding: 15px;
            font-size: 14px;
        }

        .books-table td {
            padding: 15px;
            border-bottom: 1px solid #F4DECB;
            color: #495057;
            font-size: 14px;
        }

        .books-table tr:last-child td {
            border-bottom: none;
        }

        .book-cover {
            width: 80px;
            height: 120px;
            object-fit: cover;
            border-radius: 4px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            transition: transform 0.2s ease, box-shadow 0.2s ease;
        }

        .book-cover:hover {
            transform: scale(1.05);
            box-shadow: 0 4px 8px rgba(0,0,0,0.2);
        }

        .mobile-card .book-cover {
            width: 120px;
            height: 180px;
        }

        .book-title {
            font-weight: 600;
            color: #B07154;
        }

        .status-badge {
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
        }

        .status-available {
            background-color: #E8F5E9;
            color: #2E7D32;
        }

        .status-borrowed {
            background-color: #FFEBEE;
            color: #C62828;
        }

        .action-buttons {
            display: flex;
            gap: 8px;
        }

        .action-btn {
            padding: 6px 12px;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-family: 'Montserrat', sans-serif;
            font-weight: 600;
            font-size: 12px;
            transition: background-color 0.3s;
        }

        .edit-btn {
            background: #F4DECB;
            color: #B07154;
        }

        .edit-btn:hover {
            background: #E4C4A9;
        }

        .delete-btn {
            background: #B07154;
            color: white;
        }

        .delete-btn:hover {
            background: #8B5B43;
        }

        .mobile-card {
            display: none;
            background: white;
            border-radius: 12px;
            padding: 15px;
            margin-bottom: 15px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }

        .mobile-card-header {
            display: flex;
            gap: 15px;
            margin-bottom: 15px;
        }

        .mobile-card-info {
            flex: 1;
        }

        .mobile-card-actions {
            margin-top: 15px;
        }

        @media (max-width: 1024px) {
            .controls-header {
                flex-direction: column;
                align-items: stretch;
            }

            .controls-container {
                flex-direction: column;
            }

            .filter-group {
                flex-wrap: wrap;
            }

            .search-input {
                width: 100%;
            }
        }

        @media (max-width: 768px) {
            .books-table {
                display: none;
            }

            .mobile-card {
                display: block;
            }

            .page-title {
                font-size: 20px;
            }

            select.filter-select {
                width: 100%;
            }
        }

        @media (max-width: 480px) {
            .book-management-container {
                padding: 15px;
            }

            .mobile-card {
                padding: 12px;
            }

            .action-btn {
                padding: 5px 10px;
                font-size: 11px;
            }
        }

        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.5);
        }

        .modal-content {
            background: white;
            margin: 5% auto;
            padding: 30px;
            border-radius: 12px;
            width: 90%;
            max-width: 500px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            position: relative;
        }

        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }

        .modal-header h2 {
            color: #B07154;
            font-size: 24px;
            font-weight: 600;
            margin: 0;
        }

        .close-btn {
            color: #B07154;
            font-size: 28px;
            font-weight: bold;
            cursor: pointer;
            padding: 0 8px;
            line-height: 1;
            transition: color 0.3s;
        }

        .close-btn:hover {
            color: #95604A;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            color: #333;
            font-weight: 500;
            margin-bottom: 8px;
        }

        .form-group input {
            width: 100%;
            padding: 12px;
            border: 2px solid #F4DECB;
            border-radius: 8px;
            font-size: 14px;
            font-family: 'Montserrat', sans-serif;
        }

        .form-group input:focus {
            outline: none;
            border-color: #B07154;
        }

        .form-buttons {
            display: flex;
            justify-content: flex-end;
            gap: 15px;
            margin-top: 30px;
        }

        .cancel-btn, .submit-btn {
            padding: 12px 24px;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-weight: 600;
            font-size: 14px;
            font-family: 'Montserrat', sans-serif;
            transition: background-color 0.3s;
        }

        .cancel-btn {
            background: #F4DECB;
            color: #B07154;
        }

        .cancel-btn:hover {
            background: #E8C5B0;
        }

        .submit-btn {
            background: #B07154;
            color: white;
        }

        .submit-btn:hover {
            background: #95604A;
        }

        @media (max-width: 768px) {
            .modal-content {
                margin: 10% auto;
                padding: 20px;
                width: 95%;
            }
        }

        .notification {
            position: fixed;
            top: 20px;
            right: 20px;
            padding: 15px 20px;
            border-radius: 8px;
            color: white;
            font-weight: 500;
            display: flex;
            align-items: center;
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
            z-index: 1100;
            transform: translateX(120%);
            transition: transform 0.4s ease-out;
            max-width: 300px;
        }

        .notification.show {
            transform: translateX(0);
        }

        .notification-success {
            background-color: #4CAF50;
            border-left: 5px solid #2E7D32;
        }

        .notification-error {
            background-color: #f44336;
            border-left: 5px solid #B71C1C;
        }

        .notification-icon {
            margin-right: 10px;
            font-size: 20px;
        }

        .notification-text {
            flex-grow: 1;
        }

        .notification-close {
            margin-left: 10px;
            cursor: pointer;
            font-weight: bold;
            font-size: 18px;
        }

        /* Delete Modal Specific Styles */
        #deleteModal .modal-content {
            max-width: 400px;
        }

        #deleteModal .modal-header {
            border-bottom: none;
            padding-bottom: 0;
        }

        #deleteModal .modal-header h2 {
            color: #B07154;
            font-size: 24px;
            font-weight: 600;
        }

        .warning-icon {
            width: 64px;
            height: 64px;
            background: #FEF3E8;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 20px auto;
            color: #B07154;
            font-size: 40px;
            font-weight: bold;
            font-family: 'Montserrat', sans-serif;
        }

        .delete-message {
            color: #666666;
            font-size: 16px;
            font-weight: 500;
            margin-bottom: 30px;
            font-family: 'Montserrat', sans-serif;
        }

        .button-group {
            display: flex;
            justify-content: center;
            gap: 20px;
            margin-top: 30px;
        }

        .cancel-btn {
            padding: 12px 32px;
            background: #F4DECB;
            color: #B07154;
            border: none;
            border-radius: 8px;
            font-weight: 600;
            font-size: 14px;
            font-family: 'Montserrat', sans-serif;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .cancel-btn:hover {
            background: #E8C5B0;
        }

        .delete-confirm-btn {
            padding: 12px 32px;
            background: #B07154;
            color: white;
            border: none;
            border-radius: 8px;
            font-weight: 600;
            font-size: 14px;
            font-family: 'Montserrat', sans-serif;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .delete-confirm-btn:hover {
            background: #95604A;
            transform: translateY(-1px);
            box-shadow: 0 4px 6px rgba(176, 113, 84, 0.2);
        }

        .delete-confirm-btn:active {
            transform: translateY(0);
            box-shadow: none;
        }

        .delete-icon {
            width: 16px;
            height: 16px;
            filter: brightness(0) invert(1);
        }

        @media (max-width: 768px) {
            .button-group {
                flex-direction: column;
                gap: 12px;
                padding: 0 20px;
            }

            .cancel-btn,
            .delete-confirm-btn {
                width: 100%;
                padding: 14px 20px;
            }

            .delete-confirm-btn {
                justify-content: center;
            }
        }

        /* Modal animation */
        .modal {
            animation: fadeIn 0.3s ease;
        }

        .modal-content {
            animation: slideIn 0.3s ease;
        }

        @keyframes fadeIn {
            from {
                opacity: 0;
            }
            to {
                opacity: 1;
            }
        }

        @keyframes slideIn {
            from {
                transform: translateY(-20px);
                opacity: 0;
            }
            to {
                transform: translateY(0);
                opacity: 1;
            }
        }

        /* Warning icon style */
        .warning-icon {
            width: 64px;
            height: 64px;
            background: #FEF3E8;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 20px auto;
            color: #B07154;
            font-size: 40px;
            font-weight: bold;
            font-family: 'Montserrat', sans-serif;
        }

        .delete-message {
            color: #4B5563;
            font-size: 16px;
            font-weight: 500;
            text-align: center;
            margin: 20px 0;
            font-family: 'Montserrat', sans-serif;
        }

        /* Modal header style */
        .modal-header h2 {
            color: #B07154;
            font-size: 24px;
            font-weight: 600;
            font-family: 'Montserrat', sans-serif;
            margin: 0;
        }

        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }

        .close-btn {
            color: #B07154;
            font-size: 28px;
            font-weight: bold;
            cursor: pointer;
            padding: 0 8px;
            line-height: 1;
            transition: color 0.3s;
        }

        .close-btn:hover {
            color: #95604A;
        }

        /* Updated notification styles */
        #notificationContainer {
            position: fixed;
            top: 20px;
            right: 20px;
            z-index: 1100;
        }

        .notification {
            background: #FEF3E8;
            border: 2px solid #B07154;
            border-radius: 12px;
            padding: 20px;
            margin-bottom: 15px;
            box-shadow: 0 4px 8px rgba(176, 113, 84, 0.15);
            display: flex;
            align-items: center;
            gap: 15px;
            transform: translateX(120%);
            transition: transform 0.4s ease;
            min-width: 320px;
        }

        .notification.show {
            transform: translateX(0);
        }

        .notification-success {
            background: #FEF3E8;
            border-color: #B07154;
        }

        .notification-icon {
            font-size: 24px;
            color: #B07154;
        }

        .notification-text {
            color: #B07154;
            font-weight: 600;
            font-family: 'Montserrat', sans-serif;
            font-size: 16px;
            flex-grow: 1;
        }

        .notification-close {
            cursor: pointer;
            color: #B07154;
            font-size: 24px;
            padding: 0 5px;
            opacity: 0.7;
            transition: opacity 0.3s;
        }

        .notification-close:hover {
            opacity: 1;
        }

        @media (max-width: 768px) {
            .notification {
                width: 90%;
                min-width: auto;
                margin: 0 auto 10px;
            }

            #notificationContainer {
                left: 0;
                right: 0;
                padding: 0 10px;
            }
        }

        .header {
            background: white;
            padding: 20px 40px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .admin-profile {
            display: flex;
            align-items: center;
            gap: 20px;
        }

        .admin-info {
            display: flex;
            flex-direction: column;
        }

        .datetime-display {
            display: flex;
            align-items: center;
            gap: 30px;
        }

        .time-section, .date-section {
            display: flex;
            align-items: center;
            gap: 10px;
            color: #B07154;
        }

        .time-icon, .date-icon {
            width: 24px;
            height: 24px;
        }

        .time-display, .date-display {
            font-size: 16px;
            font-weight: 500;
            color: #B07154;
            font-family: 'Montserrat', sans-serif;
        }

        .admin-name-1 {
            font-size: 22px;
            color: #B07154;
            font-weight: 600;
            margin-bottom: 4px;
            font-family: 'Montserrat', sans-serif;
        }

        @media (max-width: 768px) {
            .datetime-display {
                flex-direction: column;
                gap: 10px;
                align-items: flex-start;
            }
        }
    </style>
</head>

<body>
    <div class="mobile-menu-btn">
        <span></span>
        <span></span>
        <span></span>
    </div>
    <div class="sidebar">
        <div class="logo">
            <img src="../images/logo.png" alt="Book King Logo">
        </div>
        <div class="nav-group">
            <a href="../admin/admin-dashboard.php" class="nav-item">
                <div class="icon">
                    <img src="../images/element-2 2.svg" alt="Dashboard" width="24" height="24">
                </div>
                <div class="text">Dashboard</div>
            </a>
            <a href="../admin/catalog.php" class="nav-item">
                <div class="icon">
                    <img src="../images/Vector.svg" alt="Catalog" width="20" height="20">
                </div>
                <div class="text">Catalog</div>
            </a>
            <a href="../admin/book-management.php" class="nav-item active">
                <div class="icon">
                    <img src="../images/book.png" alt="Books" width="24" height="24">
                </div>
                <div class="text">Books</div>
            </a>
            <a href="../admin/user-management.php" class="nav-item">
                <div class="icon">
                    <img src="../images/people 3.png" alt="Users" width="24" height="24">
                </div>
                <div class="text">Users</div>
            </a>
            <a href="../admin/branch-management.php" class="nav-item">
                <div class="icon">
                    <img src="../images/buildings-2 1.png" alt="Branches" width="24" height="24">
                </div>
                <div class="text">Branches</div>
            </a>
            <a href="../admin/borrowers-management.php" class="nav-item">
                <div class="icon">
                    <img src="../images/user.png" alt="Borrowers" width="24" height="24">
                </div>
                <div class="text">Borrowers</div>
            </a>

        </div>
        <a href="../admin/admin-logout.php" class="nav-item logout">
            <div class="icon">
                <img src="../images/logout 3.png" alt="Log Out" width="24" height="24">
            </div>
            <div class="text">Log Out</div>
        </a>
    </div>

    <div class="content">
        <div class="header">
            <div class="admin-profile">
                <div class="admin-info">
                    <span class="admin-name-1">Welcome, <?= htmlspecialchars($adminFirstName . ' ' . $adminLastName) ?></span>
                </div>
            </div>
            <div class="datetime-display">
                <div class="time-section">
                    <svg class="time-icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24">
                        <path fill="#B07154" d="M12 2C6.486 2 2 6.486 2 12s4.486 10 10 10 10-4.486 10-10S17.514 2 12 2zm0 18c-4.411 0-8-3.589-8-8s3.59-8 8-8 8 3.589 8 8-3.589 8-8 8z"/>
                        <path fill="#B07154" d="M13 7h-2v6l4.5 2.7.7-1.2-3.2-1.9z"/>
                    </svg>
                    <span class="time-display">--:--:-- --</span>
                </div>
                <div class="date-section">
                    <svg class="date-icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24">
                        <path fill="#B07154" d="M19 4h-2V3a1 1 0 0 0-2 0v1H9V3a1 1 0 0 0-2 0v1H5a3 3 0 0 0-3 3v12a3 3 0 0 0 3 3h14a3 3 0 0 0 3-3V7a3 3 0 0 0-3-3zm1 15a1 1 0 0 1-1 1H5a1 1 0 0 1-1-1v-7h16v7zm0-9H4V7a1 1 0 0 1 1-1h2v1a1 1 0 0 0 2 0V6h6v1a1 1 0 0 0 2 0V6h2a1 1 0 0 1 1 1v3z"/>
                    </svg>
                    <span class="date-display">--- --, ----</span>
                </div>
            </div>
        </div>
        <div class="book-management-container">
            <div class="controls-header">
                <h1 class="page-title">Book Management</h1>
                <div class="controls-container">
                    <div class="filter-group">
                        <select class="filter-select" id="statusFilter">
                            <option value="">All Status</option>
                            <option value="available">Available</option>
                            <option value="borrowed">Borrowed</option>
                        </select>
                    </div>
                    <div class="search-box">
                        <input type="text" class="search-input" id="searchInput" placeholder="Search books...">
                    </div>
                    <button class="add-book-btn" onclick="location.href='add-book.php'">Add New Book</button>
                </div>
            </div>

            <!-- Desktop Table View -->
            <div class="books-table">
                <table>
                    <thead>
                        <tr>
                            <th>Cover</th>
                            <th>Title</th>
                            <th>Author</th>
                            <th>Type</th>
                            <th>Language</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($books as $book): ?>
                            <tr>
                                <td>
                                    <img src="<?= htmlspecialchars($book['cover_image_url'] ?? '../images/default-book-cover.jpg') ?>" 
                                         alt="<?= htmlspecialchars($book['title']) ?>" 
                                         class="book-cover">
                                </td>
                                <td class="book-title"><?= htmlspecialchars($book['title']) ?></td>
                                <td><?= htmlspecialchars($book['author']) ?></td>
                                <td><?= htmlspecialchars($book['type']) ?></td>
                                <td><?= htmlspecialchars($book['language']) ?></td>
                                <td>
                                    <span class="status-badge <?= $book['is_borrowed'] ? 'status-borrowed' : 'status-available' ?>">
                                        <?= $book['is_borrowed'] ? 'Borrowed' : 'Available' ?>
                                    </span>
                                </td>
                                <td>
                                    <div class="action-buttons">
                                        <button class="action-btn edit-btn" onclick="editBook(<?= $book['book_id'] ?>)">Edit</button>
                                        <button class="action-btn delete-btn" onclick="deleteBook(<?= $book['book_id'] ?>)">Delete</button>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <!-- Mobile Card View -->
            <div class="mobile-view">
                <?php foreach ($books as $book): ?>
                    <div class="mobile-card">
                        <div class="mobile-card-header">
                            <img src="<?= htmlspecialchars($book['cover_image_url'] ?? '../images/default-book-cover.jpg') ?>" 
                                 alt="<?= htmlspecialchars($book['title']) ?>" 
                                 class="book-cover">
                            <div class="mobile-card-info">
                                <div class="book-title"><?= htmlspecialchars($book['title']) ?></div>
                                <div><?= htmlspecialchars($book['author']) ?></div>
                                <div><?= htmlspecialchars($book['type']) ?></div>
                                <div><?= htmlspecialchars($book['language']) ?></div>
                                <span class="status-badge <?= $book['is_borrowed'] ? 'status-borrowed' : 'status-available' ?>">
                                    <?= $book['is_borrowed'] ? 'Borrowed' : 'Available' ?>
                                </span>
                            </div>
                        </div>
                        <div class="mobile-card-actions">
                            <button class="action-btn edit-btn" 
                                    onclick="editBook(<?= $book['book_id'] ?>)">
                                Edit
                            </button>
                            <button class="action-btn delete-btn" 
                                    onclick="deleteBook(<?= $book['book_id'] ?>)">
                                Delete
                            </button>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>

    <!-- Update the Edit Modal HTML -->
    <div id="editModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2>Edit Book</h2>
                <span class="close-btn" onclick="closeModal()">&times;</span>
            </div>
            <form id="editBookForm">
                <input type="hidden" id="edit_book_id" name="book_id">
                <div class="form-group">
                    <label for="edit_title">Title</label>
                    <input type="text" id="edit_title" name="title" required>
                </div>
                <div class="form-group">
                    <label for="edit_author">Author</label>
                    <input type="text" id="edit_author" name="author" required>
                </div>
                <div class="form-group">
                    <label for="edit_type">Type</label>
                    <input type="text" id="edit_type" name="type" required>
                </div>
                <div class="form-group">
                    <label for="edit_language">Language</label>
                    <input type="text" id="edit_language" name="language" required>
                </div>
                <div class="form-buttons">
                    <button type="button" class="cancel-btn" onclick="closeModal()">Cancel</button>
                    <button type="submit" class="submit-btn">Save Changes</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Update the Delete Modal HTML -->
    <div id="deleteModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2>Delete Book</h2>
                <span class="close-btn" onclick="closeDeleteModal()">&times;</span>
            </div>
            <div class="modal-body">
                <div class="warning-icon">!</div>
                <p class="delete-message">Are you sure you want to delete this book?</p>
                <div class="button-group">
                    <button class="cancel-btn" onclick="closeDeleteModal()">Cancel</button>
                    <button class="delete-confirm-btn" onclick="confirmDelete()">Delete</button>
                </div>
            </div>
        </div>
    </div>

    <div id="notificationContainer"></div>

    <script>
        // Mobile menu functionality
        document.addEventListener('DOMContentLoaded', function() {
            const mobileMenuBtn = document.querySelector('.mobile-menu-btn');
            const sidebar = document.querySelector('.sidebar');
            const content = document.querySelector('.content');
            const body = document.body;

            // Create overlay element
            const overlay = document.createElement('div');
            overlay.className = 'sidebar-overlay';
            body.appendChild(overlay);

            function toggleMenu() {
                mobileMenuBtn.classList.toggle('active');
                sidebar.classList.toggle('active');
                content.classList.toggle('sidebar-active');
                overlay.classList.toggle('active');
                body.style.overflow = sidebar.classList.contains('active') ? 'hidden' : '';
            }

            mobileMenuBtn.addEventListener('click', toggleMenu);
            overlay.addEventListener('click', toggleMenu);

            // Close menu when clicking a nav item on mobile
            const navItems = document.querySelectorAll('.nav-item');
            navItems.forEach(item => {
                item.addEventListener('click', () => {
                    if (window.innerWidth <= 768 && sidebar.classList.contains('active')) {
                        toggleMenu();
                    }
                });
            });

            // Handle resize events
            let resizeTimer;
            window.addEventListener('resize', () => {
                clearTimeout(resizeTimer);
                resizeTimer = setTimeout(() => {
                    if (window.innerWidth > 768) {
                        mobileMenuBtn.classList.remove('active');
                        sidebar.classList.remove('active');
                        content.classList.remove('sidebar-active');
                        overlay.classList.remove('active');
                        body.style.overflow = '';
                    }
                }, 250);
            });

            // Search and filter functionality
            const searchInput = document.getElementById('searchInput');
            const statusFilter = document.getElementById('statusFilter');
            const bookElements = document.querySelectorAll('.books-table tr:not(:first-child), .mobile-card');

            function filterBooks() {
                const searchTerm = searchInput.value.toLowerCase();
                const statusValue = statusFilter.value.toLowerCase();

                bookElements.forEach(element => {
                    const isTableRow = element.tagName === 'TR';
                    const title = isTableRow 
                        ? element.querySelector('.book-title').textContent.toLowerCase()
                        : element.querySelector('.mobile-card-info .book-title').textContent.toLowerCase();
                    const status = isTableRow
                        ? element.querySelector('.status-badge').textContent.toLowerCase()
                        : element.querySelector('.status-badge').textContent.toLowerCase();

                    const matchesSearch = title.includes(searchTerm);
                    const matchesStatus = !statusValue || status === statusValue;

                    element.style.display = 
                        matchesSearch && matchesStatus ? '' : 'none';
                });
            }

            searchInput.addEventListener('input', filterBooks);
            statusFilter.addEventListener('change', filterBooks);
        });

        let currentBookId = null;

        function editBook(bookId) {
            currentBookId = bookId;
            // Fetch book details
            fetch(`get_book.php?id=${bookId}`)
                .then(response => response.json())
                .then(book => {
                    document.getElementById('edit_book_id').value = book.book_id;
                    document.getElementById('edit_title').value = book.title;
                    document.getElementById('edit_author').value = book.author;
                    document.getElementById('edit_type').value = book.type;
                    document.getElementById('edit_language').value = book.language;
                    document.getElementById('editModal').style.display = 'block';
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Error fetching book details');
                });
        }

        function closeModal() {
            document.getElementById('editModal').style.display = 'none';
            currentBookId = null;
        }

        function deleteBook(bookId) {
            currentBookId = bookId;
            document.getElementById('deleteModal').style.display = 'block';
        }

        function closeDeleteModal() {
            document.getElementById('deleteModal').style.display = 'none';
            currentBookId = null;
        }

        function confirmDelete() {
            if (!currentBookId) return;

            const formData = new FormData();
            formData.append('book_id', currentBookId);

            fetch('delete_book.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showNotification('success', 'Book deleted successfully', '📚');
                    setTimeout(() => {
                        window.location.reload();
                    }, 1500);
                } else {
                    showNotification('error', data.message || 'Error deleting book', '❌');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showNotification('error', 'Error deleting book', '❌');
            })
            .finally(() => {
                closeDeleteModal();
            });
        }

        // Handle edit form submission
        document.getElementById('editBookForm').addEventListener('submit', function(e) {
            e.preventDefault();
            const formData = new FormData(this);

            fetch('edit_book.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showNotification('success', 'Book updated successfully', '✅');
                    setTimeout(() => {
                        window.location.reload();
                    }, 1500);
                } else {
                    showNotification('error', data.message || 'Error updating book', '❌');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showNotification('error', 'Error updating book', '❌');
            })
            .finally(() => {
                closeModal();
            });
        });

        // Close modals when clicking outside
        window.onclick = function(event) {
            const editModal = document.getElementById('editModal');
            const deleteModal = document.getElementById('deleteModal');
            if (event.target == editModal) {
                closeModal();
            }
            if (event.target == deleteModal) {
                closeDeleteModal();
            }
        }

        function showNotification(type, message, icon) {
            const container = document.getElementById('notificationContainer');
            const notification = document.createElement('div');
            
            notification.className = `notification notification-${type}`;
            notification.innerHTML = `
                <div class="notification-icon">📚</div>
                <div class="notification-text">Book deleted successfully</div>
                <div class="notification-close">×</div>
            `;
            
            container.appendChild(notification);
            
            // Trigger reflow for animation
            notification.offsetHeight;
            notification.classList.add('show');
            
            // Close button handler
            notification.querySelector('.notification-close').addEventListener('click', () => {
                notification.classList.remove('show');
                setTimeout(() => {
                    container.removeChild(notification);
                }, 400);
            });
            
            // Auto close after 3 seconds
            setTimeout(() => {
                if (container.contains(notification)) {
                    notification.classList.remove('show');
                    setTimeout(() => {
                        if (container.contains(notification)) {
                            container.removeChild(notification);
                        }
                    }, 400);
                }
            }, 3000);
        }

        document.addEventListener('DOMContentLoaded', function() {
            // Update datetime display
            function updateDateTime() {
                const now = new Date();
                
                // Update time
                const timeDisplay = document.querySelector('.time-display');
                timeDisplay.textContent = now.toLocaleString('en-US', {
                    hour: 'numeric',
                    minute: '2-digit',
                    second: '2-digit',
                    hour12: true
                });

                // Update date
                const dateDisplay = document.querySelector('.date-display');
                dateDisplay.textContent = now.toLocaleDateString('en-US', {
                    month: 'short',
                    day: '2-digit',
                    year: 'numeric'
                });
            }

            // Update immediately and then every second
            updateDateTime();
            setInterval(updateDateTime, 1000);
        });
    </script>
</body>

</html>