<?php
require 'tohost.php';

// Get anime episode ID from URL
if (!isset($_GET['episodeId']) || empty($_GET['episodeId'])) {
    die("Episode ID is required.");
}

$episodeId = htmlspecialchars($_GET['episodeId']);
$server = isset($_GET['server']) ? htmlspecialchars($_GET['server']) : 'hd-1'; // Default server
$category = isset($_GET['category']) ? htmlspecialchars($_GET['category']) : 'sub'; // Default category (sub)

// Endpoint to get episode sources
$sourceEndpoint = "/api/v2/hianime/episode/sources?animeEpisodeId={$episodeId}&server={$server}&category={$category}";
$sourceUrl = BASE_API_URL . $sourceEndpoint;

// Fetch data from API
$response = file_get_contents($sourceUrl);
$data = json_decode($response, true);

if (!$data || !$data['success']) {
    die("Failed to fetch episode sources.");
}

$videoUrl = $data['data']['sources'][0]['url']; // Use the first available source
$subtitles = $data['data']['tracks']; // Available subtitles
$introStart = $data['data']['intro']['start']; // Intro start time
$introEnd = $data['data']['intro']['end']; // Intro end time
$outroStart = $data['data']['outro']['start']; // Outro start time
$outroEnd = $data['data']['outro']['end']; // Outro end time
include 'header.html';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Watching Episode <?= htmlspecialchars($episodeId) ?></title>
    <link rel="stylesheet" href="styles.css">
    <script src="https://cdn.jsdelivr.net/npm/hls.js@latest"></script>
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #121212;
            color: #fff;
            margin: 0;
            padding: 0;
        }

        main.container {
            max-width: 1200px;
            margin: auto;
            padding: 20px;
            text-align: center;
        }

        #player-container {
            position: relative;
            width: 100%;
            max-width: 800px;
            margin: auto;
            background: #333;
            padding: 10px;
            border-radius: 8px;
            overflow: hidden;
        }

        #video-player {
            width: 100%;
            height: 500px;
            border-radius: 8px;
        }

        .caption-controls {
            position: absolute;
            top: 10px;
            right: 10px;
            background: rgba(0, 0, 0, 0.6);
            padding: 10px;
            border-radius: 5px;
            display: flex;
            align-items: center;
        }

        .caption-controls button {
            background: rgba(0, 0, 0, 0.5);
            color: white;
            border: none;
            padding: 8px 16px;
            margin-right: 10px;
            cursor: pointer;
            border-radius: 5px;
        }

        .caption-controls button:hover {
            background: #45a049;
        }

        .subtitle-overlay {
            position: absolute;
            bottom: 50px;
            width: 100%;
            text-align: center;
            font-size: 1.5em;
            color: white;
            text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.7);
            pointer-events: none;
            z-index: 9999;
        }
    </style>
</head>
<body>

<main class="container">
    <h1>Watching Episode <?= htmlspecialchars($episodeId) ?></h1>

    <section id="player-container">
        <!-- Video Player -->
        <video id="video-player" controls autoplay>
            Your browser does not support the video tag.
        </video>

        <!-- Subtitle overlay (inside player) -->
        <div id="next-subtitle" class="subtitle-overlay"></div>

        <!-- Caption controls (like YouTube) -->
        <div class="caption-controls">
            <button id="caption-toggle">Captions: On</button>
            <select id="caption-language">
                <?php foreach ($subtitles as $subtitle): ?>
                    <?php if ($subtitle['kind'] === 'captions'): ?>
                        <option value="<?= $subtitle['file'] ?>" <?= $subtitle['default'] ? 'selected' : '' ?>>
                            <?= $subtitle['label'] ?>
                        </option>
                    <?php endif; ?>
                <?php endforeach; ?>
            </select>
        </div>
    </section>

    <p>WELCOME TO SIDDHARTHA ANIME SITE <br> SITE IS IN DEVELOPMENT</p>
</main>

<script>
    let video = document.getElementById('video-player');
    let captionToggle = document.getElementById('caption-toggle');
    let captionLanguageSelect = document.getElementById('caption-language');
    let hls = null;
    let subtitles = <?= json_encode($subtitles) ?>;
    let currentSubtitleTrack = null;

    // Set the initial video source
    function updateVideoSource(url) {
        if (hls) {
            hls.destroy();
        }

        if (Hls.isSupported()) {
            hls = new Hls();
            hls.loadSource(url);
            hls.attachMedia(video);
            hls.on(Hls.Events.MANIFEST_PARSED, function() {
                video.play();
            });

            hls.on(Hls.Events.SUBTITLE_TRACKS_UPDATED, function(event, data) {
                if (data.subtitleTracks.length > 0) {
                    currentSubtitleTrack = 0;
                    hls.subtitleTrack = currentSubtitleTrack;
                }
            });
        } else if (video.canPlayType('application/vnd.apple.mpegurl')) {
            video.src = url;
            video.addEventListener('loadedmetadata', function() {
                video.play();
            });
        }
    }

    // Toggle captions on/off
    captionToggle.addEventListener('click', function() {
        if (video.textTracks[0]) {
            const track = video.textTracks[0];
            track.mode = track.mode === 'showing' ? 'hidden' : 'showing';
            captionToggle.textContent = track.mode === 'showing' ? 'Captions: On' : 'Captions: Off';
        }
    });

    // Change subtitle language
    captionLanguageSelect.addEventListener('change', function() {
        let selectedFile = captionLanguageSelect.value;
        let track = video.addTextTrack('subtitles', 'English', 'en');
        track.mode = 'showing';
        track.src = selectedFile;
    });

    // Initial video setup
    updateVideoSource('<?= $videoUrl ?>');
</script>

<?php include 'footer.html'; ?>

</body>
</html>
