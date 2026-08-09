DROP DATABASE IF EXISTS autilearn;

CREATE DATABASE autilearn
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE autilearn;

CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    first_name VARCHAR(100) NULL,
    last_name VARCHAR(100) NULL,
    full_name VARCHAR(100) NULL,
    email VARCHAR(150) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    role ENUM('student', 'parent', 'teacher', 'admin') NOT NULL DEFAULT 'student',
    parent_id INT NULL,
    parent_email VARCHAR(150) NULL,
    stars INT NOT NULL DEFAULT 0,
    stars_earned INT NOT NULL DEFAULT 0,
    streak_days INT NOT NULL DEFAULT 0,
    speech_rate VARCHAR(10) DEFAULT '0.85',
    learning_pace VARCHAR(20) DEFAULT 'intermediate',
    notify_weekly_report TINYINT(1) DEFAULT 1,
    notify_speech_alert TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_users_parent (parent_id),
    INDEX idx_users_role (role)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE activity_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    parent_id INT NULL,
    activity_type VARCHAR(50) NOT NULL,
    title VARCHAR(255) NOT NULL,
    description TEXT NULL,
    duration_minutes INT NOT NULL DEFAULT 0,
    stars_earned INT NOT NULL DEFAULT 0,
    icon_class VARCHAR(100) NULL,
    color_code VARCHAR(20) NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_activity_logs_user_created (user_id, created_at),
    INDEX idx_activity_logs_parent (parent_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE user_progress (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    lesson_title VARCHAR(255) NULL,
    module_title VARCHAR(255) NULL,
    status VARCHAR(30) NOT NULL DEFAULT 'in_progress',
    is_completed TINYINT(1) NOT NULL DEFAULT 0,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL DEFAULT NULL,
    INDEX idx_user_progress_user (user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE speech_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    target_word VARCHAR(255) NOT NULL,
    heard_word VARCHAR(255) NULL,
    accuracy_score DECIMAL(5,2) NOT NULL DEFAULT 0,
    stars_earned INT NOT NULL DEFAULT 0,
    transcript TEXT NULL,
    confidence_score DECIMAL(5,2) NOT NULL DEFAULT 0,
    status VARCHAR(50) DEFAULT 'Completed',
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_speech_logs_user_created (user_id, created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;