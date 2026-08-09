<?php
// pages/home.php
?>

<style>
    /* Section Specific Styles */
    .hero-section {
        padding: 90px 0 100px;
        background: radial-gradient(circle at 10% 20%, rgba(238, 245, 255, 1) 0%, rgba(255, 255, 255, 1) 90%);
        position: relative;
    }

    .hero-title {
        font-size: 3.25rem;
        font-weight: 800;
        line-height: 1.2;
        color: var(--text-dark);
    }

    .hero-title span {
        background: linear-gradient(135deg, var(--primary-color), var(--accent-purple));
        -webkit-background-clip: text;
        -webkit-text-fill-color: transparent;
    }

    .hero-subtitle {
        font-size: 1.15rem;
        color: var(--text-muted);
        margin-top: 20px;
        max-width: 580px;
    }

    .hero-img-wrapper {
        position: relative;
    }

    .hero-img-wrapper img {
        border-radius: 30px;
        box-shadow: 0 20px 50px rgba(37, 99, 235, 0.12);
        animation: float 6s ease-in-out infinite;
    }

    @keyframes float {
        0%, 100% { transform: translateY(0px); }
        50% { transform: translateY(-15px); }
    }

    .floating-pill {
        position: absolute;
        background: #ffffff;
        padding: 12px 24px;
        border-radius: 50px;
        box-shadow: 0 15px 35px rgba(0, 0, 0, 0.08);
        display: flex;
        align-items: center;
        gap: 12px;
        font-weight: 600;
        font-size: 0.95rem;
        z-index: 2;
    }

    .pill-1 { top: 10%; left: -20px; }
    .pill-2 { bottom: 10%; right: -20px; }

    .stat-box {
        background: linear-gradient(135deg, var(--primary-color), #1d4ed8);
        color: #ffffff;
        padding: 50px 0;
        border-radius: 30px;
        margin: -40px 0 80px;
        box-shadow: 0 20px 40px rgba(37, 99, 235, 0.2);
    }

    .stat-number {
        font-size: 3rem;
        font-weight: 800;
        font-family: 'Outfit', sans-serif;
    }

    .category-card {
        text-align: center;
        padding: 28px 20px;
        border-radius: 20px;
        background: #ffffff;
        border: 1px solid #e2e8f0;
        transition: all 0.3s ease;
        cursor: pointer;
    }

    .category-card:hover {
        background: var(--primary-color);
        color: #ffffff;
        transform: translateY(-8px);
        box-shadow: 0 15px 30px rgba(37, 99, 235, 0.25);
    }

    .category-card:hover i {
        color: #ffffff !important;
    }

    .category-icon {
        font-size: 2.5rem;
        color: var(--primary-color);
        margin-bottom: 15px;
        transition: all 0.3s ease;
    }

    .progress-demo-card {
        background: #ffffff;
        border-radius: 24px;
        padding: 30px;
        box-shadow: 0 15px 35px rgba(0, 0, 0, 0.05);
    }

    .progress-bar-custom {
        height: 12px;
        border-radius: 10px;
        background-color: #e2e8f0;
    }

    .progress-bar-fill {
        height: 100%;
        border-radius: 10px;
        background: linear-gradient(90deg, var(--primary-color), var(--secondary-color));
    }

    @media (max-width: 991px) {
        .hero-title { font-size: 2.5rem; }
        .hero-section { text-align: center; }
        .hero-subtitle { margin: 20px auto; }
        .floating-pill { display: none; }
        .stat-box { border-radius: 0; margin-top: 40px; }
    }
</style>

<!-- HERO SECTION -->
<section class="hero-section">
    <div class="container">
        <div class="row align-items-center g-5">
            <div class="col-lg-6" data-aos="fade-right" data-aos-duration="1000">
                <span class="badge-sensory mb-3"><i class="fa-solid fa-sparkles me-2"></i> Spark Steps Platform</span>
                <h1 class="hero-title">
                    Empowering Every <span>Autistic Child</span> To Learn At Their Own Pace
                </h1>
                <p class="hero-subtitle">
                    Spark Steps Development combines picture exchange communication system (PECS), adaptive speech recognition, and real-time emotion detection to deliver calm, engaging, and personalized education.
                </p>
                <div class="d-flex flex-wrap gap-3 mt-4 justify-content-center justify-content-lg-start">
                    <a href="register.php" class="btn btn-custom-primary btn-lg">
                        <i class="fa-solid fa-rocket"></i> Tap here to learn
                    </a>
                    <a href="pages/about.php" class="btn btn-custom-outline btn-lg">
                        <i class="fa-solid fa-circle-play"></i> Watch Overview
                    </a>
                </div>
            </div>

            <div class="col-lg-6 text-center" data-aos="fade-left" data-aos-duration="1000">
                <div class="hero-img-wrapper d-inline-block">
                    <img src="assets/images/hero.png" alt="Spark Steps Development Learning Experience" class="img-fluid" onerror="this.src='https://images.unsplash.com/photo-1588072432836-e10032774350?auto=format&fit=crop&w=800&q=80'">
                </div>
            </div>
        </div>
    </div>
</section>


<!-- WHY AUTILEARN (LEARNING PILLARS) -->
<section class="py-5">
    <div class="container py-4">
        <div class="text-center mb-5" data-aos="fade-up">
            
            <h2 class="display-5 fw-bold">Why Choose Spark Steps Development?</h2>
            <p class="text-muted mx-auto" style="max-width: 650px;">Traditional methods can lead to sensory overload. We build learning around predictable routines, high-visual feedback, and soothing praise.</p>
        </div>

        <div class="row g-4">
            <div class="col-md-6 col-lg-4" data-aos="fade-up" data-aos-delay="100">
                <div class="sensory-card">
                    <div class="rounded-circle bg-primary bg-opacity-10 p-3 d-inline-block mb-3 text-primary fs-3">
                        <i class="fa-solid fa-images"></i>
                    </div>
                    <h4>PECS Picture Learning</h4>
                    <p class="text-muted">High-contrast, distraction-free visual cards help children associate words with real-world objects effortlessly.</p>
                </div>
            </div>

            <div class="col-md-6 col-lg-4" data-aos="fade-up" data-aos-delay="200">
                <div class="sensory-card">
                    <div class="rounded-circle bg-success bg-opacity-10 p-3 d-inline-block mb-3 text-success fs-3">
                        <i class="fa-solid fa-microphone-lines"></i>
                    </div>
                    <h4>Gentle Speech Assistant</h4>
                    <p class="text-muted">Speech-to-text calibrated for non-verbal and speech-delayed children with zero time pressure or loud penalties.</p>
                </div>
            </div>

            <div class="col-md-6 col-lg-4" data-aos="fade-up" data-aos-delay="300">
                <div class="sensory-card">
                    <div class="rounded-circle bg-purple bg-opacity-10 p-3 d-inline-block mb-3 text-primary fs-3">
                        <i class="fa-solid fa-face-smile"></i>
                    </div>
                    <h4>Emotion AI Detection</h4>
                    <p class="text-muted">Camera-based facial cues sense frustration early, automatically switching lessons to soothing break activities.</p>
                </div>
            </div>
        </div>
    </div>
</section>



<!-- LEARNING CATEGORIES -->
<section class="py-5" style="background: #f1f5f9;">
    <div class="container py-4">
        <div class="text-center mb-5" data-aos="fade-up">
            <h2 class="fw-bold">Explore Interactive Categories</h2>
            <p class="text-muted">Curated modules designed with structured predictability.</p>
        </div>

        <div class="row g-3">
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
                        <h6 class="fw-semibold mb-0"><?php echo $cat['title']; ?></h6>
                    </div>
                </div>
            <?php } ?>
        </div>
    </div>
</section>

<!-- HOW IT WORKS STEP-BY-STEP -->
<section class="py-5 bg-white">
    <div class="container py-4">
        <div class="text-center mb-5" data-aos="fade-up">
            <span class="badge-sensory mb-2">Step-by-Step Learning</span>
            <h2 class="fw-bold">How Spark Steps Works ?</h2>
        </div>

        <div class="row g-4 text-center">
            <div class="col-md-3" data-aos="fade-up" data-aos-delay="100">
                <div class="p-4">
                    <div class="rounded-circle bg-primary text-white mx-auto mb-3 d-flex align-items-center justify-content-center fw-bold fs-4" style="width: 70px; height: 70px;">1</div>
                    <h5>Create Profile</h5>
                    <p class="text-muted">Set up sensory preferences, language (English/Nepali), and custom difficulty levels.</p>
                </div>
            </div>

            <div class="col-md-3" data-aos="fade-up" data-aos-delay="200">
                <div class="p-4">
                    <div class="rounded-circle bg-primary text-white mx-auto mb-3 d-flex align-items-center justify-content-center fw-bold fs-4" style="width: 70px; height: 70px;">2</div>
                    <h5>Visual Lessons</h5>
                    <p class="text-muted">Engage with clear images and clear speech guidance in a soothing environment.</p>
                </div>
            </div>

            <div class="col-md-3" data-aos="fade-up" data-aos-delay="300">
                <div class="p-4">
                    <div class="rounded-circle bg-primary text-white mx-auto mb-3 d-flex align-items-center justify-content-center fw-bold fs-4" style="width: 70px; height: 70px;">3</div>
                    <h5>Speech Practice</h5>
                    <p class="text-muted">Practice pronouncing words with encouragement from our forgiving AI model.</p>
                </div>
            </div>

            <div class="col-md-3" data-aos="fade-up" data-aos-delay="400">
                <div class="p-4">
                    <div class="rounded-circle bg-primary text-white mx-auto mb-3 d-flex align-items-center justify-content-center fw-bold fs-4" style="width: 70px; height: 70px;">4</div>
                    <h5>Earn Stars</h5>
                    <p class="text-muted">Unlock soft rewards, collectible virtual badges, and positive reinforcement.</p>
                </div>
            </div>
        </div>
    </div>
</section>

