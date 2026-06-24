<?php
$host = 'localhost';
$username = 'root';
$password = '';

try {
    // First connect without database
    $pdo = new PDO("mysql:host=$host", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Ensure the database exists. NEVER drop it — that would destroy the whole app.
    $pdo->exec("CREATE DATABASE IF NOT EXISTS healthcare_db CHARACTER SET utf8mb4");
    echo "Database ready<br>";

    // Connect to the database
    $pdo = new PDO("mysql:host=$host;dbname=healthcare_db", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Create blood_donors table
    $pdo->exec("CREATE TABLE IF NOT EXISTS blood_donors (
        id INT PRIMARY KEY AUTO_INCREMENT,
        first_name VARCHAR(50) NOT NULL,
        last_name VARCHAR(50) NOT NULL,
        blood_group VARCHAR(5) NOT NULL,
        age INT NOT NULL,
        gender ENUM('male', 'female', 'other') NOT NULL,
        phone VARCHAR(20) NOT NULL,
        email VARCHAR(100),
        address TEXT NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )");
    echo "blood_donors table created successfully<br>";

    // Create blood_stock table
    $pdo->exec("CREATE TABLE IF NOT EXISTS blood_stock (
        id INT PRIMARY KEY AUTO_INCREMENT,
        blood_group VARCHAR(5) NOT NULL,
        quantity INT NOT NULL DEFAULT 0,
        last_updated TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    )");
    echo "blood_stock table created successfully<br>";

    // Create blood_requests table without patient_id
    $pdo->exec("CREATE TABLE IF NOT EXISTS blood_requests (
        id INT PRIMARY KEY AUTO_INCREMENT,
        requester_name VARCHAR(100) NOT NULL,
        blood_group VARCHAR(5) NOT NULL,
        units_needed INT NOT NULL,
        urgency ENUM('normal', 'urgent', 'critical') NOT NULL DEFAULT 'normal',
        hospital_name VARCHAR(200) NOT NULL,
        contact_phone VARCHAR(20) NOT NULL,
        contact_email VARCHAR(100),
        request_date DATE NOT NULL,
        status ENUM('pending', 'approved', 'completed', 'cancelled') DEFAULT 'pending',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    )");
    echo "blood_requests table created successfully<br>";

    // Initialize blood_stock with all blood groups (only if empty, so re-runs are safe)
    if ((int)$pdo->query("SELECT COUNT(*) FROM blood_stock")->fetchColumn() === 0) {
        $blood_groups = ['A+', 'A-', 'B+', 'B-', 'AB+', 'AB-', 'O+', 'O-'];
        $stmt = $pdo->prepare("INSERT INTO blood_stock (blood_group, quantity) VALUES (?, 0)");
        foreach ($blood_groups as $group) {
            $stmt->execute([$group]);
        }
        echo "Blood stock initialized with all blood groups<br>";
    }

    echo "<div style='background-color: #d4edda; color: #155724; padding: 15px; margin: 10px; border-radius: 5px;'>";
    echo "Setup completed successfully! The system is ready to use.";
    echo "</div>";
    
} catch(PDOException $e) {
    echo "<div style='background-color: #f8d7da; color: #721c24; padding: 15px; margin: 10px; border-radius: 5px;'>";
    echo "Error: " . $e->getMessage();
    echo "</div>";
}
?> 