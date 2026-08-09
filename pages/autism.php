<?php
// pages/autism.php
$pageTitle = "Sensory Friendly Learning Guide";
require_once __DIR__ . '/../includes/header.php';
?>

<style>
    .sensory-hero {
        padding: 80px 0 60px;
        background: radial-gradient(circle at 50% 10%, rgba(240, 253, 250, 1) 0%, rgba(255, 255, 255, 1) 100%);
    }

    .sensory-pill-badge {
        background: rgba(13, 148, 136, 0.12);
        color: var(--secondary-color);
        padding: 8px 20px;
        border-radius: 50px;
        font-weight: 600;
        font-size: 0.875rem;
        display: inline-block;
    }

    .checklist-item {
        display: flex;
        align-items: flex-start;
        gap: 15px;
        margin-bottom: 20px;
    }

    .checklist-icon {
        width: 36px;
        height: 36px;
        border-radius: 50%;
        background: rgba(37, 99, 235, 0.1);
        color: var(--primary-color);
        display: flex;
        align-items: center;
        justify-content: center;
        flex-shrink: 0;
        font-size: 1.1rem;
    }

    .sensory-type-card {
        border-top: 4px solid var(--secondary-color);
    }
</style>

<!-- SENSORY GUIDE HERO -->
<section class="sensory-hero text-center">
    <div class="container">
        <span class="sensory-pill-badge mb-3" data-aos="fade-down">
            <i class="fa-solid fa-feather me-2"></i> Sensory Inclusivity
        </span>
        <h1 class="display-4 fw-bold mb-3" data-aos="fade-up">
            Designing Calm, Supportive <br><span style="color: var(--secondary-color);">Sensory Environments</span>
        </h1>
        <p class="text-muted lead mx-auto" style="max-width: 720px;" data-aos="fade-up" data-aos-delay="100">
            Children on the autism spectrum process visual, auditory, and tactile information differently. Understanding sensory needs is key to unlocking effective learning.
        </p>
    </div>
</section>

<!-- UNDERSTANDING SENSORY PROFILES -->
<section class="py-5">
    <div class="container py-3">
        <div class="text-center mb-5" data-aos="fade-up">
            <span class="badge-sensory mb-2">Sensory Basics</span>
            <h2 class="fw-bold">Hypersensitivity vs. Hyposensitivity</h2>
            <p class="text-muted">Autistic children often fluctuate between two primary sensory profiles.</p>
        </div>

        <div class="row g-4">
            <div class="col-md-6" data-aos="fade-right">
                <div class="sensory-card sensory-type-card">
                    <div class="d-flex align-items-center gap-3 mb-3">
                        <div class="rounded-circle bg-warning bg-opacity-10 p-3 text-warning fs-3">
                            <i class="fa-solid fa-volume-xmark"></i>
                        </div>
                        <h4 class="fw-bold mb-0">Hypersensitive (Sensory Avoidant)</h4>
                    </div>
                    <p class="text-muted">Children feel overwhelmed by bright lights, sudden loud noises, or busy environments.</p>
                    <hr class="my-3">
                    <ul class="list-unstyled text-muted small">
                        <li class="mb-2"><i class="fa-solid fa-check text-success me-2"></i> Covers ears during loud or unexpected sounds</li>
                        <li class="mb-2"><i class="fa-solid fa-check text-success me-2"></i> Distracted by flickering fluorescent lighting</li>
                        <li class="mb-2"><i class="fa-solid fa-check text-success me-2"></i> Prefers muted colors and quiet spaces</li>
                    </ul>
                </div>
            </div>

            <div class="col-md-6" data-aos="fade-left">
                <div class="sensory-card sensory-type-card" style="border-top-color: var(--primary-color);">
                    <div class="d-flex align-items-center gap-3 mb-3">
                        <div class="rounded-circle bg-primary bg-opacity-10 p-3 text-primary fs-3">
                            <i class="fa-solid fa-bolt"></i>
                        </div>
                        <h4 class="fw-bold mb-0">Hyposensitive (Sensory Seeking)</h4>
                    </div>
                    <p class="text-muted">Children crave heightened physical input, repetitive movements, or vibrant visual feedback to engage.</p>
                    <hr class="my-3">
                    <ul class="list-unstyled text-muted small">
                        <li class="mb-2"><i class="fa-solid fa-check text-success me-2"></i> Seeks strong visual feedback and high contrast</li>
                        <li class="mb-2"><i class="fa-solid fa-check text-success me-2"></i> Benefits from rhythmic audio and tactile pacing</li>
                        <li class="mb-2"><i class="fa-solid fa-check text-success me-2"></i> Responds well to interactive motion mechanics</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- HOW AUTILEARN ADAPTS -->
<section class="py-5 bg-white">
    <div class="container py-4">
        <div class="row align-items-center g-5">
            <div class="col-lg-6" data-aos="fade-right">
                <span class="sensory-pill-badge mb-2">Built-in Accommodations</span>
                <h2 class="display-6 fw-bold mb-4">How Spark Steps Maintains Sensory Balance ?</h2>

                <div class="checklist-item">
                    <div class="checklist-icon"><i class="fa-solid fa-sliders"></i></div>
                    <div>
                        <h6 class="fw-bold mb-1">Low-Stimulation UI Settings</h6>
                        <p class="text-muted small mb-0">Clean white spaces, soft pastel themes, and distraction-free layouts remove cognitive strain.</p>
                    </div>
                </div>

                <div class="checklist-item">
                    <div class="checklist-icon"><i class="fa-solid fa-bell-slash"></i></div>
                    <div>
                        <h6 class="fw-bold mb-1">No Harsh Penalty Sounds</h6>
                        <p class="text-muted small mb-0">Replaces loud error buzzers with gentle visual cues and encouraging retry prompts.</p>
                    </div>
                </div>

                <div class="checklist-item">
                    <div class="checklist-icon"><i class="fa-solid fa-pause"></i></div>
                    <div>
                        <h6 class="fw-bold mb-1">Predictable Lesson Pacing</h6>
                        <p class="text-muted small mb-0">No countdown timers or forced speed tests, allowing the student to process concepts at their own speed.</p>
                    </div>
                </div>
            </div>

            <div class="col-lg-6" data-aos="fade-left">
                <div class="sensory-card p-4 bg-light text-center">
                    <i class="fa-solid fa-shield-heart display-1 text-primary mb-3"></i>
                    <h4 class="fw-bold">Sensory Safety Promise</h4>
                    <p class="text-muted mb-0">Every visual asset, sound file, and interaction pattern is audited against special education accessibility standards to minimize sensory overload.</p>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- PRACTICAL TIPS FOR PARENTS & TEACHERS -->
<section class="py-5" style="background: #f1f5f9;">
    <div class="container py-4">
        <div class="text-center mb-5" data-aos="fade-up">
            <h2 class="fw-bold">Setting Up a Sensory-Friendly Home Study Space</h2>
            <p class="text-muted">Simple physical adjustments that improve learning focus.</p>
        </div>

        <div class="row g-4">
            <div class="col-md-4" data-aos="fade-up" data-aos-delay="100">
                <div class="sensory-card text-center">
                    <i class="fa-solid fa-sun text-warning fs-1 mb-3"></i>
                    <h5 class="fw-bold">Lighting</h5>
                    <p class="text-muted small">Use warm natural light instead of flickering overhead fluorescent bulbs.</p>
                </div>
            </div>

            <div class="col-md-4" data-aos="fade-up" data-aos-delay="200">
                <div class="sensory-card text-center">
                    <i class="fa-solid fa-headphones text-primary fs-1 mb-3"></i>
                    <h5 class="fw-bold">Sound Management</h5>
                    <p class="text-muted small">Provide noise-canceling headphones when practicing speech or watching video lessons.</p>
                </div>
            </div>

            <div class="col-md-4" data-aos="fade-up" data-aos-delay="300">
                <div class="sensory-card text-center">
                    <i class="fa-solid fa-chair text-success fs-1 mb-3"></i>
                    <h5 class="fw-bold">Flexible Seating</h5>
                    <p class="text-muted small">Allow wiggle cushions, wobble stools, or weighted lap pads during study sessions.</p>
                </div>
            </div>
        </div>
    </div>
</section>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>