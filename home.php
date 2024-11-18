<?php
require 'tohost.php';

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
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Anime World - Home</title>
    <link rel="stylesheet" href="styles.css">
</head>
<body>

<?php include 'header.html'; ?>

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

<?php include 'footer.html'; ?>

</body>
</html>
