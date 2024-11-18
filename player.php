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

        .subtitles-container a {
            color: white;
            background-color: rgba(0, 0, 0, 0.5);
            padding: 5px 10px;
            margin: 5px;
            border-radius: 5px;
        }

        .subtitles-container a:hover {
            background-color: #45a049;
        }

        /* Subtitle overlay styling */
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
    </section>
   <center>WELCOME TO SIDDHARTHA ANIME SITE <br> SITE IS IN DEVELOPMENT</center>
</main>

<script>
    let video = document.getElementById('video-player');
    let currentCategory = '<?= htmlspecialchars($category) ?>'; // 'sub' or 'dub'
    let currentEpisodeId = '<?= htmlspecialchars($episodeId) ?>';
    let currentServer = '<?= htmlspecialchars($server) ?>';
    let hls = null;
    let introStart = <?= $introStart ?>;
    let introEnd = <?= $introEnd ?>;
    let outroStart = <?= $outroStart ?>;
    let outroEnd = <?= $outroEnd ?>;

    let subtitles = <?= json_encode($subtitles) ?>; // Available subtitles
    let currentSubtitleTrack = null;

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

            // Enable subtitle rendering if available
            hls.on(Hls.Events.SUBTITLE_TRACKS_UPDATED, function(event, data) {
                console.log('Subtitle tracks updated:', data);
                if (data.subtitleTracks.length > 0) {
                    currentSubtitleTrack = 0; // Set the first subtitle track as default (English)
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

    // Function to fetch subtitles and update display
    function fetchSubtitles(subtitles) {
        if (subtitles && subtitles.length > 0) {
            subtitles.forEach(sub => {
                if (sub.kind === 'captions' && sub.file) {
                    fetch(sub.file)
                        .then(response => response.text())
                        .then(vttData => {
                            displaySubtitles(vttData);
                        });
                }
            });
        }
    }

    // Display subtitles inside the player
    function displaySubtitles(vttData) {
        const subtitleOverlay = document.getElementById('subtitle-overlay');
        const currentTime = video.currentTime;
        const cue = getCueFromVtt(vttData, currentTime);

        if (cue) {
            subtitleOverlay.innerText = cue.text;
        } else {
            subtitleOverlay.innerText = ''; // Clear if no subtitle
        }
    }

    // Extract subtitle cue at a given time from VTT file
    function getCueFromVtt(vttData, currentTime) {
        const cues = vttData.split('\n\n');
        for (let i = 0; i < cues.length; i++) {
            let cue = cues[i].split('\n');
            if (cue.length > 1) {
                let timeRange = cue[0].split(' --> ');
                let startTime = parseVttTime(timeRange[0]);
                let endTime = parseVttTime(timeRange[1]);
                if (currentTime >= startTime && currentTime <= endTime) {
                    return { text: cue.slice(1).join(' ') };
                }
            }
        }
        return null;
    }

    // Convert VTT time format to seconds
    function parseVttTime(vttTime) {
        const timeParts = vttTime.split(':');
        const minutes = parseFloat(timeParts[0]) * 60;
        const seconds = parseFloat(timeParts[1].replace(',', '.'));
        return minutes + seconds;
    }

    // Function to update current time and check for intro/outro
    function updateCurrentTime() {
        const currentTime = video.currentTime;
        syncSubtitles(currentTime);
    }

    // Sync subtitles to the current time
    function syncSubtitles(currentTime) {
        let nextSubtitle = 'None';
        for (let i = 0; i < subtitles.length; i++) {
            let subtitle = subtitles[i];
            if (subtitle.kind === 'captions' && subtitle.file) {
                fetch(subtitle.file)
                    .then(response => response.text())
                    .then(vttData => {
                        let cue = getCueFromVtt(vttData, currentTime);
                        if (cue) {
                            nextSubtitle = cue.text;
                        }
                        document.getElementById('next-subtitle').innerText = nextSubtitle;
                    });
            }
        }
    }

    // Initialize video with the current episode source
    updateVideoSource('<?= $videoUrl ?>');
    fetchSubtitles(subtitles); // Pass subtitles to the player

    // Update current time and subtitle every time the video updates
    video.addEventListener('timeupdate', updateCurrentTime);
</script>

</body>
</html>
