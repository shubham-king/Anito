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
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Watching Episode <?= htmlspecialchars($episodeId) ?></title>
    <link rel="stylesheet" href="styles.css">
    <script src="https://cdn.jsdelivr.net/npm/hls.js@latest"></script>
    <style>
        /* General styles for the page */
body {
    font-family: Arial, sans-serif;
    background-color: #121212;
    color: #fff;
    margin: 0;
    padding: 0;
}

/* Main container */
main.container {
    max-width: 1200px;
    margin: auto;
    padding: 20px;
    text-align: center;
}

/* Video Player */
#player-container {
    position: relative;
    width: 100%;
    max-width: 800px;
    margin: auto;
    background: #333;
    padding: 10px;
    border-radius: 8px;
}

#video-player {
    width: 100%;
    height: 500px;
    border-radius: 8px;
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

/* Subtitle controls */
.subtitles-container {
    margin-top: 20px;
    text-align: center;
}

.subtitles-container a {
    color: white;
    background-color: rgba(0, 0, 0, 0.5);
    padding: 5px 10px;
    margin: 5px;
    border-radius: 5px;
    transition: background-color 0.3s ease;
}

.subtitles-container a:hover {
    background-color: #45a049;
}

/* Page Heading */
h1 {
    font-size: 2rem;
    margin-bottom: 20px;
}

/* Responsive Styles */
@media (max-width: 768px) {
    #video-player {
        height: 400px; /* Smaller video player on mobile */
    }

    h1 {
        font-size: 1.5rem; /* Adjust heading size for smaller screens */
    }

    /* Subtitle controls */
    .subtitles-container a {
        font-size: 0.8rem;
        padding: 4px 8px;
    }

    .subtitle-overlay {
        font-size: 1.2em; /* Smaller subtitle font for mobile */
    }
}

@media (max-width: 480px) {
    main.container {
        padding: 10px;
    }

    #video-player {
        height: 300px; /* Smaller video player on very small screens */
    }

    h1 {
        font-size: 1.2rem; /* Further reduce heading size */
    }

    .subtitle-overlay {
        font-size: 1em; /* Further reduce subtitle font */
    }
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

    <p>WELCOME TO SIDDHARTHA ANIME SITE <br> SITE IS IN DEVELOPMENT</p>
</main>

<script>
    // Video Player & Subtitle Sync Script
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

    function displaySubtitles(vttData) {
        const subtitleOverlay = document.getElementById('next-subtitle');
        const currentTime = video.currentTime;
        const cue = getCueFromVtt(vttData, currentTime);

        if (cue) {
            subtitleOverlay.innerText = cue.text;
        } else {
            subtitleOverlay.innerText = ''; // Clear if no subtitle
        }
    }

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

    function parseVttTime(vttTime) {
        const timeParts = vttTime.split(':');
        const minutes = parseFloat(timeParts[0]) * 60;
        const seconds = parseFloat(timeParts[1].replace(',', '.'));
        return minutes + seconds;
    }

    function updateCurrentTime() {
        const currentTime = video.currentTime;
        syncSubtitles(currentTime);
    }

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

    updateVideoSource('<?= $videoUrl ?>');
    fetchSubtitles(subtitles);
    video.addEventListener('timeupdate', updateCurrentTime);
</script>

<?php include 'footer.html'; ?>

</body>
</html>

