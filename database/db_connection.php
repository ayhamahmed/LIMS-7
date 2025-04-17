<?php
// database/db_connection.php

try {
    $pdo = new PDO(
        "mysql:host=localhost;dbname=lims",
        "root",
        "",
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );
    
    // Add Status column if it doesn't exist
    $stmt = $pdo->prepare("SHOW COLUMNS FROM admin LIKE 'Status'");
    $stmt->execute();
    if ($stmt->rowCount() == 0) {
        $pdo->exec("ALTER TABLE admin ADD COLUMN Status ENUM('active', 'deactive') NOT NULL DEFAULT 'active'");
    } else {
        // Update column type if it exists but with wrong type
        $pdo->exec("ALTER TABLE admin MODIFY COLUMN Status ENUM('active', 'deactive') NOT NULL DEFAULT 'active'");
    }
    
    return $pdo;
} catch (PDOException $e) {
    error_log('Database connection failed: ' . $e->getMessage());
    die('Connection failed: ' . $e->getMessage());
}
