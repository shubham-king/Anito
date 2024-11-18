<?php
require 'tohost.php';

// Get animeId from the URL
if (!isset($_GET['animeId']) || empty($_GET['animeId'])) {
    die("Anime ID is required.");
}
include 'header.html'; 

$animeId = htmlspecialchars($_GET['animeId']);

// Endpoint for anime info
$endpoint = '/api/v2/hianime/anime/' . $animeId;
$apiUrl = BASE_API_URL . $endpoint;

// Fetch data from API
$response = file_get_contents($apiUrl);
$data = json_decode($response, true);

if (!$data || !$data['success']) {
    die("Failed to fetch anime information.");
}

$anime = $data['data']['anime']['info'];
$moreInfo = $data['data']['anime']['moreInfo'];
$recommendedAnimes = $data['data']['recommendedAnimes'];
$relatedAnimes = $data['data']['relatedAnimes'];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($anime['name']) ?> - Anime Info</title>
    <link rel="stylesheet" href="styles.css">
</head>
<body>

<main class="container">
    <!-- Anime Details -->
    <section class="section">
        <h2><?= htmlspecialchars($anime['name']) ?></h2>
        <div class="anime-info">
            <img src="<?= $anime['poster'] ?>" alt="<?= htmlspecialchars($anime['name']) ?>">
            <div>
                <p><strong>Type:</strong> <?= $anime['stats']['type'] ?></p>
                <p><strong>Rating:</strong> <?= $anime['stats']['rating'] ?></p>
                <p><strong>Quality:</strong> <?= $anime['stats']['quality'] ?></p>
                <p><strong>Duration:</strong> <?= $anime['stats']['duration'] ?></p>
                <p><strong>Episodes:</strong> Sub: <?= $anime['stats']['episodes']['sub'] ?> | Dub: <?= $anime['stats']['episodes']['dub'] ?></p>
                <p><strong>Description:</strong> <?= htmlspecialchars($anime['description']) ?></p>

                <!-- Link to Streaming Page -->
                <a href="streaming.php?animeId=<?= $animeId ?>" class="watch-btn">Watch Episodes</a>
            </div>
        </div>
    </section>
    

    <!-- Recommended Animes -->
    <section class="section">
        <h2>Recommended Animes</h2>
        <div class="anime-grid">
            <?php foreach ($recommendedAnimes as $anime): ?>
                <a href="anime-info.php?animeId=<?= $anime['id'] ?>" class="anime-card">
                    <img src="<?= $anime['poster'] ?>" alt="<?= htmlspecialchars($anime['name']) ?>">
                    <div class="card-info">
                        <h3><?= htmlspecialchars($anime['name']) ?></h3>
                    </div>
                </a>
            <?php endforeach; ?>
        </div>
    </section>

    <!-- Related Animes -->
    <section class="section">
        <h2>Related Animes</h2>
        <div class="anime-grid">
            <?php foreach ($relatedAnimes as $anime): ?>
                <a href="anime-info.php?animeId=<?= $anime['id'] ?>" class="anime-card">
                    <img src="<?= $anime['poster'] ?>" alt="<?= htmlspecialchars($anime['name']) ?>">
                    <div class="card-info">
                        <h3><?= htmlspecialchars($anime['name']) ?></h3>
                    </div>
                </a>
            <?php endforeach; ?>
        </div>
    </section>
</main>

<?php include 'footer.html'; ?>

</body>
</html>
