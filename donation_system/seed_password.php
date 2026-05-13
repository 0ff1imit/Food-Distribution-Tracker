<?php
// Run ONCE after importing database.sql to set passwords
// Visit: http://localhost/donation_system/seed_passwords.php
// Then DELETE this file!
require_once 'includes/db.php';
$hash = password_hash('Password123!', PASSWORD_BCRYPT, ['cost'=>10]);
$stmt = $pdo->prepare("INSERT INTO users (full_name, email, password, phone, role) VALUES (?,?,?,?,?)
    ON DUPLICATE KEY UPDATE password=VALUES(password), full_name=VALUES(full_name)");
$users = [
    ['System Admin',   'admin@donation.org',       $hash, '+1-555-0100', 'admin'],
    ['Maria Santos',   'procurement@donation.org', $hash, '+1-555-0101', 'procurement'],
    ['Juan Dela Cruz', 'volunteer@donation.org',   $hash, '+1-555-0102', 'volunteer'],
];
foreach ($users as $u) { $stmt->execute($u); }
echo "<h2>✅ Users seeded! Default password: <code>Password123!</code><br>Delete this file now.</h2>";  