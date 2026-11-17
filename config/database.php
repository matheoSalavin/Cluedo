<?php

try {
    $db = new SQLite3(__DIR__ . '/../cluedo.db');

} catch (Exception $e) {
    die("Erreur de connexion à la base de données.");
}

?>
