<?php
// Start session to track splash screen display
session_start();

// Set session variable to prevent showing splash again if needed
$_SESSION['splash_shown'] = true;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CureBooking - Welcome</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            overflow: hidden;
        }

        .splash-container {
            text-align: center;
            color: white;
            z-index: 10;
        }

        .logo {
            margin-bottom: 2rem;
        }

        .logo-icon {
            width: 80px;
            height: 80px;
            background: rgba(255, 255, 255, 0.2);
            border-radius: 50%;
            margin: 0 auto 1rem;
            display: flex;
            align-items: center;
            justify-content: center;
            animation: pulse 2s infinite;
            backdrop-filter: blur(10px);
            border: 2px solid rgba(255, 255, 255, 0.3);
        }

        .logo-icon svg {
            width: 40px;
            height: 40px;
            fill: white;
        }

        .logo-text {
            font-size: 3rem;
            font-weight: 700;
            margin-bottom: 0.5rem;
            opacity: 0;
            animation: fadeInUp 1s ease 0.5s forwards;
            text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.3);
        }

        .tagline {
            font-size: 1.2rem;
            opacity: 0;
            animation: fadeInUp 1s ease 1s forwards;
            margin-bottom: 2rem;
            font-weight: 300;
        }

        .loading-bar {
            width: 200px;
            height: 4px;
            background: rgba(255, 255, 255, 0.3);
            border-radius: 2px;
            margin: 2rem auto;
            overflow: hidden;
            opacity: 0;
            animation: fadeIn 1s ease 1.5s forwards;
        }

        .loading-progress {
            width: 0%;
            height: 100%;
            background: white;
            border-radius: 2px;
            animation: loading 2s ease 2s forwards;
        }

        .pulse-dots {
            display: flex;
            justify-content: center;
            gap: 8px;
            margin-top: 2rem;
            opacity: 0;
            animation: fadeIn 1s ease 2s forwards;
        }

        .dot {
            width: 8px;
            height: 8px;
            background: white;
            border-radius: 50%;
            animation: dotPulse 1.4s ease-in-out infinite both;
        }

        .dot:nth-child(2) {
            animation-delay: 0.2s;
        }

        .dot:nth-child(3) {
            animation-delay: 0.4s;
        }

        /* Animations */
        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }

        @keyframes loading {
            from { width: 0%; }
            to { width: 100%; }
        }

        @keyframes pulse {
            0% {
                transform: scale(1);
                box-shadow: 0 0 0 0 rgba(255, 255, 255, 0.4);
            }
            70% {
                transform: scale(1.05);
                box-shadow: 0 0 0 10px rgba(255, 255, 255, 0);
            }
            100% {
                transform: scale(1);
                box-shadow: 0 0 0 0 rgba(255, 255, 255, 0);
            }
        }

        @keyframes dotPulse {
            0%, 80%, 100% {
                transform: scale(0.8);
                opacity: 0.5;
            }
            40% {
                transform: scale(1);
                opacity: 1;
            }
        }

        /* Background animation */
        .bg-animation {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            z-index: 1;
        }

        .floating-shape {
            position: absolute;
            background: rgba(255, 255, 255, 0.1);
            border-radius: 50%;
            animation: float 6s ease-in-out infinite;
        }

        .shape-1 {
            width: 100px;
            height: 100px;
            top: 10%;
            left: 10%;
            animation-delay: 0s;
        }

        .shape-2 {
            width: 150px;
            height: 150px;
            top: 60%;
            right: 10%;
            animation-delay: 2s;
        }

        .shape-3 {
            width: 80px;
            height: 80px;
            bottom: 20%;
            left: 20%;
            animation-delay: 4s;
        }

        @keyframes float {
            0%, 100% {
                transform: translateY(0px) rotate(0deg);
            }
            50% {
                transform: translateY(-20px) rotate(180deg);
            }
        }
    </style>
</head>
<body>
    <!-- Background Animation -->
    <div class="bg-animation">
        <div class="floating-shape shape-1"></div>
        <div class="floating-shape shape-2"></div>
        <div class="floating-shape shape-3"></div>
    </div>

    <!-- Splash Content -->
    <div class="splash-container">
        <div class="logo">
            <div class="logo-icon">
                <svg viewBox="0 0 24 24">
                    <path d="M19 3H5c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2zm-7 15.5c-3.6 0-6.5-2.9-6.5-6.5S8.4 5.5 12 5.5s6.5 2.9 6.5 6.5-2.9 6.5-6.5 6.5z"/>
                    <circle cx="12" cy="12" r="3.5"/>
                </svg>
            </div>
            <h1 class="logo-text">CureBooking</h1>
        </div>
        
        <p class="tagline">Your Health, Our Priority</p>
        
        <div class="loading-bar">
            <div class="loading-progress"></div>
        </div>
        
        <div class="pulse-dots">
            <div class="dot"></div>
            <div class="dot"></div>
            <div class="dot"></div>
        </div>
    </div>

    <script>
        // Redirect to your existing home page after animation
        setTimeout(() => {
            window.location.href = '/home/index.php';
        }, 4000); // 4 seconds total

        // Optional: Allow users to skip splash by clicking
        document.addEventListener('click', function() {
            window.location.href = '/home/index.php';
        });

        // Optional: Skip splash if they press any key
        document.addEventListener('keydown', function() {
            window.location.href = '/home/index.php';
        });
    </script>
</body>
</html>