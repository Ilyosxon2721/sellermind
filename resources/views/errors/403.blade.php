<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>403 — Доступ запрещен | SellerMind AI</title>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=inter:400,500,600,700,800&display=swap" rel="stylesheet" />
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif; background: #0f172a; min-height: 100vh; display: flex; align-items: center; justify-content: center; padding: 1rem; overflow: hidden; position: relative; }
        body::before { content: ''; position: absolute; inset: 0; background: radial-gradient(circle at 30% 50%, rgba(239, 68, 68, 0.15) 0%, transparent 50%), radial-gradient(circle at 70% 50%, rgba(245, 158, 11, 0.15) 0%, transparent 50%); animation: pulse 8s ease-in-out infinite; }
        @keyframes pulse { 0%, 100% { opacity: 0.5; } 50% { opacity: 1; } }
        .particles { position: absolute; inset: 0; overflow: hidden; pointer-events: none; }
        .particle { position: absolute; background: linear-gradient(135deg, #ef4444, #f59e0b); border-radius: 50%; animation: shield-float 15s infinite ease-in-out; opacity: 0.2; }
        .particle:nth-child(1) { width: 60px; height: 60px; left: 15%; animation-delay: 0s; }
        .particle:nth-child(2) { width: 80px; height: 80px; left: 40%; animation-delay: -3s; }
        .particle:nth-child(3) { width: 70px; height: 70px; left: 65%; animation-delay: -6s; }
        .particle:nth-child(4) { width: 90px; height: 90px; left: 85%; animation-delay: -9s; }
        @keyframes shield-float { 0%, 100% { transform: translateY(100vh) scale(0) rotate(0deg); opacity: 0; } 10% { opacity: 0.2; } 90% { opacity: 0.2; } 100% { transform: translateY(-100px) scale(1) rotate(360deg); opacity: 0; } }
        .container { text-align: center; max-width: 42rem; position: relative; z-index: 10; animation: fadeInUp 0.8s ease-out; }
        @keyframes fadeInUp { from { opacity: 0; transform: translateY(30px); } to { opacity: 1; transform: translateY(0); } }
        .error-illustration { position: relative; margin: 0 auto 2rem; width: 280px; height: 220px; }
        .lock-container { position: relative; width: 150px; height: 150px; margin: 0 auto; animation: shake 3s ease-in-out infinite; }
        @keyframes shake { 0%, 100% { transform: rotate(0deg); } 2%, 6% { transform: rotate(-5deg); } 4%, 8% { transform: rotate(5deg); } 10% { transform: rotate(0deg); } }
        .lock { position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); }
        .lock-body { width: 80px; height: 90px; background: linear-gradient(135deg, #ef4444, #dc2626); border-radius: 15px; position: relative; box-shadow: 0 20px 40px rgba(239, 68, 68, 0.4); animation: glow 2s ease-in-out infinite; }
        @keyframes glow { 0%, 100% { box-shadow: 0 20px 40px rgba(239, 68, 68, 0.4); } 50% { box-shadow: 0 20px 60px rgba(239, 68, 68, 0.6); } }
        .lock-shackle { width: 50px; height: 45px; border: 8px solid #ef4444; border-bottom: none; border-radius: 25px 25px 0 0; position: absolute; top: -35px; left: 50%; transform: translateX(-50%); animation: shackle-pulse 2s ease-in-out infinite; }
        @keyframes shackle-pulse { 0%, 100% { transform: translateX(-50%) scale(1); } 50% { transform: translateX(-50%) scale(1.05); } }
        .lock-keyhole { width: 12px; height: 25px; background: rgba(15, 23, 42, 0.3); border-radius: 6px 6px 0 0; position: absolute; top: 35px; left: 50%; transform: translateX(-50%); }
        .lock-keyhole::after { content: ''; position: absolute; top: 20px; left: 50%; transform: translateX(-50%); width: 0; height: 0; border-left: 8px solid transparent; border-right: 8px solid transparent; border-top: 12px solid rgba(15, 23, 42, 0.3); }
        .warning-ring { position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); border: 3px solid #ef4444; border-radius: 50%; animation: ring-expand 2s ease-out infinite; }
        .warning-ring:nth-child(1) { animation-delay: 0s; }
        .warning-ring:nth-child(2) { animation-delay: 0.5s; }
        .warning-ring:nth-child(3) { animation-delay: 1s; }
        @keyframes ring-expand { 0% { width: 100px; height: 100px; opacity: 0.8; } 100% { width: 200px; height: 200px; opacity: 0; } }
        .code-display { font-size: 4rem; font-weight: 800; background: linear-gradient(135deg, #ef4444 0%, #f59e0b 100%); -webkit-background-clip: text; -webkit-text-fill-color: transparent; background-clip: text; margin-top: 1rem; }
        .title { font-size: 2rem; font-weight: 700; color: #f8fafc; margin-bottom: 1rem; animation: fadeIn 1s ease-out 0.3s both; }
        @keyframes fadeIn { from { opacity: 0; } to { opacity: 1; } }
        .subtitle { color: #94a3b8; margin-bottom: 2.5rem; line-height: 1.6; font-size: 1.125rem; animation: fadeIn 1s ease-out 0.5s both; }
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
        @media (max-width: 640px) { .code-display { font-size: 3rem; } .title { font-size: 1.5rem; } .subtitle { font-size: 1rem; } .lock-container { width: 120px; height: 120px; } }
    </style>
</head>
<body>
    <div class="particles">
        <div class="particle"></div>
        <div class="particle"></div>
        <div class="particle"></div>
        <div class="particle"></div>
    </div>
    <div class="container">
        <div class="error-illustration">
            <div class="lock-container">
                <div class="warning-ring"></div>
                <div class="warning-ring"></div>
                <div class="warning-ring"></div>
                <div class="lock">
                    <div class="lock-shackle"></div>
                    <div class="lock-body">
                        <div class="lock-keyhole"></div>
                    </div>
                </div>
            </div>
            <div class="code-display">403</div>
        </div>
        <h1 class="title">Доступ запрещен</h1>
        <p class="subtitle">У вас недостаточно прав для просмотра этой страницы 🔒<br>Пожалуйста, войдите с правильными учетными данными</p>
        <div class="buttons">
            <a href="/" class="btn btn-primary">
                <svg width="18" height="18" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/></svg>
                На главную
            </a>
            <a href="javascript:history.back()" class="btn btn-secondary">
                <svg width="18" height="18" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
                Назад
            </a>
        </div>
        <div class="links">
            <a href="/login" class="link">Войти</a>
            <a href="/register" class="link">Регистрация</a>
            <a href="mailto:support@sellermind.ai" class="link">Связаться с администратором</a>
        </div>
    </div>
</body>
</html>
