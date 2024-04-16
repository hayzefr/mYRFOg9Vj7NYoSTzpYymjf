<?php
require_once 'config.php';

$pdo = new PDO("mysql:host=$host;dbname=$db", $user, $pass);

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

// Récupérer le classement global du joueur
$classement = $pdo->prepare("
    SELECT COUNT(*) + 1 AS rang
    FROM (
        SELECT joueur_id, SUM(points) AS total_points
        FROM points
        GROUP BY joueur_id
        HAVING total_points > (
            SELECT SUM(points)
            FROM points
            WHERE joueur_id = ?
        )
    ) AS subquery
");
$classement->execute([$joueur_id]);
$classement = $classement->fetchColumn();


    // Récupérer le joueur supérieur (celui qui a immédiatement plus de points)
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

    // Récupérer le joueur inférieur (celui qui a immédiatement moins de points)
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
    <!-- Inclure les fichiers CSS de Bootstrap à partir d'un CDN -->
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.0.0/css/bootstrap.min.css" integrity="sha384-Gn5384xqQ1aoWXA+058RXPxPg6fy4IWvTNh0E263XmFcJlSAwiGgFAW/dAiS6JXm" crossorigin="anonymous">
    <script src="https://cdn.jsdelivr.net/npm/chart.js@2.9.4/dist/Chart.min.js"></script>

</head>
<body>
        <div class="row justify-content-center align-items-center mt-4">
            <div class="col-auto">
                <!-- Logo centré -->
                <img src="/images/logo.png" alt="Logo" width="150" height="150">
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
<br>
<h1 class="text-center my-4">Statistiques global</h1>
            <canvas id="chartAllPlayers"></canvas>
           
            <script>
const ctxAllPlayers = document.getElementById('chartAllPlayers').getContext('2d');

// Créer un objet pour stocker les données des joueurs
const playerData = <?= json_encode(array_fill_keys(array_unique(array_column($points_joueurs, 'nom')), [])); ?>;

// Parcourir les résultats et organiser les données par joueur
<?php foreach ($points_joueurs as $point_joueur): ?>
playerData['<?= htmlspecialchars($point_joueur['nom']) ?>'].push({
    x: '<?= htmlspecialchars($point_joueur['date']) ?>',
    y: <?= htmlspecialchars($point_joueur['points']) ?>,
    label: '<?= htmlspecialchars($point_joueur['nom']) ?>' // Ajouter la propriété label
});
<?php endforeach; ?>

// Créer les datasets pour le graphique
const datasets = [];

// Ajouter un dataset pour chaque joueur
<?php foreach (array_unique(array_column($points_joueurs, 'nom')) as $joueur): ?>
datasets.push({
    label: '<?= htmlspecialchars($joueur) ?>',
    data: playerData['<?= htmlspecialchars($joueur) ?>'],
    borderColor: 'rgba(<?= rand(0, 255) ?>, <?= rand(0, 255) ?>, <?= rand(0, 255) ?>, 1)',
    fill: false,
    tension: 0.4,
    point: {
        radius: 3,
        hitRadius: 10,
        hoverRadius: 6,
        hoverBorderWidth: 3
    },
    tooltip: {
        callbacks: {
            title: function(context) {
                return context[0].label; // Afficher le nom du joueur dans le titre de l'info-bulle
            },
            label: function(context) {
                const dataset = context.dataset;
                const dataIndex = context.dataIndex;
                return 'Date: ' + dataset.data[dataIndex].x + ', Points: ' + dataset.data[dataIndex].y;
            }
        }
    }
});
<?php endforeach; ?>

const chartAllPlayers = new Chart(ctxAllPlayers, {
    type: 'line',
    data: {
        datasets: datasets
    },
    options: {
        responsive: true,
        scales: {
            x: {
                type: 'time',
                time: {
                    unit: 'day',
                    displayFormats: {
                        day: 'MMM DD, YYYY' // Format des dates sur l'axe des abscisses
                    },
                    parser: 'YYYY-MM-DD' // Ajouter le parseur pour analyser les dates au format 'YYYY-MM-DD'
                },
                title: {
                    display: true,
                    text: 'Dates'
                },
                display: true,
                ticks: {
                    source: 'data', // Utiliser les données pour générer les étiquettes de l'axe des abscisses
                    autoSkip: true,
                    maxRotation: 0
                }
            },
            y: {
                title: {
                    display: true,
                    text: 'Points gagnés'
                }
            }
        },
        plugins: {
            title: {
                display: true,
                text: 'Points gagnés par tous les joueurs'
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

    <!-- Inclure les fichiers JS de Bootstrap à partir d'un CDN -->
    <script src="https://code.jquery.com/jquery-3.2.1.slim.min.js" integrity="sha384-KJ3o2DKtIkvYIK3UENzmM7KCkRr/rE9/Qpg6aAZGJwFDMVNA/GpGFF93hXpG5KkN" crossorigin="anonymous"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/popper.js/1.12.9/umd/popper.min.js" integrity="sha384-ApNbgh9B+Y1QKtv3Rn7W3mgPxhU9K/ScQsAP7hUibX39j7fakFPskvXusvfa0b4Q" crossorigin="anonymous"></script>
    <script src="https://maxcdn.bootstrapcdn.com/bootstrap/4.0.0/js/bootstrap.min.js" integrity="sha384-JZR6Spejh4U02d8jOt6vLEHfe/JQGiRRSQQxSfFWpi1MquVdAyjUar5+76PVCmYl" crossorigin="anonymous"></script>
</body>
</html>