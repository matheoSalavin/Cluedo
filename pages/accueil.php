
<?php
$titre = "Cluedo - Enquête au manoir";
require_once __DIR__ . '/../config/database.php';

$resultat = $db->query("SELECT COUNT(*) AS total FROM personnages");
$personnages = $resultat->fetchArray(SQLITE3_ASSOC);

$nombrePersonnages = $personnages['total'];
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $titre; ?></title>
</head>
<body>

    <header>
        <h1>CLUEDO</h1>
        <p>Une enquête vous attend au manoir...</p>
    </header>

    <main>
        <h2>Bienvenue dans le manoir</h2>

        <p>
            Un crime a été commis.
            À vous de découvrir le coupable,
            l'arme utilisée et la pièce du crime.
        </p>

        <a href="nouvelle_partie.php">Nouvelle partie</a>
        <p>Nombre de personnages disponibles : <?php echo $nombrePersonnages; ?></p>
        <a href="regles.php">Consulter les règles du jeu</a>
    </main>

    <footer>
        <p>Projet BTS SIO</p>
    </footer>

</body>
</html>
