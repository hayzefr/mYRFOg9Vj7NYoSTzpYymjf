<?php
require_once 'config.php';

$pdo = new PDO("mysql:host=$host;dbname=$db", $user, $pass);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $date = $_POST['date'];
    $premier = $_POST['premier'];
    $deuxieme = $_POST['deuxieme'];
    $troisieme = $_POST['troisieme'];
    $joueurs_sur_table = isset($_POST['joueurs_sur_table']) ? $_POST['joueurs_sur_table'] : [];

    $joueurs_sur_table_str = implode(',', $joueurs_sur_table);
    $stmt = $pdo->prepare("INSERT INTO jours (date, premier_id, deuxieme_id, troisieme_id, joueurs_sur_table) VALUES (?, ?, ?, ?, ?)");
    $stmt->execute([$date, $premier, $deuxieme, $troisieme, $joueurs_sur_table_str]);
    $jour_id = $pdo->lastInsertId();

    $joueurs = $pdo->query("SELECT * FROM joueurs")->fetchAll();

    $points_joueurs = [];

    foreach ($joueurs as $joueur) {
        $points_joueurs[$joueur['id']] = 0;
    }

    $points_joueurs[$premier] = 10;
    $points_joueurs[$deuxieme] = 5;
    $points_joueurs[$troisieme] = 3;

    foreach ($joueurs_sur_table as $joueur_id) {
        if (!in_array($joueur_id, [$premier, $deuxieme, $troisieme])) {
            $points_joueurs[$joueur_id] = 1;
        }
    }

    foreach ($points_joueurs as $joueur_id => $points) {
        $stmt = $pdo->prepare("INSERT INTO points (jour_id, joueur_id, points) VALUES (?, ?, ?)");
        $stmt->execute([$jour_id, $joueur_id, $points]);
    }

    header('Location: index.php');
    exit();
}

$joueurs = $pdo->query("SELECT * FROM joueurs")->fetchAll();
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin</title>
    <!-- Inclure les fichiers CSS de Bootstrap à partir d'un CDN -->
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.0.0/css/bootstrap.min.css" integrity="sha384-Gn5384xqQ1aoWXA+058RXPxPg6fy4IWvTNh0E263XmFcJlSAwiGgFAW/dAiS6JXm" crossorigin="anonymous">
</head>
<body>
    <div class="row justify-content-center">
        <div class="col-auto">
            <img src="/images/logo.png" alt="Logo" width="150" height="150">
        </div>
    </div>
    <br>
    <div class="container">
        <h1 class="text-center my-4">Ajouter un nouveau tournoi</h1>
        <form action="" method="post">
            <div class="form-group row">
                <label for="date" class="col-sm-2 col-form-label">Date : </label>
                <div class="col-sm-10">
                    <input type="date" name="date" id="date" class="form-control" required><br><br>
                </div>
            </div>
            <div class="form-group row">
                <label for="premier" class="col-sm-2 col-form-label">Premier : </label>
                <div class="col-sm-10">
                    <select name="premier" id="premier" class="form-control" required>
                        <option value="">-- Choisir --</option>
                        <?php foreach ($joueurs as $joueur): ?>
                            <option value="<?= $joueur['id'] ?>"><?= htmlspecialchars($joueur['nom']) ?></option>
                        <?php endforeach; ?>
                    </select><br><br>
                </div>
            </div>
            <div class="form-group row">
                <label for="deuxieme" class="col-sm-2 col-form-label">Deuxième : </label>
                <div class="col-sm-10">
                    <select name="deuxieme" id="deuxieme" class="form-control" required>
                        <option value="">-- Choisir --</option>
                        <?php foreach ($joueurs as $joueur): ?>
                            <option value="<?= $joueur['id'] ?>"><?= htmlspecialchars($joueur['nom']) ?></option>
                        <?php endforeach; ?>
                    </select><br><br>
                </div>
            </div>
            <div class="form-group row">
                <label for="troisieme" class="col-sm-2 col-form-label">Troisième : </label>
                <div class="col-sm-10">
                    <select name="troisieme" id="troisieme" class="form-control" required>
                        <option value="">-- Choisir --</option>
                        <?php foreach ($joueurs as $joueur): ?>
                            <option value="<?= $joueur['id'] ?>"><?= htmlspecialchars($joueur['nom']) ?></option>
                        <?php endforeach; ?>
                    </select><br><br>
                </div>
            </div>
            <div class="form-group row">
                <label for="joueurs_sur_table" class="col-sm-2 col-form-label">Joueurs toujours sur la table (Survivors): </label>
                <div class="col-sm-10">
                    <?php foreach ($joueurs as $joueur): ?>
                        <div class="form-check form-check-inline">
                            <input class="form-check-input" type="checkbox" name="joueurs_sur_table[]" id="joueur_<?= $joueur['id'] ?>" value="<?= $joueur['id'] ?>">
                            <label class="form-check-label" for="joueur_<?= $joueur['id'] ?>"><?= htmlspecialchars($joueur['nom']) ?></label>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <div class="row justify-content-center mt-4">
            <div class="col-auto">
                <button type="submit" class="btn btn-primary mr-2">Ajouter</button>
                <a href="index.php" class="btn btn-secondary">
                    <i class="fas fa-arrow-left"></i> Retour
                </a>
            </div>
        </div>
        </form>
    </div>

    <!-- Footer -->
    <footer class="py-3 mt-4 bg-light text-center">
        <div class="container">
            <span>Developped by Hayze</span>
        </div>
    </footer>

    <!-- Inclure les fichiers JS de Bootstrap à partir d'un CDN -->
    <script src="https://code.jquery.com/jquery-3.2.1.slim.min.js" integrity="sha384-KJ3o2DKtIkvYIK3UENzmM7KCkRr/rE9/Qpg6aAZGJwFDMVNA/GpGFF93hXpG5KkN" crossorigin="anonymous"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/popper.js/1.12.9/umd/popper.min.js" integrity="sha384-ApNbgh9B+Y1QKtv3Rn7W3mgPxhU9K/ScQsAP7hUibX39j7fakFPskvXusvfa0b4Q" crossorigin="anonymous"></script>
    <script src="https://maxcdn.bootstrapcdn.com/bootstrap/4.0.0/js/bootstrap.min.js" integrity="sha384-JZR6Spejh4U02d8jOt6vLEHfe/JQGiRRSQQxSfFWpi1MquVdAyjUar5+76PVCmYl" crossorigin="anonymous"></script>

    <script>
    // Fonction pour gérer la désactivation des cases à cocher en fonction du choix dans les champs "Premier", "Deuxième" et "Troisième"
    function handleSelection(position) {
        // Récupérer la valeur sélectionnée dans le champ correspondant
        var selectedValue = document.getElementById(position).value;

        // Récupérer toutes les cases à cocher
        var checkboxes = document.getElementsByName("joueurs_sur_table[]");

        // Parcourir toutes les cases à cocher
        for (var i = 0; i < checkboxes.length; i++) {
            // Si la valeur de la case à cocher correspond à la valeur sélectionnée dans le champ, la désactiver
            if (checkboxes[i].value == selectedValue) {
                checkboxes[i].disabled = selectedValue !== "";
            }
        }

        // Si la valeur sélectionnée est remise à "", réactiver toutes les cases à cocher
        if (selectedValue === "") {
            for (var i = 0; i < checkboxes.length; i++) {
                checkboxes[i].disabled = false;
            }
        }
    }

    // Appeler la fonction handleSelection pour les champs "Premier", "Deuxième" et "Troisième" lorsqu'ils sont modifiés
    document.getElementById("premier").addEventListener("change", function() {
        handleSelection("premier");
    });

    document.getElementById("deuxieme").addEventListener("change", function() {
        handleSelection("deuxieme");
    });

    document.getElementById("troisieme").addEventListener("change", function() {
        handleSelection("troisieme");
    });
</script>


</body>
</html>
