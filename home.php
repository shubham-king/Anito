<?php
require 'tohost.php';

// Base URL for API
define('BASE_API_URL', 'https://yourapi.com');

// Endpoint for the homepage data
$endpoint = '/api/v2/hianime/home';
$apiUrl = BASE_API_URL . $endpoint;

// Fetch data from API
$response = file_get_contents($apiUrl);
$data = json_decode($response, true);

if (!$data || !$data['success']) {
    die("Failed to fetch anime data.");
}

$data = $data['data'];

// Set the default date to today
$date = date('Y-m-d'); // Format: yyyy-mm-dd

// Fetch schedules from the API
$scheduleEndpoint = "/api/v2/hianime/schedule?date=$date";
$scheduleApiUrl = BASE_API_URL . $scheduleEndpoint;

$scheduleResponse = file_get_contents($scheduleApiUrl);
$scheduleData = json_decode($scheduleResponse, true);

if ($scheduleData && $scheduleData['success']) {
    $scheduledAnimes = $scheduleData['data']['scheduledAnimes'];
} else {
    $scheduledAnimes = []; // Fallback if the API request fails
}
include 'header.html';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Anime World - Home</title>
    <link rel="stylesheet" href="styles.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #1c1c1c;
            color: #fff;
            margin: 0;
            padding: 0;
        }

        header {
            background: #272727;
            padding: 20px;
            text-align: center;
        }

        header a {
            color: #fff;
            font-size: 24px;
            text-decoration: none;
        }

        .container {
            padding: 20px;
            max-width: 1200px;
            margin: auto;
        }

        .section {
            margin-bottom: 40px;
        }

        .section h2 {
            color: #ff9800;
            border-bottom: 2px solid #ff9800;
            padding-bottom: 10px;
            margin-bottom: 20px;
        }

        .anime-grid {
            display: flex;
            flex-wrap: wrap;
            gap: 20px;
        }

        .anime-card {
            background: #272727;
            border-radius: 8px;
            overflow: hidden;
            text-decoration: none;
            color: #fff;
            width: calc(25% - 20px);
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
            transition: transform 0.3s;
        }

        .anime-card:hover {
            transform: scale(1.05);
        }

        .anime-card img {
            width: 100%;
            height: auto;
            object-fit: cover;
        }

        .card-info {
            padding: 10px;
        }

        .card-info h3 {
            font-size: 18px;
            margin: 0;
        }

        .schedule-grid {
            display: flex;
            flex-wrap: wrap;
            gap: 20px;
        }

        .schedule-card {
            background: #333;
            padding: 15px;
            border-radius: 8px;
            width: calc(50% - 20px);
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
        }

        .schedule-card h3 {
            color: #ff9800;
            margin-bottom: 10px;
        }

        .view-details {
            display: inline-block;
            margin-top: 10px;
            padding: 10px 15px;
            background: #ff9800;
            color: #fff;
            text-decoration: none;
            border-radius: 4px;
            transition: background 0.3s;
        }

        .view-details:hover {
            background: #ff5722;
        }

        footer {
            text-align: center;
            background: #272727;
            padding: 20px;
            color: #fff;
            margin-top: 40px;
        }

        @media (max-width: 768px) {
            .anime-card, .schedule-card {
                width: calc(100% - 20px);
            }
        }
    </style>
</head>
<body>

<center>
<header>
   <center> <a href="https://github.com/Siddhartha6909/Aniwatch" color="blue">GitHub</a></center>
</header>
</center>
<main class="container">
    <!-- Latest Anime Episodes -->
    <section class="section">
        <h2>Latest Anime Episodes</h2>
        <div class="anime-grid">
            <?php foreach ($data['latestEpisodeAnimes'] as $anime): ?>
                <a href="anime-info.php?animeId=<?= $anime['id'] ?>" class="anime-card">
                    <img src="<?= $anime['poster'] ?>" alt="<?= htmlspecialchars($anime['name']) ?>">
                    <div class="card-info">
                        <h3><?= htmlspecialchars($anime['name']) ?></h3>
                        <p>Sub: <?= $anime['episodes']['sub'] ?> | Dub: <?= $anime['episodes']['dub'] ?></p>
                    </div>
                </a>
            <?php endforeach; ?>
        </div>
    </section>

    <!-- Spotlight Anime -->
    <section class="section">
        <h2>Spotlight Anime</h2>
        <div class="anime-grid">
            <?php foreach ($data['spotlightAnimes'] as $anime): ?>
                <a href="anime-info.php?animeId=<?= $anime['id'] ?>" class="anime-card">
                    <img src="<?= $anime['poster'] ?>" alt="<?= htmlspecialchars($anime['name']) ?>">
                    <div class="card-info">
                        <h3><?= htmlspecialchars($anime['name']) ?></h3>
                    </div>
                </a>
            <?php endforeach; ?>
        </div>
    </section>

    <!-- Estimated Schedules Section -->
    <section class="section">
        <h2>Estimated Schedules (<?= htmlspecialchars($date) ?>)</h2>
        <div class="schedule-grid">
            <?php if (!empty($scheduledAnimes)): ?>
                <?php foreach ($scheduledAnimes as $anime): ?>
                    <div class="schedule-card">
                        <h3><?= htmlspecialchars($anime['name']) ?></h3>
                        <p><strong>Japanese Name:</strong> <?= htmlspecialchars($anime['jname']) ?></p>
                        <p><strong>Airing Time:</strong> <?= htmlspecialchars($anime['time']) ?></p>
                        <p><strong>Seconds Until Airing:</strong> <?= $anime['secondsUntilAiring'] ?></p>
                        <a href="anime-info.php?animeId=<?= $anime['id'] ?>" class="view-details">View Details</a>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <p>No schedules available for <?= htmlspecialchars($date) ?>.</p>
            <?php endif; ?>
        </div>
    </section>

</main>

<footer>
    <p>&copy; <?= date('Y') ?> Anime World. All Rights Reserved.</p>
</footer>

</body>
</html>
