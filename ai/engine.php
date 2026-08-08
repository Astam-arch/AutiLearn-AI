<?php
// ai/engine.php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';

// Set headers for JSON API response
header('Content-Type: application/json');

// Ensure user session is authenticated
if (!isset($_SESSION['user_id'])) {
    echo json_encode([
        'status' => 'error',
        'message' => 'Unauthorized access. Session required.'
    ]);
    exit;
}

// Accept only POST requests containing JSON payloads
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode([
        'status' => 'error',
        'message' => 'Invalid request method. Use POST.'
    ]);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
$action = $input['action'] ?? 'verify_sentence';

// Adaptive Recommendation Matrix mapped to emotional states
$recommendationMatrix = [
    'Happy' => [
        ['text' => 'I want Play', 'icon' => 'fa-gamepad', 'bg' => '#f3e8ff', 'color' => '#7e22ce'],
        ['text' => 'I want Listen Music', 'icon' => 'fa-headphones', 'bg' => '#e0e7ff', 'color' => '#4338ca'],
        ['text' => 'Thank you', 'icon' => 'fa-face-smile-beam', 'bg' => '#dcfce7', 'color' => '#166534']
    ],
    'Calm' => [
        ['text' => 'I want Read Book', 'icon' => 'fa-book-open', 'bg' => '#fef3c7', 'color' => '#a16207'],
        ['text' => 'I want Draw', 'icon' => 'fa-palette', 'bg' => '#fce7f3', 'color' => '#be185d'],
        ['text' => 'I see Park', 'icon' => 'fa-tree-city', 'bg' => '#dcfce7', 'color' => '#166534']
    ],
    'Tired' => [
        ['text' => 'I need Sleep', 'icon' => 'fa-bed', 'bg' => '#e0e7ff', 'color' => '#3730a3'],
        ['text' => 'I want Break', 'icon' => 'fa-mug-hot', 'bg' => '#fef3c7', 'color' => '#b45309'],
        ['text' => 'I need Water', 'icon' => 'fa-glass-water', 'bg' => '#e0f2fe', 'color' => '#0369a1']
    ],
    'Anxious' => [
        ['text' => 'I need Help', 'icon' => 'fa-life-ring', 'bg' => '#fee2e2', 'color' => '#b91c1c'],
        ['text' => 'I want Mom', 'icon' => 'fa-person-dress', 'bg' => '#fce7f3', 'color' => '#9d174d'],
        ['text' => 'I need Break', 'icon' => 'fa-mug-hot', 'bg' => '#fef3c7', 'color' => '#b45309']
    ],
    'Sad' => [
        ['text' => 'I want Hug', 'icon' => 'fa-hands-holding-child', 'bg' => '#dcfce7', 'color' => '#15803d'],
        ['text' => 'I need Teacher', 'icon' => 'fa-chalkboard-user', 'bg' => '#fef3c7', 'color' => '#92400e'],
        ['text' => 'I want Listen Music', 'icon' => 'fa-headphones', 'bg' => '#e0e7ff', 'color' => '#4338ca']
    ],
    'Overwhelmed' => [
        ['text' => 'Stop', 'icon' => 'fa-hand', 'bg' => '#fee2e2', 'color' => '#b91c1c'],
        ['text' => 'I need Break', 'icon' => 'fa-mug-hot', 'bg' => '#fef3c7', 'color' => '#b45309'],
        ['text' => 'I want Home', 'icon' => 'fa-house', 'bg' => '#e0f2fe', 'color' => '#0369a1']
    ]
];

switch ($action) {
    case 'verify_sentence':
        $cards = $input['cards'] ?? [];
        
        if (empty($cards)) {
            echo json_encode([
                'status' => 'success',
                'isValid' => false,
                'message' => 'No cards provided in the sentence strip.'
            ]);
            exit;
        }

        $firstCard = $cards[0];
        $isStarterValid = in_array($firstCard['type'] ?? '', ['starter', 'standalone']);

        if (!$isStarterValid) {
            echo json_encode([
                'status' => 'success',
                'isValid' => false,
                'message' => 'Sentences must begin with a proper starter phrase like "I want", "I feel", or "I need".'
            ]);
            exit;
        }

        if (count($cards) < 2 && $firstCard['type'] === 'starter') {
            echo json_encode([
                'status' => 'success',
                'isValid' => false,
                'message' => 'Incomplete sentence structure. Append a matching target object, feeling, or action card.'
            ]);
            exit;
        }

        $fullSentence = implode(' ', array_column($cards, 'text'));
        echo json_encode([
            'status' => 'success',
            'isValid' => true,
            'sentence' => $fullSentence,
            'message' => 'AI Context Verification Successful! Complete sentence structured correctly.'
        ]);
        break;

    case 'fetch_recommendations':
        $emotion = $input['emotion'] ?? 'Happy';
        $recommendations = $recommendationMatrix[$emotion] ?? $recommendationMatrix['Happy'];

        echo json_encode([
            'status' => 'success',
            'emotion' => $emotion,
            'recommendations' => $recommendations
        ]);
        break;

    default:
        echo json_encode([
            'status' => 'error',
            'message' => 'Unknown AI action command requested.'
        ]);
        break;
}