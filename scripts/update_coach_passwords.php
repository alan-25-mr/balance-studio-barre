<?php
// update_coach_passwords.php
// Adds a password column to the coaches table and sets default passwords (coach_id as password).
require_once __DIR__ . '/../config/database.php';
$pdo = getConnection();
// Add password column if not exists
$pdo->exec("ALTER TABLE coaches ADD COLUMN IF NOT EXISTS password VARCHAR(255) NULL");
// Update each coach with hashed password (using coach_id as plain password)
$stmt = $pdo->query("SELECT id, coach_id FROM coaches");
$coaches = $stmt->fetchAll(PDO::FETCH_ASSOC);
foreach ($coaches as $c) {
    $plain = $c['coach_id'];
    $hash = hash('sha256', $plain);
    $pdo->prepare("UPDATE coaches SET password = ? WHERE id = ?")->execute([$hash, $c['id']]);
    echo "Updated coach {$c['coach_id']} with password '{$plain}'\n";
}
?>
