<?php
session_start();
require_once("../../../database/database.php");

// Vérifiez si l'utilisateur est connecté
if (!isset($_SESSION['login'])) {
    header('Location: ../../../index.php');
    exit();
}

// Vérifiez si l'ID de l'épreuve est fourni dans l'URL
if (!isset($_GET['id_epreuve'])) {
    $_SESSION['error'] = "ID de l'épreuve manquant.";
    header("Location: manage-events.php");
    exit();
}

$id_epreuve = filter_input(INPUT_GET, 'id_epreuve', FILTER_VALIDATE_INT);

// Vérifiez si l'ID de l'épreuve est un entier valide
if (!$id_epreuve && $id_epreuve !== 0) {
    $_SESSION['error'] = "ID de l'épreuve invalide.";
    header("Location: manage-events.php");
    exit();
}

// Vider les messages de succès précédents
if (isset($_SESSION['success'])) {
    unset($_SESSION['success']);
}

// Récupérez les informations de l'épreuve pour affichage dans le formulaire
try {
    $queryEvent = "SELECT e.id_epreuve, e.nom_epreuve, e.date_epreuve, e.heure_epreuve, l.id_lieu, l.nom_lieu, s.id_sport, s.nom_sport
                   FROM EPREUVE e 
                   JOIN LIEU l ON e.id_lieu = l.id_lieu 
                   JOIN SPORT s ON e.id_sport = s.id_sport 
                   WHERE e.id_epreuve = :idEpreuve;";
    $statementEvent = $connexion->prepare($queryEvent);
    $statementEvent->bindParam(":idEpreuve", $id_epreuve, PDO::PARAM_INT);
    $statementEvent->execute();

    if ($statementEvent->rowCount() > 0) {
        $event = $statementEvent->fetch(PDO::FETCH_ASSOC);
    } else {
        $_SESSION['error'] = "Épreuve non trouvée.";
        header("Location: manage-events.php");
        exit();
    }
} catch (PDOException $e) {
    $_SESSION['error'] = "Erreur de base de données : " . htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8');
    header("Location: manage-events.php");
    exit();
}

// Récupérer la liste des lieux pour le menu déroulant
try {
    $queryPlaces = "SELECT id_lieu, nom_lieu FROM LIEU ORDER BY nom_lieu";
    $statementPlaces = $connexion->prepare($queryPlaces);
    $statementPlaces->execute();
    $places = $statementPlaces->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $_SESSION['error'] = "Erreur de base de données lors de la récupération des lieux : " . htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8');
    header("Location: manage-events.php");
    exit();
}

// Récupérer la liste des sports pour le menu déroulant
try {
    $querySports = "SELECT id_sport, nom_sport FROM SPORT ORDER BY nom_sport";
    $statementSports = $connexion->prepare($querySports);
    $statementSports->execute();
    $sports = $statementSports->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $_SESSION['error'] = "Erreur de base de données lors de la récupération des sports : " . htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8');
    header("Location: manage-events.php");
    exit();
}

// Vérifiez si le formulaire est soumis
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Assurez-vous d'obtenir des données sécurisées et filtrées
    $nomEpreuve = filter_input(INPUT_POST, 'nomEpreuve', FILTER_SANITIZE_SPECIAL_CHARS);
    $dateEpreuve = filter_input(INPUT_POST, 'dateEpreuve', FILTER_SANITIZE_STRING);
    $heureEpreuve = filter_input(INPUT_POST, 'heureEpreuve', FILTER_SANITIZE_STRING);
    $idLieu = filter_input(INPUT_POST, 'idLieu', FILTER_VALIDATE_INT);
    $idSport = filter_input(INPUT_POST, 'idSport', FILTER_VALIDATE_INT);

    // Vérifiez si tous les champs sont remplis
    if (empty($nomEpreuve) || empty($dateEpreuve) || empty($heureEpreuve) || !$idLieu || !$idSport) {
        $_SESSION['error'] = "Tous les champs doivent être remplis.";
        header("Location: modify-events.php?id_epreuve=$id_epreuve");
        exit();
    }

    try {
        // Requête pour mettre à jour les informations de l'épreuve
        $query = "UPDATE EPREUVE SET nom_epreuve = :nomEpreuve, date_epreuve = :dateEpreuve, 
                  heure_epreuve = :heureEpreuve, id_lieu = :idLieu, id_sport = :idSport 
                  WHERE id_epreuve = :idEpreuve";
        $statement = $connexion->prepare($query);
        $statement->bindParam(":nomEpreuve", $nomEpreuve, PDO::PARAM_STR);
        $statement->bindParam(":dateEpreuve", $dateEpreuve, PDO::PARAM_STR);
        $statement->bindParam(":heureEpreuve", $heureEpreuve, PDO::PARAM_STR);
        $statement->bindParam(":idLieu", $idLieu, PDO::PARAM_INT);
        $statement->bindParam(":idSport", $idSport, PDO::PARAM_INT);
        $statement->bindParam(":idEpreuve", $id_epreuve, PDO::PARAM_INT);

        // Exécutez la requête
        if ($statement->execute()) {
            $_SESSION['success'] = "L'épreuve a été modifiée avec succès.";
            header("Location: manage-events.php");
            exit();
        } else {
            $_SESSION['error'] = "Erreur lors de la modification de l'épreuve.";
            header("Location: modify-events.php?id_epreuve=$id_epreuve");
            exit();
        }
    } catch (PDOException $e) {
        $_SESSION['error'] = "Erreur de base de données : " . htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8');
        header("Location: modify-events.php?id_epreuve=$id_epreuve");
        exit();
    }
}
?>

<!DOCTYPE html>
<html lang="fr">

<style>
    select {
        width: 100%;
        padding: 10px;
        margin-bottom: 15px;
        box-sizing: border-box;
        border: 1px solid #ccc;
        border-radius: 5px;
    }

    select option {
        padding: 100px;
        background-color: #ffffff; /* Couleur de fond des options */
        color: #000000; /* Couleur du texte des options */
    }

    select option:hover {
        background-color: #d7c378; /* Couleur de fond au survol de l'option */
        color: #000000; /* Couleur du texte au survol de l'option */
    }
</style>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="../../../css/normalize.css">
    <link rel="stylesheet" href="../../../css/styles-computer.css">
    <link rel="stylesheet" href="../../../css/styles-responsive.css">
    <link rel="shortcut icon" href="../../../img/favicon.ico" type="image/x-icon">
    <title>Modifier une Épreuve - Jeux Olympiques - Los Angeles 2028</title>
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
                <li><a href="../admin-results/manage-results.php">Gestion Résultats</a></li>
                <li><a href="../../logout.php">Déconnexion</a></li>
            </ul>
        </nav>
    </header>

    <main>
        <h1>Modifier une Épreuve</h1>

        <!-- Affichage des messages d'erreur ou de succès -->
        <?php
        if (isset($_SESSION['error'])) {
            echo '<p style="color: red;">' . htmlspecialchars($_SESSION['error'], ENT_QUOTES, 'UTF-8') . '</p>';
            unset($_SESSION['error']);
        }
        if (isset($_SESSION['success'])) {
            echo '<p style="color: green;">' . htmlspecialchars($_SESSION['success'], ENT_QUOTES, 'UTF-8') . '</p>';
            unset($_SESSION['success']);
        }
        ?>

        <form action="modify-events.php?id_epreuve=<?php echo $id_epreuve; ?>" method="post"
              onsubmit="return confirm('Êtes-vous sûr de vouloir modifier cette épreuve ?')">
            <label for="nomEpreuve">Nom de l'épreuve :</label>
            <input type="text" name="nomEpreuve" id="nomEpreuve"
                   value="<?php echo htmlspecialchars($event['nom_epreuve'] ?? '', ENT_QUOTES, 'UTF-8'); ?>" required>

            <label for="dateEpreuve">Date de l'épreuve :</label>
            <input type="date" name="dateEpreuve" id="dateEpreuve"
                   value="<?php echo htmlspecialchars($event['date_epreuve'] ?? '', ENT_QUOTES, 'UTF-8'); ?>" required>

            <label for="heureEpreuve">Heure de l'épreuve :</label>
            <input type="time" name="heureEpreuve" id="heureEpreuve"
                   value="<?php echo htmlspecialchars($event['heure_epreuve'] ?? '', ENT_QUOTES, 'UTF-8'); ?>" required>

            <label for="idLieu">Lieu de l'épreuve :</label>
            <select name="idLieu" id="idLieu" required>
                <option value="" disabled>Choisissez un lieu</option>
                <?php foreach ($places as $place): ?>
                    <option value="<?php echo $place['id_lieu']; ?>" <?php echo ($place['id_lieu'] == $event['id_lieu']) ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($place['nom_lieu'], ENT_QUOTES, 'UTF-8'); ?>
                    </option>
                <?php endforeach; ?>
            </select><br><br>

            <label for="idSport">Sport :</label>
            <select name="idSport" id="idSport" required>
                <option value="" disabled>Choisissez un sport</option>
                <?php foreach ($sports as $sport): ?>
                    <option value="<?php echo $sport['id_sport']; ?>" <?php echo ($sport['id_sport'] == $event['id_sport']) ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($sport['nom_sport'], ENT_QUOTES, 'UTF-8'); ?>
                    </option>
                <?php endforeach; ?>
            </select><br><br>

            <input type="submit" value="Modifier l'épreuve">
        </form>

        <p class="paragraph-link">
            <a class="link-home" href="manage-events.php">Retour à la gestion des épreuves</a>
        </p>
    </main>

    <footer>
        <figure>
            <img src="../../../img/logo-jo.png" alt="logo Jeux Olympiques - Los Angeles 2028">
        </figure>
    </footer>
</body>
</html>
