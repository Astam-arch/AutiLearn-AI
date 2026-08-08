<?php
// ai/recommendations.php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';

// Session & Role Guard
if (!isset($_SESSION['user_id'])) {
    $loginUrl = defined('BASE_URL') ? BASE_URL . 'login.php' : '../login.php';
    header("Location: {$loginUrl}");
    exit;
}

$studentId = $_SESSION['user_id'];
$dashboardUrl = defined('BASE_URL') ? BASE_URL . 'student/dashboard.php' : '../student/dashboard.php';

// Handle AJAX POST requests for recording usage analytics & emotional pattern tracking
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json');
    $input = json_decode(file_get_contents('php://input'), true);
    
    $actionType = $input['action_type'] ?? 'pecs_build';
    $selectedEmotion = $input['emotion'] ?? 'neutral';
    $sentenceUsed = $input['sentence'] ?? '';

    // Log tracking data securely into database if table exists or simulate response
    try {
        // Optional: Save analytics record for teacher/parent monitoring
        echo json_encode([
            'status' => 'success',
            'message' => 'AI adaptation updated successfully',
            'suggested_next' => ($selectedEmotion === 'Sad' || selectedEmotion === 'Anxious') ? ['Calm', 'Break', 'Water'] : ['Play', 'Read Book', 'Happy']
        ]);
    } catch (Exception $e) {
        echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
    }
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>AI Real-Time Emotion & Adaptive Recommendations | <?php echo defined('SITE_NAME') ? SITE_NAME : 'AutiLearn AI'; ?></title>
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- FontAwesome Icons -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" rel="stylesheet">
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Fredoka:wght@400;600;700&family=Poppins:wght@400;500;600&display=swap" rel="stylesheet">

    <style>
        :root {
            --bg-soft: #f4f8fb;
            --primary-blue: #2563eb;
        }

        body {
            background-color: var(--bg-soft);
            font-family: 'Poppins', sans-serif;
            color: #1e293b;
            padding-bottom: 90px;
        }

        h1, h2, h3, h4, .brand-font {
            font-family: 'Fredoka', cursive, sans-serif;
        }

        .navbar-ai {
            background: #ffffff;
            border-bottom: 2px solid #e2e8f0;
            padding: 14px 0;
            box-shadow: 0 4px 12px rgba(0,0,0,0.02);
        }

        .ai-card {
            background: #ffffff;
            border: 2px solid #e2e8f0;
            border-radius: 20px;
            padding: 24px;
            box-shadow: 0 10px 25px rgba(0,0,0,0.04);
            transition: all 0.3s ease;
        }

        .ai-card:hover {
            border-color: var(--primary-blue);
            box-shadow: 0 15px 35px rgba(37, 99, 235, 0.08);
        }

        .emotion-pill {
            cursor: pointer;
            border-radius: 50px;
            padding: 10px 20px;
            font-weight: 600;
            transition: all 0.2s ease;
            border: 2px solid transparent;
        }

        .emotion-pill:hover {
            transform: translateY(-3px) scale(1.04);
        }

        .emotion-pill.active {
            border-color: #1e293b;
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
        }

        .recommendation-chip {
            background: #eff6ff;
            border: 2px solid #bfdbfe;
            color: #1e40af;
            border-radius: 14px;
            padding: 12px 18px;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 10px;
            cursor: pointer;
            transition: all 0.2s ease;
        }

        .recommendation-chip:hover {
            background: #dbeafe;
            transform: translateY(-2px);
        }
    </style>
</head>
<body>

<!-- NAVIGATION BAR -->
<nav class="navbar navbar-ai sticky-top mb-4">
    <div class="container">
        <a class="navbar-brand brand-font fs-3 text-primary d-flex align-items-center gap-2" href="<?php echo htmlspecialchars($dashboardUrl); ?>">
            <i class="fa-solid fa-arrow-left fs-4 me-1 text-secondary"></i>
            <i class="fa-solid fa-brain text-primary fs-2"></i> AI Adaptive Engine & Recommendations
        </a>
        <a href="<?php echo htmlspecialchars($dashboardUrl); ?>" class="btn btn-outline-secondary rounded-pill px-4 fw-semibold shadow-sm">
            Dashboard
        </a>
    </div>
</nav>

<div class="container">

    <!-- REAL-TIME EMOTION DETECTOR / SELECTOR -->
    <div class="ai-card mb-4">
        <div class="d-flex align-items-center gap-2 mb-3">
            <i class="fa-solid fa-face-smile-beam fs-3 text-danger"></i>
            <h3 class="m-0 fw-bold text-dark">Real-Time Emotion State Analyzer</h3>
        </div>
        <p class="text-muted small mb-3">Select your current emotional state so the AI adaptive engine can tailor communication recommendations and calm down tools dynamically.</p>
        
        <div class="d-flex flex-wrap gap-3" id="emotionContainer">
            <button class="btn btn-success-subtle text-success emotion-pill active" onclick="setEmotion('Happy', this)">
                <i class="fa-solid fa-face-smile me-1"></i> Happy
            </button>
            <button class="btn btn-primary-subtle text-primary emotion-pill" onclick="setEmotion('Calm', this)">
                <i class="fa-solid fa-cloud-sun me-1"></i> Calm
            </button>
            <button class="btn btn-info-subtle text-info emotion-pill" onclick="setEmotion('Tired', this)">
                <i class="fa-solid fa-face-tired me-1"></i> Tired
            </button>
            <button class="btn btn-warning-subtle text-warning emotion-pill" onclick="setEmotion('Anxious', this)">
                <i class="fa-solid fa-face-grimace me-1"></i> Anxious
            </button>
            <button class="btn btn-danger-subtle text-danger emotion-pill" onclick="setEmotion('Sad', this)">
                <i class="fa-solid fa-face-frown me-1"></i> Sad
            </button>
            <button class="btn btn-dark-subtle text-secondary emotion-pill" onclick="setEmotion('Overwhelmed', this)">
                <i class="fa-solid fa-burst me-1"></i> Overwhelmed
            </button>
        </div>
    </div>

    <!-- AI DYNAMIC RECOMMENDATIONS CONTAINER -->
    <div class="ai-card mb-4">
        <div class="d-flex align-items-center justify-content-between mb-3">
            <div class="d-flex align-items-center gap-2">
                <i class="fa-solid fa-wand-magic-sparkles fs-3 text-primary"></i>
                <h3 class="m-0 fw-bold text-dark">Smart Adaptive Suggestions</h3>
            </div>
            <span class="badge bg-primary text-white rounded-pill px-3 py-2">Live AI Sync</span>
        </div>
        <p class="text-muted small mb-3">Based on your current emotion state (<strong id="activeEmotionLabel">Happy</strong>) and usage activity, the AI recommends these quick phrases:</p>
        
        <div id="recommendationsList" class="row g-3">
            <!-- Dynamically populated recommendations -->
        </div>
    </div>

    <!-- SESSION INSIGHTS ANALYTICS -->
    <div class="ai-card">
        <div class="d-flex align-items-center gap-2 mb-3">
            <i class="fa-solid fa-chart-line fs-3 text-success"></i>
            <h3 class="m-0 fw-bold text-dark">Session Learning Insights</h3>
        </div>
        <div class="row g-4 text-center">
            <div class="col-md-4">
                <div class="p-3 bg-light rounded-4">
                    <span class="text-muted small d-block">Sentences Built Today</span>
                    <h2 class="fw-bold text-primary mt-1 mb-0" id="statSentences">12</h2>
                </div>
            </div>
            <div class="col-md-4">
                <div class="p-3 bg-light rounded-4">
                    <span class="text-muted small d-block">Primary Emotion Logged</span>
                    <h2 class="fw-bold text-success mt-1 mb-0" id="statEmotion">Calm</h2>
                </div>
            </div>
            <div class="col-md-4">
                <div class="p-3 bg-light rounded-4">
                    <span class="text-muted small d-block">AI Assistance Level</span>
                    <h2 class="fw-bold text-warning mt-1 mb-0">Optimal</h2>
                </div>
            </div>
        </div>
    </div>

</div>

<!-- Bootstrap 5 JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

<script>
    let currentEmotion = 'Happy';

    const recommendationsMap = {
        'Happy': [
            { text: 'I want Play', icon: 'fa-gamepad', bg: '#f3e8ff', color: '#7e22ce' },
            { text: 'I want Listen Music', icon: 'fa-headphones', bg: '#e0e7ff', color: '#4338ca' },
            { text: 'Thank you', icon: 'fa-face-smile-beam', bg: '#dcfce7', color: '#166534' }
        ],
        'Calm': [
            { text: 'I want Read Book', icon: 'fa-book-open', bg: '#fef3c7', color: '#a16207' },
            { text: 'I want Draw', icon: 'fa-palette', bg: '#fce7f3', color: '#be185d' },
            { text: 'I see Park', icon: 'fa-tree-city', bg: '#dcfce7', color: '#166534' }
        ],
        'Tired': [
            { text: 'I need Sleep', icon: 'fa-bed', bg: '#e0e7ff', color: '#3730a3' },
            { text: 'I want Break', icon: 'fa-mug-hot', bg: '#fef3c7', color: '#b45309' },
            { text: 'I need Water', icon: 'fa-glass-water', bg: '#e0f2fe', color: '#0369a1' }
        ],
        'Anxious': [
            { text: 'I need Help', icon: 'fa-life-ring', bg: '#fee2e2', color: '#b91c1c' },
            { text: 'I want Mom', icon: 'fa-person-dress', bg: '#fce7f3', color: '#9d174d' },
            { text: 'I need Break', icon: 'fa-mug-hot', bg: '#fef3c7', color: '#b45309' }
        ],
        'Sad': [
            { text: 'I want Hug', icon: 'fa-hands-holding-child', bg: '#dcfce7', color: '#15803d' },
            { text: 'I need Teacher', icon: 'fa-chalkboard-user', bg: '#fef3c7', color: '#92400e' },
            { text: 'I want Listen Music', icon: 'fa-headphones', bg: '#e0e7ff', color: '#4338ca' }
        ],
        'Overwhelmed': [
            { text: 'Stop', icon: 'fa-hand', bg: '#fee2e2', color: '#b91c1c' },
            { text: 'I need Break', icon: 'fa-mug-hot', bg: '#fef3c7', color: '#b45309' },
            { text: 'I want Home', icon: 'fa-house', bg: '#e0f2fe', color: '#0369a1' }
        ]
    };

    function setEmotion(emotionName, btnElement) {
        currentEmotion = emotionName;
        document.getElementById('activeEmotionLabel').textContent = emotionName;
        document.getElementById('statEmotion').textContent = emotionName;

        // Update active class styles on buttons
        document.querySelectorAll('.emotion-pill').forEach(btn => btn.classList.remove('active'));
        btnElement.classList.add('active');

        // Render adaptive recommendations
        renderRecommendations();

        // Dispatch background sync request
        fetch('recommendations.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ action_type: 'emotion_update', emotion: emotionName })
        }).then(res => res.json()).catch(err => console.log('Sync note:', err));
    }

    function renderRecommendations() {
        const container = document.getElementById('recommendationsList');
        container.innerHTML = '';

        let recs = recommendationsMap[currentEmotion] || recommendationsMap['Happy'];

        recs.forEach(rec => {
            const col = document.createElement('div');
            col.className = 'col-md-4';
            col.innerHTML = `
                <div class="recommendation-chip shadow-sm" style="background-color: ${rec.bg}; color: ${rec.color}; border-color: ${rec.color}44;" onclick="speakRecommendation('${rec.text}')">
                    <i class="fa-solid ${rec.icon} fs-4"></i>
                    <span class="fs-6 fw-bold">${rec.text}</span>
                </div>
            `;
            container.appendChild(col);
        });
    }

    function speakRecommendation(phrase) {
        if ('speechSynthesis' in window) {
            window.speechSynthesis.cancel();
            const utterance = new SpeechSynthesisUtterance(phrase);
            utterance.rate = 0.9;
            window.speechSynthesis.speak(utterance);
        }
    }

    // Initial render call on page load
    renderRecommendations();
</script>
</body>
</html>