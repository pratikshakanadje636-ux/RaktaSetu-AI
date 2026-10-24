
<?php
$images = [
    "images/img1.jpg",
    "images/img2.jpg",
    "images/img3.jpg"
];

$groups = ['A+', 'A-', 'B+', 'B-', 'AB+', 'AB-', 'O+', 'O-'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="RaktaSetu connects blood donors and recipients through a blood donation and request management portal.">
    <title>RaktaSetu | Connecting Blood, Saving Lives</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="css/css/home.css?v=<?php echo time(); ?>">
</head>
<body>

<!-- Top contact bar -->
<div class="topbar">
    <div class="container topbar-inner">
        <span>♥ &nbsp; RaktaSetu Blood Services</span>
        <div class="topbar-contact">
            <span>✉ support@raktasetu.com</span>
            <span>☎ +91 9876543210</span>
        </div>
        <span class="topbar-message">Donate Blood. Save Lives.</span>
    </div>
</div>

<!-- Navigation -->
<nav class="navbar navbar-expand-lg main-navbar sticky-top">
    <div class="container">
        <a class="navbar-brand brand" href="index.php">
            <span class="brand-icon">♥</span>
            <span>
                <strong>Rakta<span>Setu</span></strong>
                <small>Connecting Blood, Saving Lives</small>
            </span>
        </a>

        <button class="navbar-toggler" type="button"
                data-bs-toggle="collapse" data-bs-target="#mainMenu"
                aria-controls="mainMenu" aria-expanded="false"
                aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>

        <div class="collapse navbar-collapse" id="mainMenu">
            <ul class="navbar-nav ms-auto align-items-lg-center">
                <li class="nav-item"><a class="nav-link active" href="index.php">Home</a></li>
                <li class="nav-item"><a class="nav-link" href="donor_login.php">Donor Login</a></li>
                <li class="nav-item"><a class="nav-link" href="register.php">Become a Donor</a></li>
                <li class="nav-item"><a class="nav-link" href="search.php">Search Donor</a></li>
                <li class="nav-item"><a class="nav-link" href="request.php">Request Blood</a></li>
                <li class="nav-item">
                    <a class="nav-link admin-nav" href="admin_login.php">Admin Login</a>
                </li>
            </ul>
        </div>
    </div>
</nav>

<!-- Hero carousel -->
<section class="hero-section">
    <div id="heroSlider" class="carousel slide carousel-fade" data-bs-ride="carousel">

        <div class="carousel-indicators">
            <?php foreach ($images as $key => $image): ?>
                <button type="button" data-bs-target="#heroSlider"
                    data-bs-slide-to="<?php echo $key; ?>"
                    class="<?php echo $key === 0 ? 'active' : ''; ?>"
                    aria-label="Slide <?php echo $key + 1; ?>">
                </button>
            <?php endforeach; ?>
        </div>

        <div class="carousel-inner">
            <?php foreach ($images as $key => $image): ?>
                <div class="carousel-item <?php echo $key === 0 ? 'active' : ''; ?>">
                    <img src="<?php echo htmlspecialchars($image); ?>"
                         class="hero-image"
                         alt="Blood donation and community support"
                         <?php echo $key === 0 ? 'fetchpriority="high"' : 'loading="lazy"'; ?>>

                    <div class="hero-overlay"></div>

                    <div class="container hero-content">
                        <div class="hero-copy">
                            <span class="eyebrow">TOGETHER, WE CAN MAKE A DIFFERENCE</span>
                            <h1>Donate Blood,<br><span>Save Lives.</span></h1>
                            <p>One donation can bring hope to someone who needs it.
                               Join our community of voluntary blood donors.</p>

                            <div class="hero-actions">
                                <a href="register.php" class="btn-primary-custom">
                                    <span>♥</span> Become a Donor <span>→</span>
                                </a>
                                <a href="search.php" class="btn-outline-custom">
                                    Search Blood <span>⌕</span>
                                </a>
                            </div>

                            <div class="hero-note">
                                <span class="note-heart">♥</span>
                                <span>Small act. Big impact.</span>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <button class="carousel-control-prev" type="button"
                data-bs-target="#heroSlider" data-bs-slide="prev"
                aria-label="Previous slide">
            <span class="carousel-control-prev-icon"></span>
        </button>
        <button class="carousel-control-next" type="button"
                data-bs-target="#heroSlider" data-bs-slide="next"
                aria-label="Next slide">
            <span class="carousel-control-next-icon"></span>
        </button>
    </div>
</section>

<!-- Quick services -->
<section class="services-section section-space">
    <div class="container">
        <div class="section-heading text-center">
            <span class="section-kicker">HOW CAN WE HELP?</span>
            <h2>Everything You Need, <span>In One Place</span></h2>
            <p>Access the services that help connect donors and recipients.</p>
        </div>

        <div class="row g-4">
            <div class="col-12 col-sm-6 col-xl-3">
                <article class="service-card">
                    <div class="service-icon">♟</div>
                    <h3>Become a Donor</h3>
                    <p>Register your details and take the first step toward helping others.</p>
                    <a href="register.php" class="service-link">Register now <span>→</span></a>
                </article>
            </div>

            <div class="col-12 col-sm-6 col-xl-3">
                <article class="service-card">
                    <div class="service-icon">⌕</div>
                    <h3>Find a Donor</h3>
                    <p>Search the donor directory by blood group and available search options.</p>
                    <a href="search.php" class="service-link">Search donors <span>→</span></a>
                </article>
            </div>

            <div class="col-12 col-sm-6 col-xl-3">
                <article class="service-card">
                    <div class="service-icon">♥</div>
                    <h3>Request Blood</h3>
                    <p>Submit a blood requirement so it can be reviewed and managed.</p>
                    <a href="request.php" class="service-link">Make a request <span>→</span></a>
                </article>
            </div>

            <div class="col-12 col-sm-6 col-xl-3">
                <article class="service-card">
                    <div class="service-icon">▣</div>
                    <h3>Admin Portal</h3>
                    <p>Authorized administrators can manage donors and blood requests.</p>
                    <a href="admin_login.php" class="service-link">Admin login <span>→</span></a>
                </article>
            </div>
        </div>
    </div>
</section>

<!-- About section -->
<section class="about-section section-space">
    <div class="container">
        <div class="row align-items-center g-5">
            <div class="col-lg-6">
                <div class="about-image-wrap">
                    <img src="<?php echo htmlspecialchars($images[1]); ?>"
                         alt="Blood donation community" class="about-image" loading="lazy">
                    <div class="about-image-label">
                        <span>♥</span>
                        <div>
                            <strong>Give Hope</strong>
                            <small>Be someone's reason to smile.</small>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-lg-6">
                <span class="section-kicker">ABOUT OUR PLATFORM</span>
                <h2 class="about-title">Connecting People.<br><span>Supporting Lives.</span></h2>
                <p class="about-text">
                    RaktaSetu is a blood donation and request management platform
                    designed to bring donors and recipients closer together.
                </p>
                <p class="about-text">
                    Through donor registration, donor search and blood request
                    management, the platform helps organize essential information
                    in one accessible place.
                </p>

                <div class="about-points">
                    <div><span>✓</span> Convenient donor registration</div>
                    <div><span>✓</span> Simple blood donor search</div>
                    <div><span>✓</span> Organized blood request management</div>
                </div>

                <a href="register.php" class="btn-primary-custom about-button">
                    Join as a Donor <span>→</span>
                </a>
            </div>
        </div>
    </div>
</section>

<!-- Blood groups -->
<section class="blood-section section-space">
    <div class="container">
        <div class="section-heading text-center">
            <span class="section-kicker">KNOW YOUR BLOOD GROUP</span>
            <h2>Every Blood Group <span>Matters</span></h2>
            <p>Explore the eight main ABO and Rh blood group types.</p>
        </div>

        <div class="row g-3 blood-grid">
            <?php foreach ($groups as $group): ?>
                <div class="col-6 col-md-3">
                    <a class="blood-group-card" href="search.php"
                       aria-label="Search donors using blood group <?php echo htmlspecialchars($group); ?>">
                        <span class="blood-drop">♥</span>
                        <strong><?php echo htmlspecialchars($group); ?></strong>
                        <small>Blood Group</small>
                        <span class="blood-card-arrow">↗</span>
                    </a>
                </div>
            <?php endforeach; ?>
        </div>

        <p class="blood-note">
            Need blood? Use the donor search page to explore the available search options.
        </p>
        <div class="text-center">
            <a href="search.php" class="btn-outline-custom">Search Donors <span>→</span></a>
        </div>
    </div>
</section>

<!-- Platform features instead of invented statistics -->
<section class="impact-section section-space">
    <div class="container">
        <div class="section-heading text-center">
            <span class="section-kicker">THE RAKTASETU DIFFERENCE</span>
            <h2>Built Around <span>Helping People</span></h2>
            <p>Tools that make blood donation information easier to manage.</p>
        </div>

        <div class="row g-4">
            <div class="col-6 col-lg-3">
                <div class="impact-card">
                    <div class="impact-icon">♟</div>
                    <h3>Donor Network</h3>
                    <p>Organized donor information in one place.</p>
                </div>
            </div>
            <div class="col-6 col-lg-3">
                <div class="impact-card">
                    <div class="impact-icon">♥</div>
                    <h3>Blood Requests</h3>
                    <p>A dedicated way to submit blood requirements.</p>
                </div>
            </div>
            <div class="col-6 col-lg-3">
                <div class="impact-card">
                    <div class="impact-icon">⌕</div>
                    <h3>Donor Search</h3>
                    <p>Search tools to help find relevant donor records.</p>
                </div>
            </div>
            <div class="col-6 col-lg-3">
                <div class="impact-card">
                    <div class="impact-icon">▣</div>
                    <h3>Centralized Records</h3>
                    <p>Support for organized donor and request management.</p>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Donation process -->
<section class="process-section section-space">
    <div class="container">
        <div class="section-heading text-center">
            <span class="section-kicker">YOUR JOURNEY</span>
            <h2>Four Steps to <span>Make a Difference</span></h2>
            <p>Learn the general stages of the blood donation journey.</p>
        </div>

        <div class="row g-4 process-grid">
            <div class="col-6 col-lg-3">
                <div class="process-card">
                    <div class="process-number">01</div>
                    <div class="process-icon">♟</div>
                    <h3>Register</h3>
                    <p>Complete the donor registration form.</p>
                </div>
            </div>
            <div class="col-6 col-lg-3">
                <div class="process-card">
                    <div class="process-number">02</div>
                    <div class="process-icon">✓</div>
                    <h3>Get Screened</h3>
                    <p>Let qualified donation staff assess eligibility.</p>
                </div>
            </div>
            <div class="col-6 col-lg-3">
                <div class="process-card">
                    <div class="process-number">03</div>
                    <div class="process-icon">♥</div>
                    <h3>Donate</h3>
                    <p>Donate at an authorized blood donation centre.</p>
                </div>
            </div>
            <div class="col-6 col-lg-3">
                <div class="process-card">
                    <div class="process-number">04</div>
                    <div class="process-icon">✦</div>
                    <h3>Help Others</h3>
                    <p>Your donation may help someone in need.</p>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Gallery -->
<section class="gallery-section section-space">
    <div class="container">
        <div class="section-heading">
            <span class="section-kicker">THE SPIRIT OF GIVING</span>
            <h2>Small Acts, <span>Lasting Impact</span></h2>
            <p>Every act of kindness can make a difference.</p>
        </div>

        <div class="row g-3 gallery-grid">
            <?php foreach ($images as $key => $image): ?>
                <div class="col-12 col-md-4">
                    <div class="gallery-card">
                        <img src="<?php echo htmlspecialchars($image); ?>"
                             alt="RaktaSetu blood donation gallery image <?php echo $key + 1; ?>"
                             loading="lazy">
                        <div class="gallery-overlay">
                            <span>♥</span>
                            <p>Give Blood. Give Hope.</p>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<!-- Call to action -->
<section class="cta-section">
    <div class="container cta-content">
        <div>
            <span class="cta-kicker">BE SOMEONE'S HOPE</span>
            <h2>Your One Step Could Help Save a Life.</h2>
            <p>Join the RaktaSetu donor community today.</p>
        </div>
        <a href="register.php" class="cta-button">Register as a Donor <span>→</span></a>
    </div>
</section>

<!-- Footer -->
<footer class="site-footer">
    <div class="container">
        <div class="row g-4 footer-main">
            <div class="col-lg-5">
                <a class="brand footer-brand" href="index.php">
                    <span class="brand-icon">♥</span>
                    <span>
                        <strong>Rakta<span>Setu</span></strong>
                        <small>Connecting Blood, Saving Lives</small>
                    </span>
                </a>
                <p class="footer-description">
                    A digital platform designed to connect donors and recipients
                    and help organize blood donation information.
                </p>
            </div>

            <div class="col-6 col-lg-3">
                <h3>Quick Links</h3>
                <a href="index.php">Home</a>
                <a href="register.php">Donor Registration</a>
                <a href="donor_login.php">Donor Login</a>
                <a href="search.php">Search Donor</a>
            </div>

            <div class="col-6 col-lg-4">
                <h3>Blood Services</h3>
                <a href="request.php">Request Blood</a>
                <a href="admin_login.php">Admin Login</a>
                <p class="footer-contact">✉ support@raktasetu.com</p>
                <p class="footer-contact">☎ +91 9876543210</p>
            </div>
        </div>

        <div class="footer-bottom">
            <span>© <?php echo date('Y'); ?> RaktaSetu. All rights reserved.</span>
            <span>Made with <span class="footer-heart">♥</span> to support blood donation.</span>
        </div>
    </div>
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>