<?php
// student/calm.php
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
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Calm Sensory Zone | <?php echo defined('SITE_NAME') ? SITE_NAME : 'AutiLearn AI'; ?></title>
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- FontAwesome Icons -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" rel="stylesheet">
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Fredoka:wght@400;600;700&family=Poppins:wght@400;500;600&display=swap" rel="stylesheet">

    <style>
        :root {
            --bg-calm: #f0fdfa;
            --teal-primary: #0d9488;
            --teal-light: #ccfbf1;
            --card-radius: 24px;
        }

        body {
            background-color: var(--bg-calm);
            font-family: 'Poppins', sans-serif;
            color: #1e293b;
            padding-bottom: 80px;
        }

        h1, h2, h3, h4, .brand-font {
            font-family: 'Fredoka', cursive, sans-serif;
        }

        .navbar-calm {
            background: #ffffff;
            border-bottom: 2px solid #ccfbf1;
            padding: 14px 0;
        }

        /* GUIDED BREATHING ORB */
        .breathing-container {
            background: #ffffff;
            border-radius: var(--card-radius);
            padding: 40px 20px;
            box-shadow: 0 15px 35px rgba(13, 148, 136, 0.08);
            border: 3px solid #99f6e4;
            text-align: center;
            position: relative;
            overflow: hidden;
        }

        .orb-wrapper {
            width: 240px;
            height: 240px;
            margin: 30px auto;
            position: relative;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .breathing-orb {
            width: 140px;
            height: 140px;
            border-radius: 50%;
            background: radial-gradient(circle, #2dd4bf 0%, #0d9488 100%);
            box-shadow: 0 0 40px rgba(45, 212, 191, 0.5);
            transition: transform 4s ease-in-out, background 4s ease-in-out;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #ffffff;
            font-family: 'Fredoka', cursive;
            font-size: 1.5rem;
            user-select: none;
        }

        .breathing-orb.inhale {
            transform: scale(1.65);
            box-shadow: 0 0 70px rgba(45, 212, 191, 0.8);
            background: radial-gradient(circle, #38bdf8 0%, #0284c7 100%);
        }

        .breathing-orb.hold {
            transform: scale(1.65);
            box-shadow: 0 0 80px rgba(251, 191, 36, 0.7);
            background: radial-gradient(circle, #fbbf24 0%, #d97706 100%);
        }

        .breathing-orb.exhale {
            transform: scale(1);
            box-shadow: 0 0 30px rgba(45, 212, 191, 0.4);
            background: radial-gradient(circle, #2dd4bf 0%, #0d9488 100%);
        }

        /* SOUND GENERATOR CARDS */
        .sound-card {
            background: #ffffff;
            border-radius: 20px;
            padding: 20px;
            border: 2px solid #e2e8f0;
            text-align: center;
            cursor: pointer;
            transition: all 0.25s ease;
        }

        .sound-card:hover {
            transform: translateY(-4px);
            border-color: #0d9488;
            box-shadow: 0 10px 20px rgba(13, 148, 136, 0.1);
        }

        .sound-card.active {
            border-color: #0d9488;
            background-color: #ccfbf1;
            box-shadow: 0 10px 25px rgba(13, 148, 136, 0.2);
        }

        .sound-icon {
            width: 60px;
            height: 60px;
            border-radius: 50%;
            background: #f0fdfa;
            color: #0d9488;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.6rem;
            margin: 0 auto 12px;
            transition: all 0.3s ease;
        }

        .sound-card.active .sound-icon {
            background: #0d9488;
            color: #ffffff;
        }

        /* SENSORY POPPING GRID */
        .fidget-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 15px;
            max-width: 320px;
            margin: 0 auto;
        }

        .fidget-bubble {
            aspect-ratio: 1;
            border-radius: 50%;
            background: linear-gradient(145deg, #e0e7ff, #c7d2fe);
            border: 3px solid #818cf8;
            cursor: pointer;
            box-shadow: inset 0 -4px 6px rgba(0, 0, 0, 0.15), 0 6px 12px rgba(0, 0, 0, 0.08);
            transition: all 0.15s ease;
        }

        .fidget-bubble:active, .fidget-bubble.popped {
            transform: scale(0.92);
            background: linear-gradient(145deg, #818cf8, #6366f1);
            box-shadow: inset 0 4px 6px rgba(0, 0, 0, 0.3);
        }
    </style>
</head>
<body>

<!-- NAVIGATION BAR -->
<nav class="navbar navbar-calm sticky-top mb-4">
    <div class="container">
        <a class="navbar-brand brand-font fs-3 text-teal d-flex align-items-center gap-2" href="<?php echo htmlspecialchars($dashboardUrl); ?>" style="color: #0d9488;">
            <i class="fa-solid fa-arrow-left fs-4 me-1 text-secondary"></i>
            <i class="fa-solid fa-spa fs-2" style="color: #0d9488;"></i> Calm Sensory Zone
        </a>
        <span class="badge bg-teal-subtle text-teal rounded-pill px-3 py-2 fw-semibold fs-6" style="background: #ccfbf1; color: #0f766e;">
            <i class="fa-solid fa-heart me-1"></i> Relax & Ground
        </span>
    </div>
</nav>

<div class="container">
    <div class="row g-4">

        <!-- GUIDED BREATHING EXERCISE -->
        <div class="col-lg-6">
            <div class="breathing-container h-100">
                <span class="badge bg-teal-subtle rounded-pill px-3 py-2 fw-semibold fs-6 mb-2" style="background: #ccfbf1; color: #0f766e;">
                    4-4-4 Box Breathing
                </span>
                <h3 class="brand-font fw-bold mt-2 text-dark">Guided Breathing</h3>
                <p class="text-muted small">Follow the sphere to slow your heart rate and ease anxiety</p>

                <div class="orb-wrapper">
                    <div id="breathingOrb" class="breathing-orb">Ready</div>
                </div>

                <div id="breathInstruction" class="fw-bold fs-4 text-dark mb-4 brand-font" style="min-height: 40px;">
                    Tap Start to begin
                </div>

                <div class="d-flex justify-content-center gap-3">
                    <button id="btnStartBreathing" class="btn btn-teal btn-lg rounded-pill px-5 text-white fw-bold" style="background-color: #0d9488;">
                        <i class="fa-solid fa-play me-2"></i> Start Breathing
                    </button>
                    <button id="btnStopBreathing" class="btn btn-outline-secondary btn-lg rounded-pill px-4 fw-bold" disabled>
                        <i class="fa-solid fa-pause me-2"></i> Stop
                    </button>
                </div>
            </div>
        </div>

        <!-- AMBIENT SOUNDS & SENSORY FIDGET -->
        <div class="col-lg-6">
            <div class="row g-4">
                
                <!-- Ambient Soothing Sounds -->
                <div class="col-12">
                    <div class="bg-white rounded-4 p-4 border border-2 border-light-subtle shadow-sm">
                        <div class="d-flex align-items-center justify-content-between mb-3">
                            <h4 class="brand-font fw-bold m-0 text-dark">
                                <i class="fa-solid fa-headphones text-teal me-2" style="color: #0d9488;"></i>Ambient Soothing Sounds
                            </h4>
                            <span id="soundStatus" class="small text-muted fw-semibold">Off</span>
                        </div>

                        <div class="row g-3 row-cols-3">
                            <div class="col">
                                <div class="sound-card" id="cardRain" onclick="toggleSound('rain')">
                                    <div class="sound-icon"><i class="fa-solid fa-cloud-showers-heavy"></i></div>
                                    <div class="fw-bold text-dark small">Gentle Rain</div>
                                </div>
                            </div>
                            <div class="col">
                                <div class="sound-card" id="cardOcean" onclick="toggleSound('ocean')">
                                    <div class="sound-icon"><i class="fa-solid fa-water"></i></div>
                                    <div class="fw-bold text-dark small">Ocean Waves</div>
                                </div>
                            </div>
                            <div class="col">
                                <div class="sound-card" id="cardWhiteNoise" onclick="toggleSound('whitenoise')">
                                    <div class="sound-icon"><i class="fa-solid fa-wind"></i></div>
                                    <div class="fw-bold text-dark small">Soft Wind</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Grounding Fidget Pop Grid -->
                <div class="col-12">
                    <div class="bg-white rounded-4 p-4 border border-2 border-light-subtle shadow-sm text-center">
                        <div class="d-flex align-items-center justify-content-between mb-3">
                            <h4 class="brand-font fw-bold m-0 text-dark">
                                <i class="fa-solid fa-circle-dot text-indigo me-2" style="color: #6366f1;"></i>Sensory Bubble Pop
                            </h4>
                            <button class="btn btn-sm btn-outline-primary rounded-pill px-3" onclick="resetBubbles()">Reset</button>
                        </div>
                        <p class="text-muted small mb-3">Tap bubbles for satisfying tactile sensory relaxation</p>

                        <div class="fidget-grid" id="fidgetGrid">
                            <!-- 12 Fidget Bubbles -->
                            <div class="fidget-bubble" onclick="popBubble(this)"></div>
                            <div class="fidget-bubble" onclick="popBubble(this)"></div>
                            <div class="fidget-bubble" onclick="popBubble(this)"></div>
                            <div class="fidget-bubble" onclick="popBubble(this)"></div>
                            <div class="fidget-bubble" onclick="popBubble(this)"></div>
                            <div class="fidget-bubble" onclick="popBubble(this)"></div>
                            <div class="fidget-bubble" onclick="popBubble(this)"></div>
                            <div class="fidget-bubble" onclick="popBubble(this)"></div>
                            <div class="fidget-bubble" onclick="popBubble(this)"></div>
                            <div class="fidget-bubble" onclick="popBubble(this)"></div>
                            <div class="fidget-bubble" onclick="popBubble(this)"></div>
                            <div class="fidget-bubble" onclick="popBubble(this)"></div>
                        </div>
                    </div>
                </div>

            </div>
        </div>

    </div>
</div>

<!-- Bootstrap 5 JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

<script>
    // --- GUIDED BREATHING LOGIC ---
    let breathingInterval = null;
    let isBreathingActive = false;

    const orb = document.getElementById('breathingOrb');
    const instruction = document.getElementById('breathInstruction');
    const btnStart = document.getElementById('btnStartBreathing');
    const btnStop = document.getElementById('btnStopBreathing');

    btnStart.addEventListener('click', startBreathingCycle);
    btnStop.addEventListener('click', stopBreathingCycle);

    function startBreathingCycle() {
        if (isBreathingActive) return;
        isBreathingActive = true;
        btnStart.disabled = true;
        btnStop.disabled = false;

        runCycle();
        breathingInterval = setInterval(runCycle, 12000); // 4s inhale + 4s hold + 4s exhale
    }

    function runCycle() {
        // Phase 1: Inhale (4s)
        orb.className = 'breathing-orb inhale';
        orb.textContent = 'Inhale';
        instruction.textContent = '🌬️ Breathe in slowly...';

        // Phase 2: Hold (4s)
        setTimeout(() => {
            if (!isBreathingActive) return;
            orb.className = 'breathing-orb hold';
            orb.textContent = 'Hold';
            instruction.textContent = '⏸️ Gently hold your breath...';
        }, 4000);

        // Phase 3: Exhale (4s)
        setTimeout(() => {
            if (!isBreathingActive) return;
            orb.className = 'breathing-orb exhale';
            orb.textContent = 'Exhale';
            instruction.textContent = '😮‍💨 Slowly blow all the air out...';
        }, 8000);
    }

    function stopBreathingCycle() {
        isBreathingActive = false;
        clearInterval(breathingInterval);
        btnStart.disabled = false;
        btnStop.disabled = true;

        orb.className = 'breathing-orb';
        orb.textContent = 'Ready';
        instruction.textContent = 'Tap Start to begin';
    }


    // --- SYNTHESIZED AMBIENT SOUND GENERATOR (WEB AUDIO API) ---
    let audioCtx = null;
    let activeSource = null;
    let currentSoundType = null;

    function initAudioContext() {
        if (!audioCtx) {
            audioCtx = new (window.AudioContext || window.webkitAudioContext)();
        }
        if (audioCtx.state === 'suspended') {
            audioCtx.resume();
        }
    }

    function toggleSound(type) {
        initAudioContext();

        // Clear active selection if same button clicked
        if (currentSoundType === type) {
            stopAmbientSound();
            return;
        }

        stopAmbientSound();
        currentSoundType = type;

        // Highlight Active UI
        document.querySelectorAll('.sound-card').forEach(c => c.classList.remove('active'));
        const activeCard = document.getElementById(`card${type.charAt(0).toUpperCase() + type.slice(1)}`);
        if (activeCard) activeCard.classList.add('active');

        document.getElementById('soundStatus').textContent = `Playing ${type}`;

        // Create Pink/White Noise Synthesizer for Ambient Sound
        const bufferSize = audioCtx.sampleRate * 2;
        const noiseBuffer = audioCtx.createBuffer(1, bufferSize, audioCtx.sampleRate);
        const output = noiseBuffer.getChannelData(0);

        let b0 = 0, b1 = 0, b2 = 0, b3 = 0, b4 = 0, b5 = 0, b6 = 0;
        for (let i = 0; i < bufferSize; i++) {
            let white = Math.random() * 2 - 1;
            // Pink noise filtering for soothing acoustics
            b0 = 0.99886 * b0 + white * 0.0555179;
            b1 = 0.99332 * b1 + white * 0.0750759;
            b2 = 0.96900 * b2 + white * 0.1538520;
            b3 = 0.86650 * b3 + white * 0.3104856;
            b4 = 0.55000 * b4 + white * 0.5329522;
            b5 = -0.7616 * b5 - white * 0.0168980;
            output[i] = b0 + b1 + b2 + b3 + b4 + b5 + b6 + white * 0.5362;
            output[i] *= 0.11;
            b6 = white * 0.115926;
        }

        const whiteNoise = audioCtx.createBufferSource();
        whiteNoise.buffer = noiseBuffer;
        whiteNoise.loop = true;

        const filter = audioCtx.createBiquadFilter();

        if (type === 'rain') {
            filter.type = 'lowpass';
            filter.frequency.value = 1000;
        } else if (type === 'ocean') {
            filter.type = 'bandpass';
            filter.frequency.value = 400;
            filter.Q.value = 1.2;
        } else {
            filter.type = 'lowpass';
            filter.frequency.value = 600;
        }

        const gainNode = audioCtx.createGain();
        gainNode.gain.value = 0.3;

        whiteNoise.connect(filter);
        filter.connect(gainNode);
        gainNode.connect(audioCtx.destination);

        whiteNoise.start();
        activeSource = whiteNoise;
    }

    function stopAmbientSound() {
        if (activeSource) {
            try { activeSource.stop(); } catch(e){}
            activeSource = null;
        }
        currentSoundType = null;
        document.querySelectorAll('.sound-card').forEach(c => c.classList.remove('active'));
        document.getElementById('soundStatus').textContent = 'Off';
    }


    // --- SENSORY BUBBLE POP LOGIC ---
    function popBubble(element) {
        if (!element.classList.contains('popped')) {
            element.classList.add('popped');
            playPopSound();
        }
    }

    function resetBubbles() {
        document.querySelectorAll('.fidget-bubble').forEach(b => b.classList.remove('popped'));
    }

    function playPopSound() {
        initAudioContext();
        const osc = audioCtx.createOscillator();
        const gain = audioCtx.createGain();

        osc.type = 'sine';
        osc.frequency.setValueAtTime(400, audioCtx.currentTime);
        osc.frequency.exponentialRampToValueAtTime(800, audioCtx.currentTime + 0.08);

        gain.gain.setValueAtTime(0.3, audioCtx.currentTime);
        gain.gain.exponentialRampToValueAtTime(0.01, audioCtx.currentTime + 0.08);

        osc.connect(gain);
        gain.connect(audioCtx.destination);

        osc.start();
        osc.stop(audioCtx.currentTime + 0.08);
    }
</script>
</body>
</html>