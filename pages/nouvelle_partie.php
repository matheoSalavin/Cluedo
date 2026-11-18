<?php
session_start();

require_once __DIR__ . '/../config/database.php';
$personnageChoisi = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['id_personnage'])) {

    $idPersonnage = (int) $_POST['id_personnage'];

    $requete = $db->prepare("SELECT nom_personnage FROM personnages WHERE id_personnage = :id");
    $requete->bindValue(':id', $idPersonnage, SQLITE3_INTEGER);

    $resultatChoix = $requete->execute();

    $personnageChoisi = $resultatChoix->fetchArray(SQLITE3_ASSOC);
    if ($personnageChoisi !== false) {
    $_SESSION['personnage'] = $personnageChoisi['nom_personnage'];
    $_SESSION['id_personnage'] = $idPersonnage;
}
}

$resultat = $db->query("SELECT * FROM personnages");

?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Nouvelle partie - Cluedo</title>
</head>
<body>

    <h1>Nouvelle partie</h1>

    <p>Bienvenue dans une nouvelle enquête !</p>
    
    <h2>Choisissez votre personnage</h2>

    <form method="POST" action="nouvelle_partie.php">

        <?php
            while ($personnage = $resultat->fetchArray(SQLITE3_ASSOC)) {
                echo "<p>";
                echo "<input type='radio' name='id_personnage' value='" . $personnage['id_personnage'] . "' required>";
                echo htmlspecialchars($personnage['nom_personnage']);
                echo "</p>";
            }
        ?>

        <button type="submit">Valider mon personnage</button>

    </form>

    <?php
    if ($personnageChoisi !== null) {
        echo "<h3>Vous avez choisi : ";
        echo htmlspecialchars($personnageChoisi['nom_personnage']);
        echo "</h3>";
    }
    ?>
    <a href="accueil.php">Retour à l'accueil</a>

</body>
</html>
