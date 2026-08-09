<?php
// includes/header.php
require_once __DIR__ . '/config.php';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>
        <?php echo isset($pageTitle) ? $pageTitle . " | " . SITE_NAME : SITE_NAME; ?>
    </title>

    <!-- Bootstrap 5 CSS -->
    <link 
        href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" 
        rel="stylesheet"
    >

    <!-- FontAwesome 6 Icons -->
    <link 
        href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" 
        rel="stylesheet"
    >

    <!-- AOS Animation Library -->
    <link 
        href="https://cdn.jsdelivr.net/npm/aos@2.3.4/dist/aos.css" 
        rel="stylesheet"
    >

    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>

    <link 
        href="https://fonts.googleapis.com/css2?family=Outfit:wght@400;500;600;700;800&family=Poppins:wght@300;400;500;600;700&display=swap" 
        rel="stylesheet"
    >

    <style>
        :root {
            --primary-color: #2563eb;
            --primary-hover: #1d4ed8;
            --secondary-color: #0d9488;
            --accent-purple: #7c3aed;
            --accent-soft-yellow: #fef08a;
            --bg-light: #f8fafc;
            --card-bg: #ffffff;
            --text-dark: #0f172a;
            --text-muted: #475569;
            --sensory-border-radius: 20px;
        }

        /* ================================
           GLOBAL
        ================================= */

        body {
            font-family: 'Poppins', sans-serif;
            background-color: var(--bg-light);
            color: var(--text-dark);
            overflow-x: hidden;
            line-height: 1.6;
        }

        h1,
        h2,
        h3,
        h4,
        h5,
        h6,
        .navbar-brand {
            font-family: 'Outfit', sans-serif;
        }

        /* ================================
           NAVBAR
        ================================= */

        .navbar {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(12px);
            -webkit-backdrop-filter: blur(12px);
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.04);
            padding: 16px 0;
            transition: all 0.3s ease;
            position: relative;
            z-index: 1000;
        }

        /* ================================
           LOGO / BRAND
        ================================= */

        .navbar-brand {
            font-family: 'Outfit', sans-serif;
            font-weight: 800;
            font-size: 1.75rem;
            color: var(--primary-color) !important;

            display: inline-flex;
            align-items: center;

            gap: 10px;
            text-decoration: none;
            white-space: nowrap;

            transition: all 0.3s ease;
        }

        .navbar-brand:hover {
            color: var(--primary-hover) !important;
        }

        .brand-logo {
            width: 48px;
            height: 48px;

            object-fit: contain;
            display: block;

            transition: transform 0.3s ease;
        }

        .navbar-brand:hover .brand-logo {
            transform: scale(1.05);
        }

        /* ================================
           NAVIGATION LINKS
        ================================= */

        .nav-link {
            font-weight: 500;
            color: var(--text-muted) !important;

            margin: 0 8px;
            padding: 8px 16px !important;

            border-radius: 50px;

            transition: all 0.25s ease;
        }

        .nav-link:hover,
        .nav-link.active {
            color: var(--primary-color) !important;
            background: rgba(37, 99, 235, 0.08);
        }

        /* ================================
           PRIMARY BUTTON
        ================================= */

        .btn-custom-primary {
            background-color: var(--primary-color);
            color: #ffffff;

            font-weight: 600;

            padding: 12px 32px;

            border-radius: 50px;
            border: none;

            box-shadow: 0 8px 20px rgba(37, 99, 235, 0.25);

            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);

            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;

            text-decoration: none;
        }

        .btn-custom-primary:hover {
            background-color: var(--primary-hover);
            color: #ffffff;

            transform: translateY(-2px);

            box-shadow: 0 12px 25px rgba(37, 99, 235, 0.35);
        }

        /* ================================
           OUTLINE BUTTON
        ================================= */

        .btn-custom-outline {
            border: 2px solid var(--primary-color);

            color: var(--primary-color);

            font-weight: 600;

            padding: 11px 30px;

            border-radius: 50px;

            background: transparent;

            transition: all 0.3s ease;

            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;

            text-decoration: none;
        }

        .btn-custom-outline:hover {
            background-color: var(--primary-color);
            color: #ffffff;

            transform: translateY(-2px);
        }

        /* ================================
           SENSORY CARD
        ================================= */

        .sensory-card {
            background: var(--card-bg);

            border-radius: var(--sensory-border-radius);

            padding: 32px;

            border: 1px solid rgba(226, 232, 240, 0.8);

            box-shadow: 0 10px 30px rgba(15, 23, 42, 0.04);

            transition: all 0.35s cubic-bezier(0.4, 0, 0.2, 1);

            height: 100%;
        }

        .sensory-card:hover {
            transform: translateY(-6px);

            box-shadow: 0 20px 40px rgba(37, 99, 235, 0.08);

            border-color: rgba(37, 99, 235, 0.2);
        }

        /* ================================
           SENSORY BADGE
        ================================= */

        .badge-sensory {
            background: rgba(37, 99, 235, 0.1);

            color: var(--primary-color);

            padding: 8px 18px;

            border-radius: 50px;

            font-weight: 600;

            font-size: 0.875rem;

            display: inline-block;
        }

        /* ================================
           NAVBAR TOGGLER
        ================================= */

        .navbar-toggler {
            border: none !important;
            outline: none !important;

            box-shadow: none !important;

            padding: 6px 8px;

            border-radius: 10px;
        }

        .navbar-toggler:focus {
            box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.15) !important;
        }

        .navbar-toggler-icon {
            width: 1.5em;
            height: 1.5em;
        }

        /* ================================
           TABLET
        ================================= */

        @media (max-width: 991.98px) {

            .navbar {
                padding: 12px 0;
            }

            .navbar-brand {
                font-size: 1.5rem;
                gap: 9px;
            }

            .brand-logo {
                width: 42px;
                height: 42px;
            }

            .navbar-collapse {
                margin-top: 15px;
                padding: 10px 0 5px;
            }

            .navbar-nav {
                align-items: stretch !important;
                width: 100%;
            }

            .nav-item {
                width: 100%;
            }

            .nav-link {
                margin: 3px 0;
                padding: 10px 15px !important;

                width: 100%;
                display: block;
            }

            .navbar-nav .btn {
                width: 100%;
                justify-content: center;

                margin: 5px 0 !important;
            }

            .nav-item.ms-lg-3 {
                margin-left: 0 !important;
                margin-top: 10px !important;
            }

            .nav-item.mt-2 {
                margin-top: 5px !important;
            }
        }

        /* ================================
           MOBILE
        ================================= */

        @media (max-width: 575.98px) {

            .navbar {
                padding: 10px 0;
            }

            .navbar-brand {
                font-size: 1.25rem;
                gap: 8px;
            }

            .brand-logo {
                width: 38px;
                height: 38px;
            }

            .navbar-toggler {
                padding: 5px 7px;
            }

            .navbar-collapse {
                margin-top: 12px;
            }

            .nav-link {
                font-size: 0.95rem;
            }

            .btn-custom-primary,
            .btn-custom-outline {
                padding: 11px 20px;
            }
        }

        /* ================================
           VERY SMALL DEVICES
        ================================= */

        @media (max-width: 380px) {

            .navbar-brand {
                font-size: 1.1rem;
            }

            .brand-logo {
                width: 34px;
                height: 34px;
            }

            .navbar-toggler-icon {
                width: 1.3em;
                height: 1.3em;
            }
        }
    </style>
</head>

<body>

<!-- ================================
     NAVBAR
================================= -->

<nav class="navbar navbar-expand-lg sticky-top">

    <div class="container">

        <!-- Logo / Brand -->
        <a 
            class="navbar-brand" 
            href="<?php echo BASE_URL; ?>index.php"
        >
            <img 
                src="<?php echo BASE_URL; ?>assets/icons/logo.png"
                alt="Spark Steps Logo"
                class="brand-logo"
            >

            <span>Spark Steps</span>
        </a>


        <!-- Mobile Menu Button -->
        <button 
            class="navbar-toggler border-0 shadow-none" 
            type="button" 
            data-bs-toggle="collapse" 
            data-bs-target="#mainNav" 
            aria-controls="mainNav" 
            aria-expanded="false" 
            aria-label="Toggle navigation"
        >
            <span class="navbar-toggler-icon"></span>
        </button>


        <!-- Navigation -->
        <div class="collapse navbar-collapse" id="mainNav">

            <ul class="navbar-nav ms-auto align-items-center">

                <!-- Home -->
                <li class="nav-item">
                    <a 
                        class="nav-link <?php echo isActivePage('index.php'); ?>" 
                        href="<?php echo BASE_URL; ?>index.php"
                    >
                        Home
                    </a>
                </li>


                <!-- About -->
                <li class="nav-item">
                    <a 
                        class="nav-link <?php echo isActivePage('about.php'); ?>" 
                        href="<?php echo BASE_URL; ?>pages/about.php"
                    >
                        About
                    </a>
                </li>


      

                <!-- Sensory Guide -->
                <li class="nav-item">
                    <a 
                        class="nav-link <?php echo isActivePage('autism.php'); ?>" 
                        href="<?php echo BASE_URL; ?>pages/autism.php"
                    >
                        Sensory Guide
                    </a>
                </li>





                <!-- Login -->
                <li class="nav-item ms-lg-3 mt-3 mt-lg-0">
                    <a 
                        href="<?php echo BASE_URL; ?>login.php" 
                        class="btn btn-custom-outline me-lg-2"
                    >
                        Login
                    </a>
                </li>


       
            </ul>

        </div>

    </div>

</nav>