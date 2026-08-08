<?php
// pages/features.php
$pageTitle = "Platform Features";
require_once __DIR__ . '/../includes/header.php';
?>

<style>
    .features-hero {
        padding: 80px 0;
        background: #ffffff;
    }
    
    .feature-icon-box {
        width: 80px;
        height: 80px;
        border-radius: 20px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 2.5rem;
        margin-bottom: 25px;
        transition: all 0.4s ease;
    }

    .card-hover:hover .feature-icon-box {
        transform: scale(1.1);
        box-shadow: 0 10px 20px rgba(37, 99, 235, 0.2);
    }
</style>

<!-- FEATURES HERO -->
<section class="features-hero text-center">
    <div class="container">
        <span class="badge-sensory mb-3">Core Technology</span>
        <h1 class="display-4 fw-bold mb-4">Features Designed for <br><span class="text-primary">Every Learning Style</span></h1>
        <p class="text-muted lead mx-auto" style="max-width: 700px;">
            AutiLearn AI combines cutting-edge technology with evidence-based special education practices to create a distraction-free, supportive environment.
        </p>
    </div>
</section>

<!-- FEATURE GRID -->
<section class="py-5 bg-light">
    <div class="container py-4">
        <div class="row g-4">
            <?php
            $features = [
                [
                    "icon" => "fa-images",
                    "color" => "text-primary",
                    "bg" => "bg-primary-subtle",
                    "title" => "Visual PECS Learning",
                    "desc" => "Picture Exchange Communication System integrated directly into lessons to help non-verbal learners bridge the gap between objects and vocabulary."
                ],
                [
                    "icon" => "fa-microphone-lines",
                    "color" => "text-success",
                    "bg" => "bg-success-subtle",
                    "title" => "AI Speech Practice",
                    "desc" => "Real-time phonetic analysis that provides gentle, encouraging feedback, helping children build verbal confidence without stress."
                ],
                [
                    "icon" => "fa-face-smile-beam",
                    "color" => "text-warning",
                    "bg" => "bg-warning-subtle",
                    "title" => "Emotion AI Detection",
                    "desc" => "Uses computer vision to detect frustration or fatigue, automatically triggering 'Calm Mode' with music or break suggestions."
                ],
                [
                    "icon" => "fa-language",
                    "color" => "text-info",
                    "bg" => "bg-info-subtle",
                    "title" => "Nepali & English Support",
                    "desc" => "Fully localized content including culturally relevant imagery and dual-language AI voice synthesis for better regional accessibility."
                ],
                [
                    "icon" => "fa-chart-line",
                    "color" => "text-danger",
                    "bg" => "bg-danger-subtle",
                    "title" => "Parental Analytics",
                    "desc" => "Detailed progress reports tracking speech clarity, emotional trends, and lesson completion rates for parents and therapists."
                ],
                [
                    "icon" => "fa-star",
                    "color" => "text-purple",
                    "bg" => "bg-purple-subtle",
                    "title" => "Gamified Rewards",
                    "desc" => "A positive reinforcement system where children earn virtual stars, badges, and unlockable themes for consistent practice."
                ]
            ];

            foreach ($features as $f) {
            ?>
            <div class="col-md-6 col-lg-4" data-aos="fade-up">
                <div class="sensory-card card-hover h-100">
                    <div class="feature-icon-box <?php echo $f['bg'] . ' ' . $f['color']; ?>">
                        <i class="fa-solid <?php echo $f['icon']; ?>"></i>
                    </div>
                    <h4 class="fw-bold"><?php echo $f['title']; ?></h4>
                    <p class="text-muted"><?php echo $f['desc']; ?></p>
                </div>
            </div>
            <?php } ?>
        </div>
    </div>
</section>

<!-- TECH STACK SHOWCASE -->
<section class="py-5">
    <div class="container py-5 text-center">
        <h2 class="fw-bold mb-5">Powered by Advanced AI</h2>
        <div class="row align-items-center">
            <div class="col-md-4 mb-4">
                <div class="p-4">
                    <i class="fa-solid fa-brain fs-1 text-primary mb-3"></i>
                    <h5>Adaptive Learning Engine</h5>
                    <p class="text-muted small">Lessons adjust difficulty automatically based on the child's performance history.</p>
                </div>
            </div>
            <div class="col-md-4 mb-4">
                <div class="p-4">
                    <i class="fa-solid fa-cloud-bolt fs-1 text-primary mb-3"></i>
                    <h5>Secure Cloud Sync</h5>
                    <p class="text-muted small">Access progress data from any device, keeping the child’s learning path consistent.</p>
                </div>
            </div>
            <div class="col-md-4 mb-4">
                <div class="p-4">
                    <i class="fa-solid fa-shield-halved fs-1 text-primary mb-3"></i>
                    <h5>Privacy First</h5>
                    <p class="text-muted small">All facial and speech data is processed locally or encrypted strictly for educational goals.</p>
                </div>
            </div>
        </div>
    </div>
</section>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>