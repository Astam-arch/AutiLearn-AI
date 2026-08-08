<?php
// pages/contact.php
$pageTitle = "Contact & Support";
require_once __DIR__ . '/../includes/header.php';

// Form handling logic
$messageSent = false;
$errorMessage = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name    = trim($_POST['name'] ?? '');
    $email   = trim($_POST['email'] ?? '');
    $role    = trim($_POST['role'] ?? '');
    $subject = trim($_POST['subject'] ?? '');
    $message = trim($_POST['message'] ?? '');

    if (!empty($name) && !empty($email) && !empty($message)) {
        // Here you would process DB storage or mail dispatch
        $messageSent = true;
    } else {
        $errorMessage = "Please fill in all required fields.";
    }
}
?>

<style>
    .contact-hero {
        padding: 80px 0 50px;
        background: radial-gradient(circle at 50% 0%, rgba(219, 234, 254, 0.5) 0%, rgba(255, 255, 255, 1) 100%);
    }

    .contact-info-card {
        background: #ffffff;
        border-radius: 20px;
        padding: 30px;
        box-shadow: 0 10px 30px rgba(0, 0, 0, 0.05);
        border: 1px solid rgba(226, 232, 240, 0.8);
        height: 100%;
    }

    .info-icon-box {
        width: 50px;
        height: 50px;
        border-radius: 12px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.25rem;
        flex-shrink: 0;
    }

    .form-control:focus, .form-select:focus {
        border-color: var(--primary-color);
        box-shadow: 0 0 0 0.25rem rgba(37, 99, 235, 0.15);
    }
</style>

<!-- CONTACT HERO -->
<section class="contact-hero text-center">
    <div class="container">
        <span class="badge-sensory mb-3" data-aos="fade-down">We're Here to Help</span>
        <h1 class="display-4 fw-bold mb-3" data-aos="fade-up">Get in Touch with Our <br><span class="text-primary">Support Team</span></h1>
        <p class="text-muted lead mx-auto" style="max-width: 650px;" data-aos="fade-up" data-aos-delay="100">
            Have questions about AutiLearn AI, need assistance with your subscription, or want to collaborate with our special education team? Reach out anytime.
        </p>
    </div>
</section>

<!-- MAIN CONTACT SECTION -->
<section class="py-5">
    <div class="container pb-5">
        <div class="row g-5">
            <!-- LEFT COLUMN: CONTACT INFO & DETAILS -->
            <div class="col-lg-5" data-aos="fade-right">
                <div class="contact-info-card d-flex flex-column justify-content-between">
                    <div>
                        <h3 class="fw-bold mb-4">Contact Information</h3>
                        <p class="text-muted mb-4">Our dedicated team responds within 24 to 48 business hours.</p>

                        <div class="d-flex align-items-start gap-3 mb-4">
                            <div class="info-icon-box bg-primary-subtle text-primary">
                                <i class="fa-solid fa-envelope"></i>
                            </div>
                            <div>
                                <h6 class="fw-bold mb-1">Email Support</h6>
                                <p class="text-muted small mb-0">support@autilearn.ai</p>
                                <p class="text-muted small mb-0">info@autilearn.ai</p>
                            </div>
                        </div>

                        <div class="d-flex align-items-start gap-3 mb-4">
                            <div class="info-icon-box bg-success-subtle text-success">
                                <i class="fa-solid fa-phone"></i>
                            </div>
                            <div>
                                <h6 class="fw-bold mb-1">Phone / WhatsApp</h6>
                                <p class="text-muted small mb-0">+977 9800000000 (Nepal Regional)</p>
                                <p class="text-muted small mb-0">+1 (800) 123-4567 (Toll-Free)</p>
                            </div>
                        </div>

                        <div class="d-flex align-items-start gap-3 mb-4">
                            <div class="info-icon-box bg-info-subtle text-info">
                                <i class="fa-solid fa-location-dot"></i>
                            </div>
                            <div>
                                <h6 class="fw-bold mb-1">Head Office</h6>
                                <p class="text-muted small mb-0">AutiLearn AI Tech Hub, Kathmandu, Nepal</p>
                            </div>
                        </div>
                    </div>

                    <div class="p-3 bg-light rounded-3 mt-4">
                        <h6 class="fw-bold mb-1"><i class="fa-solid fa-heart-pulse text-danger me-2"></i>Emergency Support for Schools</h6>
                        <p class="text-muted small mb-0">If you are an institution needing immediate setup help, mark your request subject as <strong>"Urgent Institutional Setup"</strong>.</p>
                    </div>
                </div>
            </div>

            <!-- RIGHT COLUMN: CONTACT FORM -->
            <div class="col-lg-7" data-aos="fade-left">
                <div class="sensory-card p-4 p-md-5">
                    <h3 class="fw-bold mb-2">Send Us a Message</h3>
                    <p class="text-muted small mb-4">Fill out the form below and we will route your inquiry to the right specialist.</p>

                    <?php if ($messageSent): ?>
                        <div class="alert alert-success d-flex align-items-center rounded-3 mb-4" role="alert">
                            <i class="fa-solid fa-circle-check fs-4 me-3"></i>
                            <div>
                                <strong>Message Sent Successfully!</strong> Thank you for reaching out. A member of our support team will contact you shortly.
                            </div>
                        </div>
                    <?php endif; ?>

                    <?php if (!empty($errorMessage)): ?>
                        <div class="alert alert-danger d-flex align-items-center rounded-3 mb-4" role="alert">
                            <i class="fa-solid fa-circle-exclamation fs-4 me-3"></i>
                            <div><?php echo htmlspecialchars($errorMessage); ?></div>
                        </div>
                    <?php endif; ?>

                    <form action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>" method="POST">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label for="name" class="form-label fw-semibold">Your Full Name <span class="text-danger">*</span></label>
                                <input type="text" class="form-control form-control-lg fs-6" id="name" name="name" required placeholder="John Doe">
                            </div>

                            <div class="col-md-6">
                                <label for="email" class="form-label fw-semibold">Email Address <span class="text-danger">*</span></label>
                                <input type="email" class="form-control form-control-lg fs-6" id="email" name="email" required placeholder="name@example.com">
                            </div>

                            <div class="col-md-6">
                                <label for="role" class="form-label fw-semibold">I am a...</label>
                                <select class="form-select form-select-lg fs-6" id="role" name="role">
                                    <option value="Parent" selected>Parent / Caregiver</option>
                                    <option value="Educator">Special Educator / Teacher</option>
                                    <option value="Therapist">Speech / Behavioral Therapist</option>
                                    <option value="Institution">School Administrator</option>
                                    <option value="Other">Other</option>
                                </select>
                            </div>

                            <div class="col-md-6">
                                <label for="subject" class="form-label fw-semibold">Subject</label>
                                <input type="text" class="form-control form-control-lg fs-6" id="subject" name="subject" placeholder="e.g., Speech Module Assistance">
                            </div>

                            <div class="col-12">
                                <label for="message" class="form-label fw-semibold">Message <span class="text-danger">*</span></label>
                                <textarea class="form-control fs-6" id="message" name="message" rows="5" required placeholder="How can we help you?"></textarea>
                            </div>

                            <div class="col-12 mt-4">
                                <button type="submit" class="btn btn-primary btn-lg w-100 fw-bold py-3">
                                    <i class="fa-solid fa-paper-plane me-2"></i> Send Message
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- FREQUENTLY ASKED QUESTIONS -->
<section class="py-5 bg-light">
    <div class="container py-4">
        <div class="text-center mb-5" data-aos="fade-up">
            <span class="badge-sensory mb-2">Help Center</span>
            <h2 class="fw-bold">Frequently Asked Questions</h2>
            <p class="text-muted">Quick answers to common inquiries</p>
        </div>

        <div class="row justify-content-center">
            <div class="col-lg-8" data-aos="fade-up" data-aos-delay="100">
                <div class="accordion accordion-flush bg-white rounded-4 shadow-sm p-3" id="faqAccordion">
                    <div class="accordion-item border-0 border-bottom">
                        <h2 class="accordion-header" id="headingOne">
                            <button class="accordion-button fw-bold collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseOne">
                                Is AutiLearn AI free for individual parents?
                            </button>
                        </h2>
                        <div id="collapseOne" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
                            <div class="accordion-body text-muted">
                                We offer a free tier with core visual communication and basic AI speech exercises. Premium options are available for comprehensive progress tracking and specialized modules.
                            </div>
                        </div>
                    </div>

                    <div class="accordion-item border-0 border-bottom">
                        <h2 class="accordion-header" id="headingTwo">
                            <button class="accordion-button fw-bold collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseTwo">
                                Does the app work offline?
                            </button>
                        </h2>
                        <div id="collapseTwo" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
                            <div class="accordion-body text-muted">
                                Core PECS image libraries and standard audio exercises are cached for offline practice. Real-time AI speech and emotion analysis require an active internet connection.
                            </div>
                        </div>
                    </div>

                    <div class="accordion-item border-0">
                        <h2 class="accordion-header" id="headingThree">
                            <button class="accordion-button fw-bold collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseThree">
                                Is localized Nepali speech recognition fully supported?
                            </button>
                        </h2>
                        <div id="collapseThree" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
                            <div class="accordion-body text-muted">
                                Yes! AutiLearn AI includes tailored acoustic models built for Nepali phonetics as well as English, enabling accurate feedback for regional learners.
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>