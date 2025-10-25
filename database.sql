-- สร้างฐานข้อมูล
CREATE DATABASE IF NOT EXISTS uniconnect CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;
USE uniconnect;

-- ตารางผู้ใช้
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    email VARCHAR(100) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    role ENUM('user', 'moderator', 'admin') DEFAULT 'user',
    profile_pic VARCHAR(255) DEFAULT 'assets/default.png',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- ตารางหมวดหมู่
CREATE TABLE IF NOT EXISTS categories (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL UNIQUE,
    description TEXT DEFAULT NULL
);

-- ตารางกระทู้
CREATE TABLE IF NOT EXISTS posts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    content TEXT NOT NULL,
    user_id INT NOT NULL,
    category_id INT NOT NULL,
    views INT DEFAULT 0,
    pinned BOOLEAN DEFAULT FALSE,
    hidden BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE CASCADE
);

-- ตารางความคิดเห็น
CREATE TABLE IF NOT EXISTS comments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    post_id INT NOT NULL,
    user_id INT NOT NULL,
    content TEXT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (post_id) REFERENCES posts(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- ตารางรายงาน (สำหรับ moderation)
CREATE TABLE IF NOT EXISTS reports (
    id INT AUTO_INCREMENT PRIMARY KEY,
    post_id INT DEFAULT NULL,
    comment_id INT DEFAULT NULL,
    user_id INT NOT NULL,
    reason TEXT NOT NULL,
    status ENUM('pending', 'resolved') DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (post_id) REFERENCES posts(id) ON DELETE CASCADE,
    FOREIGN KEY (comment_id) REFERENCES comments(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- ข้อมูลตัวอย่าง
INSERT INTO categories (name) VALUES 
('General'), ('Academics'), ('Housing'), ('Jobs'), ('Events'), ('Lost & Found'), ('Buy & Sell');

INSERT INTO users (username, email, password, role) VALUES 
('admin', 'admin@example.com', '$2y$10$K.0Xb2bFz1jZ6w3q4r5sO.Ow8p9q0r1s2t3u4v5w6x7y8z9A0B1C', 'admin');  -- password: password