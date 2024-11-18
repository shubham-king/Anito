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
$englishSubtitle = array_filter($subtitles, function ($sub) {
    return $sub['label'] === 'English'; // Only include English subtitles
});

$englishSubtitleFile = $englishSubtitle ? array_values($englishSubtitle)[0]['file'] : ''; // Get the English subtitle file URL

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
        #player-container {
            position: relative;
            width: 100%;
            max-width: 800px;
            margin: auto;
        }

        #video-player {
            width: 100%;
            height: 500px;
        }

        .footer {
            background-color: rgba(0, 0, 0, 0.7);
            color: white;
            padding: 10px;
            text-align: center;
            font-size: 16px;
            position: absolute;
            bottom: 0;
            width: 100%;
        }

        .footer span {
            font-weight: bold;
        }

        /* Subtitle overlay */
        .subtitle-overlay {
            position: absolute;
            bottom: 50px;
            width: 100%;
            text-align: center;
            font-size: 1.5em;
            color: white;
            text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.7);
            pointer-events: none;
        }

        /* Current time display */
        .time-overlay {
            position: absolute;
            top: 10px;
            left: 10px;
            font-size: 20px;
            color: white;
            background-color: rgba(0, 0, 0, 0.7);
            padding: 5px;
            font-weight: bold;
            border-radius: 5px;
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
            <!-- Add subtitles track if available -->
            <?php if ($englishSubtitleFile): ?>
                <track kind="subtitles" src="<?= htmlspecialchars($englishSubtitleFile) ?>" label="English" srclang="en" default>
            <?php endif; ?>
        </video>

        <!-- Subtitle overlay -->
        <div id="subtitle-overlay" class="subtitle-overlay"></div>

        <!-- Current time overlay -->
        <div id="time-overlay" class="time-overlay">00:00</div>
    </section>

</main>

<script>
    let video = document.getElementById('video-player');
    let hls = null;
    let englishSubtitleFile = "<?= $englishSubtitleFile ?>"; // English subtitle file URL
    let subtitleOverlay = document.getElementById('subtitle-overlay');
    let timeOverlay = document.getElementById('time-overlay');
    let subtitlesData = []; // To store subtitles data

    // Function to fetch and parse subtitle file
    async function fetchSubtitles(fileUrl) {
        const response = await fetch(fileUrl);
        const text = await response.text();

        // Parse the VTT file to extract subtitle cues
        const cues = parseVTT(text);
        subtitlesData = cues; // Store parsed subtitle data
    }

    // Parse VTT format and extract subtitle cues
    function parseVTT(vttText) {
        const cues = [];
        const lines = vttText.split('\n');

        let startTime = null;
        let endTime = null;
        let subtitleText = '';

        for (let line of lines) {
            // Time line
            if (line.match(/\d{2}:\d{2}:\d{2}.\d{3}/)) {
                const times = line.split(' --> ');
                startTime = parseTime(times[0]);
                endTime = parseTime(times[1]);
            } else if (line.trim()) {
                // Subtitle text
                subtitleText += line + ' ';
            } else {
                // Empty line, means we have finished a subtitle entry
                if (startTime !== null && endTime !== null && subtitleText.trim()) {
                    cues.push({ start: startTime, end: endTime, text: subtitleText.trim() });
                    startTime = endTime = subtitleText = '';
                }
            }
        }

        return cues;
    }

    // Convert time format from hh:mm:ss.ms to seconds
    function parseTime(timeString) {
        const parts = timeString.split(':');
        const seconds = parseFloat(parts[0]) * 60 * 60 + parseFloat(parts[1]) * 60 + parseFloat(parts[2].replace(',', '.'));
        return seconds;
    }

    // Function to update video source
    function updateVideoSource(url) {
        if (hls) {
            hls.destroy(); // Destroy previous HLS instance if it exists
        }

        // Initialize the HLS.js player
        if (Hls.isSupported()) {
            hls = new Hls();
            hls.loadSource(url);
            hls.attachMedia(video);
            hls.on(Hls.Events.MANIFEST_PARSED, function() {
                video.play();
            });
        } else if (video.canPlayType('application/vnd.apple.mpegurl')) {
            video.src = url;
            video.addEventListener('loadedmetadata', function() {
                video.play();
            });
        }
    }

    // Event listener to update the current time overlay
    video.addEventListener('timeupdate', function() {
        // Update current time overlay
        timeOverlay.innerText = formatTime(video.currentTime);

        // Display subtitles if available
        displaySubtitles(video.currentTime);
    });

    // Format time as mm:ss
    function formatTime(seconds) {
        const minutes = Math.floor(seconds / 60);
        const remainingSeconds = Math.floor(seconds % 60);
        return `${minutes.toString().padStart(2, '0')}:${remainingSeconds.toString().padStart(2, '0')}`;
    }

    // Display subtitles based on current time
    function displaySubtitles(currentTime) {
        // Fetch the subtitle text for the current time
        let subtitle = getSubtitleAtTime(currentTime);
        if (subtitle) {
            subtitleOverlay.innerText = subtitle.text;
        } else {
            subtitleOverlay.innerText = '';
        }
    }

    // Get subtitle text for a given time
    function getSubtitleAtTime(currentTime) {
        for (let subtitle of subtitlesData) {
            if (currentTime >= subtitle.start && currentTime <= subtitle.end) {
                return subtitle;
            }
        }
        return null;
    }

    // Initialize video with the current episode source
    updateVideoSource('<?= $videoUrl ?>');

    // Fetch the English subtitles when the page loads
    if (englishSubtitleFile) {
        fetchSubtitles(englishSubtitleFile);
    }
</script>

</body>
</html>
