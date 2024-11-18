<?php
require 'tohost.php';

// Get the query and optional filters
$query = isset($_GET['q']) ? htmlspecialchars($_GET['q']) : '';
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;

// Build the API URL
$endpoint = '/api/v2/hianime/search';
$apiUrl = BASE_API_URL . $endpoint . '?q=' . urlencode($query) . '&page=' . $page;

// Fetch search results
$response = file_get_contents($apiUrl);
$data = json_decode($response, true);

if (!$data || !$data['success']) {
    die("Failed to fetch search results.");
}

$searchResults = $data['data']['animes'];
$currentPage = $data['data']['currentPage'];
$totalPages = $data['data']['totalPages'];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Search Results - Anime World</title>
    <link rel="stylesheet" href="styles.css">
</head>
<body>

<?php include 'header.html'; ?>

<main class="container">
    <section class="section">
        <h2>Search Results for "<?= htmlspecialchars($query) ?>"</h2>

        <?php if (empty($searchResults)): ?>
            <p>No results found. Try another search query.</p>
        <?php else: ?>
            <div class="anime-grid">
                <?php foreach ($searchResults as $anime): ?>
                    <a href="anime-info.php?animeId=<?= $anime['id'] ?>" class="anime-card">
                        <img src="<?= $anime['poster'] ?>" alt="<?= htmlspecialchars($anime['name']) ?>">
                        <div class="card-info">
                            <h3><?= htmlspecialchars($anime['name']) ?></h3>
                            <p>Type: <?= $anime['type'] ?></p>
                            <p>Rating: <?= $anime['rating'] ?></p>
                        </div>
                    </a>
                <?php endforeach; ?>
            </div>

            <!-- Pagination -->
            <div class="pagination">
                <?php if ($currentPage > 1): ?>
                    <a href="search.php?q=<?= urlencode($query) ?>&page=<?= $currentPage - 1 ?>">Previous</a>
                <?php endif; ?>
                <span>Page <?= $currentPage ?> of <?= $totalPages ?></span>
                <?php if ($currentPage < $totalPages): ?>
                    <a href="search.php?q=<?= urlencode($query) ?>&page=<?= $currentPage + 1 ?>">Next</a>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </section>
</main>

<footer>
    <p>&copy; 2024 Anime World. Powered by Tohost Cloud Services.</p>
</footer>

</body>
</html>
