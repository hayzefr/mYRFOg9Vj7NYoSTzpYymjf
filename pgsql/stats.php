<?php
require_once 'config.php';

$joueur_id = isset($_GET['id']) ? (int) $_GET['id'] : 0;

if ($joueur_id > 0) {
    $joueur = $pdo->prepare("SELECT * FROM joueurs WHERE id = ?");
    $joueur->execute([$joueur_id]);
    $joueur = $joueur->fetch();

    $points = $pdo->prepare("SELECT jours.date, points.points FROM jours INNER JOIN points ON jours.id = points.jour_id WHERE points.joueur_id = ? ORDER BY jours.date ASC");
    $points->execute([$joueur_id]);
    $points = $points->fetchAll();

    $victoires = $pdo->prepare("SELECT COUNT(*) FROM points WHERE joueur_id = ? AND points = 10");
    $victoires->execute([$joueur_id]);
    $victoires = $victoires->fetchColumn();

    $classement = $pdo->prepare("
    SELECT COUNT(*) + 1 AS rang
    FROM (
        SELECT joueur_id, SUM(points) AS total_points
        FROM points
        GROUP BY joueur_id
        HAVING SUM(points) > (
            SELECT SUM(points)
            FROM points
            WHERE joueur_id = ?
        )
    ) AS subquery    
    ");
    $classement->execute([$joueur_id]);
    $classement = $classement->fetchColumn();

    $joueur_superieur = $pdo->prepare("
        SELECT j.*
        FROM joueurs j
        LEFT JOIN (
            SELECT joueur_id, SUM(points) AS total_points
            FROM points
            GROUP BY joueur_id
        ) p ON j.id = p.joueur_id
        WHERE (p.total_points > (SELECT SUM(points) FROM points WHERE joueur_id = ?) OR p.total_points IS NULL)
        AND j.id != ?
        ORDER BY p.total_points ASC, j.nom ASC
        LIMIT 1
    ");
    $joueur_superieur->execute([$joueur_id, $joueur_id]);
    $joueur_superieur = $joueur_superieur->fetch();

    $joueur_inferieur = $pdo->prepare("
        SELECT j.*
        FROM joueurs j
        LEFT JOIN (
            SELECT joueur_id, SUM(points) AS total_points
            FROM points
            GROUP BY joueur_id
        ) p ON j.id = p.joueur_id
        WHERE (p.total_points < (SELECT SUM(points) FROM points WHERE joueur_id = ?) OR p.total_points IS NULL)
        AND j.id != ?
        ORDER BY p.total_points DESC, j.nom DESC
        LIMIT 1
    ");
    $joueur_inferieur->execute([$joueur_id, $joueur_id]);
    $joueur_inferieur = $joueur_inferieur->fetch();

    $points_joueurs = $pdo->prepare("
        SELECT points.joueur_id, joueurs.nom, jours.date, points.points
        FROM points
        INNER JOIN joueurs ON points.joueur_id = joueurs.id
        INNER JOIN jours ON points.jour_id = jours.id
        ORDER BY jours.date ASC, joueurs.nom ASC
    ");
    $points_joueurs->execute();
    $points_joueurs = $points_joueurs->fetchAll();

} else {
    header('Location: index.php');
    exit();
}

?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Statistiques de <?= htmlspecialchars($joueur['nom']) ?></title>
    <link rel="stylesheet" href="css/bootstrap.min.css">
    <script src="js/Chart.min.js"></script>

</head>
<body>
    <div class="row justify-content-center align-items-center mt-4">
        <div class="col-auto">
            <img src="images/logo.png" alt="Logo" width="150" height="150">
        </div>
    </div>
    <div class="container">
        <h1 class="text-center my-4">Statistiques de <?= htmlspecialchars($joueur['nom']) ?></h1>
        <div class="row">
            <div class="col">
                <h4>Classement global : <?= $classement ?></h4>
            </div>
        </div>
        <div class="row">
            <div class="col">
                <h4>Joueur supérieur : <?= $joueur_superieur ? htmlspecialchars($joueur_superieur['nom']) : 'Aucun' ?></h4>
            </div>
        </div>
        <div class="row">
            <div class="col">
                <h4>Joueur inférieur : <?= $joueur_inferieur ? htmlspecialchars($joueur_inferieur['nom']) : 'Aucun' ?></h4>
            </div>
        </div>
        <?php if (count($points) > 0): ?>
            <canvas id="chart"></canvas>
            <script>
            const ctx = document.getElementById('chart').getContext('2d');
            const chart = new Chart(ctx, {
                type: 'line',
                data: {
                    labels: [<?php echo "'" . implode("', '", array_column($points, 'date')) . "'"; ?>],
                    datasets: [{
                        label: 'Points',
                        data: [<?php echo implode(", ", array_column($points, 'points')); ?>],
                        borderColor: 'rgba(75, 192, 192, 1)',
                        fill: false
                    }]
                },
                options: {
                    responsive: true,
                    scales: {
                        x: {
                            type: 'time',
                            time: {
                                unit: 'day'
                            },
                            title: {
                                display: true,
                                text: 'Date'
                            }
                        },
                        y: {
                            title: {
                                display: true,
                                text: 'Points'
                            }
                        }
                    },
                    plugins: {
                        title: {
                            display: true,
                            text: 'Points gagnés par <?= htmlspecialchars($joueur['nom']) ?>'
                        }
                    }
                }
            });
            </script>
        <?php else: ?>
            <p class="text-center">Aucune donnée disponible pour ce joueur.</p>
        <?php endif; ?>

        <a href="index.php" class="btn btn-secondary mt-4">Retour</a>
    </div>

    <script src="js/jquery-3.2.1.slim.min.js"></script>
    <script src="js/popper.min.js"></script>
    <script src="js/bootstrap.min.js"></script>
</body>
</html>