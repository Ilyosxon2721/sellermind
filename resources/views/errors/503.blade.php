<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>503 — Техническое обслуживание | SellerMind</title>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=inter:400,500,600,700,800&display=swap" rel="stylesheet" />
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
            background: linear-gradient(135deg, #f0f9ff 0%, #e0f2fe 100%);
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
            .code { color: #38bdf8; }
            .info { background: rgba(30, 41, 59, 0.7); color: #94a3b8; }
        }
        .container { text-align: center; max-width: 32rem; }
        .icon-wrapper {
            width: 6rem;
            height: 6rem;
            margin: 0 auto 1.5rem;
            background: linear-gradient(135deg, #bae6fd 0%, #e0f2fe 100%);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            animation: spin 3s linear infinite;
        }
        @keyframes spin {
            from { transform: rotate(0deg); }
            to { transform: rotate(360deg); }
        }
        .icon { width: 3rem; height: 3rem; color: #0284c7; }
        .code {
            font-size: 6rem;
            font-weight: 800;
            color: #0284c7;
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
        .info {
            padding: 1rem;
            background: rgba(255,255,255,0.8);
            border-radius: 0.5rem;
            font-size: 0.875rem;
            color: #6b7280;
        }
        .btn {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.75rem 1.5rem;
            margin-top: 1.5rem;
            font-size: 0.875rem;
            font-weight: 500;
            border-radius: 0.5rem;
            text-decoration: none;
            background: #0284c7;
            color: white;
            transition: all 0.15s ease;
        }
        .btn:hover { background: #0369a1; transform: translateY(-2px); }
    </style>
</head>
<body>
    <div class="container">
        <div class="icon-wrapper">
            <svg class="icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/>
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
            </svg>
        </div>
        <div class="code">503</div>
        <h1 class="title">Техническое обслуживание</h1>
        <p class="subtitle">
            Мы проводим плановые работы для улучшения сервиса. Пожалуйста, вернитесь через несколько минут.
        </p>
        <div class="info">
            <p>Обычно обслуживание занимает не более 15 минут. Приносим извинения за неудобства.</p>
        </div>
        <a href="javascript:location.reload()" class="btn">
            <svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
            </svg>
            Проверить снова
        </a>
    </div>
</body>
</html>
