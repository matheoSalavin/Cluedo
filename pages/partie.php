<?php
session_start();

if (!isset($_SESSION['id_personnage'])) {
    header('Location: nouvelle_partie.php');
    exit;
}
require_once __DIR__ . '/../config/database.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['nouvelle_position'])) {

    $requeteDeplacement = $db->prepare("
        SELECT p2.nom_piece
        FROM portes
        JOIN pieces p1 ON portes.id_piece1 = p1.id_piece
        JOIN pieces p2 ON portes.id_piece2 = p2.id_piece
        WHERE p1.nom_piece = :position

        UNION

        SELECT p1.nom_piece
        FROM portes
        JOIN pieces p1 ON portes.id_piece1 = p1.id_piece
        JOIN pieces p2 ON portes.id_piece2 = p2.id_piece
        WHERE p2.nom_piece = :position
    ");

    $requeteDeplacement->bindValue(':position', $_SESSION['position'], SQLITE3_TEXT);
    $resultatDeplacement = $requeteDeplacement->execute();

    while ($piece = $resultatDeplacement->fetchArray(SQLITE3_ASSOC)) {
        if ($piece['nom_piece'] === $_POST['nouvelle_position']) {
            $_SESSION['position'] = $piece['nom_piece'];
            break;
        }
    }
}

$requete = $db->prepare("
    SELECT p2.nom_piece
    FROM portes
    JOIN pieces p1 ON portes.id_piece1 = p1.id_piece
    JOIN pieces p2 ON portes.id_piece2 = p2.id_piece
    WHERE p1.nom_piece = :position

    UNION

    SELECT p1.nom_piece
    FROM portes
    JOIN pieces p1 ON portes.id_piece1 = p1.id_piece
    JOIN pieces p2 ON portes.id_piece2 = p2.id_piece
    WHERE p2.nom_piece = :position
");

$requete->bindValue(':position', $_SESSION['position'], SQLITE3_TEXT);
$resultat = $requete->execute();
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Partie en cours - Cluedo</title>
</head>
<body>

    <h1>Votre enquête commence !</h1>

    <p>Personnage : <?php echo htmlspecialchars($_SESSION['personnage']); ?></p>

    <p>Vous vous trouvez dans : <?php echo htmlspecialchars($_SESSION['position']); ?></p>
    <h2>Pièces accessibles</h2>

    <?php
    while ($piece = $resultat->fetchArray(SQLITE3_ASSOC)) {
        echo "<form method='POST'>";

        echo "<input type='hidden' name='nouvelle_position' value='" . htmlspecialchars($piece['nom_piece'], ENT_QUOTES, 'UTF-8') . "'>";

        echo "<button type='submit'>" . htmlspecialchars($piece['nom_piece']) . "</button>";

        echo "</form>";
    }
    ?>

</body>
</html>