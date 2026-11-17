
<?php
require_once __DIR__ . '/config/database.php';

$resultat = $db->query("SELECT name FROM sqlite_master WHERE type = 'table'");

echo "<h1>Tables de la base de données</h1>";

while ($table = $resultat->fetchArray(SQLITE3_ASSOC)) {
    echo "<p>" . htmlspecialchars($table['name']) . "</p>";
}
?>
