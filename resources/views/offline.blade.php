<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Offline | SellerMind AI</title>
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

        .particles {
            position: absolute;
            inset: 0;
            overflow: hidden;
            pointer-events: none;
        }

        .particle {
            position: absolute;
            background: radial-gradient(circle, #f59e0b 0%, transparent 70%);
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

        .error-illustration {
            position: relative;
            margin: 0 auto 2rem;
            width: 280px;
            height: 200px;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .wifi-icon {
            width: 160px;
            height: 160px;
            animation: pulse 2s ease-in-out infinite;
        }

        @keyframes pulse {
            0%, 100% { transform: scale(1); opacity: 1; }
            50% { transform: scale(1.05); opacity: 0.8; }
        }

        .wifi-slash {
            stroke: #ef4444;
            stroke-dasharray: 100;
            stroke-dashoffset: 100;
            animation: drawSlash 1s ease-out 0.5s forwards;
        }

        @keyframes drawSlash {
            to { stroke-dashoffset: 0; }
        }

        .wifi-wave {
            fill: none;
            stroke: #f59e0b;
            stroke-width: 4;
            stroke-linecap: round;
            animation: wave 2s ease-in-out infinite;
        }

        .wifi-wave:nth-child(1) { animation-delay: 0s; }
        .wifi-wave:nth-child(2) { animation-delay: 0.2s; }
        .wifi-wave:nth-child(3) { animation-delay: 0.4s; }

        @keyframes wave {
            0%, 100% { opacity: 0.3; }
            50% { opacity: 1; }
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

        .status-card {
            background: rgba(255, 255, 255, 0.05);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 1rem;
            padding: 1.5rem;
            margin-bottom: 2rem;
            backdrop-filter: blur(10px);
            animation: fadeIn 1s ease-out 0.6s both;
        }

        .status-item {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 0.75rem 0;
            border-bottom: 1px solid rgba(255, 255, 255, 0.05);
        }

        .status-item:last-child {
            border-bottom: none;
        }

        .status-label {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            color: #94a3b8;
        }

        .status-value {
            font-weight: 600;
            color: #f8fafc;
        }

        .status-value.pending {
            color: #f59e0b;
        }

        .status-value.offline {
            color: #ef4444;
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
            border: none;
            cursor: pointer;
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
            background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%);
            color: white;
            box-shadow: 0 10px 25px -5px rgba(245, 158, 11, 0.4);
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 15px 35px -5px rgba(245, 158, 11, 0.5);
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

        .spinner {
            width: 18px;
            height: 18px;
            border: 2px solid rgba(255, 255, 255, 0.3);
            border-top-color: white;
            border-radius: 50%;
            animation: spin 0.8s linear infinite;
            display: none;
        }

        .btn.loading .spinner {
            display: block;
        }

        .btn.loading .btn-text {
            display: none;
        }

        @keyframes spin {
            to { transform: rotate(360deg); }
        }

        .info {
            margin-top: 2.5rem;
            padding: 1rem;
            background: rgba(59, 130, 246, 0.1);
            border: 1px solid rgba(59, 130, 246, 0.2);
            border-radius: 0.75rem;
            animation: fadeIn 1s ease-out 0.9s both;
        }

        .info p {
            color: #93c5fd;
            font-size: 0.875rem;
            line-height: 1.6;
        }

        .info strong {
            color: #60a5fa;
        }

        @media (max-width: 640px) {
            .title { font-size: 1.5rem; }
            .subtitle { font-size: 1rem; }
            .error-illustration { width: 200px; height: 150px; }
            .wifi-icon { width: 120px; height: 120px; }
        }
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
            <svg class="wifi-icon" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                <path class="wifi-wave" d="M2.5 8.5C6.5 4.5 12 4 12 4s5.5.5 9.5 4.5" opacity="0.4"/>
                <path class="wifi-wave" d="M5 12c2.5-2.5 7-3 7-3s4.5.5 7 3" opacity="0.6"/>
                <path class="wifi-wave" d="M8 15.5c1.5-1.5 4-2 4-2s2.5.5 4 2" opacity="0.8"/>
                <circle cx="12" cy="19" r="2" fill="#f59e0b"/>
                <line class="wifi-slash" x1="4" y1="4" x2="20" y2="20" stroke-width="2.5" stroke-linecap="round"/>
            </svg>
        </div>

        <h1 class="title">Нет подключения к интернету</h1>
        <p class="subtitle">
            Проверьте соединение и попробуйте снова.<br>
            Ваши данные сохранены и будут синхронизированы автоматически.
        </p>

        <div class="status-card">
            <div class="status-item">
                <span class="status-label">
                    <svg width="20" height="20" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.111 16.404a5.5 5.5 0 017.778 0M12 20h.01m-7.08-7.071c3.904-3.905 10.236-3.905 14.141 0M1.394 9.393c5.857-5.857 15.355-5.857 21.213 0"/>
                    </svg>
                    Соединение
                </span>
                <span class="status-value offline" id="connection-status">Отсутствует</span>
            </div>
            <div class="status-item">
                <span class="status-label">
                    <svg width="20" height="20" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                    </svg>
                    Ожидающие действия
                </span>
                <span class="status-value pending" id="pending-count">0</span>
            </div>
            <div class="status-item">
                <span class="status-label">
                    <svg width="20" height="20" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 8h14M5 8a2 2 0 110-4h14a2 2 0 110 4M5 8v10a2 2 0 002 2h10a2 2 0 002-2V8m-9 4h4"/>
                    </svg>
                    Кэшированные данные
                </span>
                <span class="status-value" id="cache-status">Доступны</span>
            </div>
        </div>

        <div class="buttons">
            <button class="btn btn-primary" onclick="retryConnection()">
                <span class="spinner"></span>
                <span class="btn-text">
                    <svg width="18" height="18" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                    </svg>
                    Повторить
                </span>
            </button>
            <a href="/dashboard" class="btn btn-secondary">
                <svg width="18" height="18" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/>
                </svg>
                Дашборд (кэш)
            </a>
        </div>

        <div class="info">
            <p>
                <strong>SellerMind</strong> работает в офлайн режиме.
                Все изменения сохраняются локально и будут синхронизированы
                при восстановлении соединения.
            </p>
        </div>
    </div>

    <script nonce="{{ $cspNonce ?? '' }}">
        // Проверка соединения
        function updateConnectionStatus() {
            const statusEl = document.getElementById('connection-status');
            if (navigator.onLine) {
                statusEl.textContent = 'Есть';
                statusEl.classList.remove('offline');
                statusEl.classList.add('online');
                // Перенаправляем на предыдущую страницу или дашборд
                setTimeout(() => {
                    const returnUrl = sessionStorage.getItem('sm_return_url') || '/dashboard';
                    window.location.href = returnUrl;
                }, 1000);
            } else {
                statusEl.textContent = 'Отсутствует';
                statusEl.classList.add('offline');
                statusEl.classList.remove('online');
            }
        }

        // Получение количества ожидающих действий
        async function updatePendingCount() {
            const countEl = document.getElementById('pending-count');
            try {
                if (window.SmBackgroundSync) {
                    const count = await window.SmBackgroundSync.getPendingCount();
                    countEl.textContent = count;
                }
            } catch (e) {
                countEl.textContent = '—';
            }
        }

        // Повторная попытка подключения
        function retryConnection() {
            const btn = event.target.closest('.btn');
            btn.classList.add('loading');

            // Пробуем загрузить страницу
            fetch('/api/health', { method: 'HEAD' })
                .then(() => {
                    window.location.reload();
                })
                .catch(() => {
                    btn.classList.remove('loading');
                    updateConnectionStatus();
                });
        }

        // Слушаем изменения соединения
        window.addEventListener('online', updateConnectionStatus);
        window.addEventListener('offline', updateConnectionStatus);

        // Инициализация
        updateConnectionStatus();
        updatePendingCount();

        // Сохраняем текущий URL для возврата
        if (document.referrer && !document.referrer.includes('/offline')) {
            sessionStorage.setItem('sm_return_url', document.referrer);
        }
    </script>
</body>
</html>
