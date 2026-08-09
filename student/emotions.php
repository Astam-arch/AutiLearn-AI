<?php
// student/emotions.php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';

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

$userId = $_SESSION['user_id'];
$studentName = $_SESSION['full_name'] ?? 'Learner';
$dashboardUrl = defined('BASE_URL') ? BASE_URL . 'student/dashboard.php' : 'dashboard.php';
$speechLabUrl = defined('BASE_URL') ? BASE_URL . 'student/speech.php' : 'speech.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'save_progress') {
    header('Content-Type: application/json');
    $score = filter_input(INPUT_POST, 'score', FILTER_VALIDATE_INT);
    $total = filter_input(INPUT_POST, 'total', FILTER_VALIDATE_INT);
    
    if ($score !== false && $total !== false && $total > 0) {
        $percentage = round(($score / $total) * 100);
        try {
            $stmt = $pdo->prepare("INSERT INTO student_progress (user_id, activity_type, score, total_items, percentage, updated_at) VALUES (?, 'emotion_quiz', ?, ?, ?, NOW()) ON DUPLICATE KEY UPDATE score = VALUES(score), total_items = VALUES(total_items), percentage = VALUES(percentage), updated_at = NOW()");
            $stmt->execute([$userId, $score, $total, $percentage]);
            echo json_encode(['status' => 'success', 'message' => 'Progress saved successfully.']);
        } catch (Exception $e) {
            echo json_encode(['status' => 'error', 'message' => 'Database error.']);
        }
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Invalid data.']);
    }
    exit;
}

$emotionsList = [
    [
        'id' => 'happy',
        'name' => 'Happy',
        'emoji' => '😊',
        'color' => '#16a34a',
        'bg_subtle' => '#dcfce7',
        'description' => 'Feeling joy, gladness, or positive satisfaction about something nice.',
        'example_phrase' => 'I feel so happy when playing with my friends!',
        'facial_clue' => 'Corners of the mouth turned up in a smile, bright eyes.'
    ],
    [
        'id' => 'sad',
        'name' => 'Sad',
        'emoji' => '😢',
        'color' => '#0284c7',
        'bg_subtle' => '#e0f2fe',
        'description' => 'Feeling sorrow, unhappiness, or down because something disappointing happened.',
        'example_phrase' => 'I feel sad when my favorite toy breaks.',
        'facial_clue' => 'Corners of the mouth turned down, eyebrows slanted.'
    ],
    [
        'id' => 'angry',
        'name' => 'Angry',
        'emoji' => '😠',
        'color' => '#dc2626',
        'bg_subtle' => '#fee2e2',
        'description' => 'Feeling strong annoyance, displeasure, or frustration.',
        'example_phrase' => 'I feel angry when someone takes my turn without asking.',
        'facial_clue' => 'Furrowed eyebrows, tense lips, tight posture.'
    ],
    [
        'id' => 'excited',
        'name' => 'Excited',
        'emoji' => '🤩',
        'color' => '#d97706',
        'bg_subtle' => '#fef3c7',
        'description' => 'Feeling very enthusiastic, energetic, and eagerly looking forward to something.',
        'example_phrase' => 'I am so excited for our school field trip tomorrow!',
        'facial_clue' => 'Wide smiling mouth, wide open sparkling eyes, energetic body language.'
    ],
    [
        'id' => 'calm',
        'name' => 'Calm',
        'emoji' => '😌',
        'color' => '#0d9488',
        'bg_subtle' => '#ccfbf1',
        'description' => 'Feeling peaceful, relaxed, quiet, and free from agitation or stress.',
        'example_phrase' => 'I feel calm when listening to quiet soothing music.',
        'facial_clue' => 'Relaxed facial muscles, soft resting mouth, slow steady breathing.'
    ],
    [
        'id' => 'confused',
        'name' => 'Confused',
        'emoji' => '🤔',
        'color' => '#7c3aed',
        'bg_subtle' => '#ede9fe',
        'description' => 'Not understanding something clearly and needing help or explanation.',
        'example_phrase' => 'I feel confused when the instructions are tricky.',
        'facial_clue' => 'Tilted head, furrowed brow, looking thoughtfully.'
    ]
];

$emotionQuiz = [
    [
        'question' => 'Your friend shares their favorite snack with you. Which emotion are you most likely feeling?',
        'options' => ['Angry', 'Happy', 'Sad', 'Confused'],
        'correct' => 1,
        'explanation' => 'Sharing nice things makes people feel glad and happy (😊).'
    ],
    [
        'question' => 'You cannot find your favorite book anywhere in the room. How might you feel?',
        'options' => ['Excited', 'Calm', 'Sad', 'Happy'],
        'correct' => 2,
        'explanation' => 'Losing or misplacing something special often makes us feel sad or disappointed (😢).'
    ],
    [
        'question' => 'You are going to your favorite amusement park tomorrow morning! How do you feel?',
        'options' => ['Excited', 'Angry', 'Sad', 'Calm'],
        'correct' => 0,
        'explanation' => 'Looking forward to something wonderful gives you a burst of energy and excitement (🤩).'
    ],
    [
        'question' => 'You take three deep slow breaths after a long run. What state of emotion are you in?',
        'options' => ['Angry', 'Calm', 'Confused', 'Sad'],
        'correct' => 1,
        'explanation' => 'Deep breathing helps relax your body and brings you to a peaceful, calm state (😌).'
    ]
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Emotion Recognition Lab | <?php echo defined('SITE_NAME') ? SITE_NAME : 'Spark Steps'; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Fredoka:wght@400;600;700&family=Poppins:wght@400;500;600&display=swap" rel="stylesheet">
    <style>
        :root {
            --bg-soft: #f0fdf4;
            --primary-green: #16a34a;
            --card-radius: 24px;
        }
        body {
            background-color: var(--bg-soft);
            font-family: 'Poppins', sans-serif;
            color: #1e293b;
            padding-bottom: 80px;
        }
        h1, h2, h3, h4, .brand-font {
            font-family: 'Fredoka', cursive, sans-serif;
        }
        .navbar-speech {
            background: #ffffff;
            border-bottom: 2px solid #e2e8f0;
            padding: 14px 0;
        }
        .emotion-card {
            background: #ffffff;
            border-radius: var(--card-radius);
            padding: 25px;
            box-shadow: 0 10px 25px rgba(22, 163, 74, 0.05);
            border: 2px solid #bbf7d0;
            transition: transform 0.2s ease, box-shadow 0.2s ease;
            height: 100%;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
        }
        .emotion-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 15px 35px rgba(22, 163, 74, 0.1);
        }
        .emoji-circle {
            width: 70px;
            height: 70px;
            border-radius: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 2.5rem;
            margin-bottom: 15px;
        }
        .quiz-container {
            background: #ffffff;
            border-radius: var(--card-radius);
            padding: 35px;
            border: 3px solid #bbf7d0;
            box-shadow: 0 15px 35px rgba(22, 163, 74, 0.08);
        }
        .quiz-option-btn {
            display: block;
            width: 100%;
            text-align: left;
            background: #f8fafc;
            border: 2px solid #e2e8f0;
            padding: 14px 20px;
            border-radius: 14px;
            font-weight: 500;
            color: #334155;
            margin-bottom: 12px;
            transition: all 0.2s ease;
        }
        .quiz-option-btn:hover {
            background: #f0fdf4;
            border-color: #16a34a;
            color: #15803d;
        }
        .quiz-option-btn.correct {
            background: #dcfce7 !important;
            border-color: #16a34a !important;
            color: #15803d !important;
            font-weight: 600;
        }
        .quiz-option-btn.incorrect {
            background: #fee2e2 !important;
            border-color: #ef4444 !important;
            color: #991b1b !important;
        }
    </style>
</head>
<body>

<nav class="navbar navbar-speech sticky-top mb-4">
    <div class="container">
        <a class="navbar-brand brand-font fs-3 text-success d-flex align-items-center gap-2" href="<?php echo htmlspecialchars($dashboardUrl); ?>">
            <i class="fa-solid fa-arrow-left fs-4 me-1 text-secondary"></i>
            <i class="fa-solid fa-face-smile text-success fs-2"></i> Emotion Recognition Lab
        </a>
        <div class="d-flex align-items-center gap-3">
            <a href="<?php echo htmlspecialchars($speechLabUrl); ?>" class="btn btn-success rounded-pill px-4 fw-semibold shadow-sm">
                <i class="fa-solid fa-microphone-lines me-2"></i> Go to Speech Lab
            </a>
        </div>
    </div>
</nav>

<div class="container">
    <div class="row mb-4">
        <div class="col-12">
            <div class="p-4 p-md-5 rounded-4 bg-white border border-success-subtle shadow-sm text-center text-md-start d-md-flex align-items-center justify-content-between">
                <div>
                    <span class="badge bg-success-subtle text-success rounded-pill px-3 py-2 fw-semibold fs-6 mb-2">
                        <i class="fa-solid fa-heart me-1"></i> Social & Emotional Learning (SEL)
                    </span>
                    <h1 class="brand-font text-success mb-2">Understand & Express Emotions</h1>
                    <p class="text-secondary mb-0">Learn how to recognize different feelings, match facial expressions, and build empathy through interactive practice!</p>
                </div>
                <div class="mt-3 mt-md-0">
                    <button id="btnReadOverview" class="btn btn-outline-success rounded-pill px-4 py-2 fw-semibold">
                        <i class="fa-solid fa-volume-high me-2"></i> Listen to Guide
                    </button>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-4">
        <div class="col-lg-7">
            <h4 class="fw-bold text-dark mb-3 brand-font">
                <i class="fa-solid fa-book-open text-success me-2"></i>Emotion Library & Expressions
            </h4>

            <div class="row g-3">
                <?php foreach ($emotionsList as $emo): ?>
                    <div class="col-md-6">
                        <div class="emotion-card">
                            <div>
                                <div class="d-flex align-items-center justify-content-between mb-2">
                                    <div class="emoji-circle" style="background-color: <?php echo $emo['bg_subtle']; ?>;">
                                        <?php echo $emo['emoji']; ?>
                                    </div>
                                    <button class="btn btn-sm btn-light rounded-circle shadow-sm" onclick="speakText('<?php echo htmlspecialchars($emo['name'] . ': ' . $emo['description'] . ' Example: ' . $emo['example_phrase'], ENT_QUOTES); ?>')" title="Listen">
                                        <i class="fa-solid fa-volume-high text-success"></i>
                                    </button>
                                </div>
                                <h3 class="fw-bold text-dark brand-font fs-4 mb-1"><?php echo htmlspecialchars($emo['name']); ?></h3>
                                <p class="text-secondary small mb-2"><?php echo htmlspecialchars($emo['description']); ?></p>
                                
                                <div class="bg-light rounded-3 p-2 small mb-2 text-dark">
                                    <strong><i class="fa-solid fa-comment text-success me-1"></i> Say:</strong> 
                                    <span class="text-muted fst-italic">"<?php echo htmlspecialchars($emo['example_phrase']); ?>"</span>
                                </div>
                            </div>

                            <div class="border-top pt-2 small text-muted">
                                <i class="fa-solid fa-eye text-primary me-1"></i> <strong>Clue:</strong> <?php echo htmlspecialchars($emo['facial_clue']); ?>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>

        <div class="col-lg-5">
            <h4 class="fw-bold text-dark mb-3 brand-font">
                <i class="fa-solid fa-puzzle-piece text-success me-2"></i>Guess the Emotion Quiz
            </h4>

            <div class="quiz-container">
                <div id="quizCardBody">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <span id="quizCounter" class="badge bg-success-subtle text-success fw-bold px-3 py-2 rounded-pill">Question 1 of 4</span>
                        <span id="quizScoreBadge" class="text-muted small fw-semibold">Score: 0 / 4</span>
                    </div>

                    <h5 id="quizQuestionText" class="fw-bold text-dark mb-4 brand-font">Loading question...</h5>

                    <div id="quizOptionsContainer" class="mb-3"></div>

                    <div id="quizFeedbackBox" class="alert alert-info d-none mt-3 rounded-4 small">
                        <strong>Explanation:</strong> <span id="quizExplanationText"></span>
                    </div>

                    <button id="nextQuizBtn" class="btn btn-success w-100 rounded-pill py-3 fw-bold mt-3 d-none">
                        Next Question <i class="fa-solid fa-arrow-right ms-2"></i>
                    </button>
                </div>

                <div id="quizCompleteScreen" class="text-center py-4 d-none">
                    <div class="fs-1 text-warning mb-2"><i class="fa-solid fa-trophy"></i></div>
                    <h3 class="fw-bold brand-font text-success">Emotion Quiz Completed!</h3>
                    <p id="finalScoreText" class="text-secondary mb-4">You scored 4 out of 4 correct!</p>
                    <button onclick="restartQuiz()" class="btn btn-success rounded-pill px-4 py-2 fw-bold">
                        <i class="fa-solid fa-rotate-right me-2"></i> Try Again
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
    const synth = window.speechSynthesis;
    function speakText(text) {
        if (!synth) return;
        synth.cancel();
        const utterance = new SpeechSynthesisUtterance(text);
        utterance.rate = 0.85;
        synth.speak(utterance);
    }

    document.getElementById('btnReadOverview').addEventListener('click', () => {
        speakText("Welcome to the Emotion Recognition Lab. Explore different feelings, learn how to express them, and take the quiz to test your social skills!");
    });

    const quizData = <?php echo json_encode($emotionQuiz); ?>;
    let currentQuizIndex = 0;
    let score = 0;
    let answered = false;

    const quizCounter = document.getElementById('quizCounter');
    const quizScoreBadge = document.getElementById('quizScoreBadge');
    const quizQuestionText = document.getElementById('quizQuestionText');
    const quizOptionsContainer = document.getElementById('quizOptionsContainer');
    const quizFeedbackBox = document.getElementById('quizFeedbackBox');
    const quizExplanationText = document.getElementById('quizExplanationText');
    const nextQuizBtn = document.getElementById('nextQuizBtn');
    const quizCardBody = document.getElementById('quizCardBody');
    const quizCompleteScreen = document.getElementById('quizCompleteScreen');
    const finalScoreText = document.getElementById('finalScoreText');

    function saveQuizProgress(finalScore, totalItems) {
        const formData = new URLSearchParams();
        formData.append('action', 'save_progress');
        formData.append('score', finalScore);
        formData.append('total', totalItems);

        fetch(window.location.href, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded'
            },
            body: formData.toString()
        })
        .then(response => response.json())
        .then(data => {
            console.log(data.message);
        })
        .catch(error => {
            console.error('Error saving progress:', error);
        });
    }

    function loadQuizQuestion() {
        if (currentQuizIndex >= quizData.length) {
            quizCardBody.classList.add('d-none');
            quizCompleteScreen.classList.remove('d-none');
            finalScoreText.textContent = `You scored ${score} out of ${quizData.length} correct!`;
            speakText(`Quiz completed! You scored ${score} out of ${quizData.length}. Fantastic job understanding emotions!`);
            saveQuizProgress(score, quizData.length);
            return;
        }

        answered = false;
        quizFeedbackBox.classList.add('d-none');
        nextQuizBtn.classList.add('d-none');

        const q = quizData[currentQuizIndex];
        quizCounter.textContent = `Question ${currentQuizIndex + 1} of ${quizData.length}`;
        quizScoreBadge.textContent = `Score: ${score} / ${quizData.length}`;
        quizQuestionText.textContent = q.question;

        quizOptionsContainer.innerHTML = '';
        q.options.forEach((opt, idx) => {
            const btn = document.createElement('button');
            btn.className = 'quiz-option-btn';
            btn.innerHTML = `<i class="fa-solid fa-circle-dot text-muted me-2 small"></i> ${opt}`;
            btn.onclick = () => selectQuizOption(idx, q.correct, q.explanation);
            quizOptionsContainer.appendChild(btn);
        });
    }

    function selectQuizOption(selectedIndex, correctIndex, explanation) {
        if (answered) return;
        answered = true;

        const optionButtons = quizOptionsContainer.querySelectorAll('button');
        
        if (selectedIndex === correctIndex) {
            optionButtons[selectedIndex].classList.add('correct');
            optionButtons[selectedIndex].innerHTML = `<i class="fa-solid fa-circle-check text-success me-2"></i> ${quizData[currentQuizIndex].options[selectedIndex]}`;
            score++;
            quizScoreBadge.textContent = `Score: ${score} / ${quizData.length}`;
            speakText("Correct!");
        } else {
            optionButtons[selectedIndex].classList.add('incorrect');
            optionButtons[selectedIndex].innerHTML = `<i class="fa-solid fa-circle-xmark text-danger me-2"></i> ${quizData[currentQuizIndex].options[selectedIndex]}`;
            optionButtons[correctIndex].classList.add('correct');
            speakText("Not quite.");
        }

        quizExplanationText.textContent = explanation;
        quizFeedbackBox.classList.remove('d-none');
        nextQuizBtn.classList.remove('d-none');
    }

    nextQuizBtn.onclick = () => {
        currentQuizIndex++;
        loadQuizQuestion();
    };

    function restartQuiz() {
        currentQuizIndex = 0;
        score = 0;
        quizCompleteScreen.classList.add('d-none');
        quizCardBody.classList.remove('d-none');
        loadQuizQuestion();
    }

    loadQuizQuestion();
</script>
</body>
</html>