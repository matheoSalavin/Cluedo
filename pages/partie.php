
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

if (!isset($_SESSION['historique'])) {
    $_SESSION['historique'] = [];
}

// Initialisation des pièces visitées
if (!isset($_SESSION['pieces_visitees'])) {
    $_SESSION['pieces_visitees'] = ['Hall'];
}

// Gestion du déplacement
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['nouvelle_position']) && !isset($_SESSION['partie_terminee'])) {
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

        // Ajouter la pièce aux pièces visitées
        if (!in_array($piece['nom_piece'], $_SESSION['pieces_visitees'])) {
            $_SESSION['pieces_visitees'][] = $piece['nom_piece'];
        }

    break;
        }
    }
}

// Gestion des hypothèses
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['faire_hypothese']) && !isset($_SESSION['partie_terminee'])) {
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

        // Enregistrer l'hypothèse dans l'historique
        $_SESSION['historique'][] = [
            'piece' => $position,
            'personnage' => $idPersonnage,
            'arme' => $idArme,
            'resultat' => empty($erreurs) ? 'Correcte' : 'Incorrecte'
        ];

        // Bloquer la pièce uniquement si l'hypothèse est correcte
    if (empty($erreurs)) {
        $_SESSION['hypotheses'][$position] = true;
    }

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

    <?php if (isset($_SESSION['partie_terminee'])) { ?>

        <h1>Enquête terminée !</h1>

    <?php } else { ?>

        <h1>Votre enquête commence !</h1>

    <?php } ?>  

    <p>Personnage : <?php echo htmlspecialchars($_SESSION['personnage']); ?></p>

    <p>Vous vous trouvez dans : <?php echo htmlspecialchars($_SESSION['position']); ?></p>

    <?php if (!isset($_SESSION['partie_terminee'])) { ?>
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
    <h2>Historique des hypothèses</h2>

    <?php if (!empty($_SESSION['historique'])) { ?>

        <table border="1">
            <tr>
                <th>N°</th>
                <th>Pièce</th>
                <th>Personnage</th>
                <th>Arme</th>
                <th>Résultat</th>
            </tr>

            <?php foreach ($_SESSION['historique'] as $numero => $hypothese) { ?>

                <tr>
                    <td><?php echo $numero + 1; ?></td>
                    <td><?php echo htmlspecialchars($hypothese['piece']); ?></td>
                    <td><?php
                        $requetePersonnage = $db->prepare("SELECT nom_personnage FROM personnages WHERE id_personnage = :id");
                        $requetePersonnage->bindValue(':id', $hypothese['personnage'], SQLITE3_INTEGER);
                        $resultatPersonnage = $requetePersonnage->execute();
                        $personnage = $resultatPersonnage->fetchArray(SQLITE3_ASSOC);
                        echo htmlspecialchars($personnage['nom_personnage']);
                    ?></td>
                    <td><?php
                        $requeteArme = $db->prepare("SELECT nom_arme FROM armes WHERE id_arme = :id");
                        $requeteArme->bindValue(':id', $hypothese['arme'], SQLITE3_INTEGER);
                        $resultatArme = $requeteArme->execute();
                        $arme = $resultatArme->fetchArray(SQLITE3_ASSOC);
                        echo htmlspecialchars($arme['nom_arme']);
                    ?></td>
                    <td><?php echo htmlspecialchars($hypothese['resultat']); ?></td>
                </tr>

            <?php } ?>

        </table>

    <?php } else { ?>

        <p>Aucune hypothèse pour le moment.</p>

    <?php } ?>

    <?php
    if ($messageHypothese != '') {
        echo "<p>" . htmlspecialchars($messageHypothese) . "</p>";
    }
    ?>

    <<p>Nombre d'hypothèses : <?php echo $_SESSION['nombre_hypotheses']; ?></p>

<?php } else { ?>

    <h3>Bravo, vous avez trouvé le coupable !</h3>

    <h3>La solution secrète</h3>

    <p>Coupable :
        <?php echo htmlspecialchars($_SESSION['coupable']['nom_personnage']); ?>
    </p>

    <p>Arme :
        <?php echo htmlspecialchars($_SESSION['arme']['nom_arme']); ?>
    </p>

    <p>Pièce :
        <?php echo htmlspecialchars($_SESSION['piece_secrete']['nom_piece']); ?>
    </p>

    <h3>Récapitulatif de votre enquête</h3>

    <p>Personnage choisi :
        <?php echo htmlspecialchars($_SESSION['personnage']); ?>
    </p>

    <p>Nombre d'hypothèses :
        <?php echo $_SESSION['nombre_hypotheses']; ?>
    </p>

    <h3>Pièces visitées</h3>

    <ul>
        <?php foreach ($_SESSION['pieces_visitees'] as $piece) { ?>
            <li><?php echo htmlspecialchars($piece); ?></li>
        <?php } ?>
    </ul>

    <h3>Historique de vos hypothèses</h3>

    <table border="1">
        <tr>
            <th>N°</th>
            <th>Pièce</th>
            <th>Personnage</th>
            <th>Arme</th>
            <th>Résultat</th>
        </tr>

        <?php foreach ($_SESSION['historique'] as $numero => $hypothese) { ?>
            <tr>
                <td><?php echo $numero + 1; ?></td>
                <td><?php echo htmlspecialchars($hypothese['piece']); ?></td>
                <td>
                    <?php
                    $requetePersonnage = $db->prepare(
                        "SELECT nom_personnage FROM personnages WHERE id_personnage = :id"
                    );
                    $requetePersonnage->bindValue(':id', $hypothese['personnage'], SQLITE3_INTEGER);
                    $resultatPersonnage = $requetePersonnage->execute();
                    $personnage = $resultatPersonnage->fetchArray(SQLITE3_ASSOC);

                    echo htmlspecialchars($personnage['nom_personnage']);
                    ?>
                </td>
                <td>
                    <?php
                    $requeteArme = $db->prepare(
                        "SELECT nom_arme FROM armes WHERE id_arme = :id"
                    );
                    $requeteArme->bindValue(':id', $hypothese['arme'], SQLITE3_INTEGER);
                    $resultatArme = $requeteArme->execute();
                    $arme = $resultatArme->fetchArray(SQLITE3_ASSOC);

                    echo htmlspecialchars($arme['nom_arme']);
                    ?>
                </td>
                <td><?php echo htmlspecialchars($hypothese['resultat']); ?></td>
            </tr>
        <?php } ?>
    </table>

    <br>

    <form action="nouvelle_partie.php" method="POST">
        <button type="submit">Recommencer une partie</button>
    </form>

<?php } ?>

</body>
</html>