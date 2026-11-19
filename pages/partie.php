<?php
session_start();

if (!isset($_SESSION['id_personnage'])) {
    header('Location: nouvelle_partie.php');
    exit;
}
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

</body>
</html>