<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>403 — Доступ запрещён | SellerMind</title>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=inter:400,500,600,700,800&display=swap" rel="stylesheet" />
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
            background: linear-gradient(135deg, #fffbeb 0%, #fef3c7 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 1rem;
        }
        @media (prefers-color-scheme: dark) {
            body { background: linear-gradient(135deg, #0f172a 0%, #1e293b 100%); }
            .title { color: #f8fafc; }
            .subtitle { color: #94a3b8; }
            .code { color: #fbbf24; }
            .link { color: #60a5fa; }
            .link:hover { color: #93c5fd; }
        }
        .container { text-align: center; max-width: 32rem; }
        .icon-wrapper {
            width: 6rem;
            height: 6rem;
            margin: 0 auto 1.5rem;
            background: linear-gradient(135deg, #fde68a 0%, #fef3c7 100%);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            animation: shake 0.5s ease-in-out;
        }
        @keyframes shake {
            0%, 100% { transform: translateX(0); }
            25% { transform: translateX(-5px); }
            75% { transform: translateX(5px); }
        }
        .icon { width: 3rem; height: 3rem; color: #d97706; }
        .code {
            font-size: 6rem;
            font-weight: 800;
            color: #d97706;
            line-height: 1;
            margin-bottom: 0.5rem;
        }
        .title {
            font-size: 1.5rem;
            font-weight: 700;
            color: #1f2937;
            margin-bottom: 0.5rem;
        }
        .subtitle {
            color: #6b7280;
            margin-bottom: 2rem;
            line-height: 1.6;
        }
        .buttons { display: flex; flex-wrap: wrap; gap: 0.75rem; justify-content: center; }
        .btn {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.75rem 1.5rem;
            font-size: 0.875rem;
            font-weight: 500;
            border-radius: 0.5rem;
            text-decoration: none;
            transition: all 0.15s ease;
        }
        .btn-primary {
            background: #d97706;
            color: white;
        }
        .btn-primary:hover { background: #b45309; transform: translateY(-2px); }
        .btn-secondary {
            background: white;
            color: #374151;
            border: 1px solid #d1d5db;
        }
        .btn-secondary:hover { background: #f9fafb; border-color: #9ca3af; }
        .links { margin-top: 2rem; display: flex; gap: 1.5rem; justify-content: center; flex-wrap: wrap; }
        .link { color: #2563eb; text-decoration: none; font-size: 0.875rem; }
        .link:hover { color: #1d4ed8; text-decoration: underline; }
    </style>
</head>
<body>
    <div class="container">
        <div class="icon-wrapper">
            <svg class="icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
            </svg>
        </div>
        <div class="code">403</div>
        <h1 class="title">Доступ запрещён</h1>
        <p class="subtitle">
            У вас нет прав для просмотра этой страницы. Войдите в систему или обратитесь к администратору.
        </p>
        <div class="buttons">
            <a href="/login" class="btn btn-primary">
                <svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 16l-4-4m0 0l4-4m-4 4h14m-5 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h7a3 3 0 013 3v1"/>
                </svg>
                Войти
            </a>
            <a href="/" class="btn btn-secondary">
                <svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/>
                </svg>
                На главную
            </a>
        </div>
        <div class="links">
            <a href="mailto:support@sellermind.com" class="link">Поддержка</a>
        </div>
    </div>
</body>
</html>
