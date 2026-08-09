<?php
// student/pecs.php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/activity_tracking.php';

// Session & Role Guard
if (!isset($_SESSION['user_id'])) {
    $loginUrl = defined('BASE_URL') ? BASE_URL . 'login.php' : '../login.php';
    header("Location: {$loginUrl}");
    exit;
}

if (isset($_SESSION['role']) && $_SESSION['role'] !== 'student') {
    $role = $_SESSION['role'];
    $dashboardUrl = defined('BASE_URL') ? BASE_URL . "{$role}/dashboard.php" : "../{$role}/dashboard.php";
    header("Location: {$dashboardUrl}");
    exit;
}

$studentId = $_SESSION['user_id'];
$studentName = $_SESSION['full_name'] ?? 'Learner';
$dashboardUrl = defined('BASE_URL') ? BASE_URL . 'student/dashboard.php' : 'dashboard.php';

// Handle AJAX Request to Save PECS Activity & Sentence Building
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_SERVER['HTTP_CONTENT_TYPE']) && strpos($_SERVER['HTTP_CONTENT_TYPE'], 'application/json') !== false) {
    $rawInput = file_get_contents('php://input');
    $data = json_decode($rawInput, true);

    if (isset($data['action']) && $data['action'] === 'save_pecs_attempt') {
        header('Content-Type: application/json');
        
        $sentence = trim($data['sentence'] ?? '');
        $isVerified = intval($data['is_verified'] ?? 0);
        $cardCount = intval($data['card_count'] ?? 0);
        $emotionState = trim($data['emotion_state'] ?? 'Happy');

        if (!empty($sentence)) {
            try {
                $stars = $isVerified ? 2 : 1;
                recordStudentActivity($pdo, (int)$studentId, 'pecs', 'PECS Sentence Builder', 'Built a ' . $cardCount . '-card sentence: “' . $sentence . '”', 2, $stars, 'fa-icons', '#6366f1');
                echo json_encode(['status' => 'success', 'message' => 'PECS progress saved successfully!']);
            } catch (Exception $e) {
                echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
            }
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Empty sentence data']);
        }
        exit;
    }
}

// Expanded PECS Categories & Cards with Strict Grammar and Target Types
$pecsData = [
    'starters' => [
        'title' => '1. Sentence Starters',
        'icon'  => 'fa-comment-dots',
        'color' => '#2563eb',
        'cards' => [
            ['id' => 's1', 'text' => 'I want', 'type' => 'starter', 'target_categories' => ['needs', 'actions', 'places'], 'icon' => 'fa-hand-holding-hand', 'bg' => '#dbeafe', 'color' => '#1e40af'],
            ['id' => 's2', 'text' => 'I feel', 'type' => 'starter', 'target_categories' => ['feelings'], 'icon' => 'fa-heart', 'bg' => '#fce7f3', 'color' => '#9d174d'],
            ['id' => 's3', 'text' => 'I need', 'type' => 'starter', 'target_categories' => ['needs', 'actions'], 'icon' => 'fa-circle-exclamation', 'bg' => '#fee2e2', 'color' => '#991b1b'],
            ['id' => 's4', 'text' => 'I see', 'type' => 'starter', 'target_categories' => ['needs', 'feelings', 'actions', 'people', 'places'], 'icon' => 'fa-eye', 'bg' => '#e0e7ff', 'color' => '#3730a3'],
            ['id' => 's5', 'text' => 'Please', 'type' => 'starter', 'target_categories' => ['needs', 'actions'], 'icon' => 'fa-hands-praying', 'bg' => '#fef3c7', 'color' => '#92400e'],
            ['id' => 's6', 'text' => 'Thank you', 'type' => 'standalone', 'target_categories' => [], 'icon' => 'fa-face-smile-beam', 'bg' => '#dcfce7', 'color' => '#166534'],
        ]
    ],
    'needs' => [
        'title' => '2. Basic Needs & Objects',
        'icon'  => 'fa-hand-sparkles',
        'color' => '#0d9488',
        'cards' => [
            ['id' => 'n1', 'text' => 'Water', 'type' => 'object', 'category' => 'needs', 'icon' => 'fa-glass-water', 'bg' => '#e0f2fe', 'color' => '#0369a1'],
            ['id' => 'n2', 'text' => 'Food', 'type' => 'object', 'category' => 'needs', 'icon' => 'fa-utensils', 'bg' => '#ffedd5', 'color' => '#c2410c'],
            ['id' => 'n3', 'text' => 'Bathroom', 'type' => 'object', 'category' => 'needs', 'icon' => 'fa-restroom', 'bg' => '#f3e8ff', 'color' => '#6b21a8'],
            ['id' => 'n4', 'text' => 'Help', 'type' => 'object', 'category' => 'needs', 'icon' => 'fa-life-ring', 'bg' => '#fee2e2', 'color' => '#b91c1c'],
            ['id' => 'n5', 'text' => 'Break', 'type' => 'object', 'category' => 'needs', 'icon' => 'fa-mug-hot', 'bg' => '#fef3c7', 'color' => '#b45309'],
            ['id' => 'n6', 'text' => 'Sleep', 'type' => 'object', 'category' => 'needs', 'icon' => 'fa-bed', 'bg' => '#e0e7ff', 'color' => '#3730a3'],
            ['id' => 'n7', 'text' => 'Medicine', 'type' => 'object', 'category' => 'needs', 'icon' => 'fa-pills', 'bg' => '#ccfbf1', 'color' => '#115e59'],
            ['id' => 'n8', 'text' => 'Snack', 'type' => 'object', 'category' => 'needs', 'icon' => 'fa-cookie-bite', 'bg' => '#ffedd5', 'color' => '#9a3412'],
        ]
    ],
    'feelings' => [
        'title' => '3. Emotions & Feelings',
        'icon'  => 'fa-face-smile',
        'color' => '#e11d48',
        'cards' => [
            ['id' => 'f1', 'text' => 'Happy', 'type' => 'emotion', 'category' => 'feelings', 'icon' => 'fa-face-smile', 'bg' => '#dcfce7', 'color' => '#15803d'],
            ['id' => 'f2', 'text' => 'Sad', 'type' => 'emotion', 'category' => 'feelings', 'icon' => 'fa-face-frown', 'bg' => '#e0f2fe', 'color' => '#0369a1'],
            ['id' => 'f3', 'text' => 'Calm', 'type' => 'emotion', 'category' => 'feelings', 'icon' => 'fa-cloud-sun', 'bg' => '#f0fdf4', 'color' => '#166534'],
            ['id' => 'f4', 'text' => 'Tired', 'type' => 'emotion', 'category' => 'feelings', 'icon' => 'fa-face-tired', 'bg' => '#f1f5f9', 'color' => '#475569'],
            ['id' => 'f5', 'text' => 'Anxious', 'type' => 'emotion', 'category' => 'feelings', 'icon' => 'fa-face-grimace', 'bg' => '#fef3c7', 'color' => '#b45309'],
            ['id' => 'f6', 'text' => 'Overwhelmed', 'type' => 'emotion', 'category' => 'feelings', 'icon' => 'fa-burst', 'bg' => '#fee2e2', 'color' => '#b91c1c'],
            ['id' => 'f7', 'text' => 'Excited', 'type' => 'emotion', 'category' => 'feelings', 'icon' => 'fa-face-laugh-beam', 'bg' => '#fef08a', 'color' => '#854d0e'],
            ['id' => 'f8', 'text' => 'Angry', 'type' => 'emotion', 'category' => 'feelings', 'icon' => 'fa-face-angry', 'bg' => '#ffe4e6', 'color' => '#9f1239'],
        ]
    ],
    'actions' => [
        'title' => '4. Actions & Play',
        'icon'  => 'fa-puzzle-piece',
        'color' => '#8b5cf6',
        'cards' => [
            ['id' => 'a1', 'text' => 'Play', 'type' => 'action', 'category' => 'actions', 'icon' => 'fa-gamepad', 'bg' => '#f3e8ff', 'color' => '#7e22ce'],
            ['id' => 'a2', 'text' => 'Listen Music', 'type' => 'action', 'category' => 'actions', 'icon' => 'fa-headphones', 'bg' => '#e0e7ff', 'color' => '#4338ca'],
            ['id' => 'a3', 'text' => 'Draw', 'type' => 'action', 'category' => 'actions', 'icon' => 'fa-palette', 'bg' => '#fce7f3', 'color' => '#be185d'],
            ['id' => 'a4', 'text' => 'Read Book', 'type' => 'action', 'category' => 'actions', 'icon' => 'fa-book-open', 'bg' => '#fef3c7', 'color' => '#a16207'],
            ['id' => 'a5', 'text' => 'Go Outside', 'type' => 'action', 'category' => 'actions', 'icon' => 'fa-tree', 'bg' => '#dcfce7', 'color' => '#15803d'],
            ['id' => 'a6', 'text' => 'Stop', 'type' => 'action', 'category' => 'actions', 'icon' => 'fa-hand', 'bg' => '#fee2e2', 'color' => '#b91c1c'],
            ['id' => 'a7', 'text' => 'Write', 'type' => 'action', 'category' => 'actions', 'icon' => 'fa-pen-clip', 'bg' => '#e0e7ff', 'color' => '#3730a3'],
            ['id' => 'a8', 'text' => 'Computer', 'type' => 'action', 'category' => 'actions', 'icon' => 'fa-laptop', 'bg' => '#ccfbf1', 'color' => '#0f766e'],
        ]
    ],
    'people' => [
        'title' => '5. People',
        'icon'  => 'fa-users',
        'color' => '#d97706',
        'cards' => [
            ['id' => 'p1', 'text' => 'Teacher', 'type' => 'person', 'category' => 'people', 'icon' => 'fa-chalkboard-user', 'bg' => '#fef3c7', 'color' => '#92400e'],
            ['id' => 'p2', 'text' => 'Mom', 'type' => 'person', 'category' => 'people', 'icon' => 'fa-person-dress', 'bg' => '#fce7f3', 'color' => '#9d174d'],
            ['id' => 'p3', 'text' => 'Dad', 'type' => 'person', 'category' => 'people', 'icon' => 'fa-person', 'bg' => '#e0e7ff', 'color' => '#3730a3'],
            ['id' => 'p4', 'text' => 'Friend', 'type' => 'person', 'category' => 'people', 'icon' => 'fa-user-group', 'bg' => '#dcfce7', 'color' => '#166534'],
            ['id' => 'p5', 'text' => 'Doctor', 'type' => 'person', 'category' => 'people', 'icon' => 'fa-user-doctor', 'bg' => '#e0f2fe', 'color' => '#0369a1'],
        ]
    ],
    'places' => [
        'title' => '6. Places',
        'icon'  => 'fa-map-location-dot',
        'color' => '#0284c7',
        'cards' => [
            ['id' => 'l1', 'text' => 'Home', 'type' => 'place', 'category' => 'places', 'icon' => 'fa-house', 'bg' => '#e0f2fe', 'color' => '#0369a1'],
            ['id' => 'l2', 'text' => 'School', 'type' => 'place', 'category' => 'places', 'icon' => 'fa-school', 'bg' => '#fef3c7', 'color' => '#92400e'],
            ['id' => 'l3', 'text' => 'Park', 'type' => 'place', 'category' => 'places', 'icon' => 'fa-tree-city', 'bg' => '#dcfce7', 'color' => '#166534'],
            ['id' => 'l4', 'text' => 'Classroom', 'type' => 'place', 'category' => 'places', 'icon' => 'fa-door-open', 'bg' => '#f3e8ff', 'color' => '#6b21a8'],
        ]
    ]
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Advanced Grammar PECS Board & AI Engine | <?php echo defined('SITE_NAME') ? SITE_NAME : 'Spark Steps'; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Fredoka:wght@400;600;700&family=Poppins:wght@400;500;600&display=swap" rel="stylesheet">

    <style>
        :root {
            --bg-soft: #f4f8fb;
            --primary-blue: #2563eb;
            --card-radius: 20px;
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

        .navbar-pecs {
            background: #ffffff;
            border-bottom: 2px solid #e2e8f0;
            padding: 14px 0;
            box-shadow: 0 4px 12px rgba(0,0,0,0.02);
        }

        .sentence-strip-container {
            background: rgba(255, 255, 255, 0.96);
            backdrop-filter: blur(10px);
            border: 3px solid #cbd5e1;
            border-radius: 24px;
            padding: 22px;
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.06);
            position: sticky;
            top: 15px;
            z-index: 1020;
        }

        .sentence-strip {
            min-height: 125px;
            background: #f8fafc;
            border: 2px dashed #94a3b8;
            border-radius: 18px;
            padding: 14px;
            display: flex;
            align-items: center;
            gap: 14px;
            overflow-x: auto;
            scroll-behavior: smooth;
        }

        .sentence-strip-empty {
            color: #94a3b8;
            font-weight: 500;
            font-size: 1.05rem;
            width: 100%;
            text-align: center;
            user-select: none;
        }

        .pecs-card {
            width: 110px;
            height: 110px;
            border-radius: 18px;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            padding: 8px;
            cursor: pointer;
            user-select: none;
            transition: all 0.3s cubic-bezier(0.175, 0.885, 0.32, 1.275);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.04);
            border: 2px solid transparent;
            text-align: center;
            flex-shrink: 0;
        }

        .pecs-card:hover {
            transform: translateY(-6px) scale(1.05);
            box-shadow: 0 12px 24px rgba(37, 99, 235, 0.12);
            border-color: var(--primary-blue);
        }

        .pecs-card:active {
            transform: scale(0.94);
        }

        .pecs-card i {
            font-size: 2.2rem;
            margin-bottom: 6px;
            transition: transform 0.2s ease;
        }

        .pecs-card:hover i {
            transform: scale(1.1);
        }

        .pecs-card span {
            font-size: 0.85rem;
            font-weight: 700;
            line-height: 1.1;
        }

        .sentence-item {
            animation: bounceIn 0.35s cubic-bezier(0.175, 0.885, 0.32, 1.275);
            position: relative;
        }

        @keyframes bounceIn {
            0% { transform: scale(0.3); opacity: 0; }
            50% { transform: scale(1.08); opacity: 1; }
            100% { transform: scale(1); opacity: 1; }
        }

        @keyframes shakeError {
            0%, 100% { transform: translateX(0); }
            20%, 60% { transform: translateX(-6px); }
            40%, 80% { transform: translateX(6px); }
        }

        .shake-animation {
            animation: shakeError 0.4s ease-in-out;
        }

        .category-header {
            border-bottom: 3px solid #e2e8f0;
            padding-bottom: 8px;
            margin-top: 35px;
            margin-bottom: 20px;
        }

        .control-btn {
            border-radius: 50px;
            padding: 10px 20px;
            font-family: 'Fredoka', cursive;
            font-size: 1rem;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            transition: all 0.2s ease;
        }

        .control-btn:hover {
            transform: translateY(-2px);
        }

        .emotion-pill {
            cursor: pointer;
            border-radius: 50px;
            padding: 8px 16px;
            font-weight: 600;
            font-size: 0.9rem;
            transition: all 0.2s ease;
            border: 2px solid transparent;
        }
        .emotion-pill:hover {
            transform: translateY(-2px);
        }
        .emotion-pill.active {
            border-color: #1e293b;
            box-shadow: 0 4px 10px rgba(0,0,0,0.12);
        }

        .recommendation-chip {
            border-radius: 14px;
            padding: 10px 16px;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 10px;
            cursor: pointer;
            transition: all 0.2s ease;
            border: 2px solid transparent;
        }
        .recommendation-chip:hover {
            transform: translateY(-2px);
            filter: brightness(0.96);
        }

        #aiFeedbackBanner {
            display: none;
            border-radius: 14px;
            font-size: 0.95rem;
            font-weight: 500;
            animation: fadeIn 0.3s ease;
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(-4px); }
            to { opacity: 1; transform: translateY(0); }
        }
    </style>
</head>
<body>

<nav class="navbar navbar-pecs sticky-top mb-4">
    <div class="container">
        <a class="navbar-brand brand-font fs-3 text-primary d-flex align-items-center gap-2" href="<?php echo htmlspecialchars($dashboardUrl); ?>">
            <i class="fa-solid fa-arrow-left fs-4 me-1 text-secondary"></i>
            <i class="fa-solid fa-brain text-primary fs-2"></i> PECS & Advanced Grammar Guard
        </a>
        <div class="d-flex align-items-center gap-2">
            <button id="ttsToggleBtn" class="btn btn-outline-primary rounded-pill px-3 py-2 text-nowrap fw-semibold shadow-sm">
                <i class="fa-solid fa-volume-high me-1"></i> Auto-Voice: <span id="ttsStatus">ON</span>
            </button>
            <a href="<?php echo htmlspecialchars($dashboardUrl); ?>" class="btn btn-outline-secondary rounded-pill px-4 fw-semibold shadow-sm">
                Dashboard
            </a>
        </div>
    </div>
</nav>

<div class="container">
    <div class="card border-0 shadow-sm rounded-4 p-4 mb-4 bg-white">
        <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-3">
            <div>
                <h5 class="brand-font m-0 text-dark"><i class="fa-solid fa-face-smile-beam text-danger me-2"></i>Live Emotion State & AI Suggestions</h5>
                <span class="text-muted small">Select how you feel right now to get tailored communication recommendations:</span>
            </div>
            <div class="d-flex flex-wrap gap-2" id="emotionContainer">
                <button class="btn btn-success-subtle text-success emotion-pill active" onclick="changeEmotion('Happy', this)">Happy</button>
                <button class="btn btn-primary-subtle text-primary emotion-pill" onclick="changeEmotion('Calm', this)">Calm</button>
                <button class="btn btn-info-subtle text-info emotion-pill" onclick="changeEmotion('Tired', this)">Tired</button>
                <button class="btn btn-warning-subtle text-warning emotion-pill" onclick="changeEmotion('Anxious', this)">Anxious</button>
                <button class="btn btn-danger-subtle text-danger emotion-pill" onclick="changeEmotion('Sad', this)">Sad</button>
                <button class="btn btn-dark-subtle text-secondary emotion-pill" onclick="changeEmotion('Overwhelmed', this)">Overwhelmed</button>
            </div>
        </div>

        <hr class="text-muted opacity-25 my-2">

        <div class="mt-3">
            <span class="text-muted small fw-semibold mb-2 d-block"><i class="fa-solid fa-wand-magic-sparkles text-primary me-1"></i> AI Recommended Sentences for (<span id="activeEmotionText">Happy</span>):</span>
            <div class="row g-2" id="aiRecommendationsGrid"></div>
        </div>
    </div>

    <div class="sentence-strip-container mb-4" id="stripContainer">
        <div class="d-flex justify-content-between align-items-center mb-2">
            <div>
                <span class="brand-font fs-4 text-dark"><i class="fa-solid fa-layer-group text-primary me-2"></i>Complete Sentence Strip</span>
                <span id="grammarGuidanceBadge" class="badge bg-primary-subtle text-primary ms-2 px-3 py-1 rounded-pill fw-semibold">Rule: Tap a sentence starter</span>
            </div>
            <span class="text-muted small d-none d-sm-inline">Advanced Grammar Guard Active</span>
        </div>

        <div id="sentenceStrip" class="sentence-strip">
            <div id="emptyMessage" class="sentence-strip-empty">
                <i class="fa-solid fa-wand-magic-sparkles me-2 text-primary"></i>Tap any card below to add it to your sentence strip and learn sentence structure...
            </div>
        </div>

        <div id="aiFeedbackBanner" class="alert mt-3 mb-0 p-3 shadow-sm" role="alert"></div>

        <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mt-3">
            <div class="d-flex gap-2 flex-wrap">
                <button id="btnPlay" class="btn btn-success control-btn px-4 shadow-sm" disabled>
                    <i class="fa-solid fa-play fs-5"></i> Speak Sentence
                </button>
                <button id="btnAiVerify" class="btn btn-primary control-btn px-4 shadow-sm" disabled>
                    <i class="fa-solid fa-brain fs-5"></i> AI Grammar Check & Verify
                </button>
            </div>
            <div class="d-flex gap-2">
                <button id="btnBackspace" class="btn btn-outline-warning control-btn px-3 shadow-sm" disabled title="Remove Last Card">
                    <i class="fa-solid fa-delete-left fs-5"></i>
                </button>
                <button id="btnClear" class="btn btn-outline-danger control-btn px-3 shadow-sm" disabled title="Clear Strip">
                    <i class="fa-solid fa-trash-can fs-5"></i> Clear All
                </button>
            </div>
        </div>
    </div>

    <?php foreach ($pecsData as $catKey => $category): ?>
        <div class="category-section">
            <div class="category-header d-flex align-items-center gap-2">
                <i class="fa-solid <?php echo $category['icon']; ?> fs-3" style="color: <?php echo $category['color']; ?>;"></i>
                <h3 class="m-0 fw-bold" style="color: <?php echo $category['color']; ?>;"><?php echo htmlspecialchars($category['title']); ?></h3>
            </div>

            <div class="row g-3 row-cols-3 row-cols-sm-4 row-cols-md-6">
                <?php foreach ($category['cards'] as $card): ?>
                    <div class="col d-flex justify-content-center">
                        <div class="pecs-card" 
                             style="background-color: <?php echo $card['bg']; ?>; color: <?php echo $card['color']; ?>;"
                             onclick="addCardByText('<?php echo htmlspecialchars($card['text'], ENT_QUOTES); ?>')">
                            <i class="fa-solid <?php echo $card['icon']; ?>"></i>
                            <span><?php echo htmlspecialchars($card['text']); ?></span>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    <?php endforeach; ?>
</div>

<script>
    const masterCardsDatabase = {
        <?php foreach ($pecsData as $catKey => $cat): ?>
            <?php foreach ($cat['cards'] as $c): ?>
                "<?php echo $c['text']; ?>": {
                    text: "<?php echo $c['text']; ?>",
                    type: "<?php echo $c['type']; ?>",
                    category: "<?php echo $c['category'] ?? $catKey; ?>",
                    icon: "<?php echo $c['icon']; ?>",
                    bg: "<?php echo $c['bg']; ?>",
                    color: "<?php echo $c['color']; ?>",
                    targets: <?php echo isset($c['target_categories']) ? "['" . implode("','", $c['target_categories']) . "']" : "[]"; ?>
                },
            <?php endforeach; ?>
        <?php endforeach; ?>
    };

    let selectedCards = [];
    let autoSpeak = true;
    let currentEmotion = 'Happy';

    const sentenceStrip = document.getElementById('sentenceStrip');
    const emptyMessage = document.getElementById('emptyMessage');
    const stripContainer = document.getElementById('stripContainer');
    const btnPlay = document.getElementById('btnPlay');
    const btnAiVerify = document.getElementById('btnAiVerify');
    const btnBackspace = document.getElementById('btnBackspace');
    const btnClear = document.getElementById('btnClear');
    const ttsToggleBtn = document.getElementById('ttsToggleBtn');
    const ttsStatus = document.getElementById('ttsStatus');
    const aiFeedbackBanner = document.getElementById('aiFeedbackBanner');
    const aiRecommendationsGrid = document.getElementById('aiRecommendationsGrid');
    const activeEmotionText = document.getElementById('activeEmotionText');
    const grammarGuidanceBadge = document.getElementById('grammarGuidanceBadge');

    const synth = window.speechSynthesis;

    function speakText(text) {
        if (!synth) return;
        synth.cancel();
        const utterance = new SpeechSynthesisUtterance(text);
        utterance.rate = 0.9;
        utterance.pitch = 1.0;
        synth.speak(utterance);
    }

    ttsToggleBtn.addEventListener('click', () => {
        autoSpeak = !autoSpeak;
        ttsStatus.textContent = autoSpeak ? 'ON' : 'OFF';
        ttsToggleBtn.classList.toggle('btn-outline-primary', autoSpeak);
        ttsToggleBtn.classList.toggle('btn-outline-secondary', !autoSpeak);
    });

    function changeEmotion(emotionName, btnElement) {
        currentEmotion = emotionName;
        activeEmotionText.textContent = emotionName;

        document.querySelectorAll('.emotion-pill').forEach(btn => btn.classList.remove('active'));
        if (btnElement) btnElement.classList.add('active');

        fetch('../ai/engine.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ action: 'fetch_recommendations', emotion: emotionName })
        })
        .then(res => res.json())
        .then(data => {
            if (data.status === 'success') {
                renderRecommendations(data.recommendations);
            }
        })
        .catch(err => console.error('Error loading AI recommendations:', err));
    }

    function renderRecommendations(recs) {
        aiRecommendationsGrid.innerHTML = '';
        recs.forEach(rec => {
            const col = document.createElement('div');
            col.className = 'col-md-4';
            col.innerHTML = `
                <div class="recommendation-chip shadow-sm" style="background-color: ${rec.bg}; color: ${rec.color};" onclick="loadFullRecommendation('${rec.text}')">
                    <i class="fa-solid ${rec.icon} fs-5"></i>
                    <span>${rec.text}</span>
                </div>
            `;
            aiRecommendationsGrid.appendChild(col);
        });
    }

    function loadFullRecommendation(phrase) {
        selectedCards = [];
        hideAiFeedback();
        let parts = phrase.split(' ');
        
        parts.forEach((_, idx) => {
            let partialKey = parts.slice(0, idx + 1).join(' ');
            if (masterCardsDatabase[partialKey] && !selectedCards.includes(masterCardsDatabase[partialKey])) {
                selectedCards.push(masterCardsDatabase[partialKey]);
            }
        });

        if (selectedCards.length === 0 && masterCardsDatabase[phrase]) {
            selectedCards.push(masterCardsDatabase[phrase]);
        }

        renderStrip();
        if (autoSpeak) {
            speakText(phrase);
        }
    }

    function addCardByText(cardText) {
        let card = masterCardsDatabase[cardText];
        if (!card) return;

        const isAlreadyAdded = selectedCards.some(item => item.text.toLowerCase() === card.text.toLowerCase());
        if (isAlreadyAdded) {
            triggerErrorAnimation();
            showAiFeedback('warning', `<i class="fa-solid fa-triangle-exclamation me-2"></i><strong>Grammar Guard:</strong> The word <em>"${card.text}"</em> is already in your sentence. Duplicate words are not allowed!`);
            speakText(`You already added ${card.text}.`);
            return;
        }

        if (selectedCards.length === 0) {
            if (card.type !== 'starter' && card.type !== 'standalone') {
                triggerErrorAnimation();
                showAiFeedback('warning', `<i class="fa-solid fa-triangle-exclamation me-2"></i><strong>Grammar Rule:</strong> Sentences must start with a starter phrase like "I want" or "I feel".`);
                speakText("Please select a sentence starter first.");
                return;
            }
        } else {
            let firstCard = selectedCards[0];
            if (firstCard.type === 'starter' && firstCard.targets && firstCard.targets.length > 0) {
                if (card.category && !firstCard.targets.includes(card.category)) {
                    triggerErrorAnimation();
                    showAiFeedback('warning', `<i class="fa-solid fa-triangle-exclamation me-2"></i><strong>Grammar Rule:</strong> After "${firstCard.text}", try selecting words from: <em>${firstCard.targets.join(', ')}</em>.`);
                    speakText(`After ${firstCard.text}, choose a matching word.`);
                    return;
                }
            }
        }

        selectedCards.push(card);
        hideAiFeedback();

        if (autoSpeak) {
            speakText(card.text);
        }

        renderStrip();
    }

    function triggerErrorAnimation() {
        stripContainer.classList.add('shake-animation');
        setTimeout(() => stripContainer.classList.remove('shake-animation'), 400);
    }

    document.getElementById('btnBackspace').addEventListener('click', () => {
        if (selectedCards.length > 0) {
            selectedCards.pop();
            hideAiFeedback();
            renderStrip();
        }
    });

    btnClear.addEventListener('click', () => {
        selectedCards = [];
        hideAiFeedback();
        renderStrip();
    });

    function saveAttemptToServer(sentence, isVerified, cardCount) {
        const payload = {
            action: 'save_pecs_attempt',
            sentence: sentence,
            is_verified: isVerified,
            card_count: cardCount,
            emotion_state: currentEmotion
        };

        fetch(window.location.href, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify(payload)
        })
        .then(response => response.json())
        .then(data => {
            console.log('PECS Progress Saved:', data);
        })
        .catch(error => {
            console.error('Error saving PECS progress:', error);
        });
    }

    btnPlay.addEventListener('click', () => {
        if (selectedCards.length === 0) return;
        const fullSentence = selectedCards.map(c => c.text).join(' ');
        speakText(fullSentence + ".");
        saveAttemptToServer(fullSentence, 0, selectedCards.length);
    });

    btnAiVerify.addEventListener('click', () => {
        if (selectedCards.length === 0) return;

        showAiFeedback('info', `<i class="fa-solid fa-spinner fa-spin me-2"></i>Analyzing sentence structure via backend AI engine...`);

        fetch('../ai/engine.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                action: 'verify_sentence',
                cards: selectedCards
            })
        })
        .then(res => res.json())
        .then(data => {
            if (data.isValid) {
                showAiFeedback('success', `<i class="fa-solid fa-circle-check me-2"></i><strong>AI Verified:</strong> <em>"${data.sentence}."</em> Excellent grammar structure!`);
                speakText(`Great job! Complete sentence: ${data.sentence}`);
                saveAttemptToServer(data.sentence, 1, selectedCards.length);
            } else {
                triggerErrorAnimation();
                showAiFeedback('danger', `<i class="fa-solid fa-circle-exclamation me-2"></i><strong>AI Grammar Notice:</strong> ${data.message}`);
                speakText(data.message);
            }
        })
        .catch(err => {
            showAiFeedback('danger', `<i class="fa-solid fa-circle-xmark me-2"></i>Failed to communicate with AI engine backend.`);
        });
    });

    function showAiFeedback(type, htmlContent) {
        aiFeedbackBanner.className = `alert alert-${type} mt-3 mb-0 p-3 shadow-sm`;
        aiFeedbackBanner.innerHTML = htmlContent;
        aiFeedbackBanner.style.display = 'block';
    }

    function hideAiFeedback() {
        aiFeedbackBanner.style.display = 'none';
    }

    function renderStrip() {
        sentenceStrip.innerHTML = '';

        if (selectedCards.length === 0) {
            sentenceStrip.appendChild(emptyMessage);
            btnPlay.disabled = true;
            btnAiVerify.disabled = true;
            btnBackspace.disabled = true;
            btnClear.disabled = true;
            grammarGuidanceBadge.textContent = 'Rule: Tap a sentence starter';
            return;
        }

        btnPlay.disabled = false;
        btnAiVerify.disabled = false;
        btnBackspace.disabled = false;
        btnClear.disabled = false;

        if (selectedCards.length === 1 && selectedCards[0].type === 'starter') {
            grammarGuidanceBadge.textContent = `Rule: Add a matching target word`;
        } else {
            grammarGuidanceBadge.textContent = `Sentence ready for check`;
        }

        selectedCards.forEach((card) => {
            const cardElem = document.createElement('div');
            cardElem.className = 'pecs-card sentence-item';
            cardElem.style.backgroundColor = card.bg;
            cardElem.style.color = card.color;
            cardElem.innerHTML = `
                <i class="fa-solid ${card.icon}"></i>
                <span>${card.text}</span>
            `;

            cardElem.addEventListener('click', (e) => {
                e.stopPropagation();
                speakText(card.text);
            });

            sentenceStrip.appendChild(cardElem);
        });

        sentenceStrip.scrollLeft = sentenceStrip.scrollWidth;
    }

    changeEmotion('Happy', null);
</script>
</body>
</html>
