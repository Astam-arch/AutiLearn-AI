<?php
// student/speech.php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/activity_tracking.php';

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

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'save_attempt') {
    header('Content-Type: application/json');
    
    $wordId = $_POST['word_id'] ?? '';
    $word = trim($_POST['word'] ?? '');
    $spokenText = trim($_POST['spoken_text'] ?? '');
    $isCorrect = intval($_POST['is_correct'] ?? 0);
    $stars = intval($_POST['stars'] ?? 0);
    $feedbackType = trim($_POST['feedback_type'] ?? '');

    if (!empty($word)) {
        try {
            recordSpeechActivity($pdo, (int)$studentId, $word, $spokenText, $isCorrect === 1, $stars);
            echo json_encode(['status' => 'success']);
        } catch (Exception $e) {
            echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
        }
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Invalid word data']);
    }
    exit;
}

$practiceCards = [
    [
        'category' => 'Basic Words (Nouns)',
        'level'    => 'Level 1',
        'badge'    => 'bg-success-subtle text-success',
        'items'    => [
            ['id' => 'w1', 'word' => 'Water', 'phonetic' => 'WAH-ter', 'icon' => 'fa-glass-water', 'color' => '#0284c7'],
            ['id' => 'w2', 'word' => 'Apple', 'phonetic' => 'AH-pul', 'icon' => 'fa-apple-whole', 'color' => '#dc2626'],
            ['id' => 'w3', 'word' => 'Book', 'phonetic' => 'BOOK', 'icon' => 'fa-book', 'color' => '#eab308'],
            ['id' => 'w4', 'word' => 'Ball', 'phonetic' => 'BAWL', 'icon' => 'fa-futbol', 'color' => '#16a34a'],
        ]
    ],
    [
        'category' => 'Actions & Verbs (V1 Focus)',
        'level'    => 'Level 2',
        'badge'    => 'bg-primary-subtle text-primary',
        'items'    => [
            ['id' => 'v1', 'word' => 'Play', 'phonetic' => 'PLAY', 'icon' => 'fa-gamepad', 'color' => '#9333ea'],
            ['id' => 'v2', 'word' => 'Eat', 'phonetic' => 'EET', 'icon' => 'fa-utensils', 'color' => '#f97316'],
            ['id' => 'v3', 'word' => 'Run', 'phonetic' => 'RUN', 'icon' => 'fa-person-running', 'color' => '#06b6d4'],
            ['id' => 'v4', 'word' => 'Help', 'phonetic' => 'HELP', 'icon' => 'fa-handshake', 'color' => '#2563eb'],
        ]
    ],
    [
        'category' => 'Emotions & Feelings',
        'level'    => 'Level 3',
        'badge'    => 'bg-info-subtle text-info',
        'items'    => [
            ['id' => 'e1', 'word' => 'Happy', 'phonetic' => 'HAP-ee', 'icon' => 'fa-face-smile', 'color' => '#16a34a'],
            ['id' => 'e2', 'word' => 'Excited', 'phonetic' => 'ik-SY-tid', 'icon' => 'fa-face-grin-stars', 'color' => '#0ea5e9'],
            ['id' => 'e3', 'word' => 'Calm down', 'phonetic' => 'KAHM down', 'icon' => 'fa-spa', 'color' => '#14b8a6'],
            ['id' => 'e4', 'word' => 'Surprised', 'phonetic' => 'ser-PRYZD', 'icon' => 'fa-face-surprise', 'color' => '#ec4899'],
        ]
    ],
    [
        'category' => 'Social Greetings',
        'level'    => 'Level 4',
        'badge'    => 'bg-warning-subtle text-warning-emphasis',
        'items'    => [
            ['id' => 's1', 'word' => 'Hello', 'phonetic' => 'heh-LOH', 'icon' => 'fa-hand-wave', 'color' => '#eab308'],
            ['id' => 's2', 'word' => 'Thank you', 'phonetic' => 'THANGK yoo', 'icon' => 'fa-heart', 'color' => '#e11d48'],
            ['id' => 's3', 'word' => 'Good morning', 'phonetic' => 'GOOD MOR-ning', 'icon' => 'fa-sun', 'color' => '#d97706'],
            ['id' => 's4', 'word' => 'Goodbye', 'phonetic' => 'good-BYE', 'icon' => 'fa-person-walking', 'color' => '#64748b'],
        ]
    ],
    [
        'category' => 'Short Phrases',
        'level'    => 'Level 5',
        'badge'    => 'bg-danger-subtle text-danger',
        'items'    => [
            ['id' => 'p1', 'word' => 'Please help', 'phonetic' => 'PLEEZ help', 'icon' => 'fa-hands-helping', 'color' => '#2563eb'],
            ['id' => 'p2', 'word' => 'I want water', 'phonetic' => 'EYE wahnt WAH-ter', 'icon' => 'fa-glass-water', 'color' => '#0284c7'],
            ['id' => 'p3', 'word' => 'Time to rest', 'phonetic' => 'tym too rest', 'icon' => 'fa-bed', 'color' => '#7c3aed'],
        ]
    ],
    [
        'category' => 'Complete Sentences',
        'level'    => 'Level 6',
        'badge'    => 'bg-dark text-white',
        'items'    => [
            ['id' => 'cs1', 'word' => 'Can we play together', 'phonetic' => 'kan wee play too-GEH-ther', 'icon' => 'fa-users', 'color' => '#16a34a'],
            ['id' => 'cs2', 'word' => 'My day is going great', 'phonetic' => 'my day iz GO-ing grayt', 'icon' => 'fa-face-laugh', 'color' => '#d97706'],
        ]
    ]
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>AI Speech Lab | <?php echo defined('SITE_NAME') ? SITE_NAME : 'Spark Steps'; ?></title>
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

        .practice-display-card {
            background: #ffffff;
            border-radius: var(--card-radius);
            padding: 40px 20px;
            box-shadow: 0 15px 35px rgba(22, 163, 74, 0.08);
            border: 3px solid #bbf7d0;
            text-align: center;
            position: relative;
        }

        .target-word-display {
            font-size: 2.5rem;
            font-weight: 700;
            color: #15803d;
            letter-spacing: 0.5px;
            margin-bottom: 5px;
            line-height: 1.2;
        }

        .phonetic-display {
            font-size: 1.15rem;
            color: #64748b;
            font-weight: 500;
            margin-bottom: 30px;
        }

        .mic-btn-container {
            position: relative;
            display: inline-block;
            margin: 20px 0;
        }

        .btn-mic {
            width: 110px;
            height: 110px;
            border-radius: 50%;
            background: linear-gradient(135deg, #22c55e, #16a34a);
            color: #ffffff;
            border: none;
            font-size: 2.8rem;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto;
            box-shadow: 0 10px 25px rgba(22, 163, 74, 0.35);
            cursor: pointer;
            transition: all 0.3s cubic-bezier(0.175, 0.885, 0.32, 1.275);
            outline: none;
        }

        .btn-mic:hover {
            transform: scale(1.08);
            box-shadow: 0 15px 30px rgba(22, 163, 74, 0.45);
        }

        .btn-mic.recording {
            background: linear-gradient(135deg, #ef4444, #dc2626);
            box-shadow: 0 0 0 0 rgba(239, 68, 68, 0.7);
            animation: pulse-red 1.3s infinite;
        }

        @keyframes pulse-red {
            0% { transform: scale(0.98); box-shadow: 0 0 0 0 rgba(239, 68, 68, 0.7); }
            70% { transform: scale(1.08); box-shadow: 0 0 0 25px rgba(239, 68, 68, 0); }
            100% { transform: scale(0.98); box-shadow: 0 0 0 0 rgba(239, 68, 68, 0); }
        }

        .word-select-card {
            background: #ffffff;
            border-radius: 18px;
            padding: 12px 16px;
            border: 2px solid #e2e8f0;
            cursor: pointer;
            transition: all 0.2s ease;
            height: 100%;
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .word-select-card:hover, .word-select-card.active {
            border-color: #16a34a;
            background-color: #f0fdf4;
            transform: translateY(-2px);
            box-shadow: 0 6px 15px rgba(22, 163, 74, 0.1);
        }

        .icon-box {
            width: 42px;
            height: 42px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.25rem;
            flex-shrink: 0;
        }

        .feedback-box {
            border-radius: 18px;
            padding: 18px;
            margin-top: 20px;
            display: none;
            animation: fadeIn 0.3s ease-in-out;
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .star-rating i {
            font-size: 2rem;
            color: #cbd5e1;
            transition: color 0.3s ease;
        }

        .star-rating i.active {
            color: #f59e0b;
        }
        
        .sidebar-scroll-container {
            max-height: 75vh;
            overflow-y: auto;
            padding-right: 5px;
        }
    </style>
</head>
<body>

<nav class="navbar navbar-speech sticky-top mb-4">
    <div class="container">
        <a class="navbar-brand brand-font fs-3 text-success d-flex align-items-center gap-2" href="<?php echo htmlspecialchars($dashboardUrl); ?>">
            <i class="fa-solid fa-arrow-left fs-4 me-1 text-secondary"></i>
            <i class="fa-solid fa-microphone-lines text-success fs-2"></i> AI Speech Lab
        </a>
        <div class="d-flex align-items-center gap-2">
            <span class="badge bg-success-subtle text-success rounded-pill px-3 py-2 fw-semibold fs-6">
                <i class="fa-solid fa-star text-warning me-1"></i> Practice Mode
            </span>
        </div>
    </div>
</nav>

<div class="container">
    <div class="row g-4">
        <div class="col-lg-7">
            <div class="practice-display-card">
                <span id="activeCategoryBadge" class="badge bg-success-subtle text-success rounded-pill px-3 py-2 fw-semibold fs-6 mb-3">
                    Level 1 • Basic Words (Nouns)
                </span>

                <div id="targetWord" class="target-word-display brand-font" data-id="w1">Water</div>
                <div id="targetPhonetic" class="phonetic-display">[ WAH-ter ]</div>

                <button id="btnListen" class="btn btn-outline-success rounded-pill px-4 py-2 mb-4 fw-semibold">
                    <i class="fa-solid fa-volume-high me-2 fs-5"></i> Hear Pronunciation
                </button>

                <div class="d-block text-center">
                    <div class="mic-btn-container">
                        <button id="btnMic" class="btn-mic">
                            <i class="fa-solid fa-microphone" id="micIcon"></i>
                        </button>
                    </div>
                    <div id="micStatus" class="fw-semibold text-secondary mt-2 fs-5">
                        Tap microphone & say the word
                    </div>
                </div>

                <div id="transcriptBox" class="mt-3 text-muted small fst-italic" style="min-height: 24px;"></div>

                <div id="feedbackBox" class="feedback-box">
                    <div class="star-rating mb-2" id="starContainer">
                        <i class="fa-solid fa-star"></i>
                        <i class="fa-solid fa-star"></i>
                        <i class="fa-solid fa-star"></i>
                    </div>
                    <h4 id="feedbackTitle" class="fw-bold mb-1">Awesome Job!</h4>
                    <p id="feedbackText" class="mb-0 small text-secondary">We heard you clearly!</p>
                </div>
            </div>
        </div>

        <div class="col-lg-5">
            <h4 class="fw-bold text-dark mb-3 brand-font">
                <i class="fa-solid fa-layer-group text-success me-2"></i>Select Level & Practice
            </h4>

            <div class="sidebar-scroll-container">
                <?php foreach ($practiceCards as $group): ?>
                    <div class="mb-4">
                        <div class="d-flex align-items-center justify-content-between mb-2">
                            <span class="fw-bold text-secondary uppercase small" style="font-size: 0.85rem;"><?php echo htmlspecialchars($group['category']); ?></span>
                            <span class="badge <?php echo $group['badge']; ?> rounded-pill small"><?php echo htmlspecialchars($group['level']); ?></span>
                        </div>

                        <div class="row g-2">
                            <?php foreach ($group['items'] as $index => $item): ?>
                                <div class="col-12">
                                    <div class="word-select-card <?php echo ($item['word'] === 'Water') ? 'active' : ''; ?>"
                                         onclick="selectWord('<?php echo htmlspecialchars($item['id'], ENT_QUOTES); ?>', '<?php echo htmlspecialchars($item['word'], ENT_QUOTES); ?>', '<?php echo htmlspecialchars($item['phonetic'], ENT_QUOTES); ?>', '<?php echo htmlspecialchars($group['level'] . ' • ' . $group['category'], ENT_QUOTES); ?>', this)">
                                        <div class="icon-box" style="background-color: <?php echo $item['color']; ?>20; color: <?php echo $item['color']; ?>;">
                                            <i class="fa-solid <?php echo $item['icon']; ?>"></i>
                                        </div>
                                        <div class="flex-grow-1">
                                            <div class="fw-bold text-dark fs-6"><?php echo htmlspecialchars($item['word']); ?></div>
                                            <div class="text-muted small" style="font-size: 0.78rem;"><?php echo htmlspecialchars($item['phonetic']); ?></div>
                                        </div>
                                        <i class="fa-solid fa-chevron-right text-muted small"></i>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

<script>
    let currentWordId = "w1";
    let currentWord = "Water";
    let currentPhonetic = "WAH-ter";
    let isRecording = false;

    const targetWordElem = document.getElementById('targetWord');
    const targetPhoneticElem = document.getElementById('targetPhonetic');
    const activeCategoryBadge = document.getElementById('activeCategoryBadge');
    const btnListen = document.getElementById('btnListen');
    const btnMic = document.getElementById('btnMic');
    const micIcon = document.getElementById('micIcon');
    const micStatus = document.getElementById('micStatus');
    const transcriptBox = document.getElementById('transcriptBox');
    const feedbackBox = document.getElementById('feedbackBox');
    const feedbackTitle = document.getElementById('feedbackTitle');
    const feedbackText = document.getElementById('feedbackText');
    const starContainer = document.getElementById('starContainer');

    const synth = window.speechSynthesis;

    function speakWord(text) {
        if (!synth) return;
        synth.cancel();
        const utterance = new SpeechSynthesisUtterance(text);
        utterance.rate = 0.85;
        utterance.pitch = 1.0;
        synth.speak(utterance);
    }

    btnListen.addEventListener('click', () => {
        speakWord(currentWord);
    });

    function selectWord(id, word, phonetic, badgeInfo, cardElement) {
        currentWordId = id;
        currentWord = word;
        currentPhonetic = phonetic;

        targetWordElem.textContent = word;
        targetWordElem.setAttribute('data-id', id);
        targetPhoneticElem.textContent = `[ ${phonetic} ]`;
        activeCategoryBadge.textContent = badgeInfo;

        feedbackBox.style.display = 'none';
        transcriptBox.textContent = '';
        micStatus.textContent = 'Tap microphone & say the word';

        document.querySelectorAll('.word-select-card').forEach(card => card.classList.remove('active'));
        if (cardElement) cardElement.classList.add('active');

        speakWord(word);
    }

    const SpeechRecognition = window.SpeechRecognition || window.webkitSpeechRecognition;
    let recognition = null;

    if (SpeechRecognition) {
        recognition = new SpeechRecognition();
        recognition.continuous = false;
        recognition.interimResults = true;
        recognition.lang = 'en-US';

        recognition.onstart = function() {
            isRecording = true;
            btnMic.classList.add('recording');
            micIcon.className = 'fa-solid fa-stop';
            micStatus.textContent = 'Listening... Speak now!';
            micStatus.classList.replace('text-secondary', 'text-danger');
            feedbackBox.style.display = 'none';
            transcriptBox.textContent = '';
        };

        recognition.onresult = function(event) {
            let transcript = '';
            for (let i = event.resultIndex; i < event.results.length; i++) {
                transcript += event.results[i][0].transcript;
            }
            transcriptBox.textContent = `Heard: "${transcript}"`;

            if (event.results[0].isFinal) {
                evaluateSpeech(transcript.trim());
            }
        };

        recognition.onerror = function(event) {
            stopRecording();
            micStatus.textContent = 'Couldn\'t catch that. Tap mic and try again!';
            micStatus.classList.replace('text-danger', 'text-secondary');
        };

        recognition.onend = function() {
            stopRecording();
        };
    } else {
        micStatus.textContent = 'Speech recognition is not supported on this browser (Try Google Chrome or MS Edge).';
        btnMic.disabled = true;
        btnMic.style.opacity = '0.5';
    }

    btnMic.addEventListener('click', () => {
        if (!recognition) return;

        if (isRecording) {
            recognition.stop();
        } else {
            recognition.start();
        }
    });

    function stopRecording() {
        isRecording = false;
        btnMic.classList.remove('recording');
        micIcon.className = 'fa-solid fa-microphone';
        if (micStatus.textContent === 'Listening... Speak now!') {
            micStatus.textContent = 'Tap microphone & say the word';
            micStatus.classList.replace('text-danger', 'text-secondary');
        }
    }

    function saveAttemptToServer(spokenText, isCorrect, stars, feedbackType) {
        const formData = new URLSearchParams();
        formData.append('action', 'save_attempt');
        formData.append('word_id', currentWordId);
        formData.append('word', currentWord);
        formData.append('spoken_text', spokenText);
        formData.append('is_correct', isCorrect);
        formData.append('stars', stars);
        formData.append('feedback_type', feedbackType);

        fetch(window.location.href, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: formData.toString()
        })
        .then(response => response.json())
        .then(data => {
            console.log('Attempt logged:', data);
        })
        .catch(error => {
            console.error('Error logging attempt:', error);
        });
    }

    function evaluateSpeech(spokenText) {
        const cleanSpoken = spokenText.toLowerCase().replace(/[^a-z0-9 ]/g, '').trim();
        const cleanTarget = currentWord.toLowerCase().replace(/[^a-z0-9 ]/g, '').trim();

        feedbackBox.style.display = 'block';

        const stars = starContainer.querySelectorAll('i');
        stars.forEach(s => s.classList.remove('active'));

        const spokenWordsArray = cleanSpoken.split(/\s+/);
        const targetWordsArray = cleanTarget.split(/\s+/);

        if (cleanSpoken === cleanTarget) {
            stars.forEach(s => s.classList.add('active'));
            feedbackBox.className = 'feedback-box bg-success-subtle text-success border border-success';
            feedbackTitle.textContent = '🌟 Perfect Pronunciation!';
            feedbackText.innerHTML = `Great job! You said <b>"${spokenText}"</b> correctly.`;
            speakWord('Great job! Perfect!');
            saveAttemptToServer(spokenText, 1, 3, 'perfect');
            return;
        }

        if (targetWordsArray.length === 1 && spokenWordsArray.length > 1) {
            if (spokenWordsArray.includes(cleanTarget)) {
                stars.forEach(s => s.classList.remove('active'));
                feedbackBox.className = 'feedback-box bg-warning-subtle text-warning-emphasis border border-warning';
                feedbackTitle.textContent = '⚠️ Extra Words Detected!';
                feedbackText.innerHTML = `You correctly pronounced <b>"${currentWord}"</b>, but you added extra words: <b>"${spokenText}"</b>. Please say ONLY the target word!`;
                speakWord(`You said ${currentWord}, but added extra words. Please say only, ${currentWord}.`);
                saveAttemptToServer(spokenText, 0, 0, 'extra_words');
                return;
            }
        }

        let isVerb4Mismatch = (cleanSpoken === cleanTarget + 'ing' || cleanSpoken === cleanTarget + 'g' || cleanSpoken === cleanTarget + 'ed');
        if (isVerb4Mismatch) {
            stars.forEach(s => s.classList.remove('active'));
            feedbackBox.className = 'feedback-box bg-warning-subtle text-warning-emphasis border border-warning';
            feedbackTitle.textContent = '⚠️ Verb Form Notice (Verb-4 / Modification)';
            feedbackText.innerHTML = `You said <b>"${spokenText}"</b> (modified form). The target requires the exact base form <b>"${currentWord}"</b>. Try again!`;
            speakWord(`Good try! You said ${spokenText}, but please use the base form, ${currentWord}.`);
            saveAttemptToServer(spokenText, 0, 0, 'verb_mismatch');
            return;
        }

        if (cleanSpoken === cleanTarget + 's' || cleanSpoken === cleanTarget + 'es') {
            stars.forEach(s => s.classList.remove('active'));
            feedbackBox.className = 'feedback-box bg-warning-subtle text-warning-emphasis border border-warning';
            feedbackTitle.textContent = '⚠️ Plural Form Detected';
            feedbackText.innerHTML = `You said <b>"${spokenText}"</b> (Plural). Please say the singular form <b>"${currentWord}"</b>.`;
            speakWord(`You said plural. Please say singular, ${currentWord}.`);
            saveAttemptToServer(spokenText, 0, 0, 'plural_detected');
            return;
        }

        let targetFirstLetter = cleanTarget.charAt(0);
        let spokenFirstLetter = cleanSpoken.charAt(0);

        if (targetFirstLetter === spokenFirstLetter) {
            stars.forEach(s => s.classList.remove('active'));
            feedbackBox.className = 'feedback-box bg-warning-subtle text-warning-emphasis border border-warning';
            feedbackTitle.textContent = '🔍 Almost There!';
            feedbackText.innerHTML = `We heard <b>"${spokenText}"</b>, but the target is <b>"${currentWord}"</b>. Listen to the audio and try again!`;
            speakWord(`Not quite. You said ${spokenText}. Let's try ${currentWord}.`);
            saveAttemptToServer(spokenText, 0, 0, 'almost_there');
        } else {
            stars.forEach(s => s.classList.remove('active'));
            feedbackBox.className = 'feedback-box bg-danger-subtle text-danger border border-danger';
            feedbackTitle.textContent = '❌ Keep Practicing!';
            feedbackText.innerHTML = `That sounded like <b>"${spokenText}"</b>. Let's say <b>"${currentWord}"</b> together.`;
            speakWord(`Let's try again. Say, ${currentWord}.`);
            saveAttemptToServer(spokenText, 0, 0, 'incorrect');
        }
    }
</script>
</body>
</html>
