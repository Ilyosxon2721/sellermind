<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>500 — Ошибка сервера | SellerMind</title>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=inter:400,500,600,700,800&display=swap" rel="stylesheet" />
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
            background: linear-gradient(135deg, #fef2f2 0%, #fee2e2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 1rem;
        }
        @media (prefers-color-scheme: dark) {
            body { background: linear-gradient(135deg, #0f172a 0%, #1e293b 100%); }
            .card { background: #1e293b; border-color: #334155; }
            .title { color: #f8fafc; }
            .subtitle { color: #94a3b8; }
            .code { color: #f87171; }
            .link { color: #60a5fa; }
            .link:hover { color: #93c5fd; }
        }
        .container { text-align: center; max-width: 32rem; }
        .icon-wrapper {
            width: 6rem;
            height: 6rem;
            margin: 0 auto 1.5rem;
            background: linear-gradient(135deg, #fecaca 0%, #fee2e2 100%);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            animation: pulse 2s ease-in-out infinite;
        }
        @keyframes pulse {
            0%, 100% { transform: scale(1); opacity: 1; }
            50% { transform: scale(1.05); opacity: 0.9; }
        }
        .icon { width: 3rem; height: 3rem; color: #dc2626; }
        .code {
            font-size: 6rem;
            font-weight: 800;
            color: #dc2626;
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
            background: #dc2626;
            color: white;
        }
        .btn-primary:hover { background: #b91c1c; transform: translateY(-2px); }
        .btn-secondary {
            background: white;
            color: #374151;
            border: 1px solid #d1d5db;
        }
        .btn-secondary:hover { background: #f9fafb; border-color: #9ca3af; }
        .info {
            margin-top: 2rem;
            padding: 1rem;
            background: rgba(255,255,255,0.7);
            border-radius: 0.5rem;
            font-size: 0.875rem;
            color: #6b7280;
        }
        @media (prefers-color-scheme: dark) {
            .info { background: rgba(30, 41, 59, 0.7); color: #94a3b8; }
        }
        .links { margin-top: 1.5rem; display: flex; gap: 1.5rem; justify-content: center; flex-wrap: wrap; }
        .link { color: #2563eb; text-decoration: none; font-size: 0.875rem; }
        .link:hover { color: #1d4ed8; text-decoration: underline; }
    </style>
</head>
<body>
    <div class="container">
        <div class="icon-wrapper">
            <svg class="icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
            </svg>
        </div>
        <div class="code">500</div>
        <h1 class="title">Ошибка сервера</h1>
        <p class="subtitle">
            Что-то пошло не так на нашей стороне. Мы уже знаем о проблеме и работаем над её устранением.
        </p>
        <div class="buttons">
            <a href="javascript:location.reload()" class="btn btn-primary">
                <svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                </svg>
                Обновить страницу
            </a>
            <a href="/" class="btn btn-secondary">
                <svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/>
                </svg>
                На главную
            </a>
        </div>
        <div class="info">
            <p>Если проблема повторяется, пожалуйста, свяжитесь с нашей службой поддержки.</p>
        </div>
        <div class="links">
            <a href="mailto:support@sellermind.com" class="link">Написать в поддержку</a>
            <a href="https://t.me/sellermind_support" class="link">Telegram</a>
        </div>
    </div>
</body>
</html>
