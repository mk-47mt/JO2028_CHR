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
    $nomAthlete = filter_input(INPUT_POST, 'nomAthlete', FILTER_SANITIZE_SPECIAL_CHARS);
    $prenomAthlete = filter_input(INPUT_POST, 'prenomAthlete', FILTER_SANITIZE_SPECIAL_CHARS);
    $nomGenre = filter_input(INPUT_POST, 'nomGenre', FILTER_SANITIZE_SPECIAL_CHARS);
    $nomPays = filter_input(INPUT_POST, 'nomPays', FILTER_SANITIZE_SPECIAL_CHARS);

    // Vérification du token CSRF
    if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
        $_SESSION['error'] = "Token CSRF invalide.";
        header("Location: add-athlete.php");
        exit();
    }

    // Vérifiez si le nom de l'athlete est vide
    if (empty($nomAthlete) || empty($prenomAthlete) || empty($nomGenre) || empty($nomPays)) {
        $_SESSION['error'] = "Le nom, pays, genre et le prénom de l'athlète ne peuvent pas être vides.";
        header("Location: add-athlete.php");
        exit();
    }

    try {
        // Vérifiez si l'athlete existe déjà
        $queryCheck = "SELECT a.id_athlete, g.nom_genre, g.id_genre, p.id_pays, p.nom_pays 
        FROM ATHLETE a
        JOIN GENRE g ON a.id_genre = g.id_genre
        JOIN PAYS p ON a.id_pays = p.id_pays
        WHERE a.nom_athlete = :nomAthlete 
        AND a.prenom_athlete = :prenomAthlete 
        AND p.nom_pays = :nomPays 
        AND g.nom_genre = :nomGenre";

        $statementCheck = $connexion->prepare($queryCheck);
        $statementCheck->bindParam(":nomAthlete", $nomAthlete, PDO::PARAM_STR);
        $statementCheck->bindParam(":prenomAthlete", $prenomAthlete, PDO::PARAM_STR);
        $statementCheck->bindParam(":nomPays", $nomPays, PDO::PARAM_STR);
        $statementCheck->bindParam(":nomGenre", $nomGenre, PDO::PARAM_STR);
        $statementCheck->execute();

        if ($statementCheck->rowCount() > 0) {
            $_SESSION['error'] = "L'athlete existe déjà.";
            header("Location: add-athlete.php");
            exit();
        } else {
            // Requête pour ajouter un athlete
            $query = "INSERT INTO ATHLETE (nom_athlete, prenom_athlete, id_genre, id_pays) VALUES (:nomAthlete, :prenomAthlete, :nomGenre, :nomPays)";
            $statement = $connexion->prepare($query);

            // Lier les paramètres
            $statement->bindParam(":nomAthlete", $nomAthlete, PDO::PARAM_STR);
            $statement->bindParam(":prenomAthlete", $prenomAthlete, PDO::PARAM_STR);
            $statement->bindParam(":nomGenre", $nomGenre, PDO::PARAM_STR);
            $statement->bindParam(":nomPays", $nomPays, PDO::PARAM_STR);

            // Exécutez la requête
            if ($statement->execute()) {
                $_SESSION['success'] = "L'athlete a été ajouté avec succès.";
                header("Location: manage-athletes.php");
                exit();
            } else {
                $_SESSION['error'] = "Erreur lors de l'ajout de l'athlète.";
                header("Location: add-athlete.php");
                exit();
            }
        }
    } catch (PDOException $e) {
        $_SESSION['error'] = "Erreur de base de données : " . htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8');
        header("Location: add-athlete.php");
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
    <title>Ajouter un Athlete - Jeux Olympiques - Los Angeles 2028</title>
    <style>
        /* Ajoutez votre style CSS ici */
    </style>
</head>

<body>
    <header>
        <nav>
            <!-- Menu vers les pages sports, events, et results -->
            <ul class="menu">
                <li><a href="../admin-users/manage-users.php">Gestion Administrateurs</a></li>
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

    <main>
        <h1>Ajouter un Athlète</h1>
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
        <form action="add-athletes.php" method="post" onsubmit="return confirm('Êtes-vous sûr de vouloir ajouter cette athlète ?')">
            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
            <label for="nomAthlete">Nom de l'Athlète :</label>
            <input type="text" name="nomAthlete" id="nomAthlete" required>
            
            <label for="prenomAthlete">Prenom de l'Athlète :</label>
            <input type="text" name="prenomAthlete" id="prenomAthlete" required>

            <label for="nomGenre">Genre de l'Athlète :</label>
            <select name="nomGenre" id="nomGenre" required>
                <option value="Selectionner un genre" disabled selected>Choisissez un genre</option> <!-- disabled selected= option par default non selectionnable-->
                <?php
                // Récupérer la liste des genres
                try {
                    $queryGenre = "SELECT id_genre, nom_genre FROM GENRE 
                    ORDER BY nom_genre";

                    $statementGenre = $connexion->prepare($queryGenre);
                    $statementGenre->execute();
                    $genres = $statementGenre->fetchAll(PDO::FETCH_ASSOC);

                    foreach ($genres as $genre) { // Changer $nomGenre en $genre
                        echo '<option value="' . htmlspecialchars($genre['id_genre'], ENT_QUOTES, 'UTF-8') . '">' . htmlspecialchars($genre['nom_genre'], ENT_QUOTES, 'UTF-8') . '</option>';
                    }
                } catch (PDOException $e) {
                     echo '<option value="" disabled>Erreur de chargement des genres</option>';
                }
                ?>
        </select>

        <label for="nomPays">Pays de l'Athlète :</label>
        <select name="nomPays" id="nomPays" required>
            <option value="" disabled selected>Choisissez un pays</option>
            <?php
            // Récupérer la liste des pays
            try {
                $queryCountry = "SELECT id_pays, nom_pays FROM PAYS ORDER BY nom_pays";
                $statementCountry = $connexion->prepare($queryCountry);
                $statementCountry->execute();
                $countries = $statementCountry->fetchAll(PDO::FETCH_ASSOC);

                foreach ($countries as $country) {
                    echo '<option value="' . htmlspecialchars($country['id_pays'], ENT_QUOTES, 'UTF-8') . '">' . htmlspecialchars($country['nom_pays'], ENT_QUOTES, 'UTF-8') . '</option>';
                }
            } catch (PDOException $e) {
                echo '<option value="" disabled>Erreur de chargement des pays</option>';
            }
            ?>
        </select>

            <input type="submit" value="Ajouter l'athlete">
        </form>
        <p class="paragraph-link">
            <a class="link-home" href="manage-athletes.php">Retour à la gestion des athlètes</a>
        </p>
    </main>

    <footer>
        <figure>
            <img src="../../../img/logo-jo.png" alt="logo Jeux Olympiques - Los Angeles 2028">
        </figure>
    </footer>

</body>

</html>