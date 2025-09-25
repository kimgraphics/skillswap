-- Insert sample users (passwords are all "password123")
INSERT INTO users (username, email, password_hash, first_name, last_name, bio, location, availability) VALUES
('johndoe', 'john@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'John', 'Doe', 'Web developer with 5 years of experience', 'New York', 'weekends'),
('sarahj', 'sarah@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Sarah', 'Johnson', 'Graphic designer and photography enthusiast', 'Chicago', 'both'),
('mikec', 'mike@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Mike', 'Chen', 'Professional photographer and music producer', 'Los Angeles', 'flexible'),
('emilyw', 'emily@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Emily', 'Wilson', 'Language teacher and cooking enthusiast', 'Boston', 'weekdays');

-- Get skill IDs
SET @webdev = (SELECT id FROM skills WHERE name = 'Web Development');
SET @design = (SELECT id FROM skills WHERE name = 'Graphic Design');
SET @photo = (SELECT id FROM skills WHERE name = 'Photography');
SET @cooking = (SELECT id FROM skills WHERE name = 'Cooking');
SET @language = (SELECT id FROM skills WHERE name = 'Language Tutoring');
SET @music = (SELECT id FROM skills WHERE name = 'Music Production');

-- Insert user skills
INSERT INTO user_skills (user_id, skill_id, type, proficiency) VALUES
(2, @webdev, 'teach', 'advanced'),
(2, @design, 'learn', 'beginner'),
(3, @design, 'teach', 'expert'),
(3, @photo, 'teach', 'advanced'),
(3, @music, 'learn', 'intermediate'),
(4, @photo, 'teach', 'intermediate'),
(4, @cooking, 'learn', 'beginner'),
(5, @cooking, 'teach', 'advanced'),
(5, @language, 'teach', 'expert'),
(5, @webdev, 'learn', 'beginner');

-- Create some matches
INSERT INTO matches (user1_id, user2_id, skill_teach_id, skill_learn_id, status) VALUES
(2, 3, @webdev, @design, 'accepted'),
(3, 4, @design, @photo, 'pending'),
(4, 5, @photo, @cooking, 'completed');

-- Insert sample messages
INSERT INTO messages (match_id, sender_id, message_type, content, is_read) VALUES
(1, 2, 'text', 'Hi Sarah! I saw you want to learn graphic design. I can help with that!', TRUE),
(1, 3, 'text', 'That would be amazing! I can teach you web development in return.', TRUE),
(1, 2, 'text', 'Perfect! When are you available to start?', FALSE);

-- Insert sample reviews
INSERT INTO reviews (match_id, reviewer_id, reviewed_id, rating, comment) VALUES
(3, 4, 5, 5, 'Emily is an amazing cooking teacher! Learned so much.'),
(3, 5, 4, 4, 'Great photography session with Mike. Would recommend!');