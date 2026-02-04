<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>500 — Ошибка сервера | SellerMind AI</title>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=inter:400,500,600,700,800&display=swap" rel="stylesheet" />
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif; background: #f8fafc; min-height: 100vh; display: flex; align-items: center; justify-content: center; padding: 1rem; overflow: hidden; position: relative; }
        body::before { content: ''; position: absolute; inset: 0; background: linear-gradient(135deg, #f8fafc 0%, #eff6ff 50%, #f8fafc 100%); animation: gradientShift 15s ease infinite; }
        @keyframes gradientShift { 0%, 100% { opacity: 1; } 50% { opacity: 0.8; } }
        .particles { position: absolute; inset: 0; overflow: hidden; pointer-events: none; }
        .particle { position: absolute; background: radial-gradient(circle, #3b82f6 0%, transparent 70%); border-radius: 50%; animation: float 20s infinite ease-in-out; opacity: 0.15; }
        .particle:nth-child(1) { width: 80px; height: 80px; left: 10%; animation-delay: 0s; }
        .particle:nth-child(2) { width: 60px; height: 60px; left: 30%; animation-delay: -5s; }
        .particle:nth-child(3) { width: 100px; height: 100px; left: 50%; animation-delay: -10s; }
        .particle:nth-child(4) { width: 70px; height: 70px; left: 70%; animation-delay: -15s; }
        .particle:nth-child(5) { width: 90px; height: 90px; left: 85%; animation-delay: -7s; }
        @keyframes float { 0%, 100% { transform: translateY(100vh) scale(0); opacity: 0; } 10% { opacity: 0.15; } 90% { opacity: 0.15; } 100% { transform: translateY(-100px) scale(1); opacity: 0; } }
        .container { text-align: center; max-width: 42rem; position: relative; z-index: 10; animation: fadeInUp 0.8s ease-out; }
        @keyframes fadeInUp { from { opacity: 0; transform: translateY(30px); } to { opacity: 1; transform: translateY(0); } }
        .error-illustration { position: relative; margin: 0 auto 2rem; width: 280px; height: 220px; }
        .tools-container { position: relative; width: 150px; height: 150px; margin: 0 auto; }
        .tool { position: absolute; animation: toolSpin 4s ease-in-out infinite; transform-origin: center; }
        .wrench { width: 80px; height: 80px; fill: #3b82f6; top: 50%; left: 50%; transform: translate(-50%, -50%) rotate(0deg); }
        .gear { width: 60px; height: 60px; fill: #60a5fa; animation: gearRotate 3s linear infinite; }
        .gear-1 { top: 10%; left: 10%; }
        .gear-2 { top: 10%; right: 10%; animation-delay: -1s; }
        .gear-3 { bottom: 10%; left: 10%; animation-delay: -2s; }
        @keyframes toolSpin { 0%, 100% { transform: translate(-50%, -50%) rotate(-15deg); } 50% { transform: translate(-50%, -50%) rotate(15deg); } }
        @keyframes gearRotate { from { transform: rotate(0deg); } to { transform: rotate(360deg); } }
        .code-display { font-size: 4rem; font-weight: 800; background: linear-gradient(135deg, #2563eb 0%, #3b82f6 100%); -webkit-background-clip: text; -webkit-text-fill-color: transparent; background-clip: text; margin-top: 1rem; }
        .title { font-size: 2rem; font-weight: 700; color: #1e293b; margin-bottom: 1rem; animation: fadeIn 1s ease-out 0.3s both; }
        @keyframes fadeIn { from { opacity: 0; } to { opacity: 1; } }
        .subtitle { color: #64748b; margin-bottom: 2.5rem; line-height: 1.6; font-size: 1.125rem; animation: fadeIn 1s ease-out 0.5s both; }
        .buttons { display: flex; flex-wrap: wrap; gap: 1rem; justify-content: center; animation: fadeIn 1s ease-out 0.7s both; }
        .btn { display: inline-flex; align-items: center; gap: 0.5rem; padding: 0.875rem 1.75rem; font-size: 0.9375rem; font-weight: 600; border-radius: 0.75rem; text-decoration: none; transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1); position: relative; overflow: hidden; }
        .btn::before { content: ''; position: absolute; inset: 0; background: linear-gradient(135deg, rgba(255,255,255,0.2), transparent); transform: translateX(-100%); transition: transform 0.5s ease; }
        .btn:hover::before { transform: translateX(100%); }
        .btn-primary { background: linear-gradient(135deg, #2563eb 0%, #3b82f6 100%); color: white; box-shadow: 0 10px 25px -5px rgba(37, 99, 235, 0.3); }
        .btn-primary:hover { transform: translateY(-2px); box-shadow: 0 15px 35px -5px rgba(37, 99, 235, 0.4); }
        .btn-secondary { background: white; color: #1e293b; border: 1px solid #e2e8f0; box-shadow: 0 1px 3px rgba(0, 0, 0, 0.06); }
        .btn-secondary:hover { background: #f8fafc; border-color: #cbd5e1; transform: translateY(-2px); box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08); }
        .links { margin-top: 2.5rem; display: flex; gap: 2rem; justify-content: center; flex-wrap: wrap; animation: fadeIn 1s ease-out 0.9s both; }
        .link { color: #2563eb; text-decoration: none; font-size: 0.9375rem; font-weight: 500; transition: all 0.2s ease; position: relative; }
        .link::after { content: ''; position: absolute; bottom: -2px; left: 0; width: 0; height: 2px; background: #2563eb; transition: width 0.3s ease; }
        .link:hover { color: #1d4ed8; }
        .link:hover::after { width: 100%; }
        @media (max-width: 640px) { .code-display { font-size: 3rem; } .title { font-size: 1.5rem; } .subtitle { font-size: 1rem; } .tools-container { width: 120px; height: 120px; } }
    </style>
</head>
<body>
    <div class="particles">
        <div class="particle"></div>
        <div class="particle"></div>
        <div class="particle"></div>
        <div class="particle"></div>
        <div class="particle"></div>
    </div>
    <div class="container">
        <div class="error-illustration">
            <div class="tools-container">
                <svg class="gear gear-1" viewBox="0 0 24 24" fill="currentColor">
                    <path d="M12 15.5A3.5 3.5 0 0 1 8.5 12 3.5 3.5 0 0 1 12 8.5a3.5 3.5 0 0 1 3.5 3.5 3.5 3.5 0 0 1-3.5 3.5m7.43-2.53c.04-.32.07-.64.07-.97 0-.33-.03-.66-.07-1l2.11-1.63c.19-.15.24-.42.12-.64l-2-3.46c-.12-.22-.39-.31-.61-.22l-2.49 1c-.52-.39-1.06-.73-1.69-.98l-.37-2.65A.506.506 0 0 0 14 2h-4c-.25 0-.46.18-.5.42l-.37 2.65c-.63.25-1.17.59-1.69.98l-2.49-1c-.22-.09-.49 0-.61.22l-2 3.46c-.13.22-.07.49.12.64L4.57 11c-.04.34-.07.67-.07 1 0 .33.03.65.07.97l-2.11 1.66c-.19.15-.25.42-.12.64l2 3.46c.12.22.39.3.61.22l2.49-1.01c.52.4 1.06.74 1.69.99l.37 2.65c.04.24.25.42.5.42h4c.25 0 .46-.18.5-.42l.37-2.65c.63-.26 1.17-.59 1.69-.99l2.49 1.01c.22.08.49 0 .61-.22l2-3.46c.12-.22.07-.49-.12-.64l-2.11-1.66z"/>
                </svg>
                <svg class="gear gear-2" viewBox="0 0 24 24" fill="currentColor">
                    <path d="M12 15.5A3.5 3.5 0 0 1 8.5 12 3.5 3.5 0 0 1 12 8.5a3.5 3.5 0 0 1 3.5 3.5 3.5 3.5 0 0 1-3.5 3.5m7.43-2.53c.04-.32.07-.64.07-.97 0-.33-.03-.66-.07-1l2.11-1.63c.19-.15.24-.42.12-.64l-2-3.46c-.12-.22-.39-.31-.61-.22l-2.49 1c-.52-.39-1.06-.73-1.69-.98l-.37-2.65A.506.506 0 0 0 14 2h-4c-.25 0-.46.18-.5.42l-.37 2.65c-.63.25-1.17.59-1.69.98l-2.49-1c-.22-.09-.49 0-.61.22l-2 3.46c-.13.22-.07.49.12.64L4.57 11c-.04.34-.07.67-.07 1 0 .33.03.65.07.97l-2.11 1.66c-.19.15-.25.42-.12.64l2 3.46c.12.22.39.3.61.22l2.49-1.01c.52.4 1.06.74 1.69.99l.37 2.65c.04.24.25.42.5.42h4c.25 0 .46-.18.5-.42l.37-2.65c.63-.26 1.17-.59 1.69-.99l2.49 1.01c.22.08.49 0 .61-.22l2-3.46c.12-.22.07-.49-.12-.64l-2.11-1.66z"/>
                </svg>
                <svg class="gear gear-3" viewBox="0 0 24 24" fill="currentColor">
                    <path d="M12 15.5A3.5 3.5 0 0 1 8.5 12 3.5 3.5 0 0 1 12 8.5a3.5 3.5 0 0 1 3.5 3.5 3.5 3.5 0 0 1-3.5 3.5m7.43-2.53c.04-.32.07-.64.07-.97 0-.33-.03-.66-.07-1l2.11-1.63c.19-.15.24-.42.12-.64l-2-3.46c-.12-.22-.39-.31-.61-.22l-2.49 1c-.52-.39-1.06-.73-1.69-.98l-.37-2.65A.506.506 0 0 0 14 2h-4c-.25 0-.46.18-.5.42l-.37 2.65c-.63.25-1.17.59-1.69.98l-2.49-1c-.22-.09-.49 0-.61.22l-2 3.46c-.13.22-.07.49.12.64L4.57 11c-.04.34-.07.67-.07 1 0 .33.03.65.07.97l-2.11 1.66c-.19.15-.25.42-.12.64l2 3.46c.12.22.39.3.61.22l2.49-1.01c.52.4 1.06.74 1.69.99l.37 2.65c.04.24.25.42.5.42h4c.25 0 .46-.18.5-.42l.37-2.65c.63-.26 1.17-.59 1.69-.99l2.49 1.01c.22.08.49 0 .61-.22l2-3.46c.12-.22.07-.49-.12-.64l-2.11-1.66z"/>
                </svg>
                <svg class="wrench tool" viewBox="0 0 24 24" fill="currentColor">
                    <path d="M22.7 19l-9.1-9.1c.9-2.3.4-5-1.5-6.9-2-2-5-2.4-7.4-1.3L9 6 6 9 1.6 4.7C.4 7.1.9 10.1 2.9 12.1c1.9 1.9 4.6 2.4 6.9 1.5l9.1 9.1c.4.4 1 .4 1.4 0l2.3-2.3c.5-.4.5-1.1.1-1.4z"/>
                </svg>
            </div>
            <div class="code-display">500</div>
        </div>
        <h1 class="title">Ошибка сервера</h1>
        <p class="subtitle">Упс! Что-то пошло не так на нашей стороне<br>Мы уже работаем над исправлением</p>
        <div class="buttons">
            <a href="/" class="btn btn-primary">
                <svg width="18" height="18" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/></svg>
                На главную
            </a>
            <a href="javascript:history.back()" class="btn btn-secondary">
                <svg width="18" height="18" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                Обновить
            </a>
        </div>
        <div class="links">
            <a href="/login" class="link">Войти</a>
            <a href="mailto:support@sellermind.ai" class="link">Сообщить об ошибке</a>
        </div>
    </div>
</body>
</html>
