-- Hapus admin yang ada
DELETE FROM admins WHERE username = 'admin';

-- Buat admin baru dengan password 'admin123' (hashed)
INSERT INTO admins (username, password, email) VALUES 
('admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin@survey.com');

-- Atau jika ingin password 'password123'
-- INSERT INTO admins (username, password, email) VALUES 
-- ('admin', '$2y$10$YourHashHere', 'admin@survey.com');