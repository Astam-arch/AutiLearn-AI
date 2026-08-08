<?php
// student/lessons.php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';

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

$studentName = $_SESSION['full_name'] ?? 'Learner';
$dashboardUrl = defined('BASE_URL') ? BASE_URL . 'student/dashboard.php' : 'dashboard.php';

// Interactive Lessons Data Structure
$lessons = [
    [
        'id'          => 'emotions',
        'title'       => 'Recognizing Emotions',
        'description' => 'Match facial expressions and learn how feelings look and sound.',
        'icon'        => 'fa-face-smile',
        'color'       => '#3b82f6',
        'bg_light'    => '#eff6ff',
        'badge'       => 'Social Skills',
        'cards'       => [
            ['word' => 'Happy', 'icon' => 'fa-face-smile-beam', 'color' => '#16a34a', 'options' => ['Happy', 'Sad', 'Angry']],
            ['word' => 'Sad', 'icon' => 'fa-face-frown', 'color' => '#2563eb', 'options' => ['Excited', 'Sad', 'Calm']],
            ['word' => 'Angry', 'icon' => 'fa-face-angry', 'color' => '#dc2626', 'options' => ['Angry', 'Happy', 'Sleepy']],
            ['word' => 'Surprised', 'icon' => 'fa-face-surprise', 'color' => '#d97706', 'options' => ['Scared', 'Surprised', 'Hungry']]
        ]
    ],
    [
        'id'          => 'routines',
        'title'       => 'Daily Routines',
        'description' => 'Learn step-by-step everyday activities like brushing teeth and meals.',
        'icon'        => 'fa-calendar-check',
        'color'       => '#10b981',
        'bg_light'    => '#ecfdf5',
        'badge'       => 'Life Skills',
        'cards'       => [
            ['word' => 'Brush Teeth', 'icon' => 'fa-tooth', 'color' => '#0284c7', 'options' => ['Brush Teeth', 'Wash Hands', 'Sleep']],
            ['word' => 'Wash Hands', 'icon' => 'fa-hands-bubbles', 'color' => '#0d9488', 'options' => ['Eat Food', 'Wash Hands', 'Play']],
            ['word' => 'Eat Meals', 'icon' => 'fa-utensils', 'color' => '#eab308', 'options' => ['Read Book', 'Eat Meals', 'Shower']],
            ['word' => 'Bed Time', 'icon' => 'fa-bed', 'color' => '#6366f1', 'options' => ['Bed Time', 'School', 'Exercise']]
        ]
    ],
    [
        'id'          => 'objects',
        'title'       => 'Everyday Objects',
        'description' => 'Identify common objects around your home and classroom.',
        'icon'        => 'fa-shapes',
        'color'       => '#f59e0b',
        'bg_light'    => '#fffbeb',
        'badge'       => 'Vocabulary',
        'cards'       => [
            ['word' => 'Clock', 'icon' => 'fa-clock', 'color' => '#b45309', 'options' => ['Clock', 'Watch', 'Phone']],
            ['word' => 'Book', 'icon' => 'fa-book', 'color' => '#2563eb', 'options' => ['Pencil', 'Book', 'Paper']],
            ['word' => 'Pencil', 'icon' => 'fa-pencil', 'color' => '#16a34a', 'options' => ['Eraser', 'Pencil', 'Ruler']],
            ['word' => 'Backpack', 'icon' => 'fa-bag-shopping', 'color' => '#9333ea', 'options' => ['Backpack', 'Chair', 'Table']]
        ]
    ],
    [
        'id'          => 'animals',
        'title'       => 'Animal World',
        'description' => 'Discover animals, their names, and hear how to speak them clearly.',
        'icon'        => 'fa-paw',
        'color'       => '#8b5cf6',
        'bg_light'    => '#f5f3ff',
        'badge'       => 'Nature',
        'cards'       => [
            ['word' => 'Cat', 'icon' => 'fa-cat', 'color' => '#d97706', 'options' => ['Cat', 'Dog', 'Bird']],
            ['word' => 'Dog', 'icon' => 'fa-dog', 'color' => '#7c2d12', 'options' => ['Fish', 'Dog', 'Rabbit']],
            ['word' => 'Bird', 'icon' => 'fa-crow', 'color' => '#2563eb', 'options' => ['Bird', 'Frog', 'Duck']],
            ['word' => 'Fish', 'icon' => 'fa-fish', 'color' => '#0284c7', 'options' => ['Fish', 'Turtle', 'Whale']]
        ]
    ]
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Daily Interactive Lessons | <?php echo defined('SITE_NAME') ? SITE_NAME : 'AutiLearn AI'; ?></title>
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- FontAwesome Icons -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" rel="stylesheet">
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Fredoka:wght@400;600;700&family=Poppins:wght@400;500;600&display=swap" rel="stylesheet">

    <style>
        :root {
            --bg-soft: #f8fafc;
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

        .navbar-lessons {
            background: #ffffff;
            border-bottom: 2px solid #e2e8f0;
            padding: 14px 0;
        }

        .lesson-card {
            background: #ffffff;
            border-radius: var(--card-radius);
            padding: 28px;
            border: 2px solid #e2e8f0;
            transition: all 0.3s cubic-bezier(0.175, 0.885, 0.32, 1.275);
            height: 100%;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
        }

        .lesson-card:hover {
            transform: translateY(-6px);
            box-shadow: 0 15px 30px rgba(0, 0, 0, 0.07);
        }

        .lesson-icon-box {
            width: 70px;
            height: 70px;
            border-radius: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 2.2rem;
            margin-bottom: 18px;
        }

        /* MODAL INTERACTIVE QUIZ AREA */
        .modal-content {
            border-radius: var(--card-radius);
            border: none;
            overflow: hidden;
        }

        .quiz-card-display {
            background: #f8fafc;
            border-radius: 20px;
            padding: 30px;
            text-align: center;
            border: 3px dashed #cbd5e1;
            margin-bottom: 25px;
        }

        .quiz-card-display i {
            font-size: 5rem;
            margin-bottom: 15px;
        }

        .option-btn {
            border-radius: 16px;
            padding: 16px 20px;
            font-family: 'Fredoka', cursive;
            font-size: 1.25rem;
            border: 2px solid #cbd5e1;
            background: #ffffff;
            color: #1e293b;
            transition: all 0.2s ease;
            width: 100%;
            text-align: center;
            cursor: pointer;
        }

        .option-btn:hover {
            border-color: #2563eb;
            background: #eff6ff;
            color: #1d4ed8;
        }

        .option-btn.correct {
            background: #dcfce7 !important;
            border-color: #16a34a !important;
            color: #15803d !important;
        }

        .option-btn.wrong {
            background: #fee2e2 !important;
            border-color: #dc2626 !important;
            color: #b91c1c !important;
        }

        .star-score {
            font-size: 1.5rem;
            color: #f59e0b;
        }
    </style>
</head>
<body>

<!-- NAVIGATION BAR -->
<nav class="navbar navbar-lessons sticky-top mb-4">
    <div class="container">
        <a class="navbar-brand brand-font fs-3 text-primary d-flex align-items-center gap-2" href="<?php echo htmlspecialchars($dashboardUrl); ?>">
            <i class="fa-solid fa-arrow-left fs-4 me-1 text-secondary"></i>
            <i class="fa-solid fa-graduation-cap text-primary fs-2"></i> Daily Lessons
        </a>
        <div class="d-flex align-items-center gap-3">
            <span class="badge bg-warning-subtle text-warning-emphasis rounded-pill px-3 py-2 fw-bold fs-6">
                <i class="fa-solid fa-star text-warning me-1"></i> <span id="totalStars">0</span> Stars Collected
            </span>
        </div>
    </div>
</nav>

<div class="container">
    <div class="text-center mb-5">
        <h2 class="brand-font fw-bold text-dark fs-1">Pick a Learning Topic</h2>
        <p class="text-muted fs-5">Interactive visual cards with clear pronunciation audio</p>
    </div>

    <!-- LESSON CARDS GRID -->
    <div class="row g-4 row-cols-1 row-cols-md-2 row-cols-lg-4">
        <?php foreach ($lessons as $lesson): ?>
            <div class="col">
                <div class="lesson-card" style="border-top: 5px solid <?php echo $lesson['color']; ?>;">
                    <div>
                        <div class="d-flex align-items-center justify-content-between mb-2">
                            <span class="badge rounded-pill px-3 py-2 fw-semibold" style="background-color: <?php echo $lesson['bg_light']; ?>; color: <?php echo $lesson['color']; ?>;">
                                <?php echo htmlspecialchars($lesson['badge']); ?>
                            </span>
                            <span class="small text-muted fw-semibold"><i class="fa-solid fa-clone me-1"></i><?php echo count($lesson['cards']); ?> Cards</span>
                        </div>

                        <div class="lesson-icon-box" style="background-color: <?php echo $lesson['bg_light']; ?>; color: <?php echo $lesson['color']; ?>;">
                            <i class="fa-solid <?php echo $lesson['icon']; ?>"></i>
                        </div>

                        <h4 class="brand-font fw-bold text-dark mb-2"><?php echo htmlspecialchars($lesson['title']); ?></h4>
                        <p class="text-muted small mb-4"><?php echo htmlspecialchars($lesson['description']); ?></p>
                    </div>

                    <button class="btn btn-lg w-100 fw-bold rounded-pill text-white" 
                            style="background-color: <?php echo $lesson['color']; ?>;"
                            onclick="startLesson('<?php echo $lesson['id']; ?>')">
                        <i class="fa-solid fa-circle-play me-2"></i> Start Activity
                    </button>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</div>

<!-- INTERACTIVE LESSON MODAL -->
<div class="modal fade" id="lessonModal" data-bs-backdrop="static" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content p-4">
            
            <div class="modal-header border-0 pb-0">
                <div class="d-flex align-items-center gap-2">
                    <span id="modalTopicBadge" class="badge bg-primary-subtle text-primary rounded-pill px-3 py-2 fw-semibold">Lesson</span>
                    <span id="cardProgress" class="fw-bold text-secondary small">Card 1 of 4</span>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" onclick="stopAudio()"></button>
            </div>

            <div class="modal-body">
                
                <!-- Target Visual Display -->
                <div class="quiz-card-display">
                    <i id="quizCardIcon" class="fa-solid fa-face-smile" style="color: #16a34a;"></i>
                    <div class="d-block mt-2">
                        <button id="btnListenCard" class="btn btn-outline-primary rounded-pill px-4 fw-semibold">
                            <i class="fa-solid fa-volume-high me-2"></i> Tap to Listen
                        </button>
                    </div>
                </div>

                <h4 class="text-center brand-font fw-bold mb-4" id="quizQuestion">Which word matches this picture?</h4>

                <!-- Multiple Choice Options -->
                <div class="row g-3" id="optionsContainer">
                    <!-- Dynamic Options Inserted Here -->
                </div>

                <!-- Feedback Banner -->
                <div id="lessonFeedback" class="alert mt-4 rounded-4 text-center fw-bold fs-5 d-none"></div>

            </div>

            <div class="modal-footer border-0 d-flex justify-content-between">
                <div class="star-score">
                    <i class="fa-solid fa-star"></i> <span id="currentLessonStars">0</span>
                </div>
                <button id="btnNextCard" class="btn btn-primary btn-lg rounded-pill px-5 fw-bold d-none" onclick="nextCard()">
                    Next Card <i class="fa-solid fa-arrow-right ms-2"></i>
                </button>
            </div>

        </div>
    </div>
</div>

<!-- Bootstrap 5 JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

<script>
    // Lessons JSON payload
    const lessonsData = <?php echo json_encode($lessons); ?>;
    
    let activeLesson = null;
    let currentCardIndex = 0;
    let totalStarsCount = 0;
    let currentLessonStarsCount = 0;

    const modalElem = new bootstrap.Modal(document.getElementById('lessonModal'));
    const modalTopicBadge = document.getElementById('modalTopicBadge');
    const cardProgress = document.getElementById('cardProgress');
    const quizCardIcon = document.getElementById('quizCardIcon');
    const btnListenCard = document.getElementById('btnListenCard');
    const optionsContainer = document.getElementById('optionsContainer');
    const lessonFeedback = document.getElementById('lessonFeedback');
    const btnNextCard = document.getElementById('btnNextCard');
    const currentLessonStars = document.getElementById('currentLessonStars');
    const totalStars = document.getElementById('totalStars');

    // Speech Synthesis
    const synth = window.speechSynthesis;

    function speakWord(text) {
        if (!synth) return;
        synth.cancel();
        const utterance = new SpeechSynthesisUtterance(text);
        utterance.rate = 0.85;
        synth.speak(utterance);
    }

    function stopAudio() {
        if (synth) synth.cancel();
    }

    // Start Lesson Activity
    function startLesson(lessonId) {
        activeLesson = lessonsData.find(l => l.id === lessonId);
        if (!activeLesson) return;

        currentCardIndex = 0;
        currentLessonStarsCount = 0;
        currentLessonStars.textContent = '0';

        modalTopicBadge.textContent = activeLesson.title;
        modalTopicBadge.style.backgroundColor = activeLesson.bg_light;
        modalTopicBadge.style.color = activeLesson.color;

        loadCard();
        modalElem.show();
    }

    // Load Single Card Question
    function loadCard() {
        const card = activeLesson.cards[currentCardIndex];
        
        cardProgress.textContent = `Card ${currentCardIndex + 1} of ${activeLesson.cards.length}`;
        quizCardIcon.className = `fa-solid ${card.icon}`;
        quizCardIcon.style.color = card.color;

        lessonFeedback.className = 'alert mt-4 rounded-4 text-center fw-bold fs-5 d-none';
        btnNextCard.classList.add('d-none');

        // Play Target Sound
        speakWord(card.word);

        btnListenCard.onclick = () => speakWord(card.word);

        // Render Options
        optionsContainer.innerHTML = '';
        card.options.forEach(option => {
            const col = document.createElement('div');
            col.className = 'col-md-4';

            const btn = document.createElement('button');
            btn.className = 'option-btn';
            btn.textContent = option;
            btn.onclick = () => checkAnswer(option, card.word, btn);

            col.appendChild(btn);
            optionsContainer.appendChild(col);
        });
    }

    // Check Answer
    function checkAnswer(selectedOption, correctAnswer, clickedBtn) {
        const allButtons = optionsContainer.querySelectorAll('.option-btn');
        allButtons.forEach(b => b.disabled = true);

        if (selectedOption === correctAnswer) {
            clickedBtn.classList.add('correct');
            lessonFeedback.textContent = '🌟 Excellent! That is correct!';
            lessonFeedback.className = 'alert alert-success mt-4 rounded-4 text-center fw-bold fs-5';
            speakWord('Excellent! Correct!');

            currentLessonStarsCount += 1;
            totalStarsCount += 1;
            currentLessonStars.textContent = currentLessonStarsCount;
            totalStars.textContent = totalStarsCount;
        } else {
            clickedBtn.classList.add('wrong');
            lessonFeedback.textContent = `Nice try! The correct word is "${correctAnswer}".`;
            lessonFeedback.className = 'alert alert-danger mt-4 rounded-4 text-center fw-bold fs-5';

            // Highlight correct one
            allButtons.forEach(b => {
                if (b.textContent === correctAnswer) b.classList.add('correct');
            });
        }

        lessonFeedback.classList.remove('d-none');
        btnNextCard.classList.remove('d-none');
    }

    // Next Card
    function nextCard() {
        currentCardIndex++;
        if (currentCardIndex < activeLesson.cards.length) {
            loadCard();
        } else {
            // Lesson Finished
            quizCardIcon.className = 'fa-solid fa-trophy text-warning';
            btnListenCard.classList.add('d-none');
            optionsContainer.innerHTML = `
                <div class="col-12 text-center py-4">
                    <h3 class="brand-font fw-bold text-success fs-2 mb-2">Lesson Completed! 🎉</h3>
                    <p class="fs-5 text-muted">You earned ${currentLessonStarsCount} stars in this topic!</p>
                </div>
            `;
            lessonFeedback.classList.add('d-none');
            btnNextCard.classList.add('d-none');
            speakWord('Lesson completed! Great job!');
        }
    }
</script>
</body>
</html>