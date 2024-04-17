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
    (SELECT string_agg(j4.nom, ', ') FROM unnest(jours.joueurs_sur_table::int[]) AS joueur_id JOIN joueurs j4 ON j4.id = joueur_id) AS joueurs_sur_table_noms
FROM
    jours
LEFT JOIN
    joueurs j1 ON jours.premier_id = j1.id
LEFT JOIN
    joueurs j2 ON jours.deuxieme_id = j2.id
LEFT JOIN
    joueurs j3 ON jours.troisieme_id = j3.id
GROUP BY
    jours.id, j1.nom, j2.nom, j3.nom, jours.joueurs_sur_table
ORDER BY
    jours.date DESC

")->fetchAll(PDO::FETCH_ASSOC);

$classement = $pdo->query("
    SELECT
        joueurs.id,
        joueurs.nom,
        COALESCE(SUM(points.points), 0) AS total
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
        COALESCE(SUM(points.points), 0) AS total
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
    <link rel="stylesheet" href="css/bootstrap.min.css">
    <style>
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
                <div class="text-scroll">
                    Bienvenue sur notre site de classement de poker - Suivez les résultats et le classement général en direct !
                </div>
            </div>
        </div>
        <div class="row justify-content-center align-items-center mt-4">
            <div class="col-auto">
                <img src="images/logo.png" alt="Logo" width="150" height="150">
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
                <a href="admin.php" class="btn btn-primary">Nouveau tournoi</a>
            </div>
        </div>
    </div>

    <footer class="py-3 mt-4 bg-light text-center">
        <div class="container">
            <span>v1.0 - Developped by APS</span>
        </div>
    </footer>

    <script src="js/jquery-3.2.1.slim.min.js"></script>
    <script src="js/popper.min.js"></script>
    <script src="js/bootstrap.min.js"></script>
</body>
</html>