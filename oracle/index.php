<?php

setlocale(LC_TIME, 'fr_FR.utf8', 'fra');

require_once 'config.php';

$jours = $pdo->query("
    SELECT
        jours.id,
        jours.date,
        j1.nom AS premier,
        j2.nom AS deuxieme,
        j3.nom AS troisieme,
        jours.joueurs_sur_table,
        GROUP_CONCAT(j4.nom SEPARATOR ', ') AS joueurs_sur_table_noms
    FROM
        jours
    LEFT JOIN
        joueurs j1 ON jours.premier_id = j1.id
    LEFT JOIN
        joueurs j2 ON jours.deuxieme_id = j2.id
    LEFT JOIN
        joueurs j3 ON jours.troisieme_id = j3.id
    LEFT JOIN
        joueurs j4 ON FIND_IN_SET(j4.id, jours.joueurs_sur_table)
    GROUP BY
        jours.id
    ORDER BY
        jours.date DESC
")->fetchAll(PDO::FETCH_ASSOC);

$classement = $pdo->query("
    SELECT
        joueurs.id,
        joueurs.nom,
        SUM(points.points) AS total
    FROM
        joueurs
    LEFT JOIN
        points ON joueurs.id = points.joueur_id
    GROUP BY
        joueurs.id
    ORDER BY
        total DESC
")->fetchAll(PDO::FETCH_ASSOC);

$podium = $pdo->query("
    SELECT
        joueurs.nom,
        SUM(points.points) AS total
    FROM
        joueurs
    LEFT JOIN
        points ON joueurs.id = points.joueur_id
    GROUP BY
        joueurs.id
    ORDER BY
        total DESC
    LIMIT 3
")->fetchAll(PDO::FETCH_ASSOC);

?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Classement</title>
    <!-- Inclure les fichiers CSS de Bootstrap à partir d'un CDN -->
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.0.0/css/bootstrap.min.css" integrity="sha384-Gn5384xqQ1aoWXA+058RXPxPg6fy4IWvTNh0E263XmFcJlSAwiGgFAW/dAiS6JXm" crossorigin="anonymous">
    <style>
        /* Styles personnalisés pour le texte défilant et le tableau */
        .text-scroll {
            background-color: #5A9E03;
            color: white;
            padding: 10px;
            white-space: nowrap;
            overflow: hidden;
            box-sizing: content-box;
            animation: scroll 10s linear infinite;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        @keyframes scroll {
            0% { transform: translateX(100%); }
            100% { transform: translateX(-100%); }
        }

        .table-colored tbody tr:nth-child(even) {
            background-color: #f8f9fa;
        }

        .table-colored tbody tr:nth-child(odd) {
            background-color: white;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="row">
            <div class="col-12">
                <!-- Texte défilant -->
                <div class="text-scroll">
                    Bienvenue sur notre site de classement de poker - Suivez les résultats et le classement général en direct !
                </div>
            </div>
        </div>
        <div class="row justify-content-center align-items-center mt-4">
            <div class="col-auto">
                <!-- Logo centré -->
                <img src="/images/logo.png" alt="Logo" width="150" height="150">
            </div>
        </div>
        <div class="row mt-4 align-items-start">
            <div class="col-4">
            <h2 class="text-center my-4 mt-0">Podium</h2>
                <ul class="list-group">
                    <?php
                    $positions = ['🥇', '🥈', '🥉'];
                    for ($i = 0; $i < count($podium); $i++) {
                        echo '<li class="list-group-item d-flex justify-content-between align-items-center">';
                        echo $positions[$i] . ' ' . htmlspecialchars($podium[$i]['nom']) . ' - ' . htmlspecialchars($podium[$i]['total']) . ' points';
                        echo '</li>';
                    }
                    ?>
                </ul>
            </div>
            <div class="col-8">
                <h1 class="text-center my-4">Classement général</h1>
                <table class="table table-bordered table-colored table-layout-fixed w-100">
                    <thead>
                        <tr>
                            <th style="width: 70%;">Joueur</th>
                            <th style="width: 30%;">Points</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($classement as $joueur): ?>
                            <tr>
                                <td><a href="stats.php?id=<?= $joueur['id'] ?>"><?= htmlspecialchars($joueur['nom']) ?></a></td>
                                <td><?= htmlspecialchars($joueur['total']) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>    
                </table>
            </div>
        </div>
        <div class="row mt-4">
            <div class="col-12">
                <h1 class="text-center my-4">Résultats par jour</h1>
                <table class="table table-bordered table-colored table-layout-fixed w-100">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Premier (10 points)</th>
                            <th>Deuxième (5 points)</th>
                            <th>Troisième (3 points)</th>
                            <th>Survivors (1 point)</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($jours as $jour): ?>
                            <tr>
                                <td><?= 'Tournoi du ' . htmlspecialchars(strftime('%A %d/%m/%Y', strtotime($jour['date']))) ?></td>
                                <td><?= htmlspecialchars($jour['premier']) ?></td>
                                <td><?= htmlspecialchars($jour['deuxieme']) ?></td>
                                <td><?= htmlspecialchars($jour['troisieme']) ?></td>
                                <td>
                                    <?php if (!empty($jour['joueurs_sur_table_noms'])): ?>
                                        <?= htmlspecialchars($jour['joueurs_sur_table_noms']) ?>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
        <div class="row justify-content-center mt-4">
            <div class="col-auto">
                <!-- Bouton vers la page d'administration -->
                <a href="admin.php" class="btn btn-primary">Nouveau tournoi</a>
            </div>
        </div>
    </div>

    <!-- Footer -->
    <footer class="py-3 mt-4 bg-light text-center">
        <div class="container">
            <span>v1.0 - Developped by APS</span>
        </div>
    </footer>

    <!-- Inclure les fichiers JS de Bootstrap à partir d'un CDN -->
    <script src="https://code.jquery.com/jquery-3.2.1.slim.min.js" integrity="sha384-KJ3o2DKtIkvYIK3UENzmM7KCkRr/rE9/Qpg6aAZGJwFDMVNA/GpGFF93hXpG5KkN" crossorigin="anonymous"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/popper.js/1.12.9/umd/popper.min.js" integrity="sha384-ApNbgh9B+Y1QKtv3Rn7W3mgPxhU9K/ScQsAP7hUibX39j7fakFPskvXusvfa0b4Q" crossorigin="anonymous"></script>
    <script src="https://maxcdn.bootstrapcdn.com/bootstrap/4.0.0/js/bootstrap.min.js" integrity="sha384-JZR6Spejh4U02d8jOt6vLEHfe/JQGiRRSQQxSfFWpi1MquVdAyjUar5+76PVCmYl" crossorigin="anonymous"></script>
</body>
</html>