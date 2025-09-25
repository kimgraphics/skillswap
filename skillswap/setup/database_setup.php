<?php
require_once '../includes/database.php';

$database = new Database();
$conn = $database->getConnection();

try {
    // Users Table
    $sql = "CREATE TABLE IF NOT EXISTS users (
        id INT PRIMARY KEY AUTO_INCREMENT,
        username VARCHAR(50) UNIQUE NOT NULL,
        email VARCHAR(100) UNIQUE NOT NULL,
        password_hash VARCHAR(255) NOT NULL,
        first_name VARCHAR(50) NOT NULL,
        last_name VARCHAR(50) NOT NULL,
        profile_image VARCHAR(255) DEFAULT 'default.png',
        bio TEXT,
        location VARCHAR(100),
        availability ENUM('weekdays', 'weekends', 'both', 'flexible') DEFAULT 'flexible',
        role ENUM('user', 'admin') DEFAULT 'user',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        is_active BOOLEAN DEFAULT TRUE
    )";
    $conn->exec($sql);
    echo "Users table created successfully.<br>";

    // Skills Table
    $sql = "CREATE TABLE IF NOT EXISTS skills (
        id INT PRIMARY KEY AUTO_INCREMENT,
        name VARCHAR(100) NOT NULL,
        category VARCHAR(50),
        description TEXT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )";
    $conn->exec($sql);
    echo "Skills table created successfully.<br>";

    // User Skills Table
    $sql = "CREATE TABLE IF NOT EXISTS user_skills (
        id INT PRIMARY KEY AUTO_INCREMENT,
        user_id INT NOT NULL,
        skill_id INT NOT NULL,
        type ENUM('teach', 'learn') NOT NULL,
        proficiency ENUM('beginner', 'intermediate', 'advanced', 'expert') DEFAULT 'intermediate',
        description TEXT,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
        FOREIGN KEY (skill_id) REFERENCES skills(id) ON DELETE CASCADE,
        UNIQUE KEY unique_user_skill (user_id, skill_id, type)
    )";
    $conn->exec($sql);
    echo "User skills table created successfully.<br>";

    // Matches Table
    $sql = "CREATE TABLE IF NOT EXISTS matches (
        id INT PRIMARY KEY AUTO_INCREMENT,
        user1_id INT NOT NULL,
        user2_id INT NOT NULL,
        skill_teach_id INT NOT NULL,
        skill_learn_id INT NOT NULL,
        status ENUM('pending', 'accepted', 'rejected', 'completed') DEFAULT 'pending',
        matched_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        completed_at TIMESTAMP NULL,
        FOREIGN KEY (user1_id) REFERENCES users(id) ON DELETE CASCADE,
        FOREIGN KEY (user2_id) REFERENCES users(id) ON DELETE CASCADE,
        FOREIGN KEY (skill_teach_id) REFERENCES skills(id) ON DELETE CASCADE,
        FOREIGN KEY (skill_learn_id) REFERENCES skills(id) ON DELETE CASCADE
    )";
    $conn->exec($sql);
    echo "Matches table created successfully.<br>";

    // Messages Table
    $sql = "CREATE TABLE IF NOT EXISTS messages (
        id INT PRIMARY KEY AUTO_INCREMENT,
        match_id INT NOT NULL,
        sender_id INT NOT NULL,
        message_type ENUM('text', 'image', 'video') DEFAULT 'text',
        content TEXT NOT NULL,
        media_url VARCHAR(255) NULL,
        is_read BOOLEAN DEFAULT FALSE,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (match_id) REFERENCES matches(id) ON DELETE CASCADE,
        FOREIGN KEY (sender_id) REFERENCES users(id) ON DELETE CASCADE
    )";
    $conn->exec($sql);
    echo "Messages table created successfully.<br>";

    // Reviews Table
    $sql = "CREATE TABLE IF NOT EXISTS reviews (
        id INT PRIMARY KEY AUTO_INCREMENT,
        match_id INT NOT NULL,
        reviewer_id INT NOT NULL,
        reviewed_id INT NOT NULL,
        rating TINYINT CHECK (rating BETWEEN 1 AND 5),
        comment TEXT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (match_id) REFERENCES matches(id) ON DELETE CASCADE,
        FOREIGN KEY (reviewer_id) REFERENCES users(id) ON DELETE CASCADE,
        FOREIGN KEY (reviewed_id) REFERENCES users(id) ON DELETE CASCADE,
        UNIQUE KEY unique_match_review (match_id, reviewer_id)
    )";
    $conn->exec($sql);
    echo "Reviews table created successfully.<br>";

    // Insert sample skills
    $sampleSkills = [
        ['Web Development', 'Technology'],
        ['Graphic Design', 'Creative'],
        ['Photography', 'Creative'],
        ['Cooking', 'Lifestyle'],
        ['Language Tutoring', 'Education'],
        ['Data Science', 'Technology'],
        ['Digital Marketing', 'Business'],
        ['UI/UX Design', 'Creative'],
        ['Music Production', 'Creative'],
        ['Public Speaking', 'Personal Development']
    ];

    $stmt = $conn->prepare("INSERT INTO skills (name, category) VALUES (?, ?)");
    foreach ($sampleSkills as $skill) {
        $stmt->execute([$skill[0], $skill[1]]);
    }
    echo "Sample skills inserted successfully.<br>";

    // Create admin user (password: admin123)
    $hashedPassword = password_hash('admin123', PASSWORD_DEFAULT);
    $stmt = $conn->prepare("INSERT INTO users (username, email, password_hash, first_name, last_name, role) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->execute(['admin', 'admin@skillswap.com', $hashedPassword, 'System', 'Administrator', 'admin']);
    echo "Admin user created successfully.<br>";

    echo "Database setup completed successfully!";
    
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}
?>