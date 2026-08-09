<?php
// pages/about.php
$pageTitle = "About Our Mission";
require_once __DIR__ . '/../includes/header.php';
?>

<style>
    /* About Page Specific Styles */
    .about-hero {
        padding: 100px 0 80px;
        background: radial-gradient(circle at 50% 0%, rgba(238, 242, 255, 0.6) 0%, rgba(255, 255, 255, 1) 100%);
    }

    .about-title {
        font-size: 3.5rem;
        font-weight: 800;
        color: #0f172a;
        letter-spacing: -0.02em;
    }

    .pillar-icon {
        width: 68px;
        height: 68px;
        border-radius: 20px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.8rem;
        margin-bottom: 24px;
        transition: all 0.3s ease;
    }

    .sensory-card {
        background: #ffffff;
        border-radius: 24px;
        padding: 36px 28px;
        border: 1px solid #e2e8f0;
        transition: all 0.3s cubic-bezier(0.16, 1, 0.3, 1);
        height: 100%;
        box-shadow: 0 4px 20px rgba(15, 23, 42, 0.02);
    }

    .sensory-card:hover {
        transform: translateY(-8px);
        box-shadow: 0 20px 40px rgba(79, 70, 229, 0.08);
    }

    .pillar-1 { background: rgba(37, 99, 235, 0.08); color: #2563eb; }
    .pillar-2 { background: rgba(13, 148, 136, 0.08); color: #0d9488; }
    .pillar-3 { background: rgba(124, 58, 237, 0.08); color: #7c3aed; }
    .pillar-4 { background: rgba(234, 179, 8, 0.08); color: #ca8a04; }

    .about-img-box img {
        border-radius: 32px;
        box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.15);
    }

    .experience-badge {
        position: absolute;
        bottom: 30px;
        right: 30px;
        background: #ffffff;
        padding: 24px 32px;
        border-radius: 24px;
        box-shadow: 0 20px 40px rgba(0, 0, 0, 0.1);
        border-left: 6px solid #4f46e5;
    }

    .feature-check {
        display: flex;
        align-items: center;
        gap: 12px;
        font-weight: 600;
        color: #334155;
    }
</style>

<!-- HERO SECTION -->
<section class="about-hero text-center">
    <div class="container">
        <span class="badge-sensory mb-3" data-aos="fade-down"><i class="fa-solid fa-heart me-2"></i> Our Purpose & Passion</span>
        <h1 class="about-title mb-4" data-aos="fade-up">Building Education Where <br><span class="text-primary">Every Mind Thrives</span></h1>
        <p class="text-muted mx-auto lead" style="max-width: 700px; line-height: 1.7;" data-aos="fade-up" data-aos-delay="100">
            Spark Steps was born from a fundamental belief: children on the autism spectrum don't have learning deficits—they simply have unique, brilliant ways of processing the world.
        </p>
    </div>
</section>

<!-- OUR STORY -->
<section class="py-5">
    <div class="container py-4">
        <div class="row align-items-center g-5">
            <div class="col-lg-6" data-aos="fade-right">
                <div class="about-img-box position-relative">
                    <img src="../assets/images/hero.png" alt="Children Learning" class="img-fluid" onerror="this.src='https://images.unsplash.com/photo-1503676260728-1c00da094a0b?auto=format&fit=crop&w=800&q=80'">
                    <div class="experience-badge d-none d-md-block">
                        <h3 class="fw-bold text-primary mb-0">100%</h3>
                        <p class="mb-0 text-muted small fw-bold">Sensory Inclusive Design</p>
                    </div>
                </div>
            </div>

            <div class="col-lg-6" data-aos="fade-left">
                <span class="badge-sensory mb-3">Bridging the Gap</span>
                <h2 class="display-6 fw-bold mb-4">Reimagining Learning for Neurodiverse Children</h2>
                <p class="text-muted mb-4" style="line-height: 1.7;">
                    Traditional classrooms often rely on high-pressure auditory lectures and rigid schedules. For neurodiverse learners, these environments frequently trigger sensory overload, leading to frustration and withdrawal.
                </p>
                <p class="text-muted mb-4" style="line-height: 1.7;">
                    Spark Steps replaces that anxiety with <strong>predictability</strong>. By combining PECS visual communication, gentle speech recognition, and real-time emotion monitoring, we create a safe harbor where progress is celebrated, never forced.
                </p>

                <div class="row g-3">
                    <div class="col-sm-6"><div class="feature-check"><i class="fa-solid fa-check-circle text-primary"></i> Distraction-Free</div></div>
                    <div class="col-sm-6"><div class="feature-check"><i class="fa-solid fa-check-circle text-primary"></i> Nepali & English</div></div>
                    <div class="col-sm-6"><div class="feature-check"><i class="fa-solid fa-check-circle text-primary"></i> Zero Penalties</div></div>
                    <div class="col-sm-6"><div class="feature-check"><i class="fa-solid fa-check-circle text-primary"></i> Real-time Tracking</div></div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- CORE PILLARS -->
<section class="py-5" style="background: #f8fafc;">
    <div class="container py-4">
        <div class="text-center mb-5" data-aos="fade-up">
            <h2 class="fw-bold display-6">Designed Around Four Core Pillars</h2>
            <p class="text-muted">Every feature in Spark Steps serves a specific psychological and sensory purpose.</p>
        </div>

        <div class="row g-4">
            <?php
            $pillars = [
                ["icon" => "fa-eye", "title" => "Visual Communication", "text" => "Uses high-contrast PECS cards so non-verbal children match concepts to visual meanings.", "class" => "pillar-1"],
                ["icon" => "fa-microphone", "title" => "Speech Encouragement", "text" => "Our engine measures phonetic closeness without negative feedback, building verbal confidence.", "class" => "pillar-2"],
                ["icon" => "fa-face-smile-beam", "title" => "Emotion Sensing", "text" => "Facial analysis detects stress, offering soothing breaks before a meltdown occurs.", "class" => "pillar-3"],
                ["icon" => "fa-trophy", "title" => "Positive Reinforcement", "text" => "Earn stars and badges that celebrate every minor milestone reached in the learning journey.", "class" => "pillar-4"]
            ];
            foreach($pillars as $p) { ?>
            <div class="col-md-6 col-lg-3" data-aos="fade-up">
                <div class="sensory-card">
                    <div class="pillar-icon <?php echo $p['class']; ?>"><i class="fa-solid <?php echo $p['icon']; ?>"></i></div>
                    <h5 class="fw-bold mb-3"><?php echo $p['title']; ?></h5>
                    <p class="text-muted small lh-lg"><?php echo $p['text']; ?></p>
                </div>
            </div>
            <?php } ?>
        </div>
    </div>
</section>

<!-- CALL TO ACTION -->
<section class="py-5 text-center text-white" style="background: linear-gradient(135deg, #4f46e5 0%, #7c3aed 100%);">
    <div class="container py-5" data-aos="zoom-in">
        <h2 class="display-6 fw-bold mb-3">Join Us in Making Education Sensory-Inclusive</h2>
        <p class="lead mb-4 opacity-90 mx-auto" style="max-width: 600px;">Experience how AI-driven personalized learning turns frustration into daily confidence.</p>
        <a href="<?php echo BASE_URL; ?>register.php" class="btn btn-light btn-lg rounded-pill fw-bold text-primary px-5 py-3 shadow-lg">
            Get Started Free <i class="fa-solid fa-arrow-right ms-2"></i>
        </a>
    </div>
</section>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>