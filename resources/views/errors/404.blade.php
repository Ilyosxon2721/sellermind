<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>404 — Страница не найдена | SellerMind AI</title>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=inter:400,500,600,700,800&display=swap" rel="stylesheet" />
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
            background: #0f172a;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 1rem;
            overflow: hidden;
            position: relative;
        }

        /* Animated gradient background */
        body::before {
            content: '';
            position: absolute;
            inset: 0;
            background: linear-gradient(135deg, #0f172a 0%, #1e293b 50%, #0f172a 100%);
            animation: gradientShift 15s ease infinite;
        }

        @keyframes gradientShift {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.8; }
        }

        /* Floating particles */
        .particles {
            position: absolute;
            inset: 0;
            overflow: hidden;
            pointer-events: none;
        }

        .particle {
            position: absolute;
            background: radial-gradient(circle, #3b82f6 0%, transparent 70%);
            border-radius: 50%;
            animation: float 20s infinite ease-in-out;
            opacity: 0.3;
        }

        .particle:nth-child(1) { width: 80px; height: 80px; left: 10%; animation-delay: 0s; }
        .particle:nth-child(2) { width: 60px; height: 60px; left: 30%; animation-delay: -5s; }
        .particle:nth-child(3) { width: 100px; height: 100px; left: 50%; animation-delay: -10s; }
        .particle:nth-child(4) { width: 70px; height: 70px; left: 70%; animation-delay: -15s; }
        .particle:nth-child(5) { width: 90px; height: 90px; left: 85%; animation-delay: -7s; }

        @keyframes float {
            0%, 100% { transform: translateY(100vh) scale(0); opacity: 0; }
            10% { opacity: 0.3; }
            90% { opacity: 0.3; }
            100% { transform: translateY(-100px) scale(1); opacity: 0; }
        }

        .container {
            text-align: center;
            max-width: 42rem;
            position: relative;
            z-index: 10;
            animation: fadeInUp 0.8s ease-out;
        }

        @keyframes fadeInUp {
            from { opacity: 0; transform: translateY(30px); }
            to { opacity: 1; transform: translateY(0); }
        }

        /* Animated 404 illustration */
        .error-illustration {
            position: relative;
            margin: 0 auto 2rem;
            width: 280px;
            height: 200px;
        }

        .code-display {
            font-size: 8rem;
            font-weight: 800;
            background: linear-gradient(135deg, #3b82f6 0%, #8b5cf6 50%, #ec4899 100%);
            background-size: 200% 200%;
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            line-height: 1;
            animation: gradientMove 3s ease infinite, glitch 5s infinite;
            position: relative;
        }

        @keyframes gradientMove {
            0%, 100% { background-position: 0% 50%; }
            50% { background-position: 100% 50%; }
        }

        @keyframes glitch {
            0%, 90%, 100% { transform: translate(0); }
            91% { transform: translate(-2px, 2px); }
            92% { transform: translate(2px, -2px); }
            93% { transform: translate(-2px, 2px); }
            94% { transform: translate(2px, -2px); }
        }

        /* Robot SVG */
        .robot {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            width: 120px;
            height: 120px;
            animation: bounce 2s ease-in-out infinite;
        }

        @keyframes bounce {
            0%, 100% { transform: translate(-50%, -50%) translateY(0); }
            50% { transform: translate(-50%, -50%) translateY(-20px); }
        }

        .robot-head {
            fill: #2563eb;
            animation: headTilt 3s ease-in-out infinite;
            transform-origin: center;
        }

        @keyframes headTilt {
            0%, 100% { transform: rotate(-5deg); }
            50% { transform: rotate(5deg); }
        }

        .robot-eye {
            fill: #60a5fa;
            animation: blink 4s infinite;
        }

        @keyframes blink {
            0%, 48%, 52%, 100% { opacity: 1; }
            50% { opacity: 0; }
        }

        .title {
            font-size: 2rem;
            font-weight: 700;
            color: #f8fafc;
            margin-bottom: 1rem;
            animation: fadeIn 1s ease-out 0.3s both;
        }

        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }

        .subtitle {
            color: #94a3b8;
            margin-bottom: 2.5rem;
            line-height: 1.6;
            font-size: 1.125rem;
            animation: fadeIn 1s ease-out 0.5s both;
        }

        .buttons {
            display: flex;
            flex-wrap: wrap;
            gap: 1rem;
            justify-content: center;
            animation: fadeIn 1s ease-out 0.7s both;
        }

        .btn {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.875rem 1.75rem;
            font-size: 0.9375rem;
            font-weight: 600;
            border-radius: 0.75rem;
            text-decoration: none;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            position: relative;
            overflow: hidden;
        }

        .btn::before {
            content: '';
            position: absolute;
            inset: 0;
            background: linear-gradient(135deg, rgba(255,255,255,0.1), transparent);
            transform: translateX(-100%);
            transition: transform 0.5s ease;
        }

        .btn:hover::before {
            transform: translateX(100%);
        }

        .btn-primary {
            background: linear-gradient(135deg, #2563eb 0%, #3b82f6 100%);
            color: white;
            box-shadow: 0 10px 25px -5px rgba(37, 99, 235, 0.4);
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 15px 35px -5px rgba(37, 99, 235, 0.5);
        }

        .btn-primary:active {
            transform: translateY(0);
        }

        .btn-secondary {
            background: rgba(255, 255, 255, 0.05);
            color: #f8fafc;
            border: 1px solid rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(10px);
        }

        .btn-secondary:hover {
            background: rgba(255, 255, 255, 0.1);
            border-color: rgba(255, 255, 255, 0.2);
            transform: translateY(-2px);
        }

        .links {
            margin-top: 2.5rem;
            display: flex;
            gap: 2rem;
            justify-content: center;
            flex-wrap: wrap;
            animation: fadeIn 1s ease-out 0.9s both;
        }

        .link {
            color: #60a5fa;
            text-decoration: none;
            font-size: 0.9375rem;
            font-weight: 500;
            transition: all 0.2s ease;
            position: relative;
        }

        .link::after {
            content: '';
            position: absolute;
            bottom: -2px;
            left: 0;
            width: 0;
            height: 2px;
            background: #60a5fa;
            transition: width 0.3s ease;
        }

        .link:hover {
            color: #93c5fd;
        }

        .link:hover::after {
            width: 100%;
        }

        /* Responsive */
        @media (max-width: 640px) {
            .code-display { font-size: 5rem; }
            .title { font-size: 1.5rem; }
            .subtitle { font-size: 1rem; }
            .error-illustration { width: 200px; height: 150px; }
            .robot { width: 80px; height: 80px; }
        }
    </style>
</head>
<body>
    <!-- Animated particles -->
    <div class="particles">
        <div class="particle"></div>
        <div class="particle"></div>
        <div class="particle"></div>
        <div class="particle"></div>
        <div class="particle"></div>
    </div>

    <div class="container">
        <div class="error-illustration">
            <div class="code-display">404</div>
            <svg class="robot" viewBox="0 0 120 120" fill="none" xmlns="http://www.w3.org/2000/svg">
                <!-- Robot body -->
                <rect class="robot-head" x="30" y="35" width="60" height="50" rx="10"/>
                <!-- Antenna -->
                <line x1="60" y1="25" x2="60" y2="35" stroke="#2563eb" stroke-width="3" stroke-linecap="round"/>
                <circle cx="60" cy="22" r="5" fill="#3b82f6"/>
                <!-- Eyes -->
                <circle class="robot-eye" cx="45" cy="55" r="6"/>
                <circle class="robot-eye" cx="75" cy="55" r="6"/>
                <!-- Mouth -->
                <path d="M 45 70 Q 60 75 75 70" stroke="#60a5fa" stroke-width="3" stroke-linecap="round" fill="none"/>
                <!-- Arms -->
                <rect x="20" y="50" width="8" height="25" rx="4" fill="#334155"/>
                <rect x="92" y="50" width="8" height="25" rx="4" fill="#334155"/>
            </svg>
        </div>

        <h1 class="title">Упс! Страница не найдена</h1>
        <p class="subtitle">
            Похоже, эта страница отправилась в космос 🚀<br>
            Давайте вернем вас на правильный путь!
        </p>

        <div class="buttons">
            <a href="/" class="btn btn-primary">
                <svg width="18" height="18" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/>
                </svg>
                На главную
            </a>
            <a href="javascript:history.back()" class="btn btn-secondary">
                <svg width="18" height="18" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                </svg>
                Назад
            </a>
        </div>

        <div class="links">
            <a href="/login" class="link">Войти</a>
            <a href="/register" class="link">Регистрация</a>
            <a href="mailto:support@sellermind.ai" class="link">Поддержка</a>
        </div>
    </div>
</body>
</html>
