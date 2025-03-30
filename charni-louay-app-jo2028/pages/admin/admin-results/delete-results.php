<?php
session_start();
require_once("../../../database/database.php");

// Protection CSRF
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
        $_SESSION['error'] = "Token CSRF invalide.";
        header('Location: ../../../index.php');
        exit();
    }
}

// Vérifiez si l'utilisateur est connecté
if (!isset($_SESSION['login'])) {
    header('Location: ../../../index.php');
    exit();
}

// Génération du token CSRF si ce n'est pas déjà fait
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32)); // Génère un token CSRF sécurisé
}

// Vérifiez si l'ID de l'athlète et l'ID de l'épreuve sont fournis dans l'URL
if (!isset($_GET['id_athlete']) || !isset($_GET['id_epreuve'])) {
    $_SESSION['error'] = "ID de l'athlète ou de l'épreuve manquant.";
    header("Location: manage-results.php");
    exit();
} else {
    // On sécurise et valide les ID passés dans l'URL
    $id_athlete = filter_input(INPUT_GET, 'id_athlete', FILTER_VALIDATE_INT);
    $id_epreuve = filter_input(INPUT_GET, 'id_epreuve', FILTER_VALIDATE_INT);

    // Vérification que les IDs sont valides
    if ($id_athlete === false || $id_epreuve === false) {
        $_SESSION['error'] = "ID de l'athlète ou de l'épreuve invalide.";
        header("Location: manage-results.php");
        exit();
    } else {
        try {
            // Requête pour supprimer la participation de l'athlète à l'épreuve dans la table PARTICIPER
            $sql = "DELETE FROM PARTICIPER WHERE id_athlete = :id_athlete AND id_epreuve = :id_epreuve";
            $statement = $connexion->prepare($sql);
            $statement->bindParam(':id_athlete', $id_athlete, PDO::PARAM_INT);
            $statement->bindParam(':id_epreuve', $id_epreuve, PDO::PARAM_INT);

            // Exécution de la requête
            if ($statement->execute()) {
                $_SESSION['success'] = "La participation a été supprimée avec succès.";
            } else {
                $_SESSION['error'] = "Erreur lors de la suppression de la participation.";
            }

            // Redirigez l'utilisateur vers la page de gestion des résultats après suppression
            header('Location: manage-results.php');
            exit();

        } catch (PDOException $e) {
            // Gestion des erreurs de la base de données
            $_SESSION['error'] = "Erreur lors de la suppression de la participation : " . htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8');
            header('Location: manage-results.php');
            exit();
        }
    }
}
?>
