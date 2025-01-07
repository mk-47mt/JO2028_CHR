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
    $nomLieu = filter_input(INPUT_POST, 'nomLieu', FILTER_SANITIZE_SPECIAL_CHARS);
    $adresseLieu = filter_input(INPUT_POST, 'adresseLieu', FILTER_SANITIZE_SPECIAL_CHARS);
    $villeLieu = filter_input(INPUT_POST, 'villeLieu', FILTER_SANITIZE_SPECIAL_CHARS);
    $cpLieu = filter_input(INPUT_POST, 'cpLieu', FILTER_SANITIZE_SPECIAL_CHARS);

    // Vérification du token CSRF
    if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
        $_SESSION['error'] = "Token CSRF invalide.";
        header("Location: add-place.php");
        exit();
    }

    // Vérification si les champs sont vides
    if (empty($nomLieu) || empty($adresseLieu) || empty($villeLieu) || empty($cpLieu)) {
        $_SESSION['error'] = "Tous les champs doivent être remplis.";
        header("Location: add-place.php");
        exit();
    }

    try {
        // Vérifiez si le lieu existe déjà
        $queryCheck = "SELECT id_lieu FROM LIEU WHERE nom_lieu = :nomLieu AND adresse_lieu = :adresseLieu";
        $statementCheck = $connexion->prepare($queryCheck);
        $statementCheck->bindParam(":nomLieu", $nomLieu, PDO::PARAM_STR);
        $statementCheck->bindParam(":adresseLieu", $adresseLieu, PDO::PARAM_STR);
        $statementCheck->execute();

        if ($statementCheck->rowCount() > 0) {
            $_SESSION['error'] = "Le lieu existe déjà.";
            header("Location: add-place.php");
            exit();
        } else {
            // Requête pour ajouter un lieu
            $query = "INSERT INTO LIEU (nom_lieu, adresse_lieu, ville_lieu, cp_lieu) VALUES (:nomLieu, :adresseLieu, :villeLieu, :cpLieu)";
            $statement = $connexion->prepare($query);
            $statement->bindParam(":nomLieu", $nomLieu, PDO::PARAM_STR);
            $statement->bindParam(":adresseLieu", $adresseLieu, PDO::PARAM_STR);
            $statement->bindParam(":villeLieu", $villeLieu, PDO::PARAM_STR);
            $statement->bindParam(":cpLieu", $cpLieu, PDO::PARAM_STR);

            // Exécutez la requête
            if ($statement->execute()) {
                $_SESSION['success'] = "Le lieu a été ajouté avec succès.";
                header("Location: manage-places.php");
                exit();
            } else {
                $_SESSION['error'] = "Erreur lors de l'ajout du lieu.";
                header("Location: add-place.php");
                exit();
            }
        }
    } catch (PDOException $e) {
        $_SESSION['error'] = "Erreur de base de données : " . htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8');
        header("Location: add-place.php");
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
    <title>Ajouter un Lieu - Jeux Olympiques - Los Angeles 2028</title>
    <style>
        /* Ajoutez votre style CSS ici */
    </style>
</head>

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
        <h1>Ajouter un Lieu</h1>
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
        <form action="add-place.php" method="post" onsubmit="return confirm('Êtes-vous sûr de vouloir ajouter ce lieu ?')">
            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
            <label for="nomLieu">Nom du Lieu :</label>
            <input type="text" name="nomLieu" id="nomLieu" required><br>

            <label for="adresseLieu">Adresse du Lieu :</label>
            <input type="text" name="adresseLieu" id="adresseLieu" required><br>

            <label for="villeLieu">Ville :</label>
            <input type="text" name="villeLieu" id="villeLieu" required><br>

            <label for="cpLieu">Code Postal :</label>
            <input type="text" name="cpLieu" id="cpLieu" required><br>

            <input type="submit" value="Ajouter le Lieu">
        </form>
        <p class="paragraph-link">
            <a class="link-home" href="manage-places.php">Retour à la gestion des lieux</a>
        </p>
    </main>

    <footer>
        <figure>
            <img src="../../../img/logo-jo.png" alt="logo Jeux Olympiques - Los Angeles 2028">
        </figure>
    </footer>

</body>

</html>
