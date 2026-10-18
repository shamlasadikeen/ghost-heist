
CREATE TABLE IF NOT EXISTS room_progress (
    user_id INT NOT NULL,
    difficulty ENUM('beginner', 'intermediate', 'hard') NOT NULL,
    room_number TINYINT UNSIGNED NOT NULL,
    completed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    PRIMARY KEY (user_id, difficulty, room_number)
);
