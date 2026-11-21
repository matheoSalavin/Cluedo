
<?php
session_start();

if (!isset($_SESSION['id_personnage'])) {
    header('Location: nouvelle_partie.php');
    exit;
}

require_once __DIR__ . '/../config/database.php';

$messageHypothese = '';

// Initialisation des hypothèses
if (!isset($_SESSION['hypotheses'])) {
    $_SESSION['hypotheses'] = [];
}

if (!isset($_SESSION['nombre_hypotheses'])) {
    $_SESSION['nombre_hypotheses'] = 0;
}

// Gestion du déplacement
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

// Gestion des hypothèses
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['faire_hypothese'])) {

    $position = $_SESSION['position'];

    if (isset($_SESSION['hypotheses'][$position])) {
        $messageHypothese = "Vous avez déjà fait une hypothèse dans cette pièce.";
    } else {

        $idPersonnage = (int) $_POST['personnage_suspect'];
        $idArme = (int) $_POST['arme_suspect'];

        $erreurs = [];

        if ($idPersonnage != $_SESSION['coupable']['id_personnage']) {
            $erreurs[] = "Le personnage est incorrect.";
        }

        if ($idArme != $_SESSION['arme']['id_arme']) {
            $erreurs[] = "L'arme est incorrecte.";
        }

        if ($position != $_SESSION['piece_secrete']['nom_piece']) {
            $erreurs[] = "La pièce est incorrecte.";
        }

        $_SESSION['hypotheses'][$position] = true;
        $_SESSION['nombre_hypotheses']++;

        if (empty($erreurs)) {
            $_SESSION['partie_terminee'] = true;
            $messageHypothese = "Bravo ! Vous avez trouvé la solution du Cluedo !";
        } else {
            $indice = $erreurs[array_rand($erreurs)];
            $messageHypothese = "Hypothèse incorrecte. " . $indice;
        }
    }
}

// Récupération des pièces accessibles
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

// Récupération des personnages et des armes
$listePersonnages = $db->query("SELECT * FROM personnages");
$listeArmes = $db->query("SELECT * FROM armes");

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

    <h2>Faire une hypothèse</h2>

    <form method="POST">

        <label>Personnage suspect :</label>

        <select name="personnage_suspect" required>
            <?php
            while ($personnage = $listePersonnages->fetchArray(SQLITE3_ASSOC)) {
                echo "<option value='" . $personnage['id_personnage'] . "'>";
                echo htmlspecialchars($personnage['nom_personnage']);
                echo "</option>";
            }
            ?>
        </select>

        <br><br>

        <label>Arme :</label>

        <select name="arme_suspect" required>
            <?php
            while ($arme = $listeArmes->fetchArray(SQLITE3_ASSOC)) {
                echo "<option value='" . $arme['id_arme'] . "'>";
                echo htmlspecialchars($arme['nom_arme']);
                echo "</option>";
            }
            ?>
        </select>

        <p>Pièce : <?php echo htmlspecialchars($_SESSION['position']); ?></p>

        <button type="submit" name="faire_hypothese">Valider mon hypothèse</button>

    </form>

    <?php
    if ($messageHypothese != '') {
        echo "<p>" . htmlspecialchars($messageHypothese) . "</p>";
    }
    ?>

    <p>Nombre d'hypothèses : <?php echo $_SESSION['nombre_hypotheses']; ?></p>

</body>
</html>
