<?php
/**
 * Shared activity telemetry for student learning pages.
 * The parent dashboard reads these tables, so every student activity must be
 * recorded here instead of only in the page-specific practice table.
 */
function ensureActivityTrackingSchema(PDO $pdo): void
{
    static $ready = false;
    if ($ready) return;

    $pdo->exec("CREATE TABLE IF NOT EXISTS activity_logs (
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
        INDEX idx_activity_logs_user_created (user_id, created_at)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    $pdo->exec("CREATE TABLE IF NOT EXISTS user_progress (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        lesson_title VARCHAR(255) NULL,
        module_title VARCHAR(255) NULL,
        status VARCHAR(30) NOT NULL DEFAULT 'in_progress',
        is_completed TINYINT(1) NOT NULL DEFAULT 0,
        created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP NULL DEFAULT NULL,
        INDEX idx_user_progress_user (user_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    $pdo->exec("CREATE TABLE IF NOT EXISTS speech_logs (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        target_word VARCHAR(255) NOT NULL,
        heard_word VARCHAR(255) NULL,
        accuracy_score DECIMAL(5,2) NOT NULL DEFAULT 0,
        stars_earned INT NOT NULL DEFAULT 0,
        created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_speech_logs_user_created (user_id, created_at)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    // Older installations may already contain tables created by earlier pages
    // with a different shape. Add the columns the parent dashboard needs.
    $requiredColumns = [
        'activity_logs' => [
            'parent_id' => 'INT NULL', 'activity_type' => "VARCHAR(50) NOT NULL DEFAULT 'game'",
            'title' => "VARCHAR(255) NOT NULL DEFAULT 'Activity'", 'description' => 'TEXT NULL',
            'duration_minutes' => 'INT NOT NULL DEFAULT 0', 'stars_earned' => 'INT NOT NULL DEFAULT 0',
            'icon_class' => 'VARCHAR(100) NULL', 'color_code' => 'VARCHAR(20) NULL',
            'created_at' => 'TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP',
        ],
        'user_progress' => [
            'lesson_title' => 'VARCHAR(255) NULL', 'module_title' => 'VARCHAR(255) NULL',
            'status' => "VARCHAR(30) NOT NULL DEFAULT 'in_progress'", 'is_completed' => 'TINYINT(1) NOT NULL DEFAULT 0',
            'created_at' => 'TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP', 'updated_at' => 'TIMESTAMP NULL DEFAULT NULL',
        ],
        'speech_logs' => [
            'target_word' => "VARCHAR(255) NOT NULL DEFAULT ''", 'heard_word' => 'VARCHAR(255) NULL',
            'accuracy_score' => 'DECIMAL(5,2) NOT NULL DEFAULT 0', 'stars_earned' => 'INT NOT NULL DEFAULT 0',
            'created_at' => 'TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP',
        ],
    ];
    foreach ($requiredColumns as $table => $columns) {
        $existing = $pdo->query("SHOW COLUMNS FROM `{$table}`")->fetchAll(PDO::FETCH_COLUMN, 0);
        foreach ($columns as $column => $definition) {
            if (!in_array($column, $existing, true)) {
                $pdo->exec("ALTER TABLE `{$table}` ADD COLUMN `{$column}` {$definition}");
            }
        }
    }
    $ready = true;
}

function recordStudentActivity(PDO $pdo, int $studentId, string $type, string $title, string $description, int $duration = 1, int $stars = 0, string $icon = 'fa-gamepad', string $color = '#6366f1'): void
{
    ensureActivityTrackingSchema($pdo);
    $parentStmt = $pdo->prepare('SELECT parent_id FROM users WHERE id = ? LIMIT 1');
    $parentStmt->execute([$studentId]);
    $parentId = $parentStmt->fetchColumn() ?: null;

    $stmt = $pdo->prepare('INSERT INTO activity_logs (user_id, parent_id, activity_type, title, description, duration_minutes, stars_earned, icon_class, color_code, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())');
    $stmt->execute([$studentId, $parentId, $type, $title, $description, max(0, $duration), max(0, $stars), $icon, $color]);
}

function recordSpeechActivity(PDO $pdo, int $studentId, string $targetWord, string $heardWord, bool $isCorrect, int $stars): void
{
    ensureActivityTrackingSchema($pdo);
    $accuracy = $isCorrect ? 100 : 0;
    $speech = $pdo->prepare('INSERT INTO speech_logs (user_id, target_word, heard_word, accuracy_score, stars_earned, created_at) VALUES (?, ?, ?, ?, ?, NOW())');
    $speech->execute([$studentId, $targetWord, $heardWord, $accuracy, max(0, $stars)]);
    recordStudentActivity($pdo, $studentId, 'speech', 'Speech practice: ' . $targetWord, 'Practised saying “' . $targetWord . '”' . ($isCorrect ? ' successfully.' : '.'), 1, $stars, 'fa-microphone', '#10b981');
}
