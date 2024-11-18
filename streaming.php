<?php
require 'tohost.php';

if (!isset($_GET['animeId']) || empty($_GET['animeId'])) {
    die("Anime ID is required.");
}
include 'header.html'; 

$animeId = htmlspecialchars($_GET['animeId']);

// Endpoint for fetching episodes
$endpoint = '/api/v2/hianime/anime/' . $animeId . '/episodes';
$apiUrl = BASE_API_URL . $endpoint;

// Fetch data from API
$response = file_get_contents($apiUrl);
$data = json_decode($response, true);

if (!$data || !$data['success']) {
    die("Failed to fetch episodes.");
}

$episodes = $data['data']['episodes'];
$totalEpisodes = $data['data']['totalEpisodes'];

// Group episodes into sections of 100
$groupedEpisodes = [];
foreach ($episodes as $episode) {
    $groupIndex = floor(($episode['number'] - 1) / 100);
    $groupedEpisodes[$groupIndex][] = $episode;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Streaming - <?= htmlspecialchars($animeId) ?></title>
    <link rel="stylesheet" href="streaming.css">
</head>
<body>

<main class="container">
    <h1>Streaming Episodes for <?= htmlspecialchars($animeId) ?></h1>

    <?php foreach ($groupedEpisodes as $groupIndex => $group): ?>
        <section class="episode-section">
            <h2 class="collapsible" data-target="group-<?= $groupIndex ?>">
                Episodes <?= $groupIndex * 100 + 1 ?> - <?= min(($groupIndex + 1) * 100, $totalEpisodes) ?>
            </h2>
            <div id="group-<?= $groupIndex ?>" class="episode-grid hidden">
                <?php foreach ($group as $episode): ?>
                    <a href="player.php?episodeId=<?= $episode['episodeId'] ?>" class="episode-card">
                        <div class="episode-number">Episode <?= $episode['number'] ?></div>
                        <div class="episode-title"><?= htmlspecialchars($episode['title']) ?></div>
                        <?php if ($episode['isFiller']): ?>
                            <span class="filler-tag">Filler</span>
                        <?php endif; ?>
                    </a>
                <?php endforeach; ?>
            </div>
        </section>
    <?php endforeach; ?>

</main>

<script>
    // JavaScript to toggle visibility of episode groups
    document.querySelectorAll('.collapsible').forEach(header => {
        header.addEventListener('click', () => {
            const targetId = header.getAttribute('data-target');
            const target = document.getElementById(targetId);
            target.classList.toggle('hidden');
        });
    });
</script>

<?php include 'footer.html'; ?>

</body>
</html>
