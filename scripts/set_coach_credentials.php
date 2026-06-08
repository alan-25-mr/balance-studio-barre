<?php
require_once __DIR__ . '/../config/database.php';
$pdo = getConnection();
// Add password column if not exists
$pdo->exec("ALTER TABLE coaches ADD COLUMN IF NOT EXISTS password VARCHAR(255) NULL");
// Set passwords (hashed with SHA-256)
$fanyHash = hash('sha256', 'fany123');
$fatiHash = hash('sha256', 'fati123');
$stmt = $pdo->prepare("UPDATE coaches SET password = ? WHERE coach_id = ?");
$stmt->execute([$fanyHash, '101']);
$stmt->execute([$fatiHash, '102']);
echo "Passwords updated for coaches.\n";
?>
