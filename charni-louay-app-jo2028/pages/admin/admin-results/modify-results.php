<?php
session_start();
require_once("../../../database/database.php");

// Vérifiez si l'utilisateur est connecté
if (!isset($_SESSION['login'])) {
    header('Location: ../../../index.php');
    exit();
}

// Vérifiez si les ID sont fournis dans l'URL
if (!isset($_GET['id_athlete']) || !isset($_GET['id_epreuve'])) {
    $_SESSION['error'] = "ID de l'athlète ou de l'épreuve manquant.";
    header("Location: manage-results.php");
    exit();
}

$id_athlete = filter_input(INPUT_GET, 'id_athlete', FILTER_VALIDATE_INT);
$id_epreuve = filter_input(INPUT_GET, 'id_epreuve', FILTER_VALIDATE_INT);

// Vérifiez si les IDs sont valides
if (!$id_athlete || !$id_epreuve) {
    $_SESSION['error'] = "ID de l'athlète ou de l'épreuve invalide.";
    header("Location: manage-results.php");
    exit();
}

try {
    // Récupération du résultat via la table PARTICIPER
    $queryResult = "SELECT * FROM PARTICIPER p
                    INNER JOIN ATHLETE a ON a.id_athlete = p.id_athlete
                    INNER JOIN EPREUVE e ON e.id_epreuve = p.id_epreuve
                    WHERE p.id_athlete = :idAthlete AND p.id_epreuve = :idEpreuve";
    $statementResult = $connexion->prepare($queryResult);
    $statementResult->bindParam(":idAthlete", $id_athlete, PDO::PARAM_INT);
    $statementResult->bindParam(":idEpreuve", $id_epreuve, PDO::PARAM_INT);
    $statementResult->execute();

    if ($statementResult->rowCount() > 0) {
        // Récupère le résultat existant
        $result = $statementResult->fetch(PDO::FETCH_ASSOC);
    } else {
        $_SESSION['error'] = "Résultat non trouvé.";
        header("Location: manage-results.php");
        exit();
    }

    // Récupération des options d'athlète et d'épreuve
    $statementAthlete = $connexion->prepare("SELECT * FROM ATHLETE");
    $statementAthlete->execute();
    $athleteOptions = $statementAthlete->fetchAll(PDO::FETCH_ASSOC);

    $statementEpreuve = $connexion->prepare("SELECT * FROM EPREUVE");
    $statementEpreuve->execute();
    $epreuveOptions = $statementEpreuve->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    $_SESSION['error'] = "Erreur de base de données : " . $e->getMessage();
    header("Location: manage-results.php");
    exit();
}

// Vérifiez si le formulaire est soumis
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Assurez-vous de bien valider et sécuriser les données
    $nomAthlete = filter_input(INPUT_POST, 'nomAthlete', FILTER_SANITIZE_SPECIAL_CHARS);
    $prenomAthlete = filter_input(INPUT_POST, 'prenomAthlete', FILTER_SANITIZE_SPECIAL_CHARS);
    $nomEpreuve = filter_input(INPUT_POST, 'nomEpreuve', FILTER_SANITIZE_SPECIAL_CHARS);
    $resultat = filter_input(INPUT_POST, 'resultat', FILTER_SANITIZE_SPECIAL_CHARS);

    // Validation des champs
    if (empty($nomAthlete) || empty($prenomAthlete) || empty($nomEpreuve)) {
        $_SESSION['error'] = "Tous les champs doivent être remplis.";
        header("Location: modify-results.php?id_athlete=$id_athlete&id_epreuve=$id_epreuve");
        exit();
    }

    try {
        // Requête pour mettre à jour le résultat dans la table PARTICIPER
        $query = "UPDATE PARTICIPER
                  SET resultat = :resultat
                  WHERE id_athlete = :idAthlete AND id_epreuve = :idEpreuve";
        $statement = $connexion->prepare($query);
        $statement->bindParam(":resultat", $resultat, PDO::PARAM_STR);
        $statement->bindParam(":idAthlete", $id_athlete, PDO::PARAM_INT);
        $statement->bindParam(":idEpreuve", $id_epreuve, PDO::PARAM_INT);

        if ($statement->execute()) {
            $_SESSION['success'] = "Le résultat a été modifié avec succès.";
            header("Location: manage-results.php");
            exit();
        } else {
            $_SESSION['error'] = "Erreur lors de la modification du résultat.";
            header("Location: modify-results.php?id_athlete=$id_athlete&id_epreuve=$id_epreuve");
            exit();
        }
    } catch (PDOException $e) {
        $_SESSION['error'] = "Erreur de base de données : " . $e->getMessage();
        header("Location: modify-results.php?id_athlete=$id_athlete&id_epreuve=$id_epreuve");
        exit();
    }
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="../../../css/normalize.css">
    <link rel="stylesheet" href="../../../css/styles-computer.css">
    <link rel="stylesheet" href="../../../css/styles-responsive.css">
    <link rel="shortcut icon" href="../../../img/favicon.ico" type="image/x-icon">
    <title>Modifier un Résultat - Jeux Olympiques - Los Angeles 2028</title>
    <style>
        form select {
            width: 100%;
            padding: 10px;
            margin-bottom: 15px;
            box-sizing: border-box;
            border: 1px solid #ccc;
            border-radius: 5px;
        }
    </style>
</head>
<body>
    <header>
        <nav>
            <ul class="menu">
                <li><a href="../admin.php">Accueil Administration</a></li>
                <li><a href="../admin-sports/manage-sports.php">Gestion Sports</a></li>
                <li><a href="../admin-places/manage-places.php">Gestion Lieux</a></li>
                <li><a href="../admin-countries/manage-countries.php">Gestion Pays</a></li>
                <li><a href="../admin-events/manage-events.php">Gestion Calendrier</a></li>
                <li><a href="../admin-athletes/manage-athletes.php">Gestion Athlètes</a></li>
                <li><a href="../admin-genres/manage-genres.php">Gestion Genres</a></li>
                <li><a href="../admin-results/manage-results.php">Gestion Résultats</a></li>
                <li><a href="../../logout.php">Déconnexion</a></li>
            </ul>
        </nav>
    </header>

    <main>
        <h1>Modifier un Résultat</h1>

        <?php if (isset($_SESSION['error'])): ?>
            <p style="color: red;"><?= $_SESSION['error']; unset($_SESSION['error']); ?></p>
        <?php endif; ?>
        <?php if (isset($_SESSION['success'])): ?>
            <p style="color: green;"><?= $_SESSION['success']; unset($_SESSION['success']); ?></p>
        <?php endif; ?>

        <form action="modify-results.php?id_athlete=<?= $id_athlete; ?>&id_epreuve=<?= $id_epreuve; ?>" method="post">
            <label>Nom de l'Athlète :</label>
            <select name="nomAthlete" required>
                <?php foreach ($athleteOptions as $athlete): ?>
                    <option value="<?= $athlete['nom_athlete']; ?>" <?= ($athlete['id_athlete'] == $id_athlete) ? 'selected' : ''; ?>>
                        <?= htmlspecialchars($athlete['nom_athlete']); ?>
                    </option>
                <?php endforeach; ?>
            </select>

            <label>Prénom de l'Athlète :</label>
            <select name="prenomAthlete" required>
                <?php foreach ($athleteOptions as $athlete): ?>
                    <option value="<?= $athlete['prenom_athlete']; ?>" <?= ($athlete['id_athlete'] == $id_athlete) ? 'selected' : ''; ?>>
                        <?= htmlspecialchars($athlete['prenom_athlete']); ?>
                    </option>
                <?php endforeach; ?>
            </select>

            <label>Épreuve :</label>
            <select name="nomEpreuve" required>
                <?php foreach ($epreuveOptions as $epreuve): ?>
                    <option value="<?= $epreuve['nom_epreuve']; ?>" <?= ($epreuve['id_epreuve'] == $id_epreuve) ? 'selected' : ''; ?>>
                        <?= htmlspecialchars($epreuve['nom_epreuve']); ?>
                    </option>
                <?php endforeach; ?>
            </select>

            <label>Résultat :</label>
            <input type="text" name="resultat" value="<?= htmlspecialchars($result['resultat']); ?>" required>

            <input type="submit" value="Modifier le Résultat">
        </form>
    </main>

    <footer>
        <figure>
            <img src="../../../img/logo-jo.png" alt="logo Jeux Olympiques - Los Angeles 2028">
        </figure>
    </footer>
</body>
</html>
