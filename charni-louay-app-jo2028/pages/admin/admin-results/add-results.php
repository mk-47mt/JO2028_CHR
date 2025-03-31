<?php
session_start();
require_once("../../../database/database.php");

// Vérifiez si l'utilisateur est connecté
if (!isset($_SESSION['login'])) {
    header('Location: ../../../index.php');
    exit();
}

// Générer un token CSRF si ce n'est pas déjà fait
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32)); // Génère un token CSRF sécurisé
}

// Récupérer les athlètes et les épreuves
try {
    // Récupération des athlètes
    $statementAthlete = $connexion->prepare("SELECT * FROM ATHLETE");
    $statementAthlete->execute();
    $athleteOptions = $statementAthlete->fetchAll(PDO::FETCH_ASSOC);

    // Récupération des épreuves
    $statementEpreuve = $connexion->prepare("SELECT * FROM EPREUVE");
    $statementEpreuve->execute();
    $epreuveOptions = $statementEpreuve->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $_SESSION['error'] = "Erreur de base de données : " . htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8');
    header("Location: manage-results.php");
    exit();
}

// Vérifiez si le formulaire est soumis
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Assurez-vous d'obtenir des données sécurisées et filtrées
    $nomAthlete = filter_input(INPUT_POST, 'nomAthlete', FILTER_SANITIZE_SPECIAL_CHARS);
    $prenomAthlete = filter_input(INPUT_POST, 'prenomAthlete', FILTER_SANITIZE_SPECIAL_CHARS);
    $nomEpreuve = filter_input(INPUT_POST, 'nomEpreuve', FILTER_SANITIZE_SPECIAL_CHARS);
    $resultat = filter_input(INPUT_POST, 'resultat', FILTER_SANITIZE_SPECIAL_CHARS);

    // Vérification du token CSRF
    if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
        $_SESSION['error'] = "Token CSRF invalide.";
        header("Location: add-results.php");
        exit();
    }

    // Vérifiez si les champs sont vides
    if (empty($nomAthlete) || empty($prenomAthlete) || empty($nomEpreuve) || empty($resultat)) {
        $_SESSION['error'] = "Tous les champs doivent être remplis.";
        header("Location: add-results.php");
        exit();
    }

    try {
        // Requête pour ajouter un résultat
        $queryResultat = "INSERT INTO RESULTAT (nom_athlete, prenom_athlete, nom_epreuve, resultat) 
                          VALUES (:nomAthlete, :prenomAthlete, :nomEpreuve, :resultat)";
        $statementResultat = $connexion->prepare($queryResultat);
        $statementResultat->bindParam(":nomAthlete", $nomAthlete, PDO::PARAM_STR);
        $statementResultat->bindParam(":prenomAthlete", $prenomAthlete, PDO::PARAM_STR);
        $statementResultat->bindParam(":nomEpreuve", $nomEpreuve, PDO::PARAM_STR);
        $statementResultat->bindParam(":resultat", $resultat, PDO::PARAM_STR);

        if ($statementResultat->execute()) {
            $_SESSION['success'] = "Le résultat a été ajouté avec succès.";
            header("Location: manage-results.php");
            exit();
        } else {
            $_SESSION['error'] = "Erreur lors de l'ajout du résultat.";
            header("Location: add-results.php");
            exit();
        }
    } catch (PDOException $e) {
        $_SESSION['error'] = "Erreur de base de données : " . htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8');
        header("Location: add-results.php");
        exit();
    }
}

// Afficher les erreurs en PHP (fonctionne à condition d’avoir activé l’option en local)
error_reporting(E_ALL);
ini_set("display_errors", 1);
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
    <title>Ajouter un Résultat - Jeux Olympiques - Los Angeles 2028</title>
    <script>
        // JavaScript pour afficher les prénoms en fonction du nom sélectionné
        function updatePrenom() {
            var nomSelect = document.getElementById("nomAthlete");
            var prenomSelect = document.getElementById("prenomAthlete");
            var athletes = <?php echo json_encode($athleteOptions); ?>;
            var selectedNom = nomSelect.value;
            prenomSelect.innerHTML = ""; // Vide le champ des prénoms

            // Remplir les prénoms en fonction du nom sélectionné
            athletes.forEach(function(athlete) {
                if (athlete.nom_athlete === selectedNom) {
                    var option = document.createElement("option");
                    option.value = athlete.prenom_athlete;
                    option.text = athlete.prenom_athlete;
                    prenomSelect.appendChild(option);
                }
            });
        }
    </script>
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
        <h1>Ajouter un Résultat</h1>
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
        <form action="add-results.php" method="post" onsubmit="return confirm('Êtes-vous sûr de vouloir ajouter ce résultat ?')">
            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">

            <!-- Choix du nom de l'athlète -->
            <label>Nom de l'Athlète :</label>
            <select name="nomAthlete" id="nomAthlete" onchange="updatePrenom()" required>
                <option value="">Sélectionner un nom</option>
                <?php foreach ($athleteOptions as $athlete): ?>
                    <option value="<?= $athlete['nom_athlete']; ?>">
                        <?= htmlspecialchars($athlete['nom_athlete']); ?>
                    </option>
                <?php endforeach; ?>
            </select>

            <!-- Choix du prénom de l'athlète -->
            <label>Prénom de l'Athlète :</label>
            <select name="prenomAthlete" id="prenomAthlete" required>
                <option value="">Sélectionner un prénom</option>
            </select>

            <!-- Choix de l'épreuve -->
            <label>Epreuve :</label>
            <select name="nomEpreuve" required>
                <?php foreach ($epreuveOptions as $epreuve): ?>
                    <option value="<?= $epreuve['nom_epreuve']; ?>">
                        <?= htmlspecialchars($epreuve['nom_epreuve']); ?>
                    </option>
                <?php endforeach; ?>
            </select>

            <label>Résultat :</label>
            <input type="text" name="resultat" required>

            <input type="submit" value="Ajouter le Résultat">
        </form>

        <p class="paragraph-link">
            <a class="link-home" href="manage-results.php">Retour à la gestion des résultats</a>
        </p>
    </main>

    <footer>
        <figure>
            <img src="../../../img/logo-jo.png" alt="logo Jeux Olympiques - Los Angeles 2028">
        </figure>
    </footer>
</body>
</html>
