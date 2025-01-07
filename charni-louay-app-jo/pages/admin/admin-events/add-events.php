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

// Vérifiez si le formulaire est soumis
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Assurez-vous d'obtenir des données sécurisées et filtrées
    $nomEpreuve = filter_input(INPUT_POST, 'nomEpreuve', FILTER_SANITIZE_SPECIAL_CHARS);
    $dateEpreuve = filter_input(INPUT_POST, 'dateEpreuve', FILTER_SANITIZE_STRING);
    $heureEpreuve = filter_input(INPUT_POST, 'heureEpreuve', FILTER_SANITIZE_STRING);
    $idLieu = filter_input(INPUT_POST, 'idLieu', FILTER_VALIDATE_INT);
    $idSport = filter_input(INPUT_POST, 'idSport', FILTER_VALIDATE_INT);

    // Vérification du token CSRF
    if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
        $_SESSION['error'] = "Token CSRF invalide.";
        header("Location: add-events.php");
        exit();
    }

    // Vérifiez si le nom de l'épreuve est vide
    if (empty($nomEpreuve)) {
        $_SESSION['error'] = "Le nom de l'épreuve ne peut pas être vide.";
        header("Location: add-events.php");
        exit();
    }

    // Vérifiez si le lieu et le sport sont valides
    if (!$idLieu || !$idSport) {
        $_SESSION['error'] = "Le lieu ou le sport spécifié est invalide.";
        header("Location: add-events.php");
        exit();
    }

    try {
        // Insertion de l'épreuve
        $query = "INSERT INTO EPREUVE (nom_epreuve, date_epreuve, heure_epreuve, id_lieu, id_sport) 
                  VALUES (:nomEpreuve, :dateEpreuve, :heureEpreuve, :idLieu, :idSport)";
        $statement = $connexion->prepare($query);
        $statement->bindParam(":nomEpreuve", $nomEpreuve, PDO::PARAM_STR);
        $statement->bindParam(":dateEpreuve", $dateEpreuve, PDO::PARAM_STR);
        $statement->bindParam(":heureEpreuve", $heureEpreuve, PDO::PARAM_STR);
        $statement->bindParam(":idLieu", $idLieu, PDO::PARAM_INT);
        $statement->bindParam(":idSport", $idSport, PDO::PARAM_INT);

        // Exécutez la requête
        if ($statement->execute()) {
            $_SESSION['success'] = "L'épreuve a été ajoutée avec succès.";
            header("Location: manage-events.php");
            exit();
        } else {
            $_SESSION['error'] = "Erreur lors de l'ajout de l'épreuve.";
            header("Location: add-events.php");
            exit();
        }
    } catch (PDOException $e) {
        $_SESSION['error'] = "Erreur de base de données : " . htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8');
        header("Location: add-events.php");
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
    <title>Ajouter une Épreuve - Jeux Olympiques - Los Angeles 2028</title>
    <style>
        /* Ajoutez votre style CSS ici */
    </style>
</head>

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

<body>
    <header>
        <nav>
            <!-- Menu vers les pages sports, events, et results -->
            <ul class="menu">
                <li><a href="../admin.php">Accueil Administration</a></li>
                <li><a href="manage-sports.php">Gestion Sports</a></li>
                <li><a href="manage-places.php">Gestion Lieux</a></li>
                <li><a href="manage-countries.php">Gestion Pays</a></li>
                <li><a href="manage-events.php">Gestion Calendrier</a></li>
                <li><a href="manage-athletes.php">Gestion Athlètes</a></li>
                <li><a href="manage-results.php">Gestion Résultats</a></li>
                <li><a href="../../logout.php">Déconnexion</a></li>
            </ul>
        </nav>
    </header>

    <main>
        <h1>Ajouter une Épreuve</h1>
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
        <form action="add-events.php" method="post" onsubmit="return confirm('Êtes-vous sûr de vouloir ajouter cette épreuve ?')">
            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
            <label for="nomEpreuve">Nom de l'épreuve :</label>
            <input type="text" name="nomEpreuve" id="nomEpreuve" required>

            <label for="dateEpreuve">Date de l'épreuve :</label>
            <input type="date" name="dateEpreuve" id="dateEpreuve" required>

            <label for="heureEpreuve">Heure de l'épreuve :</label>
            <input type="time" name="heureEpreuve" id="heureEpreuve" required>

            <label for="idLieu">Lieu :</label>
            <select name="idLieu" id="idLieu" required>
                <option value="" disabled selected>Choisissez un lieu</option>
                <?php
                // Récupérer la liste des lieux
                try {
                    $queryPlaces = "SELECT id_lieu, nom_lieu FROM LIEU ORDER BY nom_lieu";
                    $statementPlaces = $connexion->prepare($queryPlaces);
                    $statementPlaces->execute();
                    $places = $statementPlaces->fetchAll(PDO::FETCH_ASSOC);

                    foreach ($places as $place) {
                        echo '<option value="' . htmlspecialchars($place['id_lieu'], ENT_QUOTES, 'UTF-8') . '">' . htmlspecialchars($place['nom_lieu'], ENT_QUOTES, 'UTF-8') . '</option>';
                    }
                } catch (PDOException $e) {
                    echo '<option value="" disabled>Erreur de chargement des lieux</option>';
                }
                ?>
            </select>

            <label for="idSport">Sport :</label>
            <select name="idSport" id="idSport" required>
                <option value="" disabled selected>Choisissez un sport</option>
                <?php
                // Récupérer la liste des sports
                try {
                    $querySports = "SELECT id_sport, nom_sport FROM SPORT ORDER BY nom_sport";
                    $statementSports = $connexion->prepare($querySports);
                    $statementSports->execute();
                    $sports = $statementSports->fetchAll(PDO::FETCH_ASSOC);

                    foreach ($sports as $sport) {
                        echo '<option value="' . htmlspecialchars($sport['id_sport'], ENT_QUOTES, 'UTF-8') . '">' . htmlspecialchars($sport['nom_sport'], ENT_QUOTES, 'UTF-8') . '</option>';
                    }
                } catch (PDOException $e) {
                    echo '<option value="" disabled>Erreur de chargement des sports</option>';
                }
                ?>
            </select>

            <input type="submit" value="Ajouter l'épreuve">
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
