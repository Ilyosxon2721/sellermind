<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>503 — Техническое обслуживание | SellerMind AI</title>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=inter:400,500,600,700,800&display=swap" rel="stylesheet" />
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif; background: #0f172a; min-height: 100vh; display: flex; align-items: center; justify-content: center; padding: 1rem; overflow: hidden; position: relative; }
        body::before { content: ''; position: absolute; inset: 0; background: radial-gradient(circle at 40% 40%, rgba(59, 130, 246, 0.15) 0%, transparent 50%), radial-gradient(circle at 60% 60%, rgba(139, 92, 246, 0.15) 0%, transparent 50%); animation: pulse 10s ease-in-out infinite; }
        @keyframes pulse { 0%, 100% { opacity: 0.5; } 50% { opacity: 1; } }
        .stars { position: absolute; inset: 0; overflow: hidden; pointer-events: none; }
        .star { position: absolute; background: white; border-radius: 50%; animation: twinkle 3s infinite; }
        .star:nth-child(1) { width: 2px; height: 2px; top: 20%; left: 10%; animation-delay: 0s; }
        .star:nth-child(2) { width: 3px; height: 3px; top: 30%; left: 30%; animation-delay: 0.5s; }
        .star:nth-child(3) { width: 2px; height: 2px; top: 50%; left: 50%; animation-delay: 1s; }
        .star:nth-child(4) { width: 2px; height: 2px; top: 70%; left: 70%; animation-delay: 1.5s; }
        .star:nth-child(5) { width: 3px; height: 3px; top: 80%; left: 20%; animation-delay: 2s; }
        .star:nth-child(6) { width: 2px; height: 2px; top: 15%; left: 80%; animation-delay: 0.3s; }
        .star:nth-child(7) { width: 2px; height: 2px; top: 40%; left: 85%; animation-delay: 1.2s; }
        .star:nth-child(8) { width: 3px; height: 3px; top: 60%; left: 15%; animation-delay: 1.8s; }
        @keyframes twinkle { 0%, 100% { opacity: 0.3; transform: scale(1); } 50% { opacity: 1; transform: scale(1.5); } }
        .container { text-align: center; max-width: 42rem; position: relative; z-index: 10; animation: fadeInUp 0.8s ease-out; }
        @keyframes fadeInUp { from { opacity: 0; transform: translateY(30px); } to { opacity: 1; transform: translateY(0); } }
        .error-illustration { position: relative; margin: 0 auto 2rem; width: 280px; height: 220px; }
        .maintenance-container { position: relative; width: 150px; height: 150px; margin: 0 auto; }
        .cone { width: 60px; height: 70px; position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); animation: coneFloat 2s ease-in-out infinite; }
        @keyframes coneFloat { 0%, 100% { transform: translate(-50%, -50%); } 50% { transform: translate(-50%, -60%); } }
        .cone-body { width: 0; height: 0; border-left: 30px solid transparent; border-right: 30px solid transparent; border-bottom: 60px solid #f97316; filter: drop-shadow(0 10px 20px rgba(249, 115, 22, 0.4)); }
        .cone-stripe { position: absolute; height: 8px; background: white; width: 100%; }
        .cone-stripe:nth-child(1) { top: 15px; }
        .cone-stripe:nth-child(2) { top: 35px; }
        .progress-ring { position: absolute; top: 0; left: 0; width: 150px; height: 150px; }
        .progress-ring circle { fill: none; stroke: #3b82f6; stroke-width: 3; stroke-dasharray: 440; stroke-dashoffset: 0; animation: progress 3s ease-in-out infinite; transform-origin: center; transform: rotate(-90deg); }
        @keyframes progress { 0% { stroke-dashoffset: 440; } 50% { stroke-dashoffset: 0; } 100% { stroke-dashoffset: -440; } }
        .code-display { font-size: 4rem; font-weight: 800; background: linear-gradient(135deg, #3b82f6 0%, #8b5cf6 100%); -webkit-background-clip: text; -webkit-text-fill-color: transparent; background-clip: text; margin-top: 1rem; }
        .title { font-size: 2rem; font-weight: 700; color: #f8fafc; margin-bottom: 1rem; animation: fadeIn 1s ease-out 0.3s both; }
        @keyframes fadeIn { from { opacity: 0; } to { opacity: 1; } }
        .subtitle { color: #94a3b8; margin-bottom: 2.5rem; line-height: 1.6; font-size: 1.125rem; animation: fadeIn 1s ease-out 0.5s both; }
        .status-badge { display: inline-flex; align-items: center; gap: 0.5rem; background: rgba(59, 130, 246, 0.1); border: 1px solid rgba(59, 130, 246, 0.3); color: #60a5fa; padding: 0.5rem 1rem; border-radius: 2rem; font-size: 0.875rem; font-weight: 500; margin-bottom: 2rem; animation: fadeIn 1s ease-out 0.4s both; }
        .status-dot { width: 8px; height: 8px; background: #60a5fa; border-radius: 50%; animation: statusPulse 2s ease-in-out infinite; }
        @keyframes statusPulse { 0%, 100% { opacity: 1; transform: scale(1); } 50% { opacity: 0.5; transform: scale(1.2); } }
        .buttons { display: flex; flex-wrap: wrap; gap: 1rem; justify-content: center; animation: fadeIn 1s ease-out 0.7s both; }
        .btn { display: inline-flex; align-items: center; gap: 0.5rem; padding: 0.875rem 1.75rem; font-size: 0.9375rem; font-weight: 600; border-radius: 0.75rem; text-decoration: none; transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1); position: relative; overflow: hidden; }
        .btn::before { content: ''; position: absolute; inset: 0; background: linear-gradient(135deg, rgba(255,255,255,0.1), transparent); transform: translateX(-100%); transition: transform 0.5s ease; }
        .btn:hover::before { transform: translateX(100%); }
        .btn-primary { background: linear-gradient(135deg, #2563eb 0%, #3b82f6 100%); color: white; box-shadow: 0 10px 25px -5px rgba(37, 99, 235, 0.4); }
        .btn-primary:hover { transform: translateY(-2px); box-shadow: 0 15px 35px -5px rgba(37, 99, 235, 0.5); }
        .btn-secondary { background: rgba(255, 255, 255, 0.05); color: #f8fafc; border: 1px solid rgba(255, 255, 255, 0.1); backdrop-filter: blur(10px); }
        .btn-secondary:hover { background: rgba(255, 255, 255, 0.1); border-color: rgba(255, 255, 255, 0.2); transform: translateY(-2px); }
        .links { margin-top: 2.5rem; display: flex; gap: 2rem; justify-content: center; flex-wrap: wrap; animation: fadeIn 1s ease-out 0.9s both; }
        .link { color: #60a5fa; text-decoration: none; font-size: 0.9375rem; font-weight: 500; transition: all 0.2s ease; position: relative; }
        .link::after { content: ''; position: absolute; bottom: -2px; left: 0; width: 0; height: 2px; background: #60a5fa; transition: width 0.3s ease; }
        .link:hover { color: #93c5fd; }
        .link:hover::after { width: 100%; }
        @media (max-width: 640px) { .code-display { font-size: 3rem; } .title { font-size: 1.5rem; } .subtitle { font-size: 1rem; } .maintenance-container { width: 120px; height: 120px; } }
    </style>
</head>
<body>
    <div class="stars">
        <div class="star"></div>
        <div class="star"></div>
        <div class="star"></div>
        <div class="star"></div>
        <div class="star"></div>
        <div class="star"></div>
        <div class="star"></div>
        <div class="star"></div>
    </div>
    <div class="container">
        <div class="error-illustration">
            <div class="maintenance-container">
                <svg class="progress-ring" viewBox="0 0 150 150">
                    <circle cx="75" cy="75" r="70"/>
                </svg>
                <div class="cone">
                    <div style="position: relative;">
                        <div class="cone-body"></div>
                        <div class="cone-stripe"></div>
                        <div class="cone-stripe"></div>
                    </div>
                </div>
            </div>
            <div class="code-display">503</div>
        </div>
        <div class="status-badge">
            <span class="status-dot"></span>
            Идет обновление
        </div>
        <h1 class="title">Техническое обслуживание</h1>
        <p class="subtitle">Мы обновляем систему для вашего удобства ⚙️<br>Скоро вернемся с улучшениями!</p>
        <div class="buttons">
            <a href="/" class="btn btn-primary">
                <svg width="18" height="18" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/></svg>
                Попробовать снова
            </a>
            <a href="javascript:history.back()" class="btn btn-secondary">
                <svg width="18" height="18" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
                Назад
            </a>
        </div>
        <div class="links">
            <a href="https://twitter.com/sellermind" class="link" target="_blank">Следить за обновлениями</a>
            <a href="mailto:support@sellermind.ai" class="link">Связаться с нами</a>
        </div>
    </div>
</body>
</html>
<?php /**PATH D:\server\OSPanel\home\sellermind\resources\views\errors\503.blade.php ENDPATH**/ ?>