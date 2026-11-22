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

        // Réinitialisation de la partie
        $_SESSION['hypotheses'] = [];
        $_SESSION['nombre_hypotheses'] = 0;
        unset($_SESSION['partie_terminee']);

        // Nouveau tirage de la solution secrète
        $requeteCoupable = $db->query("SELECT * FROM personnages ORDER BY RANDOM() LIMIT 1");
        $_SESSION['coupable'] = $requeteCoupable->fetchArray(SQLITE3_ASSOC);

        $requeteArme = $db->query("SELECT * FROM armes ORDER BY RANDOM() LIMIT 1");
        $_SESSION['arme'] = $requeteArme->fetchArray(SQLITE3_ASSOC);

        $requetePiece = $db->query("SELECT * FROM pieces ORDER BY RANDOM() LIMIT 1");
        $_SESSION['piece_secrete'] = $requetePiece->fetchArray(SQLITE3_ASSOC);

        $_SESSION['position'] = 'Hall';

        header('Location: partie.php');
        exit;
    }
}

$resultat = $db->query("SELECT * FROM personnages");

if (!isset($_SESSION['coupable'])) {
    $requeteCoupable = $db->query("SELECT * FROM personnages ORDER BY RANDOM() LIMIT 1");
    $coupable = $requeteCoupable->fetchArray(SQLITE3_ASSOC);

    $_SESSION['coupable'] = $coupable;
}

if (!isset($_SESSION['position'])) {
    $_SESSION['position'] = 'Hall';
}

if (!isset($_SESSION['arme']) || !isset($_SESSION['piece_secrete'])) {
    $requeteArme = $db->query("SELECT * FROM armes ORDER BY RANDOM() LIMIT 1");
    $arme = $requeteArme->fetchArray(SQLITE3_ASSOC);

    $requetePiece = $db->query("SELECT * FROM pieces ORDER BY RANDOM() LIMIT 1");
    $piece = $requetePiece->fetchArray(SQLITE3_ASSOC);

    $_SESSION['arme'] = $arme;
    $_SESSION['piece_secrete'] = $piece;
}

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
                echo "<input type='radio' name='id_personnage' value='" . $personnage['id_personnage'] . "' required";

                if (isset($_SESSION['id_personnage']) && $_SESSION['id_personnage'] == $personnage['id_personnage']) {
                    echo " checked";
                }

                echo ">";
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
