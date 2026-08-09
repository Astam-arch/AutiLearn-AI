<?php
// student/grammer.php
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
            $stmt = $pdo->prepare("INSERT INTO student_progress (user_id, activity_type, score, total_items, percentage, updated_at) VALUES (?, 'grammar_quiz', ?, ?, ?, NOW()) ON DUPLICATE KEY UPDATE score = VALUES(score), total_items = VALUES(total_items), percentage = VALUES(percentage), updated_at = NOW()");
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

$grammarTopics = [
    [
        'id' => 't1',
        'title' => 'Verb Forms (V1 vs V4) - Base & Continuous',
        'icon' => 'fa-shapes',
        'color' => '#0284c7',
        'summary' => 'Learn the difference between base action words (V1) and continuous action words (V4 with -ing).',
        'rules' => [
            ['v1' => 'Water', 'v4' => 'Watering', 'desc' => 'Use base form (V1) for commands or general facts. Use V4 (-ing) for actions happening right now.'],
            ['v1' => 'Play', 'v4' => 'Playing', 'desc' => 'Example: "I like to play" (V1) vs "He is playing" (V4).'],
            ['v1' => 'Run', 'v4' => 'Running', 'desc' => 'Note spelling changes for double consonants in continuous forms!'],
            ['v1' => 'Help', 'v4' => 'Helping', 'desc' => 'Always make sure to use the correct verb form requested in speech practice.']
        ]
    ],
    [
        'id' => 't2',
        'title' => 'Singular vs Plural Nouns',
        'icon' => 'fa-clone',
        'color' => '#dc2626',
        'summary' => 'Understand when to use single items versus multiple items (adding -s or -es).',
        'rules' => [
            ['singular' => 'Book', 'plural' => 'Books', 'desc' => 'Most nouns add an "s" at the end to show more than one.'],
            ['singular' => 'Apple', 'plural' => 'Apples', 'desc' => 'One apple vs many apples.'],
            ['singular' => 'Ball', 'plural' => 'Balls', 'desc' => 'Used when counting objects or describing groups.'],
            ['singular' => 'Box', 'plural' => 'Boxes', 'desc' => 'Nouns ending in x, ch, sh, or s add "es".']
        ]
    ],
    [
        'id' => 't3',
        'title' => 'Basic Sentence Structure',
        'icon' => 'fa-puzzle-piece',
        'color' => '#16a34a',
        'summary' => 'How to put words together to form clear and meaningful sentences.',
        'rules' => [
            ['pattern' => 'Subject + Verb', 'example' => 'I run.', 'desc' => 'The simplest complete sentence structure in English.'],
            ['pattern' => 'Subject + Verb + Object', 'example' => 'I want water.', 'desc' => 'Tells who is doing the action and what object receives it.'],
            ['pattern' => 'Polite Social Phrases', 'example' => 'Thank you / Please help', 'desc' => 'Essential phrases for daily communication and respect.']
        ]
    ]
];

$quizQuestions = [
    [
        'question' => 'What is the correct base form (V1) if someone says "Watering"?',
        'options' => ['Water', 'Waters', 'Watered', 'Watery'],
        'correct' => 0,
        'explanation' => 'The base form (V1) is "Water". "Watering" is the V4 continuous form.'
    ],
    [
        'question' => 'Choose the plural form of the noun "Book":',
        'options' => ['Booking', 'Bookes', 'Books', 'Book'],
        'correct' => 2,
        'explanation' => 'Adding an "s" makes "Book" plural into "Books".'
    ],
    [
        'question' => 'Which sentence follows the standard Subject + Verb + Object rule?',
        'options' => ['Run fast', 'I want water', 'Happy day', 'Please'],
        'correct' => 1,
        'explanation' => '"I" (Subject) + "want" (Verb) + "water" (Object).'
    ],
    [
        'question' => 'What is the continuous form (V4) for the base action word "Play"?',
        'options' => ['Played', 'Plays', 'Playing', 'Player'],
        'correct' => 2,
        'explanation' => 'Adding "-ing" to the base verb creates the continuous form (V4), making it "Playing".'
    ],
    [
        'question' => 'What is the plural form of nouns ending in letters like "x", "ch", or "sh" (e.g., Box)?',
        'options' => ['Boxs', 'Boxes', 'Boxing', 'Boxen'],
        'correct' => 1,
        'explanation' => 'Nouns ending in x, ch, sh, or s add "-es" to form their correct plural.'
    ],
    [
        'question' => 'Which option represents a polite daily communication phrase?',
        'options' => ['Go away', 'Thank you', 'Run fast now', 'Quiet room'],
        'correct' => 1,
        'explanation' => '"Thank you" is an essential polite social phrase used for appreciation and respect.'
    ]
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Grammar Mastery Lab | <?php echo defined('SITE_NAME') ? SITE_NAME : 'Spark Steps'; ?></title>
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
        .grammar-card {
            background: #ffffff;
            border-radius: var(--card-radius);
            padding: 30px;
            box-shadow: 0 10px 30px rgba(22, 163, 74, 0.06);
            border: 2px solid #bbf7d0;
            margin-bottom: 25px;
            transition: transform 0.2s ease;
        }
        .grammar-card:hover {
            transform: translateY(-3px);
        }
        .icon-box {
            width: 50px;
            height: 50px;
            border-radius: 14px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            flex-shrink: 0;
        }
        .table-custom th {
            background-color: #f8fafc;
            color: #475569;
            font-weight: 600;
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
            <i class="fa-solid fa-book-open-reader text-success fs-2"></i> Grammar Mastery Lab
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
                        <i class="fa-solid fa-graduation-cap me-1"></i> Interactive Grammar Guide
                    </span>
                    <h1 class="brand-font text-success mb-2">Master Rules & Verb Forms</h1>
                    <p class="text-secondary mb-0">Learn core grammar principles, verb modifications (V1 vs V4), and plural structures to boost your speech accuracy!</p>
                </div>
                <div class="mt-3 mt-md-0">
                    <button id="btnReadOverview" class="btn btn-outline-success rounded-pill px-4 py-2 fw-semibold">
                        <i class="fa-solid fa-volume-high me-2"></i> Listen to Overview
                    </button>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-4">
        <div class="col-lg-7">
            <h4 class="fw-bold text-dark mb-3 brand-font">
                <i class="fa-solid fa-book text-success me-2"></i>Core Grammar Lessons
            </h4>

            <?php foreach ($grammarTopics as $topic): ?>
                <div class="grammar-card">
                    <div class="d-flex align-items-center gap-3 mb-3">
                        <div class="icon-box" style="background-color: <?php echo $topic['color']; ?>20; color: <?php echo $topic['color']; ?>;">
                            <i class="fa-solid <?php echo $topic['icon']; ?>"></i>
                        </div>
                        <div>
                            <h3 class="fw-bold text-dark fs-4 mb-0 brand-font"><?php echo htmlspecialchars($topic['title']); ?></h3>
                            <p class="text-muted small mb-0"><?php echo htmlspecialchars($topic['summary']); ?></p>
                        </div>
                    </div>

                    <div class="table-responsive">
                        <table class="table table-bordered table-custom align-middle mb-0">
                            <thead>
                                <tr>
                                    <?php if (isset($topic['rules'][0]['v1'])): ?>
                                        <th>Base Form (V1)</th>
                                        <th>Continuous (V4)</th>
                                        <th>Rule & Usage</th>
                                    <?php elseif (isset($topic['rules'][0]['singular'])): ?>
                                        <th>Singular</th>
                                        <th>Plural</th>
                                        <th>Rule & Usage</th>
                                    <?php else: ?>
                                        <th>Pattern</th>
                                        <th>Example</th>
                                        <th>Description</th>
                                    <?php endif; ?>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($topic['rules'] as $rule): ?>
                                    <tr>
                                        <?php if (isset($rule['v1'])): ?>
                                            <td><span class="badge bg-primary-subtle text-primary fw-bold px-2 py-1"><?php echo htmlspecialchars($rule['v1']); ?></span></td>
                                            <td><span class="badge bg-success-subtle text-success fw-bold px-2 py-1"><?php echo htmlspecialchars($rule['v4']); ?></span></td>
                                            <td class="small text-secondary"><?php echo htmlspecialchars($rule['desc']); ?></td>
                                        <?php elseif (isset($rule['singular'])): ?>
                                            <td><span class="badge bg-danger-subtle text-danger fw-bold px-2 py-1"><?php echo htmlspecialchars($rule['singular']); ?></span></td>
                                            <td><span class="badge bg-success-subtle text-success fw-bold px-2 py-1"><?php echo htmlspecialchars($rule['plural']); ?></span></td>
                                            <td class="small text-secondary"><?php echo htmlspecialchars($rule['desc']); ?></td>
                                        <?php else: ?>
                                            <td><code class="fw-bold text-dark"><?php echo htmlspecialchars($rule['pattern']); ?></code></td>
                                            <td><span class="text-success fw-semibold"><?php echo htmlspecialchars($rule['example']); ?></span></td>
                                            <td class="small text-secondary"><?php echo htmlspecialchars($rule['desc']); ?></td>
                                        <?php endif; ?>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <div class="col-lg-5">
            <h4 class="fw-bold text-dark mb-3 brand-font">
                <i class="fa-solid fa-puzzle-piece text-success me-2"></i>Quick Knowledge Check
            </h4>

            <div class="quiz-container">
                <div id="quizCardBody">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <span id="quizCounter" class="badge bg-success-subtle text-success fw-bold px-3 py-2 rounded-pill">Question 1 of 6</span>
                        <span id="quizScoreBadge" class="text-muted small fw-semibold">Score: 0 / 6</span>
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
                    <h3 class="fw-bold brand-font text-success">Quiz Completed!</h3>
                    <p id="finalScoreText" class="text-secondary mb-4">You scored 6 out of 6 correct!</p>
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
        speakText("Welcome to the Grammar Mastery Lab. Learn core grammar principles, verb modifications, and plural structures to boost your speech accuracy!");
    });

    const quizData = <?php echo json_encode($quizQuestions); ?>;
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
            speakText(`Quiz completed! You scored ${score} out of ${quizData.length}. Great job!`);
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