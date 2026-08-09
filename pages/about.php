<?php
// pages/about.php
$pageTitle = "About Our Mission";

require_once __DIR__ . '/../includes/header.php';
?>

<style>
    .about-hero {
        padding: 80px 0 60px;
        background: radial-gradient(circle at 50% 0%, rgba(238, 245, 255, 1) 0%, rgba(255, 255, 255, 1) 100%);
    }

    .pillar-icon {
        width: 64px;
        height: 64px;
        border-radius: 18px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.75rem;
        margin-bottom: 20px;
    }

    .pillar-1 { background: rgba(37, 99, 235, 0.12); color: #2563eb; }
    .pillar-2 { background: rgba(13, 148, 136, 0.12); color: #0d9488; }
    .pillar-3 { background: rgba(124, 58, 237, 0.12); color: #7c3aed; }
    .pillar-4 { background: rgba(234, 179, 8, 0.15); color: #ca8a04; }

    .team-card {
        border-radius: 20px;
        overflow: hidden;
        border: 1px solid #e2e8f0;
        background: #ffffff;
        transition: transform 0.3s ease;
    }

    .team-card:hover {
        transform: translateY(-5px);
    }

    .about-img-box {
        position: relative;
    }

    .about-img-box img {
        border-radius: 24px;
        box-shadow: 0 20px 40px rgba(15, 23, 42, 0.08);
    }

    .experience-badge {
        position: absolute;
        bottom: -20px;
        right: -10px;
        background: #ffffff;
        padding: 20px 30px;
        border-radius: 20px;
        box-shadow: 0 15px 30px rgba(0, 0, 0, 0.1);
        border-left: 5px solid var(--primary-color);
    }
</style>

<!-- ABOUT HERO SECTION -->
<section class="about-hero text-center">
    <div class="container">
        <span class="badge-sensory mb-3" data-aos="fade-down"><i class="fa-solid fa-heart me-2"></i> Our Purpose & Passion</span>
        <h1 class="display-4 fw-bold mb-3" data-aos="fade-up">Building Education Where <br><span class="text-primary">Every Mind Thrives</span></h1>
        <p class="text-muted mx-auto lead" style="max-width: 720px;" data-aos="fade-up" data-aos-delay="100">
            Spark Steps was born from a fundamental belief: children on the autism spectrum don't have learning deficits—they simply have unique ways of processing information.
        </p>
    </div>
</section>

<!-- OUR STORY & VISION -->
<section class="py-5">
    <div class="container py-3">
        <div class="row align-items-center g-5">
            <div class="col-lg-6" data-aos="fade-right">
                <div class="about-img-box">
                    <img src="../assets/images/hero.png" alt="Children Learning with Spark Steps." class="img-fluid" onerror="this.src='https://images.unsplash.com/photo-1503676260728-1c00da094a0b?auto=format&fit=crop&w=800&q=80'">
                    <div class="experience-badge d-none d-md-block">
                        <h3 class="fw-bold text-primary mb-0">100%</h3>
                        <p class="mb-0 text-muted small fw-semibold">Sensory Inclusive Design</p>
                    </div>
                </div>
            </div>

            <div class="col-lg-6" data-aos="fade-left">
                <span class="badge-sensory mb-2">Bridging the Educational Gap</span>
                <h2 class="display-6 fw-bold mb-3">Reimagining Learning for Neurodiverse Children</h2>
                <p class="text-muted">
                    Traditional classrooms rely heavily on auditory lectures and rigid timing. For an autistic child, this environment can spark sensory overload, frustration, and withdrawal.
                </p>
                <p class="text-muted">
                    Spark Steps combines <strong>Picture Exchange Communication Systems (PECS)</strong>, forgiving voice recognition, and real-time emotion monitoring to craft a personalized learning standard tailored to each child's pace.
                </p>

                <div class="row g-3 mt-2">
                    <div class="col-sm-6">
                        <div class="d-flex align-items-center gap-3">
                            <i class="fa-solid fa-circle-check text-success fs-4"></i>
                            <span class="fw-semibold text-dark">Distraction-Free Visuals</span>
                        </div>
                    </div>
                    <div class="col-sm-6">
                        <div class="d-flex align-items-center gap-3">
                            <i class="fa-solid fa-circle-check text-success fs-4"></i>
                            <span class="fw-semibold text-dark">Nepali & English Voices</span>
                        </div>
                    </div>
                    <div class="col-sm-6">
                        <div class="d-flex align-items-center gap-3">
                            <i class="fa-solid fa-circle-check text-success fs-4"></i>
                            <span class="fw-semibold text-dark">Zero Failure Penalties</span>
                        </div>
                    </div>
                    <div class="col-sm-6">
                        <div class="d-flex align-items-center gap-3">
                            <i class="fa-solid fa-circle-check text-success fs-4"></i>
                            <span class="fw-semibold text-dark">Parent & Therapist Tracking</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- CORE PILLARS OF OUR PLATFORM -->
<section class="py-5 bg-white">
    <div class="container py-4">
        <div class="text-center mb-5" data-aos="fade-up">
            <span class="badge-sensory mb-2">Our Methodology</span>
            <h2 class="fw-bold">Designed Around Four Core Pillars</h2>
            <p class="text-muted">Every feature in Spark Steps serves a psychological and sensory purpose.</p>
        </div>

        <div class="row g-4">
            <div class="col-md-6 col-lg-3" data-aos="fade-up" data-aos-delay="100">
                <div class="sensory-card">
                    <div class="pillar-icon pillar-1">
                        <i class="fa-solid fa-eye"></i>
                    </div>
                    <h5 class="fw-bold">Visual Communication</h5>
                    <p class="text-muted small">Leverages high-contrast picture cards (PECS) so non-verbal children can easily match words to visual meanings.</p>
                </div>
            </div>

            <div class="col-md-6 col-lg-3" data-aos="fade-up" data-aos-delay="200">
                <div class="sensory-card">
                    <div class="pillar-icon pillar-2">
                        <i class="fa-solid fa-microphone"></i>
                    </div>
                    <h5 class="fw-bold">Speech Encouragement</h5>
                    <p class="text-muted small">Our speech engine measures phonetic closeness without buzzer sounds or negative feedback, building verbal confidence.</p>
                </div>
            </div>

            <div class="col-md-6 col-lg-3" data-aos="fade-up" data-aos-delay="300">
                <div class="sensory-card">
                    <div class="pillar-icon pillar-3">
                        <i class="fa-solid fa-face-smile-beam"></i>
                    </div>
                    <h5 class="fw-bold">Emotion Sensing</h5>
                    <p class="text-muted small">Webcam facial analysis detects signs of stress or fatigue early, offering soothing musical breaks before meltdown occurs.</p>
                </div>
            </div>

            <div class="col-md-6 col-lg-4 col-lg-3" data-aos="fade-up" data-aos-delay="400">
                <div class="sensory-card">
                    <div class="pillar-icon pillar-4">
                        <i class="fa-solid fa-trophy"></i>
                    </div>
                    <h5 class="fw-bold">Positive Reinforcement</h5>
                    <p class="text-muted small">Earn stars, collectible digital badges, and soft animations that celebrate every minor milestone achieved.</p>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- WHO WE SERVE (PORTAL ECOSYSTEM) -->
<section class="py-5" style="background: #f1f5f9;">
    <div class="container py-4">
        <div class="text-center mb-5" data-aos="fade-up">
            <h2 class="fw-bold">A Unified Ecosystem for Every Stakeholder</h2>
            <p class="text-muted">Connecting children, parents, teachers, and administrators on one collaborative platform.</p>
        </div>

        <div class="row g-4">
            <div class="col-md-6 col-lg-3" data-aos="zoom-in" data-aos-delay="100">
                <div class="sensory-card text-center p-4">
                    <i class="fa-solid fa-child-reaching text-primary fs-1 mb-3"></i>
                    <h5 class="fw-bold">Students</h5>
                    <p class="text-muted small">Simple, calm portal with large buttons, spoken guidance, and interactive visual games.</p>
                </div>
            </div>

            <div class="col-md-6 col-lg-3" data-aos="zoom-in" data-aos-delay="200">
                <div class="sensory-card text-center p-4">
                    <i class="fa-solid fa-hands-holding-child text-success fs-1 mb-3"></i>
                    <h5 class="fw-bold">Parents</h5>
                    <p class="text-muted small">View daily emotional stability logs, speech progress, and customize sensory themes.</p>
                </div>
            </div>

            <div class="col-md-6 col-lg-3" data-aos="zoom-in" data-aos-delay="300">
                <div class="sensory-card text-center p-4">
                    <i class="fa-solid fa-chalkboard-user text-warning fs-1 mb-3"></i>
                    <h5 class="fw-bold">Special Educators</h5>
                    <p class="text-muted small">Assign custom IEP goals, create tailored lessons, and generate detailed progress reports.</p>
                </div>
            </div>

            <div class="col-md-6 col-lg-3" data-aos="zoom-in" data-aos-delay="400">
                <div class="sensory-card text-center p-4">
                    <i class="fa-solid fa-user-gear text-danger fs-1 mb-3"></i>
                    <h5 class="fw-bold">Administrators</h5>
                    <p class="text-muted small">Manage platform users, oversee curriculum categories, and audit AI recommendation logs.</p>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- CALL TO ACTION -->
<section class="py-5 text-center text-white" style="background: linear-gradient(135deg, var(--primary-color), var(--accent-purple));">
    <div class="container py-4" data-aos="zoom-in">
        <h2 class="display-6 fw-bold mb-3">Join Us in Making Education Sensory-Inclusive</h2>
        <p class="lead mb-4 opacity-90 mx-auto" style="max-width: 650px;">Experience how AI-driven personalized learning turns educational frustration into daily confidence.</p>
        <a href="<?php echo BASE_URL; ?>register.php" class="btn btn-light btn-lg rounded-pill fw-bold text-primary px-5 py-3 shadow">
            Get Started Free <i class="fa-solid fa-arrow-right ms-2"></i>
        </a>
    </div>
</section>

<?php
// Include Footer
require_once __DIR__ . '/../includes/footer.php';
?>