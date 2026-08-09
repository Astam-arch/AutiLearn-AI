<?php
// pages/home.php
?>

<!-- Import Google Fonts for cleaner, modern typography -->
<link href="https://fonts.googleapis.com/css2?family=Outfit:wght@400;500;600;700;800&family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">

<style>
    /* Global Section & Font Overrides */
    body {
        font-family: 'Plus Jakarta Sans', sans-serif;
    }

    h1, h2, h3, h4, h5, h6, .hero-title {
        font-family: 'Outfit', sans-serif;
    }

    /* Hero Section - Perfect Viewport Fit */
    .hero-section {
        min-height: calc(100vh - 76px);
        height: calc(100vh - 76px);
        max-height: 900px;
        display: flex;
        align-items: center;
        position: relative;
        background: radial-gradient(circle at 15% 20%, rgba(243, 244, 255, 1) 0%, rgba(248, 250, 252, 1) 90%);
        overflow: hidden;
        padding: 0 0 50px 0;
    }

    .hero-title {
        font-size: 3.2rem;
        font-weight: 800;
        line-height: 1.15;
        color: #0f172a;
        letter-spacing: -0.03em;
    }

    .hero-title span {
        background: linear-gradient(135deg, #4f46e5 0%, #9333ea 100%);
        -webkit-background-clip: text;
        -webkit-text-fill-color: transparent;
    }

    .hero-subtitle {
        font-size: 1.1rem;
        color: #475569;
        margin-top: 16px;
        line-height: 1.65;
        max-width: 540px;
        font-weight: 500;
    }

    .hero-img-wrapper {
        position: relative;
    }

    .hero-img-wrapper img {
        border-radius: 28px;
        box-shadow: 0 20px 50px rgba(79, 70, 229, 0.12);
        animation: floatImage 6s ease-in-out infinite;
        max-width: 85%;
        height: auto;
    }

    @keyframes floatImage {
        0%, 100% { transform: translateY(0px) rotate(0deg); }
        50% { transform: translateY(-10px) rotate(0.4deg); }
    }

    .badge-sensory {
        display: inline-flex;
        align-items: center;
        background: rgba(79, 70, 229, 0.08);
        color: #4f46e5;
        padding: 6px 16px;
        border-radius: 50rem;
        font-weight: 700;
        font-size: 0.82rem;
        letter-spacing: 0.02em;
    }

    /* Perfectly Centered & Clear Scroll Indicator */
    .scroll-indicator {
        position: absolute;
        bottom: 15px;
        left: 50%;
        transform: translateX(-50%);
        text-align: center;
        color: #64748b;
        font-size: 0.8rem;
        font-weight: 600;
        letter-spacing: 0.05em;
        text-transform: uppercase;
        cursor: pointer;
        z-index: 10;
        transition: color 0.2s ease;
    }

    .scroll-indicator:hover {
        color: #4f46e5;
    }

    .scroll-indicator .mouse-icon {
        width: 22px;
        height: 36px;
        border: 2px solid #cbd5e1;
        border-radius: 12px;
        margin: 0 auto 6px auto;
        position: relative;
    }

    .scroll-indicator .wheel {
        width: 4px;
        height: 8px;
        background: #4f46e5;
        border-radius: 2px;
        position: absolute;
        top: 6px;
        left: 50%;
        transform: translateX(-50%);
        animation: scrollWheel 1.8s infinite;
    }

    @keyframes scrollWheel {
        0% { transform: translateX(-50%) translateY(0); opacity: 1; }
        100% { transform: translateX(-50%) translateY(12px); opacity: 0; }
    }

    /* Sensory Cards & Containers */
    .sensory-card {
        background: #ffffff;
        border-radius: 24px;
        padding: 32px 24px;
        border: 1px solid #e2e8f0;
        transition: all 0.3s cubic-bezier(0.16, 1, 0.3, 1);
        height: 100%;
        box-shadow: 0 4px 20px rgba(15, 23, 42, 0.02);
    }

    .sensory-card:hover {
        transform: translateY(-6px);
        box-shadow: 0 20px 40px rgba(79, 70, 229, 0.08);
        border-color: #cbd5e1;
    }

    .category-card {
        text-align: center;
        padding: 28px 16px;
        border-radius: 20px;
        background: #ffffff;
        border: 1px solid #e2e8f0;
        transition: all 0.3s cubic-bezier(0.16, 1, 0.3, 1);
        cursor: pointer;
        height: 100%;
        box-shadow: 0 4px 15px rgba(0, 0, 0, 0.01);
    }

    .category-card:hover {
        background: #4f46e5;
        color: #ffffff;
        transform: translateY(-6px);
        box-shadow: 0 16px 35px rgba(79, 70, 229, 0.25);
        border-color: #4f46e5;
    }

    .category-card:hover i,
    .category-card:hover h6 {
        color: #ffffff !important;
    }

    .category-icon {
        font-size: 2.5rem;
        color: #4f46e5;
        margin-bottom: 14px;
        transition: all 0.3s ease;
    }

    .step-card {
        background: #ffffff;
        border-radius: 24px;
        padding: 32px 20px;
        border: 1px solid #e2e8f0;
        height: 100%;
        transition: all 0.3s ease;
        box-shadow: 0 4px 20px rgba(15, 23, 42, 0.02);
    }

    .step-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 15px 30px rgba(0, 0, 0, 0.05);
    }

    /* Action Buttons */
    .btn-custom-primary {
        background: linear-gradient(135deg, #4f46e5 0%, #6366f1 100%);
        color: #ffffff;
        border-radius: 50rem;
        padding: 13px 30px;
        font-weight: 700;
        border: none;
        box-shadow: 0 10px 25px rgba(79, 70, 229, 0.25);
        transition: all 0.3s ease;
    }

    .btn-custom-primary:hover {
        color: #ffffff;
        transform: translateY(-2px);
        box-shadow: 0 14px 30px rgba(79, 70, 229, 0.35);
        filter: brightness(1.05);
    }

    .btn-custom-outline {
        background: #ffffff;
        color: #0f172a;
        border: 2px solid #e2e8f0;
        border-radius: 50rem;
        padding: 13px 30px;
        font-weight: 700;
        transition: all 0.3s ease;
        box-shadow: 0 4px 15px rgba(0, 0, 0, 0.01);
    }

    .btn-custom-outline:hover {
        border-color: #4f46e5;
        color: #4f46e5;
        background: rgba(79, 70, 229, 0.03);
        transform: translateY(-2px);
    }

    /* Responsive adjustments for Tablets & Mobile Screens */
    @media (max-width: 991px) {
        .hero-section {
            min-height: auto;
            height: auto;
            padding: 50px 0 70px 0;
        }
        .hero-title { 
            font-size: 2.3rem; 
        }
        .hero-subtitle { 
            margin: 14px auto; 
            font-size: 1rem;
        }
        .hero-img-wrapper { 
            margin-top: 25px; 
        }
        .hero-img-wrapper img {
            max-width: 100%;
        }
        .scroll-indicator { 
            display: none; 
        }
    }
</style>

<!-- HERO SECTION (Fits exact first view up to watch overview) -->
<section class="hero-section">
    <div class="container my-auto">
        <div class="row align-items-center g-4">
            <div class="col-lg-6" data-aos="fade-right" data-aos-duration="1000">
                <span class="badge-sensory mb-3"><i class="fa-solid fa-sparkles me-2"></i> Spark Steps Platform</span>
                <h1 class="hero-title">
                    Empowering Every <span>Autistic Child</span> To Learn At Their Own Pace
                </h1>
                <p class="hero-subtitle">
                    Spark Steps Development combines picture exchange communication system (PECS), adaptive speech recognition, and real-time emotion detection to deliver calm, engaging, and personalized education.
                </p>
                <div class="d-flex flex-wrap gap-3 mt-4 justify-content-center justify-content-lg-start">
                    <a href="register.php" class="btn btn-custom-primary btn-lg d-inline-flex align-items-center gap-2">
                        <i class="fa-solid fa-rocket"></i> Tap here to learn
                    </a>
                    <a href="pages/about.php" class="btn btn-custom-outline btn-lg d-inline-flex align-items-center gap-2">
                        <i class="fa-solid fa-circle-play text-primary"></i> Watch Overview
                    </a>
                </div>
            </div>

            <div class="col-lg-6 text-center" data-aos="fade-left" data-aos-duration="1000">
                <div class="hero-img-wrapper d-inline-block">
                    <img src="assets/images/hero.png" alt="Spark Steps Development Learning Experience" class="img-fluid" onerror="this.src='https://images.unsplash.com/photo-1588072432836-e10032774350?auto=format&fit=crop&w=750&q=80'">
                </div>
            </div>
        </div>
    </div>
    
    <!-- Clean Interactive Mouse Scroll Indicator -->
    <div class="scroll-indicator d-none d-lg-block" onclick="window.scrollTo({top: window.innerHeight - 70, behavior: 'smooth'});">
        <div class="mouse-icon">
            <div class="wheel"></div>
        </div>
        <span>Scroll Down</span>
    </div>
</section>

<!-- WHY SPARK STEPS (LEARNING PILLARS) -->
<section class="py-5" style="background-color: #ffffff;">
    <div class="container py-4">
        <div class="text-center mb-5" data-aos="fade-up">
            <span class="badge-sensory mb-3">Core Philosophy</span>
            <h2 class="display-6 fw-bold text-dark mb-3">Why Choose Spark Steps Development?</h2>
            <p class="text-muted mx-auto fs-6 fw-medium" style="max-width: 620px;">Traditional methods can lead to sensory overload. We build learning around predictable routines, high-visual feedback, and soothing praise.</p>
        </div>

        <div class="row g-4">
            <div class="col-md-6 col-lg-4" data-aos="fade-up" data-aos-delay="100">
                <div class="sensory-card">
                    <div class="bg-primary bg-opacity-10 d-inline-flex align-items-center justify-content-center mb-4 text-primary fs-3" style="width: 60px; height: 60px; border-radius: 16px;">
                        <i class="fa-solid fa-images"></i>
                    </div>
                    <h4 class="fw-bold text-dark mb-3 fs-5">PECS Picture Learning</h4>
                    <p class="text-muted mb-0 lh-base fs-6">High-contrast, distraction-free visual cards help children associate words with real-world objects effortlessly.</p>
                </div>
            </div>

            <div class="col-md-6 col-lg-4" data-aos="fade-up" data-aos-delay="200">
                <div class="sensory-card">
                    <div class="bg-success bg-opacity-10 d-inline-flex align-items-center justify-content-center mb-4 text-success fs-3" style="width: 60px; height: 60px; border-radius: 16px;">
                        <i class="fa-solid fa-microphone-lines"></i>
                    </div>
                    <h4 class="fw-bold text-dark mb-3 fs-5">Gentle Speech Assistant</h4>
                    <p class="text-muted mb-0 lh-base fs-6">Speech-to-text calibrated for non-verbal and speech-delayed children with zero time pressure or loud penalties.</p>
                </div>
            </div>

            <div class="col-md-6 col-lg-4" data-aos="fade-up" data-aos-delay="300">
                <div class="sensory-card">
                    <div class="d-inline-flex align-items-center justify-content-center mb-4 fs-3" style="width: 60px; height: 60px; border-radius: 16px; color: #9333ea; background-color: rgba(147, 51, 234, 0.1);">
                        <i class="fa-solid fa-face-smile"></i>
                    </div>
                    <h4 class="fw-bold text-dark mb-3 fs-5">Emotion AI Detection</h4>
                    <p class="text-muted mb-0 lh-base fs-6">Camera-based facial cues sense frustration early, automatically switching lessons to soothing break activities.</p>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- LEARNING CATEGORIES -->
<section class="py-5" style="background-color: #f8fafc;">
    <div class="container py-4">
        <div class="text-center mb-5" data-aos="fade-up">
            <span class="badge-sensory mb-3">Interactive Curriculum</span>
            <h2 class="display-6 fw-bold text-dark mb-2">Explore Interactive Categories</h2>
            <p class="text-muted fs-6">Curated modules designed with structured predictability.</p>
        </div>

        <div class="row g-4">
            <?php
            $categories = [
                ["icon" => "fa-apple-whole", "title" => "Fruits & Food", "delay" => "100"],
                ["icon" => "fa-dog", "title" => "Animals", "delay" => "150"],
                ["icon" => "fa-palette", "title" => "Colors & Patterns", "delay" => "200"],
                ["icon" => "fa-shapes", "title" => "Shapes & Sizes", "delay" => "250"],
                ["icon" => "fa-calculator", "title" => "Numbers 1-100", "delay" => "300"],
                ["icon" => "fa-font", "title" => "Alphabet", "delay" => "350"],
                ["icon" => "fa-face-smile", "title" => "Emotions", "delay" => "400"],
                ["icon" => "fa-house-user", "title" => "Daily Routines", "delay" => "450"]
            ];

            foreach ($categories as $cat) {
            ?>
                <div class="col-6 col-md-4 col-lg-3" data-aos="fade-up" data-aos-delay="<?php echo $cat['delay']; ?>">
                    <div class="category-card">
                        <i class="fa-solid <?php echo $cat['icon']; ?> category-icon"></i>
                        <h6 class="fw-bold text-dark mb-0 fs-6"><?php echo $cat['title']; ?></h6>
                    </div>
                </div>
            <?php } ?>
        </div>
    </div>
</section>

<!-- HOW IT WORKS STEP-BY-STEP -->
<section class="py-5" style="background-color: #ffffff;">
    <div class="container py-4">
        <div class="text-center mb-5" data-aos="fade-up">
            <span class="badge-sensory mb-3">Step-by-Step Learning</span>
            <h2 class="display-6 fw-bold text-dark mb-2">How Spark Steps Works?</h2>
            <p class="text-muted fs-6">A clear, supportive progression built for cognitive comfort.</p>
        </div>

        <div class="row g-4">
            <div class="col-md-6 col-lg-3" data-aos="fade-up" data-aos-delay="100">
                <div class="step-card text-center">
                    <div class="rounded-circle text-white mx-auto mb-4 d-flex align-items-center justify-content-center fw-bold fs-4 shadow-sm" style="width: 65px; height: 65px; background: linear-gradient(135deg, #4f46e5 0%, #6366f1 100%);">1</div>
                    <h5 class="fw-bold text-dark mb-2 fs-5">Create Profile</h5>
                    <p class="text-muted mb-0 fs-6 lh-base">Set up sensory preferences, language options, and custom difficulty levels.</p>
                </div>
            </div>

            <div class="col-md-6 col-lg-3" data-aos="fade-up" data-aos-delay="200">
                <div class="step-card text-center">
                    <div class="rounded-circle text-white mx-auto mb-4 d-flex align-items-center justify-content-center fw-bold fs-4 shadow-sm" style="width: 65px; height: 65px; background: linear-gradient(135deg, #4f46e5 0%, #6366f1 100%);">2</div>
                    <h5 class="fw-bold text-dark mb-2 fs-5">Visual Lessons</h5>
                    <p class="text-muted mb-0 fs-6 lh-base">Engage with clear images and clear speech guidance in a soothing environment.</p>
                </div>
            </div>

            <div class="col-md-6 col-lg-3" data-aos="fade-up" data-aos-delay="300">
                <div class="step-card text-center">
                    <div class="rounded-circle text-white mx-auto mb-4 d-flex align-items-center justify-content-center fw-bold fs-4 shadow-sm" style="width: 65px; height: 65px; background: linear-gradient(135deg, #4f46e5 0%, #6366f1 100%);">3</div>
                    <h5 class="fw-bold text-dark mb-2 fs-5">Speech Practice</h5>
                    <p class="text-muted mb-0 fs-6 lh-base">Practice pronouncing words with encouragement from our forgiving AI model.</p>
                </div>
            </div>

            <div class="col-md-6 col-lg-3" data-aos="fade-up" data-aos-delay="400">
                <div class="step-card text-center">
                    <div class="rounded-circle text-white mx-auto mb-4 d-flex align-items-center justify-content-center fw-bold fs-4 shadow-sm" style="width: 65px; height: 65px; background: linear-gradient(135deg, #4f46e5 0%, #6366f1 100%);">4</div>
                    <h5 class="fw-bold text-dark mb-2 fs-5">Earn Stars</h5>
                    <p class="text-muted mb-0 fs-6 lh-base">Unlock soft rewards, collectible virtual badges, and positive reinforcement.</p>
                </div>
            </div>
        </div>
    </div>
</section>