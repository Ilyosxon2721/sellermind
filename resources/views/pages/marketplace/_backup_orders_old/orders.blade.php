@extends('layouts.app')

@section('content')
<style>
    [x-cloak] { display: none !important; }
    .animate-pulse {
        animation: pulse 2s cubic-bezier(0.4, 0, 0.6, 1) infinite;
    }
    @keyframes pulse {
        0%, 100% { opacity: 1; }
        50% { opacity: 0.5; }
    }
</style>
<div x-data="{
         orders: [],
         stats: null,
         loading: true,
         selectedOrder: null,
         showOrderModal: false,
         showRaw: false,
         activeTab: 'new',
         deliveryTypeFilter: 'all', // all by default, можно сузить до fbs/dbs/edbs
         statusFilter: '',
         loadImages: true,
         dateFrom: '',
         dateTo: '',
         searchQuery: '',
         expandedSupplies: {},
         wsSubsKey: null,
        syncInProgress: false,
        syncProgress: 0,
        syncMessage: '',
        syncStatus: '',
        lastSyncSignature: null,
        liveMonitoringEnabled: false,
        liveMonitoringActive: false,
        lastDataChange: null,
        dataChangeNotifications: [],
        wsConnectedFlag: false,
        _wsDebounceTimer: null,
        _lastWsEventTime: 0,
@php
$__uzumShopsJson = ($uzumShops ?? collect())
    ->map(fn($s) => [
        'id' => (string)($s->external_id ?? ''),
        'name' => $s->name ?? ('Shop ' . $s->external_id),
    ])
    ->values();
@endphp
         uzumShops: @js($__uzumShopsJson ?? []),
        selectedShopIds: [],
        shopAccordionOpen: false,
         // Supplies management
         supplies: [],
         openSupplies: [],
         showCreateSupplyModal: false,
         showAddToSupplyModal: false,
         showSupplyModal: false,
         selectedOrderForSupply: null,
         selectedSupply: null,
         supplyOrders: [],
         supplyToDeliver: null,
         showDeliverSupplyModal: false,
         newSupply: { name: '', description: '' },
         selectedSupplyId: null,
         suppliesLoading: false,
         fetchingNewOrders: false,
         cancelingOrder: null,
         deliveringSupply: false,
         deletingSupplyId: null,
         removingOrderFromSupplyId: null,
         confirmingOrderId: null,
         showCancelModal: false,
         orderToCancel: null,
         // FBO orders (Склад WB)
         fboOrders: [],
         fboStats: { total: 0, by_status: {} },
         fboLoading: false,
         accountId: {{ $accountId }},
         accountMarketplace: '{{ $accountMarketplace ?? 'wb' }}',
         accountName: '{{ addslashes($accountName ?? '') }}',
         defaultCurrency: '{{ ($accountMarketplace ?? 'wb') === 'uzum' ? 'UZS' : 'RUB' }}',
         isWb() {
             return this.accountMarketplace === 'wb';
         },
         isUzum() {
             return this.accountMarketplace === 'uzum';
        },
        uzumItems(order) {
            return order?.raw_payload?.orderItems || [];
        },
        shopOptions() {
            if (!this.isUzum()) return [];
            const opts = Array.isArray(this.uzumShops)
                ? this.uzumShops
                : Object.entries(this.uzumShops || {}).map(([id, name]) => ({ id, name }));
            return opts
                .filter(opt => opt && opt.id)
                .sort((a, b) => a.name.localeCompare(b.name));
        },
        shopLabel() {
            if (!this.selectedShopIds || this.selectedShopIds.length === 0) return 'Все магазины';
            const names = this.selectedShopIds
                .map(id => {
                    const found = this.shopOptions().find(o => o.id == id);
                    return found?.name || `Shop ${id}`;
                });
            return names.slice(0, 2).join(', ') + (names.length > 2 ? ` и ещё ${names.length - 2}` : '');
        },
        resetShopFilter() {
            this.selectedShopIds = [];
            this.loadOrders();
            this.loadStats();
        },
        async loadUzumShops() {
            if (!this.isUzum()) return;
            // Если магазины уже переданы сервером — используем их без запроса
            if (Array.isArray(this.uzumShops) && this.uzumShops.length > 0) {
                return;
            }
            try {
                const res = await fetch(`/api/marketplace/uzum/accounts/${this.accountId}/shops`, {
                    headers: this.getAuthHeaders()
                });
                if (res.ok) {
                    const data = await res.json();
                    const list = (data.shops || [])
                        .map(shop => ({
                            id: (shop.id || shop.external_id || '').toString(),
                            name: shop.name || `Shop ${shop.id || shop.external_id || ''}`,
                        }))
                        .filter(s => s.id);
                    this.uzumShops = list;
                    if (list.length === 0) {
                        console.warn('Uzum shops response empty');
                    }
                } else {
                    console.error('Failed to load Uzum shops', res.status, await res.text());
                }
            } catch (e) {
                console.error('Failed to load Uzum shops', e);
            }
        },
        getShopName(order) {
            if (!order) return '—';
            if (this.accountMarketplace === 'uzum') {
                const sid = order.raw_payload?.shopId || order.raw_payload?.shop_id;
                const opt = this.shopOptions().find(o => o.id == sid);
                if (opt?.name) return opt.name;
                return order.raw_payload?.shopName || (sid ? 'Shop ' + sid : '—');
            }
            return order.raw_payload?.stock?.title
                || order.raw_payload?.stock?.externalId
                || order.raw_payload?.shopName
                || order.raw_payload?.shopId
                || '—';
        },
        formatUzumDisplay(value) {
            // Строгое отображение в UTC+5, независимо от браузера/VPN
            const pad = (n) => n.toString().padStart(2, '0');
            const toMs = (val) => {
                if (val === null || val === undefined || val === '') return null;
                if (typeof val === 'number' || (typeof val === 'string' && /^\d+$/.test(val))) {
                    const num = Number(val);
                    return num > 1e12 ? num : num * 1000;
                }
                if (typeof val === 'string') {
                    // убираем дробные секунды
                    let s = val.replace(/\.\d+(Z)?$/, '$1');
                    if (s.includes('T') && !s.endsWith('Z')) s += 'Z';
                    if (/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}(\.\d+)?$/.test(s)) {
                        s = s.replace(' ', 'T');
                        if (!s.endsWith('Z')) s += 'Z';
                    }
                    const d = Date.parse(s);
                    return isNaN(d) ? null : d;
                }
                return null;
            };

            const ms = toMs(value);
            if (ms === null) return typeof value === 'string' ? value : '-';

            const ts = ms + 5 * 60 * 60 * 1000; // UTC+5
            const d = new Date(ts);
            return `${pad(d.getUTCDate())}.${pad(d.getUTCMonth() + 1)}.${d.getUTCFullYear()}, ${pad(d.getUTCHours())}:${pad(d.getUTCMinutes())}`;
        },
        parseUzumDate(value) {
            if (value === null || value === undefined || value === '') return null;
            if (typeof value === 'number' || (typeof value === 'string' && /^\d+$/.test(value))) {
                let num = Number(value);
                // Узум иногда отдаёт 14-значные штампы — обрезаем до 13 для ms
                if (value.toString().length > 13) {
                    num = Number(value.toString().slice(0, 13));
                }
                const ms = num > 1e12 ? num : num * 1000;
                const d = new Date(ms);
                return isNaN(d.getTime()) ? null : d;
            }
            if (typeof value === 'string') {
                const clean = value.replace(/\.\d+Z?$/, '');
                if (/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}$/.test(clean)) {
                    const iso = clean.replace(' ', 'T') + 'Z';
                    const d = new Date(iso);
                    return isNaN(d.getTime()) ? null : d;
                }
                const d = new Date(clean);
                return isNaN(d.getTime()) ? null : d;
            }
            const d = new Date(value);
            return isNaN(d.getTime()) ? null : d;
        },
        formatUzumDateShort(value) {
            const d = this.parseUzumDate(value);
            if (!d) return typeof value === 'string' ? value : '-';
            return d.toLocaleString('ru-RU', {
                year: 'numeric',
                month: '2-digit',
                day: '2-digit',
                hour: '2-digit',
                minute: '2-digit',
                timeZone: 'Asia/Tashkent'
            });
        },
         uzumItemImage(item) {
             // ищем первую доступную картинку из productImage.photo.* или photo.photo.*
             const photo = item?.productImage?.photo || item?.photo?.photo || item?.photo;
             if (!photo) return null;
             // photo может быть объект {high, low} или словарь размеров
             const candidates = [
                 photo['high'],
                 photo['800']?.high,
                 photo['600']?.high,
                 photo['400']?.high,
                 photo['low'],
             ].filter(Boolean);
             return candidates.length ? candidates[0] : null;
         },
        formatUzumDate(value) {
            const d = this.parseUzumDate(value);
            if (!d) return typeof value === 'string' ? value : '-';
            return d.toLocaleString('ru-RU', {
                year: 'numeric',
                month: '2-digit',
                day: '2-digit',
                hour: '2-digit',
                minute: '2-digit',
                timeZone: 'Asia/Tashkent'
            });
        },
        timeLeft(value) {
            // рассчитываем от UTC+5 чтобы совпадало с отображаемым временем
            const target = this.parseUzumDate(value);
            if (!target) return '';
            const shifted = new Date(target.getTime() + 5 * 60 * 60 * 1000);
            const diffMs = shifted.getTime() - Date.now();
            if (diffMs <= 0) return 'истекло';
            const totalMinutes = Math.floor(diffMs / 60000);
            const hours = Math.floor(totalMinutes / 60);
            const minutes = totalMinutes % 60;
            return `${hours} ч ${minutes} мин`;
         },
         tabLabel(tab) {
             if (this.accountMarketplace === 'uzum') {
                 const map = {
                     'new': 'Новые',
                     'in_assembly': 'В сборке',
                     'in_supply': 'В поставке',
                     'accepted_uzum': 'Приняты Uzum',
                     'waiting_pickup': 'Ждут выдачи',
                     'issued': 'Выданы',
                     'cancelled': 'Отменены',
                     'returns': 'Возвраты'
                 };
                 return map[tab] || tab;
             }
             const map = {
                 'new': 'Новые',
                 'in_assembly': 'На сборке',
                 'in_delivery': 'В доставке',
                 'completed': 'Архив',
                 'cancelled': 'Отменённые'
             };
             return map[tab] || tab;
         },
         // Tares (Boxes) management
         tares: [],
         showCreateTareModal: false,
         showTareModal: false,
         selectedTare: null,
         selectedSupplyForTare: null,
         newTare: { barcode: '', external_tare_id: '' },
         taresLoading: false,
         getCurrentAccount() {
             const list = this.$store.auth.marketplaceAccounts || [];
             return list.find(acc => acc.id === this.accountId) || null;
         },
         getAccountLabel() {
             const acc = this.getCurrentAccount();
             if (acc && acc.name) return acc.name;
             if (acc && acc.marketplace) {
                 const map = { wb: 'Wildberries', uzum: 'Uzum' };
                 return map[acc.marketplace] || acc.marketplace;
             }
             if (this.accountName) return this.accountName;
             const map = { wb: 'Wildberries', uzum: 'Uzum' };
             return map[this.accountMarketplace] || this.accountMarketplace || '';
         },
         getToken() {
             if (this.$store.auth.token) return this.$store.auth.token;
             const persistToken = localStorage.getItem('_x_auth_token');
             if (persistToken) {
                 try { return JSON.parse(persistToken); } catch (e) { return persistToken; }
             }
             return localStorage.getItem('auth_token') || localStorage.getItem('token');
         },
         getAuthHeaders() {
             return {
                 'Authorization': 'Bearer ' + this.getToken(),
                 'Accept': 'application/json'
            };
         },
         async init() {
             await this.$nextTick();
             if (!this.getToken()) {
                 window.location.href = '/login';
                 return;
             }
             // Set default date range (last 30 days) - учитываем часовой пояс Ташкента
             const today = new Date();
             const monthAgo = new Date(today.getTime() - 30 * 24 * 60 * 60 * 1000);
             // Используем toLocaleDateString для получения локальной даты в формате YYYY-MM-DD
             this.dateTo = today.toLocaleDateString('en-CA'); // 'en-CA' даёт формат YYYY-MM-DD
             this.dateFrom = monthAgo.toLocaleDateString('en-CA');
             // Для Uzum убираем дефолтный диапазон, чтобы видеть все заказы (без отсечения по дате)
             if (this.accountMarketplace === 'uzum') {
                 this.dateFrom = '';
                 this.dateTo = '';
             }

            // Загружаем данные параллельно для ускорения
            await Promise.all([
                this.loadOrders(),
                this.loadStats(),
                this.loadOpenSupplies(),
                this.loadUzumShops()
            ]);

             // Sync websocket indicator with global events
            window.addEventListener('websocket:connected', () => { this.wsConnectedFlag = true; });
            window.addEventListener('websocket:disconnected', () => { this.wsConnectedFlag = false; });
            const wsState = window.getWebSocketState ? window.getWebSocketState() : null;
            this.wsConnectedFlag = wsState && typeof wsState.connected !== 'undefined'
                ? wsState.connected
                : false;

             // Отложенная инициализация WebSocket (после рендера основного контента)
             // WebSocket теперь включен для всех маркетплейсов (включая Uzum)
             setTimeout(() => {
                 this.setupWebSocketListeners();
             }, 1000);
         },
         setupWebSocketListeners() {
             const companyId = this.$store.auth.currentCompany.id;
             // Поддержка нескольких аккаунтов: берём все аккаунты компании из стора (wb + uzum и др.)
             const accounts = (this.$store.auth.marketplaceAccounts || [])
                 .filter(acc => acc.company_id === companyId)
                 .map(acc => acc.id);
             // fallback: используем одиночный accountId из blade
             if (accounts.length === 0) {
                 accounts.push({{ $accountId }});
             }

            // Подписываемся на каналы через глобальный WebSocket
            const subscribeToChannels = () => {
                // Подписка на канал компании для обновлений заказов
                window.subscribeToChannel('company.' + companyId);

                accounts.forEach(accountId => {
                    const key = companyId + '-' + accountId;
                    if (window.__wbSubs && window.__wbSubs[key]) return;

                    // Старые каналы для обратной совместимости
                    window.subscribeToChannel('company.' + companyId + '.marketplace.' + accountId + '.orders');
                    window.subscribeToChannel('company.' + companyId + '.marketplace.' + accountId + '.sync');
                    window.subscribeToChannel('company.' + companyId + '.marketplace.' + accountId + '.data');

                    // Новый канал для обновлений заказов конкретного аккаунта
                    window.subscribeToChannel('marketplace-account.' + accountId);

                    if (!window.__wbSubs) window.__wbSubs = {};
                    window.__wbSubs[key] = true;
                });
            };

            // Подписываемся сразу если WebSocket уже подключен
            const wsState = window.getWebSocketState();
            if (wsState && wsState.connected) {
                subscribeToChannels();
            }

            // Слушаем событие подключения WebSocket (но проверяем дубликаты)
            window.addEventListener('websocket:connected', subscribeToChannels, { once: true });

             // Обрабатываем входящие сообщения
             window.addEventListener('websocket:message', (e) => {
                 const { channel, event, data } = e.detail;

                // Обрабатываем событие обновления заказов с дебаунсингом
                if (event === 'orders.updated' || (event === 'data.changed' && data && data.data_type === 'orders')) {
                    // Дебаунсинг: игнорируем события чаще чем раз в 3 секунды
                    const now = Date.now();
                    if (now - this._lastWsEventTime < 3000) {
                        return; // Игнорируем частые события
                    }

                    // Отменяем предыдущий таймер если есть
                    if (this._wsDebounceTimer) {
                        clearTimeout(this._wsDebounceTimer);
                    }

                    // Устанавливаем новый таймер с задержкой 500мс
                    this._wsDebounceTimer = setTimeout(() => {
                        this._lastWsEventTime = Date.now();

                        const newOrdersCount = (data && typeof data.new_orders_count !== 'undefined')
                            ? data.new_orders_count
                            : (data && data.metadata && typeof data.metadata.new_orders_count !== 'undefined'
                                ? data.metadata.new_orders_count
                                : (data && typeof data.affected_count !== 'undefined' ? data.affected_count : 0));

                        // Обновляем только если есть изменения
                        if (newOrdersCount > 0 || (data && data.change_type === 'updated')) {
                            this.loadOrders(true);
                            this.loadStats();
                            if (newOrdersCount > 0) {
                                this.showNotification('Получено ' + newOrdersCount + ' новых заказов');
                            }
                        }
                    }, 500);
                }

                 // Обрабатываем событие прогресса синхронизации
                if (event === 'sync.progress') {
                    const sig = `${data.status}|${data.progress}|${data.message || ''}`;
                    if (this.lastSyncSignature === sig) {
                        return;
                    }
                    this.lastSyncSignature = sig;

                    this.syncStatus = data.status;
                    this.syncMessage = data.message;
                    this.syncProgress = data.progress || 0;

                    if (data.status === 'started' || data.status === 'progress') {
                        this.syncInProgress = true;
                    } else if (data.status === 'completed') {
                        this.syncInProgress = false;
                        this.syncProgress = 100;
                        this.lastSyncSignature = null;

                        const created = data.data?.created || 0;
                        const updated = data.data?.updated || 0;
                        // Перезагружаем данные только если были изменения
                        if (created > 0 || updated > 0) {
                            this.loadOrders(true);
                            this.loadStats();
                            this.loadOpenSupplies();
                        }

                        // Показываем уведомление об успехе
                        if (data.data && typeof data.data.deleted !== 'undefined' && data.data.deleted > 0) {
                            this.showNotification('Синхронизация завершена. Удалено: ' + data.data.deleted + ', создано: ' + (data.data.created || 0) + ', обновлено: ' + (data.data.updated || 0));
                        } else {
                            this.showNotification('Синхронизация завершена успешно');
                        }
                    } else if (data.status === 'error') {
                        this.syncInProgress = false;
                        this.syncProgress = 0;
                        this.lastSyncSignature = null;
                        this.showNotification('Ошибка синхронизации: ' + data.message);
                    }
                }

                 // Обрабатываем событие изменения данных (live monitoring)
                if (event === 'data.changed') {
                    this.lastDataChange = data;
                    this.liveMonitoringActive = true;

                     // Добавляем уведомление о изменении
                    this.dataChangeNotifications.push({
                        id: Date.now(),
                        type: data.data_type,
                        changeType: data.change_type,
                        count: data.affected_count,
                        timestamp: data.timestamp,
                    });

                     // Удаляем старые уведомления (храним последние 5)
                     if (this.dataChangeNotifications.length > 5) {
                         this.dataChangeNotifications.shift();
                     }

                     // Автоматически обновляем данные при изменениях
                     if (data.data_type === 'orders') {
                         this.loadOrders();
                         this.loadStats();

                         const message = data.change_type === 'created'
                             ? 'Добавлено ' + data.affected_count + ' новых заказов'
                             : 'Обновлено ' + data.affected_count + ' заказов';

                         this.showNotification(message);
                     }
                 }

                // Обрабатываем событие обновления заказа Узум
                if (event === 'uzum.order.updated') {
                    // Проверяем, что это наш аккаунт
                    if (data.marketplace_account_id === {{ $accountId }}) {
                        // Обновляем список заказов и статистику
                        this.loadOrders(true); // silent mode
                        this.loadStats();

                        // Показываем уведомление
                        const actionText = data.action === 'created'
                            ? 'Новый заказ'
                            : (data.action === 'updated' ? 'Заказ обновлен' : 'Заказ удален');

                        this.showNotification(actionText + ': #' + data.external_order_id);
                    }
                }
             });
         },
         showNotification(message) {
             // Простое уведомление (можно заменить на toast notification)
             if ('Notification' in window && Notification.permission === 'granted') {
                 new Notification('SellerMind AI', {
                     body: message,
                     icon: '/favicon.ico'
                 });
             }
         },
        _loadOrdersInProgress: false,
        _loadOrdersRetryCount: 0,
        async loadOrders(silent = false) {
            // Prevent concurrent requests
            if (this._loadOrdersInProgress) {
                return;
            }
            this._loadOrdersInProgress = true;
            if (!silent) this.loading = true;

            try {
                let url = '/api/marketplace/orders?company_id=' + this.$store.auth.currentCompany.id + '&marketplace_account_id={{ $accountId }}';
                // Всегда грузим все статусы/даты, фильтруем на клиенте
                if (this.dateFrom) url += '&from=' + this.dateFrom;
                if (this.dateTo) url += '&to=' + this.dateTo;
                if (this.accountMarketplace === 'uzum' && this.selectedShopIds.length > 0) url += '&shop_id=' + this.selectedShopIds.join(',');

                const res = await fetch(url, { headers: this.getAuthHeaders() });
                if (res.ok) {
                    const data = await res.json();
                    this.orders = (data.orders || []).map(o => {
                        // Для Uzum используем фактическую дату из API без преобразований
                        if (this.accountMarketplace === 'uzum' && o.raw_payload?.dateCreated) {
                            o.ordered_at = o.raw_payload.dateCreated;
                        }
                        return o;
                    });
                    this._loadOrdersRetryCount = 0; // Reset retry count on success
                } else if (res.status === 401) {
                    window.location.href = '/login';
                } else if (res.status === 429) {
                    // Rate limited - wait and don't retry immediately
                    console.warn('Rate limited (429), waiting before retry...');
                    this._loadOrdersRetryCount++;
                    if (this._loadOrdersRetryCount < 3) {
                        await new Promise(r => setTimeout(r, 5000 * this._loadOrdersRetryCount)); // Wait 5s, 10s, 15s
                    }
                } else {
                    console.error('loadOrders failed with status:', res.status);
                }
            } catch (error) {
                console.error('loadOrders error:', error);
            } finally {
                this._loadOrdersInProgress = false;
                if (!silent) this.loading = false;
            }
        },
        _loadStatsInProgress: false,
        async loadStats() {
            // Prevent concurrent requests
            if (this._loadStatsInProgress) {
                return;
            }
            this._loadStatsInProgress = true;

            try {
                let url = '/api/marketplace/orders/stats?company_id=' + this.$store.auth.currentCompany.id + '&marketplace_account_id={{ $accountId }}';
                if (this.dateFrom) url += '&from=' + this.dateFrom;
                if (this.dateTo) url += '&to=' + this.dateTo;
                if (this.accountMarketplace === 'uzum' && this.selectedShopIds.length > 0) url += '&shop_id=' + this.selectedShopIds.join(',');

                const res = await fetch(url, { headers: this.getAuthHeaders() });
                if (res.ok) {
                    this.stats = await res.json();
                } else if (res.status === 429) {
                    console.warn('loadStats rate limited (429)');
                }
            } catch (error) {
                console.error('loadStats error:', error);
            } finally {
                this._loadStatsInProgress = false;
            }
         },
         // ========== Tares (Boxes) Management ==========
         async loadTares(supply) {
             if (!supply || !supply.id) return [];
             this.taresLoading = true;
             try {
                 const response = await axios.get(`/api/marketplace/supplies/${supply.id}/tares`, {
                     headers: this.getAuthHeaders()
                 });
                 return response.data.tares || [];
             } catch (error) {
                 console.error('Error loading tares:', error);
                 return [];
             } finally {
                 this.taresLoading = false;
             }
         },
         openCreateTareModal(supply) {
             this.selectedSupplyForTare = supply;
             this.newTare = { barcode: '', external_tare_id: '' };
             this.showCreateTareModal = true;
         },
        async createTare() {
            if (!this.selectedSupplyForTare) {
                alert('Поставка не выбрана');
                return;
            }

            this.taresLoading = true;
            try {
                // Не передаем данные - WB API создаст короб автоматически
                const response = await axios.post(`/api/marketplace/supplies/${this.selectedSupplyForTare.id}/tares`, {}, {
                    headers: this.getAuthHeaders()
                });

                this.showNotification('Короб успешно создан с ID: ' + (response.data.tare?.external_tare_id || 'N/A'));
                this.showCreateTareModal = false;

                // Reload tares for this supply
                const tares = await this.loadTares(this.selectedSupplyForTare);
                this.tares = tares;
            } catch (error) {
                console.error('Error creating tare:', error);
                alert(error.response?.data?.message || 'Ошибка при создании короба');
            } finally {
                this.taresLoading = false;
            }
        },
         async openTareModal(tare, supply) {
             this.selectedTare = tare;
             this.selectedSupplyForTare = supply;
             this.showTareModal = true;

             // Load full tare details with orders
             try {
                 const response = await axios.get(`/api/marketplace/tares/${tare.id}`, {
                     headers: this.getAuthHeaders()
                 });
                 this.selectedTare = response.data.tare;
             } catch (error) {
                 console.error('Error loading tare details:', error);
             }
         },
         async addOrderToTare(orderId) {
             if (!this.selectedTare) return;

             this.taresLoading = true;
             try {
                 const response = await axios.post(`/api/marketplace/tares/${this.selectedTare.id}/orders`, {
                     order_id: orderId
                 }, {
                     headers: this.getAuthHeaders()
                 });

                 this.selectedTare = response.data.tare;
                 await this.loadOrders(); // Reload orders to update tare_id

                 alert('Заказ добавлен в коробку!');
             } catch (error) {
                 console.error('Error adding order to tare:', error);
                 alert(error.response?.data?.message || 'Ошибка при добавлении заказа в коробку');
             } finally {
                 this.taresLoading = false;
             }
         },
         async removeOrderFromTare(orderId) {
             if (!this.selectedTare) return;
             if (!confirm('Убрать заказ из коробки?')) return;

             this.taresLoading = true;
             try {
                 const response = await axios.delete(`/api/marketplace/tares/${this.selectedTare.id}/orders`, {
                     headers: this.getAuthHeaders(),
                     data: { order_id: orderId }
                 });

                 this.selectedTare = response.data.tare;
                 await this.loadOrders(); // Reload orders to update tare_id

                 alert('Заказ удалён из коробки!');
             } catch (error) {
                 console.error('Error removing order from tare:', error);
                 alert(error.response?.data?.message || 'Ошибка при удалении заказа из коробки');
             } finally {
                 this.taresLoading = false;
             }
         },
         async deleteTare(tare) {
             if (!confirm('Удалить коробку? Заказы будут откреплены от коробки.')) return;

             this.taresLoading = true;
             try {
                 await axios.delete(`/api/marketplace/tares/${tare.id}`, {
                     headers: this.getAuthHeaders()
                 });

                 this.showTareModal = false;
                 this.selectedTare = null;

                 // Reload tares
                 if (this.selectedSupplyForTare) {
                     this.tares = await this.loadTares(this.selectedSupplyForTare);
                 }

                 await this.loadOrders(); // Reload orders to update tare_id

                 alert('Коробка успешно удалена!');
             } catch (error) {
                 console.error('Error deleting tare:', error);
                 alert(error.response?.data?.message || 'Ошибка при удалении коробки');
             } finally {
                 this.taresLoading = false;
             }
         },
        async viewOrder(order) {
            if (!order) return;
            try {
                const res = await fetch(`/api/marketplace/orders/${order.id}`, {
                    headers: this.getAuthHeaders()
                });
                if (res.ok) {
                    const data = await res.json();
                    this.selectedOrder = data.order || order;
                } else {
                    this.selectedOrder = order;
                }
            } catch (e) {
                console.error('Failed to load order details', e);
                this.selectedOrder = order;
            }
            this.showOrderModal = true;
            this.showRaw = false;
        },
         formatMoney(amount, currency = null) {
             const cur = currency || this.defaultCurrency || 'RUB';
             return new Intl.NumberFormat('ru-RU', {
                 style: 'currency',
                 currency: cur
             }).format(amount || 0);
         },
         formatPrice(kopecks) {
             // WB цены приходят в копейках
             return this.formatMoney((kopecks || 0) / 100);
         },
         formatDateTime(dateString) {
             if (!dateString) return '-';
             return new Date(dateString).toLocaleString('ru-RU', {
                 year: 'numeric',
                 month: '2-digit',
                 day: '2-digit',
                 hour: '2-digit',
                 minute: '2-digit',
                 timeZone: 'Asia/Tashkent' // UTC+5 (Tashkent)
             });
         },
         getDeliveryTypeName(type) {
             const types = {
                 'fbs': 'FBS (со склада продавца)',
                 'fbo': 'FBO (со склада WB)',
                 'dbs': 'DBS (доставка продавцом)',
                 'edbs': 'eDBS (экспресс доставка)'
             };
             return types[type] || type;
         },
         getStatusText(status) {
             const statuses = {
                 'draft': 'Черновик',
                 'in_assembly': 'На сборке',
                 'ready': 'Готова',
                 'sent': 'Отправлена',
                 'delivered': 'Доставлена',
                 'cancelled': 'Отменена'
             };
             return statuses[status] || status;
         },
         getWbProductImageUrl(nmId, size = 'tm', basketOverride = null) {
             // WB CDN pattern: https://basket-{XX}.wbbasket.ru/vol{VOL}/part{PART}/{nmId}/images/{size}/1.jpg
             // Sizes: tm (thumbnail ~196x260), c246x328, c516x688, big
             if (!nmId) return this.getProductPlaceholder();

             const vol = Math.floor(nmId / 100000);
             const part = Math.floor(nmId / 1000);

             // Самый стабильный вариант: берем последнюю цифру nmId и смещаем на +1 (без basket-00)
             const basket = basketOverride ?? ((nmId % 10) + 1);
             const basketStr = basket.toString().padStart(2, '0');

             return `https://basket-${basketStr}.wbbasket.ru/vol${vol}/part${part}/${nmId}/images/${size}/1.jpg`;
         },
         getProductPlaceholder() {
             // SVG placeholder для товара без изображения
             return 'data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iMTAwIiBoZWlnaHQ9IjEwMCIgeG1sbnM9Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvc3ZnIj48cmVjdCB3aWR0aD0iMTAwIiBoZWlnaHQ9IjEwMCIgZmlsbD0iI2YzZjRmNiIvPjx0ZXh0IHg9IjUwJSIgeT0iNTAlIiBmb250LWZhbWlseT0iQXJpYWwiIGZvbnQtc2l6ZT0iMTQiIGZpbGw9IiM5Y2EzYWYiIHRleHQtYW5jaG9yPSJtaWRkbGUiIGR5PSIuM2VtIj5GT1RPPC90ZXh0Pjwvc3ZnPg==';
         },
         handleImageError(event) {
             const img = event.target;
             const nmId = Number(img.dataset.nmid);
             const size = img.dataset.size || 'tm';

             // Попробуем до 3 разных корзин, затем покажем плейсхолдер
             const attempt = Number(img.dataset.basketAttempt || 0);
             if (nmId && attempt < 3) {
                 const nextBasket = ((nmId % 10) + 1 + attempt) % 10 + 1; // смена корзины
                 img.dataset.basketAttempt = attempt + 1;
                 img.src = this.getWbProductImageUrl(nmId, size, nextBasket);
                 return;
             }

             img.src = this.getProductPlaceholder();
             img.style.display = '';
             img.onerror = null;
         },
         toggleSupply(supplyId) {
             this.expandedSupplies[supplyId] = !this.expandedSupplies[supplyId];
         },
        isSupplyExpanded(supplyId) {
            return this.expandedSupplies[supplyId] || false;
        },
        // Quick date filters - используем локальное время
        setToday() {
            const today = new Date().toLocaleDateString('en-CA');
            this.dateFrom = today;
            this.dateTo = today;
            this.loadOrders();
            this.loadStats();
        },
        setYesterday() {
            const yesterday = new Date();
            yesterday.setDate(yesterday.getDate() - 1);
            const yesterdayStr = yesterday.toLocaleDateString('en-CA');
            this.dateFrom = yesterdayStr;
            this.dateTo = yesterdayStr;
            this.loadOrders();
            this.loadStats();
        },
        setLastWeek() {
            const today = new Date().toLocaleDateString('en-CA');
            const weekAgo = new Date();
            weekAgo.setDate(weekAgo.getDate() - 7);
            this.dateFrom = weekAgo.toLocaleDateString('en-CA');
            this.dateTo = today;
            this.loadOrders();
            this.loadStats();
        },
        setLastMonth() {
            const today = new Date().toLocaleDateString('en-CA');
            const monthAgo = new Date();
            monthAgo.setDate(monthAgo.getDate() - 30);
            this.dateFrom = monthAgo.toLocaleDateString('en-CA');
            this.dateTo = today;
            this.loadOrders();
            this.loadStats();
        },
        // Supplies Management
        async loadSupplies() {
            try {
                const response = await axios.get('/api/marketplace/supplies', {
                    headers: this.getAuthHeaders(),
                    params: {
                        company_id: this.$store.auth.currentCompany.id,
                        marketplace_account_id: {{ $accountId }}
                    }
                });
                this.supplies = response.data.supplies || [];
            } catch (error) {
                console.error('Error loading supplies:', error);
            }
        },
        async loadOpenSupplies() {
            try {
                // Загружаем ВСЕ поставки, а не только открытые
                const response = await axios.get('/api/marketplace/supplies', {
                    headers: this.getAuthHeaders(),
                    params: {
                        company_id: this.$store.auth.currentCompany.id,
                        marketplace_account_id: {{ $accountId }}
                    }
                });
                this.supplies = response.data.supplies || [];
                // Также обновляем openSupplies для совместимости со старым кодом
                this.openSupplies = this.supplies.filter(s => s.status === 'draft' || s.status === 'in_assembly' || s.status === 'ready');
            } catch (error) {
                console.error('Error loading supplies:', error);
                console.error('Error response:', error.response?.data);
                console.error('Error status:', error.response?.status);
            }
        },
        openCreateSupplyModal() {
            this.newSupply = { name: '', description: '' };
            this.showCreateSupplyModal = true;
        },
        async createSupply() {
            if (!this.newSupply.name) {
                alert('Введите название поставки');
                return;
            }

            this.suppliesLoading = true;
            try {
                const response = await axios.post('/api/marketplace/supplies', {
                    marketplace_account_id: {{ $accountId }},
                    company_id: this.$store.auth.currentCompany.id,
                    name: this.newSupply.name,
                    description: this.newSupply.description
                }, {
                    headers: this.getAuthHeaders()
                });

                this.supplies.unshift(response.data.supply);
                this.openSupplies.unshift(response.data.supply);
                this.showCreateSupplyModal = false;
                this.newSupply = { name: '', description: '' };

                // Перезагружаем список открытых поставок
                await this.loadOpenSupplies();

                alert('Поставка создана успешно!');
            } catch (error) {
                console.error('Error creating supply:', error);
                console.error('Error response:', error.response?.data);
                console.error('Error status:', error.response?.status);
                alert(error.response?.data?.message || 'Ошибка при создании поставки');
            } finally {
                this.suppliesLoading = false;
            }
        },
        async openAddToSupplyModal(order) {
            this.selectedOrderForSupply = order;
            this.selectedSupplyId = null;
            await this.loadOpenSupplies();
            this.showAddToSupplyModal = true;
        },
        async addOrderToSupply() {
            if (!this.selectedSupplyId) {
                alert('Выберите поставку');
                return;
            }

            this.suppliesLoading = true;
            try {
                const response = await axios.post(`/api/marketplace/supplies/${this.selectedSupplyId}/orders`, {
                    order_id: this.selectedOrderForSupply.id
                }, {
                    headers: this.getAuthHeaders()
                });

                // Обновляем заказ в списке
                const orderIndex = this.orders.findIndex(o => o.id === this.selectedOrderForSupply.id);
                if (orderIndex !== -1) {
                    this.orders[orderIndex] = response.data.order;
                }

                this.showAddToSupplyModal = false;
                this.selectedOrderForSupply = null;
                this.selectedSupplyId = null;

                // Перезагружаем поставки для обновления счётчиков и заказы
                await this.loadOpenSupplies();
                await this.loadOrders();

                alert('Заказ добавлен в поставку!');
            } catch (error) {
                console.error('Error adding order to supply:', error);
                console.error('Error response:', error.response?.data);
                console.error('Error status:', error.response?.status);
                alert(error.response?.data?.message || 'Ошибка при добавлении заказа в поставку');
            } finally {
                this.suppliesLoading = false;
            }
        },
        async removeOrderFromSupply(order) {
            if (!confirm('Убрать заказ из поставки?')) {
                return;
            }

            // Находим поставку по supply_id заказа
            const supply = this.supplies.find(s =>
                s.external_supply_id === order.supply_id || `SUPPLY-${s.id}` === order.supply_id
            );

            if (!supply) {
                alert('Поставка не найдена');
                return;
            }

            try {
                const response = await axios.delete(`/api/marketplace/supplies/${supply.id}/orders`, {
                    headers: this.getAuthHeaders(),
                    data: {
                        order_id: order.id
                    }
                });

                // Обновляем заказ в списке
                const orderIndex = this.orders.findIndex(o => o.id === order.id);
                if (orderIndex !== -1) {
                    this.orders[orderIndex] = response.data.order;
                }

                // Перезагружаем поставки и заказы
                await this.loadOpenSupplies();
                await this.loadOrders();
                await this.loadStats();

                alert('Заказ убран из поставки');
            } catch (error) {
                console.error('Error removing order from supply:', error);
                alert(error.response?.data?.message || 'Ошибка при удалении заказа из поставки');
            }
        },
        async closeSupplyFromPanel(supplyId) {
            if (!confirm('Закрыть поставку? После этого нельзя будет добавлять заказы.')) {
                return;
            }

            try {
                const response = await axios.post(`/api/marketplace/supplies/${supplyId}/close`, {}, {
                    headers: this.getAuthHeaders()
                });

                // Перезагружаем открытые поставки и заказы
                await this.loadOpenSupplies();
                await this.loadOrders();
                await this.loadStats();

                alert('Поставка закрыта. Теперь можно синхронизировать её с WB на странице Все поставки.');
            } catch (error) {
                console.error('Error closing supply:', error);
                alert(error.response?.data?.message || 'Ошибка при закрытии поставки');
            }
        },
        async syncSupplyWithWb(supplyId) {
            if (!confirm('Синхронизировать поставку с Wildberries? Поставка будет создана в системе WB.')) {
                return;
            }

            try {
                const response = await axios.post(`/api/marketplace/supplies/${supplyId}/sync-wb`, {}, {
                    headers: this.getAuthHeaders()
                });

                // Перезагружаем открытые поставки и заказы для обновления статуса синхронизации
                await this.loadOpenSupplies();
                await this.loadOrders();
                await this.loadStats();

                alert(response.data.message || 'Поставка успешно синхронизирована с Wildberries');
            } catch (error) {
                console.error('Error syncing supply with WB:', error);
                alert(error.response?.data?.message || 'Ошибка при синхронизации с WB');
            }
        },
        async deleteSupply(supplyId) {
            if (!confirm('Удалить пустую поставку? Это действие нельзя отменить.')) {
                return;
            }

            this.deletingSupplyId = supplyId;
            try {
                await axios.delete(`/api/marketplace/supplies/${supplyId}`, {
                    headers: this.getAuthHeaders()
                });

                // Перезагружаем список поставок
                await this.loadOpenSupplies();

                this.showNotification('Поставка успешно удалена');
            } catch (error) {
                console.error('Error deleting supply:', error);
                alert(error.response?.data?.message || 'Ошибка при удалении поставки');
            } finally {
                this.deletingSupplyId = null;
            }
        },
        async viewSupplyOrders(supply) {
            // Открываем модальное окно с заказами поставки
            this.selectedSupply = supply;
            this.showSupplyModal = true;

            try {
                const response = await axios.get(`/api/marketplace/supplies/${supply.id}`, {
                    headers: this.getAuthHeaders()
                });
                this.supplyOrders = response.data.supply.orders || [];
            } catch (error) {
                console.error('Error loading supply orders:', error);
                alert('Ошибка загрузки заказов поставки');
            }
        },
        async removeOrderFromSupplyInModal(order) {
            if (!confirm('Убрать заказ из поставки?')) {
                return;
            }

            this.removingOrderFromSupplyId = order.id;
            try {
                const response = await axios.delete(`/api/marketplace/supplies/${this.selectedSupply.id}/orders`, {
                    headers: this.getAuthHeaders(),
                    data: {
                        order_id: order.id
                    }
                });

                // Обновляем список заказов в модальном окне
                await this.viewSupplyOrders(this.selectedSupply);

                // Перезагружаем поставки и заказы
                await this.loadOpenSupplies();
                await this.loadOrders();

                alert('Заказ убран из поставки');
            } catch (error) {
                console.error('Error removing order from supply:', error);
                alert(error.response?.data?.message || 'Ошибка при удалении заказа из поставки');
            } finally {
                this.removingOrderFromSupplyId = null;
            }
        },
        async closeSupplyFromAccordion(supplyId) {
            if (!confirm('Закрыть поставку? После этого нельзя будет добавлять заказы, но можно будет передать её в доставку.')) {
                return;
            }

            try {
                const response = await axios.post(`/api/marketplace/supplies/${supplyId}/close`, {}, {
                    headers: this.getAuthHeaders()
                });

                // Перезагружаем поставки и заказы
                await this.loadOpenSupplies();
                await this.loadOrders();
                await this.loadStats();

                alert('Поставка закрыта и готова к передаче в доставку');
            } catch (error) {
                console.error('Error closing supply:', error);
                alert(error.response?.data?.message || 'Ошибка при закрытии поставки');
            }
        },
        showDeliverModal(supply) {
            this.supplyToDeliver = supply;
            this.showDeliverSupplyModal = true;
        },
        closeDeliverModal() {
            this.showDeliverSupplyModal = false;
            this.supplyToDeliver = null;
        },
        async deliverSupply() {
            if (!this.supplyToDeliver || this.deliveringSupply) return;

            this.deliveringSupply = true;
            try {
                const response = await axios.post(`/api/marketplace/supplies/${this.supplyToDeliver.id}/deliver`, {}, {
                    headers: this.getAuthHeaders()
                });

                // Закрываем модальное окно
                this.closeDeliverModal();

                // Перезагружаем поставки и заказы
                await this.loadOpenSupplies();
                await this.loadOrders();
                await this.loadStats();

                alert(response.data.message || 'Поставка успешно передана в доставку');
            } catch (error) {
                console.error('Error delivering supply:', error);
                alert(error.response?.data?.message || 'Ошибка при передаче поставки в доставку');
            } finally {
                this.deliveringSupply = false;
            }
        },
        async fetchNewOrders() {
            // Теперь используем общий метод triggerSync для всех маркетплейсов
            await this.triggerSync();
        },
        async handleSyncButton() {
            // Используем единый метод синхронизации для всех маркетплейсов
            await this.triggerSync();
        },
        async printOrderSticker(order) {
            try {
                // Если уже есть путь, пробуем распечатать существующий файл
                if (order.sticker_path) {
                    await this.printFromUrl(`/storage/${order.sticker_path}`);
                    return;
                }

                const payload = {
                    marketplace_account_id: this.accountId,
                    order_ids: [order.external_order_id],
                };
                // Uzum: PDF с DataMatrix (LARGE 58x40 или BIG 43x25)
                if (this.isUzum()) {
                    payload.size = 'LARGE';
                } else {
                    payload.type = 'png';
                    payload.width = 58;
                    payload.height = 40;
                }

                const response = await axios.post('/api/marketplace/orders/stickers', payload, {
                    headers: this.getAuthHeaders()
                });

                if (response.data.stickers && response.data.stickers.length > 0) {
                    const sticker = response.data.stickers[0];

                    // Обновляем заказ в списке
                    const orderIndex = this.orders.findIndex(o => o.id === order.id);
                    if (orderIndex !== -1) {
                        this.orders[orderIndex].sticker_path = sticker.path;
                        this.orders[orderIndex].sticker_generated_at = new Date().toISOString();
                    }

                    // Печатаем без открытия новой вкладки: base64 приоритетно, иначе URL
                    if (sticker.base64) {
                        const blob = this.base64ToBlob(sticker.base64, 'application/pdf');
                        await this.printFromBlob(blob);
                    } else {
                        const url = sticker.url || `/storage/${sticker.path}`;
                        await this.printFromUrl(url);
                    }

                    this.showNotification('Стикер успешно сгенерирован');
                } else {
                    alert('Не удалось сгенерировать стикер');
                }

            } catch (error) {
                console.error('Error printing sticker:', error);
                alert(error.response?.data?.message || 'Ошибка при печати стикера');
            }
        },
        async printFromUrl(url) {
            try {
                // Используем относительный путь, чтобы избежать CORS между 127.0.0.1 и localhost
                let fetchUrl = url;
                try {
                    const u = new URL(url, window.location.origin);
                    fetchUrl = u.pathname + u.search + u.hash;
                } catch (e) {
                    // если url уже относительный — оставляем как есть
                }

                const res = await fetch(fetchUrl, { credentials: 'include' });
                if (!res.ok) throw new Error(`Не удалось загрузить файл (${res.status})`);
                const blob = await res.blob();
                await this.printFromBlob(blob);
            } catch (e) {
                console.error('Print error', e);
                alert('Не удалось распечатать этикетку: ' + (e.message || 'ошибка загрузки'));
            }
        },
        async printFromBlob(blob) {
            const blobUrl = URL.createObjectURL(blob);
            const iframe = document.createElement('iframe');
            iframe.style.position = 'fixed';
            iframe.style.right = '0';
            iframe.style.bottom = '0';
            iframe.style.width = '0';
            iframe.style.height = '0';
            iframe.src = blobUrl;
            document.body.appendChild(iframe);
            iframe.onload = () => {
                iframe.contentWindow.focus();
                iframe.contentWindow.print();
                setTimeout(() => {
                    URL.revokeObjectURL(blobUrl);
                    iframe.remove();
                }, 1500);
            };
        },
        base64ToBlob(base64, mime) {
            const byteChars = atob(base64);
            const byteNumbers = new Array(byteChars.length);
            for (let i = 0; i < byteChars.length; i++) {
                byteNumbers[i] = byteChars.charCodeAt(i);
            }
            const byteArray = new Uint8Array(byteNumbers);
            return new Blob([byteArray], { type: mime });
        },
        openCancelModal(order) {
            this.orderToCancel = order;
            this.showCancelModal = true;
        },
        closeCancelModal() {
            this.showCancelModal = false;
            this.orderToCancel = null;
        },
        async cancelOrder() {
            if (!this.orderToCancel || this.cancelingOrder) return;

            this.cancelingOrder = this.orderToCancel.id;

            try {
                const response = await axios.post(`/api/marketplace/orders/${this.orderToCancel.id}/cancel`, {}, {
                    headers: this.getAuthHeaders()
                });

                // Обновляем заказ в списке
                const orderIndex = this.orders.findIndex(o => o.id === this.orderToCancel.id);
                if (orderIndex !== -1) {
                    this.orders[orderIndex] = response.data.order;
                }

                this.showNotification('Заказ успешно отменён');
                this.closeCancelModal();

                // Обновляем статистику и перезагружаем заказы
                await this.loadStats();
                await this.loadOrders();

            } catch (error) {
                console.error('Error canceling order:', error);
                alert(error.response?.data?.message || 'Ошибка при отмене заказа');
            } finally {
                this.cancelingOrder = null;
            }
        },
        async confirmUzumOrder(order) {
            if (this.confirmingOrderId === order.id) return;

            this.confirmingOrderId = order.id;
            try {
                const res = await axios.post(`/api/marketplace/orders/${order.id}/confirm`, {}, {
                    headers: this.getAuthHeaders()
                });
                if (res.data?.order) {
                    const idx = this.orders.findIndex(o => o.id === order.id);
                    if (idx !== -1) {
                        this.orders[idx] = res.data.order;
                    }
                    this.showNotification('Заказ подтверждён');
                    await this.loadStats();
                    await this.loadOrders();
                }
            } catch (e) {
                console.error('Confirm error', e);
                alert(e.response?.data?.message || 'Ошибка при подтверждении заказа');
            } finally {
                this.confirmingOrderId = null;
            }
        },
        handleTakeOrder(order) {
            if (this.isUzum()) {
                this.confirmUzumOrder(order);
            } else {
                this.startAssembly(order);
            }
        },
        startAssembly(order) {
            if (!order) return;
            // Временное клиентское действие: помечаем заказ как «В сборке» в UI
            order.status = 'in_assembly';
            order.status_normalized = 'in_assembly';
            this.showNotification('Заказ переведён в сборку (локально)');
            // Обновим отображение счётчиков без повторной загрузки
            this.loadStats();
        },
        async switchTab(tab) {
            this.activeTab = tab;
             // Установить фильтр статуса на основе выбранной вкладки
             switch(tab) {
                 case 'new':
                     this.statusFilter = '';
                     break;
                 case 'in_assembly':
                     this.statusFilter = '';
                     break;
                 case 'in_delivery':
                     // Показываем заказы в пути; грузим все и фильтруем на клиенте
                     this.statusFilter = '';
                     break;
                 case 'completed':
                     // Архив: только финальные статусы (получено/выкуплено/отказ)
                     this.statusFilter = '';
                     break;
                 case 'cancelled':
                     this.statusFilter = '';
                     break;
                default:
                    this.statusFilter = '';
            }
            await this.loadOrders();
            await this.loadStats();
        },
        switchDeliveryType(type) {
            this.deliveryTypeFilter = type;
            // При выборе FBO загружаем FBO заказы (для WB и Uzum)
            if (type === 'fbo') {
                this.loadFboOrders();
            }
        },
        async triggerSync() {
             if (this.syncInProgress) {
                 return;
             }

             this.syncInProgress = true;
             this.syncProgress = 0;
             this.syncMessage = 'Запуск синхронизации...';
             this.syncStatus = 'started';

             try {
                 const url = '/api/marketplace/accounts/{{ $accountId }}/sync/orders';
                 const payload = { async: true };
                 const token = this.getToken();

                 const res = await fetch(url, {
                     method: 'POST',
                     headers: {
                         ...this.getAuthHeaders(),
                         'Content-Type': 'application/json'
                     },
                     body: JSON.stringify(payload)
                 });

                 const data = await res.json();

                 if (!res.ok) {
                     throw new Error(data.message || 'Ошибка синхронизации');
                 }

                 this.showNotification('Синхронизация запущена в фоновом режиме');
                 // Прогресс и завершение будут обработаны через WebSocket события
             } catch (error) {
                 console.error('❌ Sync error:', error);
                 this.syncInProgress = false;
                 this.syncProgress = 0;
                 this.syncMessage = 'Ошибка: ' + error.message;
                 this.syncStatus = 'error';
                 this.showNotification('Ошибка синхронизации: ' + error.message);
             }
         },
         async toggleLiveMonitoring() {
             if (this.liveMonitoringEnabled) {
                 await this.stopLiveMonitoring();
             } else {
                 await this.startLiveMonitoring();
             }
         },
         async startLiveMonitoring() {
             try {
                 const url = '/api/marketplace/accounts/{{ $accountId }}/monitoring/start';
                 const res = await fetch(url, {
                     method: 'POST',
                     headers: this.getAuthHeaders()
                 });

                 const data = await res.json();

                 if (res.ok) {
                     this.liveMonitoringEnabled = true;
                     this.showNotification('Live-мониторинг запущен');
                 } else {
                     throw new Error(data.message || 'Ошибка запуска мониторинга');
                 }
             } catch (error) {
                 console.error('Failed to start monitoring:', error);
                 this.showNotification('Ошибка запуска мониторинга: ' + error.message);
             }
         },
         async stopLiveMonitoring() {
             try {
                 const url = '/api/marketplace/accounts/{{ $accountId }}/monitoring/stop';
                 const res = await fetch(url, {
                     method: 'POST',
                     headers: this.getAuthHeaders()
                 });

                 const data = await res.json();

                 if (res.ok) {
                     this.liveMonitoringEnabled = false;
                     this.liveMonitoringActive = false;
                     this.showNotification('Live-мониторинг остановлен');
                 } else {
                     throw new Error(data.message || 'Ошибка остановки мониторинга');
                 }
             } catch (error) {
                 console.error('Failed to stop monitoring:', error);
                 this.showNotification('Ошибка остановки мониторинга: ' + error.message);
             }
         },
        normalizeStatus(order) {
            if (!order) return null;
            // Uzum: приоритет - статус из БД, затем из raw_payload
            if (this.accountMarketplace === 'uzum') {
                const dbStatus = (order.status_normalized || order.status || '').toString().toLowerCase();
                const validStatuses = ['new', 'in_assembly', 'in_supply', 'accepted_uzum', 'waiting_pickup', 'issued', 'cancelled', 'returns'];

                // Если в БД уже есть валидный статус - используем его (приоритет БД)
                if (validStatuses.includes(dbStatus)) {
                    return dbStatus;
                }

                // Иначе мапим из raw_payload.status (для новых заказов из API)
                const rawStatus = (order.raw_payload?.status || '').toString().toUpperCase();
                const map = {
                    'CREATED': 'new',
                    'AWAITING_CONFIRMATION': 'new',
                    'PACKING': 'in_assembly',
                    'PROCESSING': 'in_assembly',
                    'PENDING_DELIVERY': 'in_supply',
                    'SHIPPED': 'in_supply',
                    'DELIVERING': 'accepted_uzum',
                    'ACCEPTED_AT_DP': 'accepted_uzum',
                    'DELIVERED': 'accepted_uzum',
                    'DELIVERED_TO_CUSTOMER_DELIVERY_POINT': 'waiting_pickup',
                    'COMPLETED': 'issued',
                    'CANCELED': 'cancelled',
                    'CANCELLED': 'cancelled',
                    'PENDING_CANCELLATION': 'cancelled',
                    'RETURNED': 'returns',
                };
                const mapped = map[rawStatus] || null;
                if (mapped) {
                    order.status_normalized = mapped;
                }
                return mapped;
            }
            // Wildberries: приоритет - статус из БД, затем wb_status_group, затем wb_status
            if (this.accountMarketplace === 'wb') {
                const dbStatus = (order.status_normalized || order.status || '').toString().toLowerCase();
                const validStatuses = ['new', 'in_assembly', 'in_delivery', 'completed', 'cancelled'];

                // Маппинг внешних статусов WB на внутренние
                const statusMapping = {
                    'delivered': 'completed',
                    'sold': 'completed',
                    'canceled': 'cancelled',
                    'on_delivery': 'in_delivery',
                    'shipping': 'in_delivery',
                    'sorted': 'in_assembly',
                    'assembling': 'in_assembly',
                    'waiting': 'new',
                };

                // Если статус напрямую валидный - используем его
                if (validStatuses.includes(dbStatus)) {
                    return dbStatus;
                }

                // Если статус можно смаппить на валидный - делаем это
                if (statusMapping[dbStatus]) {
                    order.status_normalized = statusMapping[dbStatus];
                    return statusMapping[dbStatus];
                }

                // Иначе мапим из wb_status_group / wb_status
                const group = (order.wb_status_group || '').toString().toLowerCase();
                const wbStatus = (order.wb_status || '').toString().toLowerCase();
                const mapGroup = {
                    'new': 'new',
                    'assembling': 'in_assembly',
                    'shipping': 'in_delivery',
                    'archive': 'completed',
                    'canceled': 'cancelled',
                };
                const mapStatusFromWb = {
                    'new': 'new',
                    'waiting': 'new',
                    'sorted': 'in_assembly',
                    'assembling': 'in_assembly',
                    'shipping': 'in_delivery',
                    'on_delivery': 'in_delivery',
                    'delivered': 'completed',
                    'sold': 'completed',
                    'canceled': 'cancelled',
                    'cancelled': 'cancelled',
                };
                const mapped = mapGroup[group] || mapStatusFromWb[wbStatus] || null;
                if (mapped) {
                    order.status_normalized = mapped;
                    return mapped;
                }
            }
            // Fallback: если статус уже установлен, возвращаем его
            if (order.status_normalized) return order.status_normalized;

            // Для WB: учитываем статус поставки как дополнительный fallback
            if (this.accountMarketplace === 'wb') {
                // Учитываем статус поставки, если заказ привязан
                const supply = this.supplies.find(s =>
                    s.external_supply_id === order.supply_id ||
                    s.id === order.supply_id ||
                    ('SUPPLY-' + s.id) === order.supply_id
                );
                if (supply) {
                    if (supply.status === 'sent') {
                        order.status_normalized = 'in_delivery';
                        return order.status_normalized;
                    }
                    if (supply.status === 'delivered') {
                        order.status_normalized = 'completed';
                        return order.status_normalized;
                    }
                    if (supply.status === 'ready' || supply.status === 'in_assembly' || supply.status === 'draft') {
                        order.status_normalized = 'in_assembly';
                        return order.status_normalized;
                    }
                }
                // Принудительно считаем заказы в поставке 'На сборке'
                if ((order.supply_id || order.supplyId) && (order.status === 'new' || !order.status)) {
                    order.status_normalized = 'in_assembly';
                    return order.status_normalized;
                }
            }

            return null;
         },
        get filteredOrders() {
            const baseFiltered = this.baseFiltered;

           // Карта статусов для фильтрации по вкладкам
            const statusMap = this.accountMarketplace === 'uzum'
                ? {
                    'new': ['new'],
                    'in_assembly': ['in_assembly'],
                    'in_supply': ['in_supply'],
                    'accepted_uzum': ['accepted_uzum'],
                    'waiting_pickup': ['waiting_pickup'],
                    'issued': ['issued'],
                    'cancelled': ['cancelled'],
                    'returns': ['returns'],
                }
                : {
                     'new': ['new'],
                     'in_assembly': ['in_assembly'],
                     'in_delivery': ['in_delivery'],
                     'completed': ['completed'],
                    'cancelled': ['cancelled'],
                };
            const allowedStatuses = statusMap[this.activeTab] ?? null;

             return baseFiltered.filter(order => {
                 if (allowedStatuses === null) return true;
                 if (allowedStatuses.length === 0) return false;
                 const st = this.normalizeStatus(order);
                 if (!st) return false;
                 return allowedStatuses.includes(st);
             });
         },
        get baseFiltered() {
             return this.orders
                .filter(order => {
                    // Фильтр по типу доставки (FBS/DBS/eDBS) - для всех маркетплейсов
                    if (this.deliveryTypeFilter && this.deliveryTypeFilter !== 'all' && this.deliveryTypeFilter !== 'fbo') {
                        // WB: wb_delivery_type
                        // Uzum: raw_payload.scheme
                        let orderDeliveryType = '';
                        if (this.accountMarketplace === 'wb') {
                            orderDeliveryType = (order.wb_delivery_type || order.details?.wb_delivery_type || '').toLowerCase();
                        } else if (this.accountMarketplace === 'uzum') {
                            orderDeliveryType = (order.raw_payload?.scheme || order.delivery_type || '').toLowerCase();
                        }
                        if (orderDeliveryType !== this.deliveryTypeFilter.toLowerCase()) return false;
                    }
                    // Фильтр по магазину (Uzum)
                    if (this.accountMarketplace === 'uzum' && this.selectedShopIds.length > 0) {
                        const sid = order.raw_payload?.shopId ? String(order.raw_payload.shopId) : '';
                        if (!this.selectedShopIds.map(String).includes(sid)) return false;
                    }
                    if (!this.searchQuery) return true;
                    const query = this.searchQuery.toLowerCase();
                    const payload = order.raw_payload || {};
                    const externalMatch = order.external_order_id && order.external_order_id.toString().includes(query);
                    const articleMatch = payload.article && payload.article.toLowerCase().includes(query);
                    const orderUidMatch = payload.orderUid && payload.orderUid.toLowerCase().includes(query);
                    return externalMatch || articleMatch || orderUidMatch;
                });
        },
         get tabOrders() {
             return this.filteredOrders;
         },
        get filteredStats() {
             // Статистика по текущему набору заказов (вкладка + все фильтры)
             const filtered = this.filteredOrders;
             const byStatus = this.accountMarketplace === 'uzum' ? {
                 new: 0,
                 in_assembly: 0,
                 in_supply: 0,
                 accepted_uzum: 0,
                 waiting_pickup: 0,
                 issued: 0,
                 cancelled: 0,
                 returns: 0
             } : {
                 new: 0,
                 in_assembly: 0,
                 in_delivery: 0,
                 completed: 0,
                 cancelled: 0
             };

             // Create a map of supply_id -> supply.status for quick lookup
             const supplyStatusMap = {};
             this.supplies.forEach(supply => {
                 const supplyId = supply.external_supply_id || supply.id;
                 supplyStatusMap[supplyId] = supply.status;
             });

             let amount = 0;
             filtered.forEach(order => {
                 const st = this.normalizeStatus(order);

                 if (this.accountMarketplace === 'uzum') {
                     if (st && byStatus.hasOwnProperty(st)) {
                         byStatus[st] += 1;
                     }
                 } else {
                     // For in_assembly tab: only count orders from supplies with status draft/in_assembly/ready
                     if (st === 'in_assembly') {
                         const orderSupplyStatus = supplyStatusMap[order.supply_id];
                         if (orderSupplyStatus === 'draft' || orderSupplyStatus === 'in_assembly' || orderSupplyStatus === 'ready') {
                             byStatus[st] += 1;
                         }
                     }
                     // For in_delivery tab: only count orders from supplies with status sent
                     else if (st === 'in_delivery') {
                         const orderSupplyStatus = supplyStatusMap[order.supply_id];
                         if (orderSupplyStatus === 'sent') {
                             byStatus[st] += 1;
                         }
                     }
                     // For other statuses (new, completed, cancelled): count normally
                     else if (st && byStatus.hasOwnProperty(st)) {
                         byStatus[st] += 1;
                     }
                 }

                const priceKopecks = order.wb_final_price
                    ?? (order.total_amount ? Math.round(order.total_amount * 100) : 0)
                    ?? (order.raw_payload?.price ? Math.round(order.raw_payload.price * 100) : 0);
                amount += priceKopecks || 0;
            });

             return {
                 total_orders: filtered.length,
                 total_amount: amount / 100,
                 by_status: byStatus
             };
        },
        get baseStats() {
            // Статистика по всем отфильтрованным заказам (без учёта активной вкладки) — для бейджей вкладок
            const filtered = this.baseFiltered;
            const byStatus = this.accountMarketplace === 'uzum' ? {
                new: 0,
                in_assembly: 0,
                in_supply: 0,
                accepted_uzum: 0,
                waiting_pickup: 0,
                issued: 0,
                cancelled: 0,
                returns: 0
            } : {
                new: 0,
                in_assembly: 0,
                in_delivery: 0,
                completed: 0,
                cancelled: 0
            };

            let amount = 0;
            const byDeliveryType = { fbs: 0, dbs: 0, edbs: 0 };

            filtered.forEach(order => {
                const st = this.normalizeStatus(order);
                if (st && byStatus.hasOwnProperty(st)) {
                    byStatus[st] += 1;
                }
                // Подсчёт по типу доставки (для всех маркетплейсов)
                let dt = '';
                if (this.accountMarketplace === 'wb') {
                    dt = (order.wb_delivery_type || order.details?.wb_delivery_type || '').toLowerCase();
                } else if (this.accountMarketplace === 'uzum') {
                    dt = (order.raw_payload?.scheme || order.delivery_type || '').toLowerCase();
                }
                if (dt === 'fbs') byDeliveryType.fbs++;
                else if (dt === 'dbs') byDeliveryType.dbs++;
                else if (dt === 'edbs') byDeliveryType.edbs++;

                const priceKopecks = order.wb_final_price
                    ?? (order.total_amount ? Math.round(order.total_amount * 100) : 0)
                    ?? (order.raw_payload?.price ? Math.round(order.raw_payload.price * 100) : 0);
                amount += priceKopecks || 0;
            });

            return {
                total_orders: filtered.length,
                total_amount: amount / 100,
                by_status: byStatus,
                by_delivery_type: byDeliveryType
            };
        },
        get displayStats() {
             // Для вкладок FBS/DBS/eDBS показываем данные отфильтрованных заказов
             return this.filteredStats;
        },
         get groupedBySupply() {
             // Группируем заказы по supply_id для вкладки На сборке
             const groups = {
                 withSupply: {},  // { supplyId: [orders] }
                 withoutSupply: [] // заказы без поставки
             };

             this.tabOrders.forEach(order => {
                 if (order.supply_id) {
                     if (!groups.withSupply[order.supply_id]) {
                         groups.withSupply[order.supply_id] = [];
                     }
                     groups.withSupply[order.supply_id].push(order);
                 } else {
                     groups.withoutSupply.push(order);
                 }
             });

            return groups;
        },
        statusLabel(order) {
            if (!order) return '';
            const st = this.normalizeStatus(order);
            // Uzum: человеко-понятные русские статусы
            if (this.accountMarketplace === 'uzum') {
                const labels = {
                    'new': 'Новые',
                    'in_assembly': 'В сборке',
                    'in_supply': 'В поставке',
                    'accepted_uzum': 'Приняты Uzum',
                    'waiting_pickup': 'Ждут выдачи',
                    'issued': 'Выданы',
                    'cancelled': 'Отменены',
                    'returns': 'Возвраты'
                };
                return labels[st] || (order.status || order.raw_payload?.status || '').toString().toUpperCase();
            }

            const map = {
                'new': 'NEW',
                'in_assembly': 'CONFIRM',
                'in_delivery': 'DELIVERY',
                'completed': 'DONE',
                'cancelled': 'CANCEL'
            };
            return map[st] || (order?.wb_supplier_status || order?.wb_status || '').toUpperCase();
        },
        statusClass(order) {
            if (!order) return {};
            const st = this.normalizeStatus(order);
            if (this.accountMarketplace === 'uzum') {
                return {
                    'bg-blue-100 text-blue-700': st === 'new',
                    'bg-orange-100 text-orange-700': st === 'in_assembly',
                    'bg-indigo-100 text-indigo-700': st === 'in_supply',
                    'bg-teal-100 text-teal-700': st === 'accepted_uzum',
                    'bg-purple-100 text-purple-700': st === 'waiting_pickup',
                    'bg-green-100 text-green-700': st === 'issued',
                    'bg-red-100 text-red-700': st === 'cancelled',
                    'bg-gray-200 text-gray-700': st === 'returns'
                };
            }
            return {
                'bg-blue-100 text-blue-700': st === 'new',
                'bg-orange-100 text-orange-700': st === 'in_assembly',
                'bg-purple-100 text-purple-700': st === 'in_delivery',
                'bg-green-100 text-green-700': st === 'completed',
                'bg-red-100 text-red-700': st === 'cancelled'
            };
        },
        async loadFboOrders() {
            // FBO доступен для WB и Uzum
            this.fboLoading = true;
            try {
                let url = '/api/marketplace/orders/fbo?company_id=' + this.$store.auth.currentCompany.id + '&marketplace_account_id=' + this.accountId;
                if (this.dateFrom) url += '&from=' + this.dateFrom;
                if (this.dateTo) url += '&to=' + this.dateTo;
                const res = await fetch(url, { headers: this.getAuthHeaders() });
                if (res.ok) {
                    const data = await res.json();
                    this.fboOrders = data.orders || [];
                    this.fboStats = data.stats || { total: 0, by_status: {} };
                } else if (res.status === 401) {
                    window.location.href = '/login';
                }
            } catch (e) {
                console.error('Error loading FBO orders:', e);
            }
            this.fboLoading = false;
        }
    }"
     class="flex h-screen bg-gray-50 browser-only"
     :class="{
         'flex-row': $store.ui.navPosition === 'left',
         'flex-row-reverse': $store.ui.navPosition === 'right'
     }">

    <template x-if="$store.ui.navPosition === 'left' || $store.ui.navPosition === 'right'">
        <x-sidebar />
    </template>

    <div class="flex-1 flex flex-col overflow-hidden"
         :class="{ 'pb-20': $store.ui.navPosition === 'bottom', 'pt-20': $store.ui.navPosition === 'top' }">
        <header class="bg-white border-b border-gray-200 px-6 py-4">
            <div class="flex items-center justify-between">
                <div class="flex items-center space-x-4">
                    <a href="/marketplace/{{ $accountId }}" class="text-gray-400 hover:text-gray-600 transition">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                        </svg>
                    </a>
                    <div>
                        <h1 class="text-2xl font-bold text-gray-900" x-text="'Заказы ' + (getAccountLabel() || '')"></h1>
                        <p class="text-gray-600 text-sm">Управление заказами маркетплейса</p>
                    </div>
                </div>
                <div class="flex items-center space-x-3">
                    <!-- WebSocket status indicator (скрыт для Uzum Market) -->
                    <div x-show="accountMarketplace !== 'uzum'" class="flex items-center space-x-2 text-sm">
                        <div class="relative">
                            <div class="w-2 h-2 rounded-full" :class="wsConnectedFlag ? 'bg-green-500' : 'bg-gray-300'"></div>
                            <div class="absolute top-0 left-0 w-2 h-2 rounded-full animate-ping" :class="wsConnectedFlag ? 'bg-green-500' : 'bg-gray-300'" x-show="wsConnectedFlag"></div>
                        </div>
                        <span class="text-gray-600" x-text="wsConnectedFlag ? (syncInProgress ? (syncProgress + '%') : 'Онлайн') : 'Офлайн'"></span>
                    </div>

                    <!-- Uzum Market: показываем таймер до следующего обновления -->
                    <div x-show="accountMarketplace === 'uzum'" class="flex items-center space-x-2 text-sm">
                        <svg class="w-4 h-4 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                        <span class="text-gray-600">Автообновление каждые 20 мин</span>
                    </div>

                    <!-- Live Monitoring Toggle (скрыт для Uzum Market) -->
                    <button x-show="accountMarketplace !== 'uzum'"
                            @click="toggleLiveMonitoring()"
                            :class="liveMonitoringEnabled ? 'bg-yellow-500 hover:bg-yellow-600' : 'bg-gray-500 hover:bg-gray-600'"
                            class="px-4 py-2 text-white rounded-lg transition flex items-center space-x-2">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                        </svg>
                        <span x-text="liveMonitoringEnabled ? 'Live ON' : 'Live OFF'"></span>
                        <div class="w-2 h-2 rounded-full" :class="liveMonitoringActive ? 'bg-green-300 animate-pulse' : ''" x-show="liveMonitoringEnabled"></div>
                    </button>

                </div>
            </div>

            <!-- Sync Progress Bar removed; percentage shown near status indicator -->

            <!-- Delivery type tabs (for all marketplaces) -->
            <div class="mt-4 flex space-x-3 overflow-x-auto pb-2">
                <button @click="switchDeliveryType('all')"
                        :class="deliveryTypeFilter === 'all' ? 'bg-gray-100 text-gray-900 border-gray-300' : 'bg-white text-gray-700 border-gray-200 hover:bg-gray-50'"
                        class="px-3 py-2 rounded-lg border text-sm font-medium transition whitespace-nowrap flex items-center gap-2">
                    Все
                    <span class="px-1.5 py-0.5 bg-gray-200 text-gray-700 rounded text-xs" x-text="baseStats?.total_orders || 0"></span>
                </button>
                <button @click="switchDeliveryType('fbs')"
                        :class="deliveryTypeFilter === 'fbs' ? 'bg-blue-50 text-blue-700 border-blue-200' : 'bg-white text-gray-700 border-gray-200 hover:bg-gray-50'"
                        class="px-3 py-2 rounded-lg border text-sm font-medium transition whitespace-nowrap flex items-center gap-2">
                    FBS
                    <span class="px-1.5 py-0.5 bg-blue-100 text-blue-700 rounded text-xs" x-text="baseStats?.by_delivery_type?.fbs || 0"></span>
                </button>
                <button @click="switchDeliveryType('dbs')"
                        :class="deliveryTypeFilter === 'dbs' ? 'bg-blue-50 text-blue-700 border-blue-200' : 'bg-white text-gray-700 border-gray-200 hover:bg-gray-50'"
                        class="px-3 py-2 rounded-lg border text-sm font-medium transition whitespace-nowrap flex items-center gap-2">
                    DBS
                    <span class="px-1.5 py-0.5 bg-blue-100 text-blue-700 rounded text-xs" x-text="baseStats?.by_delivery_type?.dbs || 0"></span>
                </button>
                <button @click="switchDeliveryType('edbs')"
                        :class="deliveryTypeFilter === 'edbs' ? 'bg-blue-50 text-blue-700 border-blue-200' : 'bg-white text-gray-700 border-gray-200 hover:bg-gray-50'"
                        class="px-3 py-2 rounded-lg border text-sm font-medium transition whitespace-nowrap flex items-center gap-2">
                    eDBS
                    <span class="px-1.5 py-0.5 bg-blue-100 text-blue-700 rounded text-xs" x-text="baseStats?.by_delivery_type?.edbs || 0"></span>
                </button>
                <!-- FBO for all marketplaces -->
                <button @click="switchDeliveryType('fbo')"
                        :class="deliveryTypeFilter === 'fbo' ? 'bg-purple-50 text-purple-700 border-purple-200' : 'bg-white text-gray-700 border-gray-200 hover:bg-gray-50'"
                        class="px-3 py-2 rounded-lg border text-sm font-medium transition whitespace-nowrap flex items-center gap-2">
                    FBO
                    <span class="px-1.5 py-0.5 bg-purple-100 text-purple-700 rounded text-xs" x-text="fboStats?.total || 0"></span>
                </button>
                <!-- Uzum shop filter -->
                <template x-if="isUzum()">
                    <div class="relative ml-2">
                        <button @click="shopAccordionOpen = !shopAccordionOpen; if (shopAccordionOpen && shopOptions().length === 0) { loadUzumShops(); }"
                                class="px-3 py-2 border border-gray-300 rounded-lg text-sm bg-white hover:bg-gray-50 flex items-center space-x-2">
                            <span class="font-semibold text-gray-900" x-text="shopLabel()"></span>
                            <svg :class="shopAccordionOpen ? 'rotate-180' : ''" class="w-4 h-4 text-gray-500 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                            </svg>
                        </button>
                        <div x-show="shopAccordionOpen" @click.away="shopAccordionOpen = false"
                             class="absolute z-10 mt-2 w-64 bg-white border border-gray-200 rounded-lg shadow-lg p-3 space-y-2">
                            <div class="flex justify-between items-center mb-2">
                                <span class="text-sm font-semibold text-gray-700">Выбор магазинов</span>
                                <button class="text-xs text-blue-600 hover:underline" @click="resetShopFilter(); shopAccordionOpen=false;">Сброс</button>
                            </div>
                            <div class="max-h-48 overflow-y-auto space-y-2">
                                <template x-if="shopOptions().length === 0">
                                    <div class="text-xs text-gray-500">Магазины не загружены. Попробуйте «Применить» или синхронизировать.</div>
                                </template>
                                <template x-for="opt in shopOptions()" :key="opt.id">
                                    <label class="flex items-center space-x-2 text-sm text-gray-700">
                                        <input type="checkbox" :value="opt.id" x-model="selectedShopIds" class="rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                                        <span>
                                            <span x-text="opt.name || ('Shop ' + opt.id)"></span>
                                            <span class="text-xs text-gray-500" x-text="'ID: ' + opt.id"></span>
                                        </span>
                                    </label>
                                </template>
                            </div>
                            <div class="flex justify-end space-x-2 pt-2 border-t border-gray-200">
                                <button @click="shopAccordionOpen=false" class="px-3 py-1 text-sm text-gray-600 bg-gray-100 rounded-lg hover:bg-gray-200">Закрыть</button>
                                <button @click="loadOrders(); loadStats(); shopAccordionOpen=false;" class="px-3 py-1 text-sm text-white bg-blue-600 rounded-lg hover:bg-blue-700">Применить</button>
                            </div>
                        </div>
                    </div>
                </template>
            </div>

            <!-- Search + Date filters -->
            <div class="mt-4 space-y-3">
                <!-- Quick date filters -->
                <div class="flex items-center space-x-2">
                    <span class="text-sm text-gray-600">Быстрый выбор:</span>
                    <button @click="setToday()"
                            class="px-3 py-1.5 bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200 text-sm transition">
                        Сегодня
                    </button>
                    <button @click="setYesterday()"
                            class="px-3 py-1.5 bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200 text-sm transition">
                        Вчера
                    </button>
                    <button @click="setLastWeek()"
                            class="px-3 py-1.5 bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200 text-sm transition">
                        7 дней
                    </button>
                    <button @click="setLastMonth()"
                            class="px-3 py-1.5 bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200 text-sm transition">
                        30 дней
                    </button>
                    <button @click="dateFrom = ''; dateTo = ''; loadOrders(); loadStats()"
                            class="px-3 py-1.5 bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200 text-sm transition">
                        Сбросить
                    </button>
                </div>

                <!-- Search and custom date range -->
                <div class="flex flex-col md:flex-row md:items-center md:space-x-3 space-y-3 md:space-y-0">
                    <input type="text" x-model="searchQuery"
                           class="px-3 py-2 w-full md:w-1/3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                           placeholder="Поиск по номеру, артикулу...">

                    <div class="flex items-center space-x-2">
                        <input type="date" x-model="dateFrom"
                               :max="dateTo || new Date().toISOString().split('T')[0]"
                               class="px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                               placeholder="От">

                        <span class="text-gray-500">—</span>

                        <input type="date" x-model="dateTo"
                               :min="dateFrom"
                               :max="new Date().toISOString().split('T')[0]"
                               class="px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                               placeholder="До">

                        <button @click="loadOrders(); loadStats()"
                                class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition text-sm font-medium">
                            Применить
                        </button>

                        <button @click="handleSyncButton()"
                                :disabled="fetchingNewOrders || syncInProgress"
                                class="px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 disabled:bg-gray-400 disabled:cursor-not-allowed transition text-sm font-medium flex items-center space-x-2">
                            <svg x-show="!(fetchingNewOrders || syncInProgress)" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                            </svg>
                            <svg x-show="fetchingNewOrders || syncInProgress" class="animate-spin w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                            </svg>
                            <span x-text="accountMarketplace === 'wb' ? (fetchingNewOrders ? 'Получение...' : 'Получить новые') : (syncInProgress ? 'Синхронизация...' : 'Синхронизировать')"></span>
                        </button>
                    </div>
                </div>

                <!-- Timezone info -->
                <p class="text-xs text-gray-500">
                    * Время заказов в UTC. По умолчанию показываются заказы за последние 30 дней.
                </p>
            </div>
        </header>

        <!-- Status Tabs (hidden when FBO is selected) -->
        <div x-show="deliveryTypeFilter !== 'fbo'" class="bg-white border-b border-gray-200 px-6">
            <template x-if="isUzum()">
                <nav class="flex space-x-4 overflow-x-auto">
                    <template x-for="tab in [
                        { key: 'new', label: 'Новые', color: 'bg-blue-100 text-blue-700' },
                        { key: 'in_assembly', label: 'В сборке', color: 'bg-orange-100 text-orange-700' },
                        { key: 'in_supply', label: 'В поставке', color: 'bg-indigo-100 text-indigo-700' },
                        { key: 'accepted_uzum', label: 'Приняты Uzum', color: 'bg-teal-100 text-teal-700' },
                        { key: 'waiting_pickup', label: 'Ждут выдачи', color: 'bg-purple-100 text-purple-700' },
                        { key: 'issued', label: 'Выданы', color: 'bg-green-100 text-green-700' },
                        { key: 'cancelled', label: 'Отменены', color: 'bg-red-100 text-red-700' },
                        { key: 'returns', label: 'Возвраты', color: 'bg-gray-200 text-gray-700' }
                    ]" :key="tab.key">
                        <button @click="switchTab(tab.key)" :class="activeTab === tab.key ? 'border-blue-600 text-blue-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'"
                                class="py-4 px-1 border-b-2 font-medium text-sm whitespace-nowrap transition flex items-center gap-2">
                            <span x-text="tab.label"></span>
                            <span :class="tab.color" x-text="baseStats?.by_status?.[tab.key] || 0" class="px-2 py-0.5 rounded-full text-xs"></span>
                        </button>
                    </template>
                </nav>
            </template>

            <template x-if="isWb()">
                <nav class="flex space-x-4">
                    <button @click="switchTab('new')" :class="activeTab === 'new' ? 'border-blue-600 text-blue-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'"
                        class="py-4 px-1 border-b-2 font-medium text-sm whitespace-nowrap transition flex items-center gap-2">
                        🆕 Новые
                        <span x-text="baseStats?.by_status?.new || 0" class="px-2 py-0.5 bg-blue-100 text-blue-700 rounded-full text-xs"></span>
                    </button>
                    <button @click="switchTab('in_assembly')" :class="activeTab === 'in_assembly' ? 'border-blue-600 text-blue-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'"
                        class="py-4 px-1 border-b-2 font-medium text-sm whitespace-nowrap transition flex items-center gap-2">
                        📦 На сборке
                        <span x-text="baseStats?.by_status?.in_assembly || 0" class="px-2 py-0.5 bg-orange-100 text-orange-700 rounded-full text-xs"></span>
                    </button>
                    <button @click="switchTab('in_delivery')" :class="activeTab === 'in_delivery' ? 'border-blue-600 text-blue-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'"
                        class="py-4 px-1 border-b-2 font-medium text-sm whitespace-nowrap transition flex items-center gap-2">
                        🚚 В доставке
                        <span x-text="baseStats?.by_status?.in_delivery || 0" class="px-2 py-0.5 bg-purple-100 text-purple-700 rounded-full text-xs"></span>
                    </button>
                    <button @click="switchTab('completed')" :class="activeTab === 'completed' ? 'border-blue-600 text-blue-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'"
                        class="py-4 px-1 border-b-2 font-medium text-sm whitespace-nowrap transition flex items-center gap-2">
                        ✅ Архив
                        <span x-text="baseStats?.by_status?.completed || 0" class="px-2 py-0.5 bg-green-100 text-green-700 rounded-full text-xs"></span>
                    </button>
                    <button @click="switchTab('cancelled')" :class="activeTab === 'cancelled' ? 'border-blue-600 text-blue-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'"
                        class="py-4 px-1 border-b-2 font-medium text-sm whitespace-nowrap transition flex items-center gap-2">
                        ❌ Отменённые
                        <span x-text="baseStats?.by_status?.cancelled || 0" class="px-2 py-0.5 bg-red-100 text-red-700 rounded-full text-xs"></span>
                    </button>
                </nav>
            </template>
        </div>

        <main class="flex-1 overflow-y-auto p-6">
            <!-- Stats - Min height to prevent CLS (hidden when FBO is selected) -->
            <div x-show="deliveryTypeFilter !== 'fbo'" class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6" style="min-height: 140px;">
                <div class="bg-gradient-to-br from-blue-500 to-blue-600 rounded-xl p-5 text-white">
                    <div class="flex items-center justify-between">
                        <div>
                            <div class="text-blue-100 text-sm mb-1">Всего заказов</div>
                            <p class="text-3xl font-bold" x-text="displayStats?.total_orders || 0"></p>
                        </div>
                        <svg class="w-12 h-12 text-blue-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"/>
                        </svg>
                    </div>
                </div>
                <div class="bg-gradient-to-br from-green-500 to-green-600 rounded-xl p-5 text-white">
                    <div class="flex items-center justify-between">
                        <div>
                            <div class="text-green-100 text-sm mb-1">Общая сумма</div>
                            <p class="text-2xl font-bold" x-text="formatMoney(displayStats?.total_amount)"></p>
                        </div>
                        <svg class="w-12 h-12 text-green-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                    </div>
                </div>
                <div class="bg-gradient-to-br from-purple-500 to-purple-600 rounded-xl p-5 text-white">
                    <div class="flex items-center justify-between">
                        <div>
                            <div class="text-purple-100 text-sm mb-1">Средний чек</div>
                            <p class="text-2xl font-bold"
                               x-text="displayStats?.total_orders > 0 ? formatMoney(displayStats?.total_amount / displayStats?.total_orders) : '-'"></p>
                        </div>
                        <svg class="w-12 h-12 text-purple-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z"/>
                        </svg>
                    </div>
                </div>
                <div class="bg-gradient-to-br from-orange-500 to-orange-600 rounded-xl p-5 text-white">
                    <div class="flex items-center justify-between">
                        <div>
                            <div class="text-orange-100 text-sm mb-1">Найдено</div>
                            <p class="text-3xl font-bold" x-text="tabOrders.length"></p>
                        </div>
                        <svg class="w-12 h-12 text-orange-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                        </svg>
                    </div>
                </div>
            </div>

            <!-- Skeleton Loading -->
            <div x-show="loading" x-cloak class="space-y-4">
                <!-- Skeleton Stats Cards -->
                <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
                    <template x-for="i in 4" :key="i">
                        <div class="bg-white rounded-xl p-5 animate-pulse">
                            <div class="h-4 bg-gray-200 rounded w-24 mb-3"></div>
                            <div class="h-8 bg-gray-200 rounded w-32"></div>
                        </div>
                    </template>
                </div>

                <!-- Skeleton Order Cards -->
                <template x-for="i in 6" :key="i">
                    <div class="bg-white rounded-xl border border-gray-200 p-6">
                        <div class="flex items-start space-x-4">
                            <!-- Image skeleton -->
                            <div class="w-24 h-24 bg-gray-200 rounded-lg animate-pulse flex-shrink-0"></div>

                            <!-- Content skeleton -->
                            <div class="flex-1 space-y-3">
                                <div class="flex items-center justify-between">
                                    <div class="h-6 bg-gray-200 rounded w-40 animate-pulse"></div>
                                    <div class="h-6 bg-gray-200 rounded w-20 animate-pulse"></div>
                                </div>
                                <div class="space-y-2">
                                    <div class="h-4 bg-gray-200 rounded w-full animate-pulse"></div>
                                    <div class="h-4 bg-gray-200 rounded w-3/4 animate-pulse"></div>
                                    <div class="h-4 bg-gray-200 rounded w-1/2 animate-pulse"></div>
                                </div>
                                <div class="flex items-center space-x-2 pt-2">
                                    <div class="h-8 bg-gray-200 rounded w-24 animate-pulse"></div>
                                    <div class="h-8 bg-gray-200 rounded w-24 animate-pulse"></div>
                                </div>
                            </div>
                        </div>
                    </div>
                </template>
            </div>

            <!-- Empty State -->
            <div x-show="!loading && tabOrders.length === 0 && deliveryTypeFilter !== 'fbo'" class="bg-white rounded-xl border-2 border-dashed border-gray-300 p-12 text-center">
                <div class="w-20 h-20 mx-auto rounded-2xl bg-gray-100 text-gray-400 flex items-center justify-center mb-4">
                    <svg class="w-10 h-10" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"/>
                    </svg>
                </div>
                <h3 class="text-xl font-semibold text-gray-900 mb-2">Заказы не найдены</h3>
                <p class="text-gray-600 mb-4">Попробуйте изменить фильтры или синхронизируйте заказы</p>
                <button @click="dateFrom = ''; dateTo = ''; statusFilter = ''; searchQuery = ''; loadOrders()"
                        class="px-4 py-2 bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200 transition">
                    Сбросить фильтры
                </button>
            </div>

            <!-- Uzum table view -->
            <div x-show="isUzum() && !loading && tabOrders.length > 0 && deliveryTypeFilter !== 'fbo'" class="bg-white border border-gray-200 rounded-xl overflow-hidden mb-6">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Номер</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Статус</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Создан</th>
                            <template x-if="activeTab === 'new'">
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Подтвердить до</th>
                            </template>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Доставить до</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Состав</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Место приёма</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Магазин</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Действия</th>
                        </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-100">
                        <template x-for="order in tabOrders" :key="order.id">
                            <tr class="hover:bg-gray-50 cursor-pointer" @click="viewOrder(order)">
                                <td class="px-4 py-3 text-sm font-semibold text-gray-900">
                                    <div class="flex items-center space-x-2">
                                        <span x-text="order.external_order_id || '—'"></span>
                                    </div>
                                </td>
                                <td class="px-4 py-3 text-sm">
                                    <span class="px-2 py-1 rounded-full text-xs font-semibold" :class="statusClass(order)" x-text="statusLabel(order)"></span>
                                </td>
                                <td class="px-4 py-3 text-sm text-gray-900">
                                    <div class="font-medium" x-text="formatUzumDisplay(order.raw_payload?.dateCreated || order.ordered_at)"></div>
                                </td>
                                <template x-if="activeTab === 'new'">
                                    <td class="px-4 py-3 text-sm text-gray-900">
                                        <div>
                                            <div class="font-medium" x-text="formatUzumDisplay(order.raw_payload?.acceptUntil)"></div>
                                            <div class="text-xs text-gray-500" x-text="timeLeft(order.raw_payload?.acceptUntil)"></div>
                                        </div>
                                    </td>
                                </template>
                                <td class="px-4 py-3 text-sm text-gray-900">
                                    <div>
                                        <div class="font-medium" x-text="formatUzumDisplay(order.raw_payload?.deliverUntil || order.raw_payload?.deliveryDate || order.raw_payload?.deliveringDate)"></div>
                                        <div class="text-xs text-gray-500" x-text="timeLeft(order.raw_payload?.deliverUntil || order.raw_payload?.deliveryDate || order.raw_payload?.deliveringDate)"></div>
                                    </div>
                                </td>
                                <td class="px-4 py-3 text-sm text-gray-900">
                            <div class="space-y-2">
                                <template x-for="(item, idx) in uzumItems(order)" :key="item.id || idx">
                                    <div class="flex items-center space-x-3">
                                        <template x-if="uzumItemImage(item)">
                                            <img :src="uzumItemImage(item)" class="w-12 h-12 rounded-lg object-cover border border-gray-200" alt="">
                                        </template>
                                        <div class="flex-1">
                                            <div class="font-medium" x-text="(item.skuTitle || item.productTitle || '—').slice(0, 80)"></div>
                                            <div class="text-xs text-gray-600">
                                                <span class="font-semibold">SKU:</span>
                                                <span x-text="item.skuId || item.productId || item.barcode || '—'"></span>
                                            </div>
                                            <div class="text-xs text-gray-500" x-text="parseInt(item.amount || 0) + ' шт'"></div>
                                        </div>
                                    </div>
                                </template>
                                <template x-if="uzumItems(order).length === 0">
                                            <div class="text-xs text-gray-500">Состав не указан</div>
                                        </template>
                                    </div>
                                </td>
                                <td class="px-4 py-3 text-sm text-gray-900">
                                    <div x-text="order.raw_payload?.stock?.address || 'Склад или пункт приёма'"></div>
                                </td>
                                <td class="px-4 py-3 text-sm text-gray-900">
                                    <div class="font-medium" x-text="getShopName(order)"></div>
                                    <div class="text-xs text-gray-500" x-show="order.raw_payload?.shopId">ID: <span x-text="order.raw_payload?.shopId"></span></div>
                                </td>
                                <td class="px-4 py-3 text-sm text-gray-900 space-x-2">
                                    <div class="inline-flex space-x-2">
                                        <template x-if="activeTab === 'new'">
                                            <span class="inline-flex space-x-2">
                                                <button @click.stop="handleTakeOrder(order)" class="px-3 py-1 bg-blue-50 text-blue-700 rounded-lg text-xs font-semibold hover:bg-blue-100">Взять в работу</button>
                                                <button @click.stop="openCancelModal(order)" class="px-2 py-1 bg-red-50 text-red-700 rounded-lg text-xs font-semibold hover:bg-red-100" title="Отменить заказ">
                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                                                    </svg>
                                                </button>
                                            </span>
                                        </template>
                                        <template x-if="activeTab === 'in_assembly'">
                                            <span class="inline-flex space-x-2">
                                                <button @click.stop="printOrderSticker(order)" class="px-3 py-1 bg-blue-50 text-blue-700 rounded-lg text-xs font-semibold hover:bg-blue-100">Печатать этикетки</button>
                                                <button @click.stop="openCancelModal(order)" class="px-2 py-1 bg-red-50 text-red-700 rounded-lg text-xs font-semibold hover:bg-red-100" title="Отменить заказ">
                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                                                    </svg>
                                                </button>
                                            </span>
                                        </template>
                                    </div>
                                </td>
                            </tr>
                        </template>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Orders List (with supply grouping for "На сборке" tab) - WB only -->
            <div x-show="isWb() && !loading && activeTab === 'in_assembly' && deliveryTypeFilter !== 'fbo'" class="space-y-6">
                <!-- Create Supply Button -->
                <div class="flex justify-end mb-4">
                    <button @click="openCreateSupplyModal()"
                            class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg transition flex items-center space-x-2">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                        </svg>
                        <span>Создать поставку</span>
                    </button>
                </div>

                <!-- All Open Supplies (draft, in_assembly, and ready) (as accordion) -->
                <template x-for="supply in supplies.filter(s => s.status === 'draft' || s.status === 'in_assembly' || s.status === 'ready')" :key="supply.id">
                    <div class="bg-white rounded-xl border-2 border-gray-300 overflow-hidden">
                        <div @click="toggleSupply(supply.external_supply_id || supply.id)"
                             class="bg-gradient-to-r from-orange-50 to-orange-100 border-b border-orange-200 p-5 cursor-pointer hover:from-orange-100 hover:to-orange-150 transition">
                            <div class="flex items-center justify-between">
                                <div class="flex items-center space-x-4">
                                    <svg class="w-8 h-8 text-orange-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
                                    </svg>
                                    <div>
                                        <div class="flex items-center space-x-2 mb-1">
                                            <h3 class="text-lg font-bold text-gray-900" x-text="supply.name || 'Поставка'"></h3>
                                            <span class="text-sm text-gray-500 font-mono" x-text="'#' + (supply.external_supply_id || supply.id)"></span>
                                        </div>
                                        <p class="text-sm text-gray-600">
                                            <span x-text="supply.orders_count || 0"></span> заказ(ов)
                                            <template x-if="supply.total_amount && supply.total_amount > 0">
                                                <span> • <span x-text="formatMoney(supply.total_amount / 100)"></span></span>
                                            </template>
                                        </p>
                                    </div>
                                </div>
                                <div class="flex items-center space-x-3">
                                    <a x-show="supply.external_supply_id && supply.external_supply_id.startsWith('WB-')"
                                       :href="`/api/marketplace/supplies/${supply.id}/barcode?type=png`"
                                       @click.stop
                                       target="_blank"
                                       class="px-2 py-1 bg-green-500 text-white text-xs rounded hover:bg-green-600 transition flex items-center space-x-1">
                                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/>
                                        </svg>
                                        <span>Стикер</span>
                                    </a>
                                    <button @click.stop="openCreateTareModal(supply)"
                                            class="px-3 py-1 bg-blue-500 text-white text-xs rounded hover:bg-blue-600 transition flex items-center space-x-1">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
                                        </svg>
                                        <span>Коробки</span>
                                    </button>
                                    <span class="px-2 py-1 text-xs font-semibold rounded-full"
                                          :class="{
                                              'bg-gray-100 text-gray-800': supply.status === 'draft',
                                              'bg-blue-100 text-blue-800': supply.status === 'in_assembly',
                                              'bg-green-100 text-green-800': supply.status === 'ready',
                                              'bg-purple-100 text-purple-800': supply.status === 'sent',
                                              'bg-emerald-100 text-emerald-800': supply.status === 'delivered'
                                          }"
                                          x-text="getStatusText(supply.status)">
                                    </span>
                                    <span class="px-3 py-1 bg-orange-200 text-orange-800 rounded-full text-sm font-semibold">
                                        <span x-text="supply.orders_count || 0"></span> шт
                                    </span>
                                    <svg class="w-6 h-6 text-gray-500 transition-transform"
                                         :class="{'rotate-180': isSupplyExpanded(supply.external_supply_id || supply.id)}"
                                         fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                                    </svg>
                                </div>
                            </div>
                        </div>
                        <div x-show="isSupplyExpanded(supply.external_supply_id || supply.id)" class="divide-y divide-gray-100" x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100">
                            <!-- Empty state -->
                            <div x-show="tabOrders.filter(o => o.supply_id === supply.external_supply_id || o.supply_id === ('SUPPLY-' + supply.id)).length === 0" class="p-8 text-center bg-gray-50">
                                <p class="text-gray-500">Нет заказов в этой поставке</p>
                            </div>

                            <template x-for="order in tabOrders.filter(o => o.supply_id === supply.external_supply_id || o.supply_id === ('SUPPLY-' + supply.id))" :key="order.id">
                                <div class="p-5 hover:bg-gray-50 transition border-b border-gray-100">
                                    <div class="flex items-start justify-between mb-4">
                                        <div class="flex-1 flex items-start space-x-4 cursor-pointer" @click="viewOrder(order)">
                                            <!-- Product Image -->
                                            <img x-show="order.photo_url || order.nm_id"
                                                 :src="order.photo_url || getWbProductImageUrl(order.nm_id || order.wb_nm_id)"
                                                 :alt="order.article || order.wb_article"
                                                 class="w-20 h-20 object-cover rounded-lg border border-gray-200"
                                                 loading="lazy"
                                                 x-on:error.once="handleImageError($event)">
                                            <div class="flex-1">
                                                <div class="flex items-center space-x-3 mb-2">
                                                    <h4 class="text-base font-bold text-gray-900">
                                                        #<span x-text="order.external_order_id"></span>
                                                    </h4>
                                                <span class="px-2 py-1 text-xs font-semibold rounded-full"
                                                      :class="statusClass(order)"
                                                      x-text="statusLabel(order)"></span>
                                                <span x-show="order.supply_id" class="px-2 py-1 text-xs bg-purple-100 text-purple-700 rounded-full flex items-center space-x-1">
                                                    <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20">
                                                        <path d="M3 1a1 1 0 000 2h1.22l.305 1.222a.997.997 0 00.01.042l1.358 5.43-.893.892C3.74 11.846 4.632 14 6.414 14H15a1 1 0 000-2H6.414l1-1H14a1 1 0 00.894-.553l3-6A1 1 0 0017 3H6.28l-.31-1.243A1 1 0 005 1H3z"/>
                                                    </svg>
                                                    <span x-text="'В поставке'"></span>
                                                </span>
                                                </div>
                                                <div class="space-y-2">
                                                    <!-- Название товара и мета -->
                                                    <div>
                                                        <div class="font-semibold text-gray-900" x-text="order.product_name || order.article || order.wb_article || '-'"></div>
                                                        <div class="text-xs text-gray-500" x-text="order.meta_info || order.article || '-'"></div>
                                                    </div>
                                                    <!-- Информация в grid -->
                                                    <div class="grid grid-cols-2 md:grid-cols-3 gap-3 text-sm">
                                                        <div>
                                                            <div class="text-xs text-gray-500">Артикул</div>
                                                            <div class="font-medium text-gray-900" x-text="order.article || order.wb_article || '-'"></div>
                                                        </div>
                                                        <div>
                                                            <div class="text-xs text-gray-500">NM ID</div>
                                                            <div class="font-medium text-gray-900" x-text="order.nm_id || order.wb_nm_id || '-'"></div>
                                                        </div>
                                                        <div>
                                                            <div class="text-xs text-gray-500">Время</div>
                                                            <div class="text-xs text-gray-900" x-text="order.time_elapsed || formatDateTime(order.ordered_at)"></div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="flex items-center space-x-3 ml-4">
                                            <div class="text-right">
                                                <div class="text-xl font-bold text-gray-900" x-text="(order.total_amount || order.wb_final_price || 0) + ' ' + (order.currency || 'RUB')"></div>
                                            </div>
                                            <!-- Action Buttons -->
                                            <div class="flex flex-col space-y-2">
                                                <button x-show="!order.supply_id && (order.status === 'new' || order.wb_status_group === 'new' || order.wb_status_group === 'assembling')"
                                                        @click.stop="openAddToSupplyModal(order)"
                                                        class="px-3 py-1.5 bg-blue-600 hover:bg-blue-700 text-white text-xs rounded-lg transition flex items-center space-x-1"
                                                        title="Добавить в поставку">
                                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                                                    </svg>
                                                    <span>В поставку</span>
                                                </button>
                                                <button x-show="order.supply_id"
                                                        @click.stop="removeOrderFromSupply(order)"
                                                        class="px-3 py-1.5 bg-red-100 hover:bg-red-200 text-red-700 text-xs rounded-lg transition flex items-center space-x-1"
                                                        title="Убрать из поставки">
                                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                                                    </svg>
                                                    <span>Убрать</span>
                                                </button>
                                                <!-- Print Sticker Button -->
                                                <button @click.stop="printOrderSticker(order)"
                                                        class="px-3 py-1.5 bg-purple-600 hover:bg-purple-700 text-white text-xs rounded-lg transition flex items-center space-x-1"
                                                        title="Печать стикера">
                                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/>
                                                    </svg>
                                                    <span x-text="order.sticker_path ? 'Скачать' : 'Печать'"></span>
                                                </button>
                                                <!-- Cancel Order Button -->
                                                <button x-show="order.status !== 'completed' && order.status !== 'canceled'"
                                                        @click.stop="openCancelModal(order)"
                                                        class="px-3 py-1.5 bg-red-600 hover:bg-red-700 text-white text-xs rounded-lg transition flex items-center space-x-1"
                                                        title="Отменить заказ">
                                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                                                    </svg>
                                                    <span>Отменить</span>
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </template>

                            <!-- Supply Actions Panel -->
                            <div x-show="isSupplyExpanded(supply.external_supply_id || supply.id)"
                                 class="bg-gray-50 px-5 py-4 border-t border-gray-200">
                                <div class="flex items-center justify-between">
                                    <div class="text-sm text-gray-600">
                                        <span class="font-semibold">ID WB:</span>
                                        <span class="font-mono" x-text="supply.external_supply_id || 'Не синхронизировано'"></span>
                                    </div>
                                    <div class="flex items-center space-x-2">
                                        <!-- Закрыть поставку (только для draft/in_assembly) -->
                                        <button x-show="supply.status === 'draft' || supply.status === 'in_assembly'"
                                                @click.stop="closeSupplyFromAccordion(supply.id)"
                                                class="px-4 py-2 bg-green-600 hover:bg-green-700 text-white text-sm rounded-lg transition flex items-center space-x-2"
                                                :disabled="supply.orders_count === 0">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                            </svg>
                                            <span>Закрыть поставку</span>
                                        </button>

                                        <!-- Передать в доставку (только для ready) -->
                                        <button x-show="supply.status === 'ready'"
                                                @click.stop="showDeliverModal(supply)"
                                                class="px-4 py-2 bg-purple-600 hover:bg-purple-700 text-white text-sm rounded-lg transition flex items-center space-x-2">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6"/>
                                            </svg>
                                            <span>Передать в доставку</span>
                                        </button>

                                        <!-- Скачать баркод -->
                                        <button x-show="supply.barcode_path && supply.external_supply_id"
                                                @click.stop="window.open(`/api/marketplace/supplies/${supply.id}/barcode?token=${$store.auth.token}`, '_blank')"
                                                class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white text-sm rounded-lg transition flex items-center space-x-2">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m0 0l-4-4m4 4l4-4"/>
                                            </svg>
                                            <span>Скачать QR</span>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </template>

                <!-- No Open Supplies Message -->
                <div x-show="supplies.filter(s => s.status === 'draft' || s.status === 'in_assembly' || s.status === 'ready').length === 0"
                     class="bg-white rounded-lg border-2 border-dashed border-gray-300 p-8 text-center">
                    <svg class="w-12 h-12 mx-auto text-gray-400 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4"/>
                    </svg>
                    <h4 class="text-lg font-semibold text-gray-900 mb-2">Нет открытых поставок</h4>
                    <p class="text-gray-600 mb-4">Создайте поставку, чтобы начать добавлять заказы для отправки на склад WB</p>
                    <button @click="openCreateSupplyModal()"
                            class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg transition inline-flex items-center space-x-2">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                        </svg>
                        <span>Создать первую поставку</span>
                    </button>
                </div>

                <!-- Orders without supply -->
                <div x-show="groupedBySupply.withoutSupply.length > 0" class="space-y-4">
                    <h3 class="text-lg font-semibold text-gray-700 px-1">Без поставки</h3>
                    <template x-for="order in groupedBySupply.withoutSupply" :key="order.id">
                        <div class="bg-white rounded-xl border border-gray-200 hover:border-blue-300 hover:shadow-md transition overflow-hidden">
                            <div class="p-5">
                                <div class="flex items-start justify-between mb-4">
                                    <div class="flex-1 flex items-start space-x-4 cursor-pointer" @click="viewOrder(order)">
                                        <!-- Product Image -->
                                        <img x-show="loadImages && (order.photo_url || order.nm_id)"
                                             :src="loadImages ? (order.photo_url || getWbProductImageUrl(order.nm_id || order.wb_nm_id)) : ''"
                                             :alt="order.article || order.wb_article"
                                             class="w-24 h-24 object-cover rounded-lg border border-gray-200"
                                             loading="lazy"
                                             x-on:error.once="handleImageError($event)">
                                        <div class="flex-1">
                                            <div class="flex items-center space-x-3 mb-2">
                                                <h3 class="text-lg font-bold text-gray-900">
                                                    Заказ #<span x-text="order.external_order_id"></span>
                                                </h3>
                                                <span class="px-3 py-1 text-xs font-semibold rounded-full bg-purple-100 text-purple-700"
                                                      x-text="getDeliveryTypeName(order.wb_delivery_type)"></span>
                                                <span class="px-3 py-1 text-xs font-semibold rounded-full"
                                                      :class="statusClass(order)"
                                                      x-text="statusLabel(order)"></span>
                                            </div>
                                            <div class="space-y-2 mb-3">
                                                <!-- Название товара и мета -->
                                                <div>
                                                    <div class="font-semibold text-gray-900" x-text="order.product_name || order.article || order.wb_article || '-'"></div>
                                                    <div class="text-xs text-gray-500" x-text="order.meta_info || order.article || '-'"></div>
                                                </div>
                                                <!-- Время с момента заказа -->
                                                <div class="flex items-center space-x-2 text-sm text-gray-600">
                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                                    </svg>
                                                    <span x-text="order.time_elapsed || formatDateTime(order.ordered_at)"></span>
                                                </div>
                                            </div>
                                            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                                                <div>
                                                    <div class="text-xs text-gray-500 mb-1">Артикул</div>
                                                    <div class="font-medium text-gray-900" x-text="order.article || order.wb_article || '-'"></div>
                                                </div>
                                                <div>
                                                    <div class="text-xs text-gray-500 mb-1">SKU</div>
                                                    <div class="font-mono text-sm text-gray-900" x-text="order.sku || order.wb_skus?.[0] || '-'"></div>
                                                </div>
                                                <div>
                                                    <div class="text-xs text-gray-500 mb-1">NM ID</div>
                                                    <div class="font-medium text-gray-900" x-text="order.nm_id || order.wb_nm_id || '-'"></div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="flex items-center space-x-3 ml-4">
                                        <div class="text-right">
                                            <div class="text-2xl font-bold text-gray-900" x-text="(order.total_amount || order.wb_final_price || 0) + ' ' + (order.currency || 'RUB')"></div>
                                        </div>
                                        <!-- Action Buttons -->
                                        <div class="flex items-center space-x-2">
                                            <button x-show="order.status === 'new' || order.wb_status_group === 'new' || order.wb_status_group === 'assembling'"
                                                    @click.stop="openAddToSupplyModal(order)"
                                                    class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white text-sm rounded-lg transition flex items-center space-x-2"
                                                    title="Добавить в поставку">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                                                </svg>
                                                <span>Добавить в поставку</span>
                                            </button>
                                            <button @click.stop="printOrderSticker(order)"
                                                    class="px-4 py-2 bg-purple-600 hover:bg-purple-700 text-white text-sm rounded-lg transition flex items-center space-x-2"
                                                    title="Печать стикера">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/>
                                                </svg>
                                                <span x-text="order.sticker_path ? 'Скачать' : 'Печать'"></span>
                                            </button>
                                            <button x-show="order.status !== 'completed' && order.status !== 'canceled'"
                                                    @click.stop="openCancelModal(order)"
                                                    class="px-4 py-2 bg-red-600 hover:bg-red-700 text-white text-sm rounded-lg transition flex items-center space-x-2"
                                                    title="Отменить заказ">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                                                </svg>
                                                <span>Отменить</span>
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </template>
                </div>
            </div>

            <!-- Shipping Tab - Grouped by Supply -->
            <div x-show="isWb() && !loading && tabOrders.length > 0 && activeTab === 'in_delivery' && deliveryTypeFilter !== 'fbo'" class="space-y-6">
                <!-- Supplies with Orders -->
                <template x-for="supply in supplies.filter(s => s.status === 'sent' && tabOrders.some(o => o.supply_id === s.external_supply_id))" :key="supply.id">
                    <div class="bg-white rounded-xl border-2 border-gray-300 overflow-hidden">
                        <!-- Supply Header -->
                        <div @click="toggleSupply(supply.external_supply_id || supply.id)"
                             class="bg-gradient-to-r from-blue-50 to-blue-100 border-b border-blue-200 p-5 cursor-pointer hover:from-blue-100 hover:to-blue-200 transition">
                            <div class="flex items-center justify-between">
                                <div class="flex items-center space-x-4">
                                    <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 8h14M5 8a2 2 0 110-4h14a2 2 0 110 4M5 8v10a2 2 0 002 2h10a2 2 0 002-2V8m-9 4h4"/>
                                    </svg>
                                    <div>
                                        <div class="flex items-center space-x-2 mb-1">
                                            <h3 class="text-lg font-bold text-gray-900" x-text="supply.name || 'Поставка'"></h3>
                                            <span class="text-sm text-gray-500 font-mono" x-text="'#' + (supply.external_supply_id || supply.id)"></span>
                                        </div>
                                        <p class="text-sm text-gray-600">
                                            <span x-text="supply.orders_count || 0"></span> заказ(ов)
                                            <template x-if="supply.total_amount && supply.total_amount > 0">
                                                <span> • <span x-text="formatMoney(supply.total_amount / 100)"></span></span>
                                            </template>
                                        </p>
                                    </div>
                                </div>
                                <div class="flex items-center space-x-4">
                                    <a x-show="supply.external_supply_id && supply.external_supply_id.startsWith('WB-')"
                                       :href="`/api/marketplace/supplies/${supply.id}/barcode?type=png`"
                                       @click.stop
                                       target="_blank"
                                       class="px-2 py-1 bg-green-500 text-white text-xs rounded hover:bg-green-600 transition flex items-center space-x-1">
                                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/>
                                        </svg>
                                        <span>Стикер</span>
                                    </a>
                                    <button @click.stop="openCreateTareModal(supply)"
                                            class="px-3 py-1 bg-blue-500 text-white text-xs rounded hover:bg-blue-600 transition flex items-center space-x-1">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
                                        </svg>
                                        <span>Коробки</span>
                                    </button>
                                    <span class="px-3 py-1 text-xs font-semibold rounded-full bg-blue-600 text-white">
                                        В доставке
                                    </span>
                                    <svg class="w-5 h-5 text-gray-500 transform transition-transform"
                                         :class="{'rotate-180': isSupplyExpanded(supply.external_supply_id || supply.id)}"
                                         fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                                    </svg>
                                </div>
                            </div>
                        </div>

                        <!-- Orders in Supply -->
                        <div x-show="isSupplyExpanded(supply.external_supply_id || supply.id)" x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100">
                            <template x-for="order in tabOrders.filter(o => o.supply_id === supply.external_supply_id)" :key="order.id">
                                <div class="border-b border-gray-200 last:border-b-0 hover:bg-gray-50 transition">
                                    <div class="p-5">
                                        <div class="flex items-start justify-between">
                                            <div class="flex-1 flex items-start space-x-4 cursor-pointer" @click="viewOrder(order)">
                                                <!-- Product Image -->
                                                <img x-show="order.photo_url || order.nm_id"
                                                     :src="order.photo_url || getWbProductImageUrl(order.nm_id || order.wb_nm_id)"
                                                     :alt="order.article || order.wb_article"
                                                     class="w-20 h-20 object-cover rounded-lg border border-gray-200"
                                                     loading="lazy"
                                                     x-on:error.once="handleImageError($event)">
                                                <div class="flex-1">
                                                    <div class="flex items-center space-x-3 mb-2">
                                                        <h3 class="text-base font-bold text-gray-900">
                                                            Заказ #<span x-text="order.external_order_id"></span>
                                                        </h3>
                                                        <span class="px-2 py-1 text-xs font-semibold rounded-full bg-purple-100 text-purple-700"
                                                              x-text="getDeliveryTypeName(order.wb_delivery_type)"></span>
                                                        <span class="px-2 py-1 text-xs font-semibold rounded-full"
                                                              :class="statusClass(order)"
                                                              x-text="statusLabel(order)"></span>
                                                    </div>
                                                    <div class="space-y-2 mb-2">
                                                        <!-- Название товара и мета -->
                                                        <div>
                                                            <div class="font-semibold text-gray-900" x-text="order.product_name || order.article || order.wb_article || '-'"></div>
                                                            <div class="text-xs text-gray-500" x-text="order.meta_info || order.article || '-'"></div>
                                                        </div>
                                                    </div>
                                                    <div class="grid grid-cols-3 gap-3 text-sm">
                                                        <div>
                                                            <div class="text-xs text-gray-500">Артикул</div>
                                                            <div class="font-medium text-gray-900" x-text="order.article || order.wb_article || '-'"></div>
                                                        </div>
                                                        <div>
                                                            <div class="text-xs text-gray-500">NM ID</div>
                                                            <div class="font-medium text-gray-900" x-text="order.nm_id || order.wb_nm_id || '-'"></div>
                                                        </div>
                                                        <div>
                                                            <div class="text-xs text-gray-500">Время</div>
                                                            <div class="text-xs text-gray-900" x-text="order.time_elapsed || formatDateTime(order.ordered_at)"></div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="flex items-center space-x-3 ml-4">
                                                <div class="text-right">
                                                    <div class="text-xl font-bold text-gray-900" x-text="(order.total_amount || order.wb_final_price || 0) + ' ' + (order.currency || 'RUB')"></div>
                                                </div>
                                                <!-- Action Buttons -->
                                                <div class="flex flex-col space-y-2">
                                                    <button @click.stop="printOrderSticker(order)"
                                                            class="px-3 py-1.5 bg-purple-600 hover:bg-purple-700 text-white text-xs rounded-lg transition flex items-center space-x-1"
                                                            title="Печать стикера">
                                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/>
                                                        </svg>
                                                        <span x-text="order.sticker_path ? 'Скачать' : 'Печать'"></span>
                                                    </button>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </template>

                            <!-- Supply Info Panel -->
                            <div x-show="isSupplyExpanded(supply.external_supply_id || supply.id)"
                                 class="bg-gray-50 px-5 py-4 border-t border-gray-200">
                                <div class="flex items-center justify-between">
                                    <div class="text-sm text-gray-600">
                                        <span class="font-semibold">ID WB:</span>
                                        <span class="font-mono" x-text="supply.external_supply_id || 'Не синхронизировано'"></span>
                                    </div>
                                    <div class="flex items-center space-x-2">
                                        <!-- Скачать баркод -->
                                        <button x-show="supply.barcode_path && supply.external_supply_id"
                                                @click.stop="window.open(`/api/marketplace/supplies/${supply.id}/barcode?token=${$store.auth.token}`, '_blank')"
                                                class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white text-sm rounded-lg transition flex items-center space-x-2">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                                            </svg>
                                            <span>Скачать баркод</span>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </template>

                <!-- No Supplies Message -->
                <div x-show="supplies.filter(s => s.status === 'sent').length === 0"
                     class="bg-white rounded-lg border-2 border-dashed border-gray-300 p-8 text-center">
                    <svg class="w-12 h-12 mx-auto text-gray-400 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 8h14M5 8a2 2 0 110-4h14a2 2 0 110 4M5 8v10a2 2 0 002 2h10a2 2 0 002-2V8m-9 4h4"/>
                    </svg>
                    <h4 class="text-lg font-semibold text-gray-900 mb-2">Нет поставок в доставке</h4>
                    <p class="text-gray-600">Поставки появятся здесь после передачи в доставку</p>
                </div>
            </div>

            <!-- Orders List (for all other tabs except in_assembly and in_delivery) -->
            <div x-show="!isUzum() && !loading && tabOrders.length > 0 && activeTab !== 'in_assembly' && activeTab !== 'in_delivery' && deliveryTypeFilter !== 'fbo'" class="space-y-4">
                <template x-for="order in tabOrders" :key="order.id">
                    <div class="bg-white rounded-xl border border-gray-200 hover:border-blue-300 hover:shadow-md transition overflow-hidden">
                        <div class="p-5 cursor-pointer" @click="viewOrder(order)">
                            <div class="flex items-start justify-between mb-4">
                                <div class="flex-1 flex items-start space-x-4">
                                    <!-- Product Image -->
                                    <img x-show="loadImages && order.wb_nm_id"
                                         :src="loadImages ? getWbProductImageUrl(order.wb_nm_id) : ''"
                                         :alt="order.wb_article"
                                         :data-nmid="order.wb_nm_id"
                                         data-size="tm"
                                         class="w-24 h-24 object-cover rounded-lg border border-gray-200 flex-shrink-0"
                                         loading="lazy"
                                         x-on:error.once="handleImageError($event)">
                                    <div class="flex-1">
                                    <div class="flex items-center space-x-3 mb-2">
                                        <h3 class="text-lg font-bold text-gray-900">
                                            Заказ #<span x-text="order.external_order_id"></span>
                                        </h3>
                                        <span class="px-3 py-1 text-xs font-semibold rounded-full bg-purple-100 text-purple-700"
                                              x-text="getDeliveryTypeName(order.wb_delivery_type)"></span>
                                        <span class="px-3 py-1 text-xs font-semibold rounded-full"
                                              :class="statusClass(order)"
                                              x-text="statusLabel(order)"></span>
                                        <!-- Supply Status Badge -->
                                        <span x-show="order.supply_id"
                                              class="px-3 py-1 text-xs font-semibold rounded-full bg-purple-100 text-purple-700">
                                            В поставке
                                        </span>
                                    </div>
                                    <div class="flex items-center space-x-4 text-sm text-gray-600">
                                        <span class="flex items-center">
                                            <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                            </svg>
                                            <span x-text="formatDateTime(order.wb_created_at_utc || order.ordered_at)"></span>
                                        </span>
                                        <span class="flex items-center" x-show="order.wb_warehouse_id">
                                            <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
                                            </svg>
                                            <span>Склад: <span x-text="order.wb_warehouse_id"></span></span>
                                        </span>
                                        <span class="flex items-center" x-show="order.wb_order_uid">
                                            <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 20l4-16m2 16l4-16M6 9h14M4 15h14"/>
                                            </svg>
                                            <span class="font-mono text-xs" x-text="order.wb_order_uid"></span>
                                        </span>
                                    </div>
                                    </div>
                                </div>
                                <div class="flex items-start space-x-4 ml-4">
                                    <div class="text-right">
                                        <div class="text-2xl font-bold text-gray-900" x-text="formatPrice(order.wb_final_price)"></div>
                                        <div class="text-sm text-gray-500" x-show="order.wb_final_price !== order.wb_sale_price">
                                            <span class="line-through" x-text="formatPrice(order.wb_sale_price)"></span>
                                        </div>
                                    </div>
                                    <!-- Action Buttons -->
                                    <div class="flex flex-col space-y-2">
                                        <button x-show="!order.supply_id && (order.status === 'new' || order.wb_status_group === 'new' || order.wb_status_group === 'assembling')"
                                                @click.stop="openAddToSupplyModal(order)"
                                                class="px-3 py-1.5 bg-blue-600 hover:bg-blue-700 text-white text-xs rounded-lg transition flex items-center space-x-1"
                                                title="Добавить в поставку">
                                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                                            </svg>
                                            <span>В поставку</span>
                                        </button>
                                        <button x-show="order.supply_id"
                                                @click.stop="removeOrderFromSupply(order)"
                                                class="px-3 py-1.5 bg-red-100 hover:bg-red-200 text-red-700 text-xs rounded-lg transition flex items-center space-x-1"
                                                title="Убрать из поставки">
                                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                                            </svg>
                                            <span>Убрать</span>
                                        </button>
                                        <!-- Print Sticker Button -->
                                        <button @click.stop="printOrderSticker(order)"
                                                class="px-3 py-1.5 bg-purple-600 hover:bg-purple-700 text-white text-xs rounded-lg transition flex items-center space-x-1"
                                                title="Печать стикера">
                                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/>
                                            </svg>
                                            <span x-text="order.sticker_path ? 'Скачать' : 'Печать'"></span>
                                        </button>
                                        <!-- Cancel Order Button -->
                                        <button x-show="order.status !== 'completed' && order.status !== 'canceled'"
                                                @click.stop="openCancelModal(order)"
                                                class="px-3 py-1.5 bg-red-600 hover:bg-red-700 text-white text-xs rounded-lg transition flex items-center space-x-1"
                                                title="Отменить заказ">
                                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                                            </svg>
                                            <span>Отменить</span>
                                        </button>
                                    </div>
                                </div>
                            </div>

                            <div class="grid grid-cols-1 md:grid-cols-4 gap-4 pt-4 border-t border-gray-100">
                                <div>
                                    <div class="text-xs text-gray-500 mb-1">Артикул</div>
                                    <div class="font-medium text-gray-900" x-text="order.wb_article || '-'"></div>
                                </div>
                                <div>
                                    <div class="text-xs text-gray-500 mb-1">SKU</div>
                                    <div class="font-mono text-sm text-gray-900" x-text="order.wb_skus?.[0] || '-'"></div>
                                </div>
                                <div>
                                    <div class="text-xs text-gray-500 mb-1">NM ID</div>
                                    <div class="font-medium text-gray-900" x-text="order.wb_nm_id || '-'"></div>
                                </div>
                                <div>
                                    <div class="text-xs text-gray-500 mb-1">Chrt ID</div>
                                    <div class="font-medium text-gray-900" x-text="order.wb_chrt_id || '-'"></div>
                                </div>
                            </div>

                            <div class="mt-4 flex items-center justify-between">
                                <div class="flex items-center space-x-2">
                                    <span x-show="order.wb_is_zero_order"
                                          class="px-2 py-1 text-xs font-medium rounded bg-red-100 text-red-700">
                                        Нулевой выкуп
                                    </span>
                                    <span x-show="order.wb_is_b2b"
                                          class="px-2 py-1 text-xs font-medium rounded bg-blue-100 text-blue-700">
                                        B2B
                                    </span>
                                </div>
                                <button @click.stop="viewOrder(order)"
                                        class="px-4 py-2 text-sm font-medium text-blue-600 hover:text-blue-700 hover:bg-blue-50 rounded-lg transition">
                                    Подробнее →
                                </button>
                            </div>
                        </div>
                    </div>
                </template>
            </div>

            <!-- FBO Orders Section (Склад маркетплейса) -->
            <div x-show="deliveryTypeFilter === 'fbo'" class="space-y-4">
                <!-- FBO Loading State -->
                <div x-show="fboLoading" class="flex justify-center items-center py-12">
                    <div class="animate-spin rounded-full h-12 w-12 border-4 border-purple-200 border-t-purple-600"></div>
                </div>

                <!-- FBO Stats Header -->
                <div x-show="!fboLoading" class="bg-gradient-to-r from-purple-50 to-purple-100 rounded-xl p-6 border border-purple-200">
                    <div class="flex items-center justify-between mb-4">
                        <div class="flex items-center space-x-3">
                            <div class="p-3 bg-purple-600 rounded-lg">
                                <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
                                </svg>
                            </div>
                            <div>
                                <h3 class="text-lg font-bold text-purple-900" x-text="isWb() ? 'FBO заказы (Склад WB)' : 'FBO заказы (Склад Uzum)'"></h3>
                                <p class="text-sm text-purple-700" x-text="isWb() ? 'Заказы, отгруженные со склада Wildberries' : 'Заказы, отгруженные со склада Uzum (Fulfillment)'"></p>
                            </div>
                        </div>
                        <button @click="loadFboOrders()" class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg text-sm font-medium transition flex items-center space-x-2">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                            </svg>
                            <span>Обновить</span>
                        </button>
                    </div>

                    <!-- FBO Stats -->
                    <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                        <div class="bg-white rounded-lg p-4 border border-purple-200">
                            <div class="text-2xl font-bold text-purple-900" x-text="fboStats.total || 0"></div>
                            <div class="text-sm text-purple-600">Всего заказов</div>
                        </div>
                        <div class="bg-white rounded-lg p-4 border border-green-200">
                            <div class="text-2xl font-bold text-green-700" x-text="fboStats.by_status?.completed || 0"></div>
                            <div class="text-sm text-green-600">Выполнено</div>
                        </div>
                        <div class="bg-white rounded-lg p-4 border border-red-200">
                            <div class="text-2xl font-bold text-red-700" x-text="fboStats.by_status?.cancelled || 0"></div>
                            <div class="text-sm text-red-600">Отменено</div>
                        </div>
                        <div class="bg-white rounded-lg p-4 border border-orange-200">
                            <div class="text-2xl font-bold text-orange-700" x-text="fboStats.by_status?.returned || 0"></div>
                            <div class="text-sm text-orange-600">Возвраты</div>
                        </div>
                    </div>
                </div>

                <!-- FBO Orders List -->
                <div x-show="!fboLoading && fboOrders.length === 0" class="bg-white rounded-xl border border-gray-200 p-12 text-center">
                    <svg class="w-16 h-16 mx-auto text-gray-400 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
                    </svg>
                    <h4 class="text-lg font-semibold text-gray-900 mb-2">Нет FBO заказов</h4>
                    <p class="text-gray-600">FBO заказы появятся здесь после синхронизации данных из Statistics API</p>
                </div>

                <div x-show="!fboLoading && fboOrders.length > 0" class="space-y-4">
                    <template x-for="order in fboOrders" :key="order.id">
                        <div class="bg-white rounded-xl border border-gray-200 hover:border-purple-300 hover:shadow-md transition overflow-hidden">
                            <div class="p-5 cursor-pointer" @click="viewOrder(order)">
                                <div class="flex items-start justify-between mb-4">
                                    <div class="flex-1 flex items-start space-x-4">
                                        <!-- Product Image -->
                                        <img x-show="loadImages && order.nm_id"
                                             :src="loadImages ? getWbProductImageUrl(order.nm_id) : ''"
                                             :alt="order.article"
                                             class="w-20 h-20 object-cover rounded-lg border border-gray-200 flex-shrink-0"
                                             loading="lazy"
                                             x-on:error.once="handleImageError($event)">
                                        <div class="flex-1">
                                            <div class="flex items-center space-x-3 mb-2">
                                                <h3 class="text-lg font-bold text-gray-900">
                                                    #<span x-text="order.external_order_id"></span>
                                                </h3>
                                                <span class="px-3 py-1 text-xs font-semibold rounded-full bg-purple-100 text-purple-700">
                                                    FBO
                                                </span>
                                                <span class="px-2 py-1 text-xs font-medium rounded"
                                                      :class="{
                                                          'bg-green-100 text-green-700': order.wb_status_group === 'archive' && !order.details?.is_cancel && !order.details?.is_return,
                                                          'bg-red-100 text-red-700': order.details?.is_cancel,
                                                          'bg-orange-100 text-orange-700': order.details?.is_return
                                                      }"
                                                      x-text="order.details?.is_cancel ? 'Отменён' : (order.details?.is_return ? 'Возврат' : 'Выполнен')"></span>
                                            </div>
                                            <p class="text-gray-700 font-medium mb-1" x-text="order.product_name || 'Товар'"></p>
                                            <p class="text-sm text-gray-500" x-text="order.meta_info || ''"></p>
                                        </div>
                                        <div class="text-right">
                                            <div class="text-lg font-bold text-gray-900" x-text="formatPrice(order.total_amount)"></div>
                                            <div class="text-sm text-gray-500" x-text="formatDateTime(order.ordered_at)"></div>
                                        </div>
                                    </div>
                                </div>

                                <div class="grid grid-cols-2 md:grid-cols-5 gap-4 pt-4 border-t border-gray-100">
                                    <div>
                                        <div class="text-xs text-gray-500 mb-1">Артикул</div>
                                        <div class="font-medium text-gray-900" x-text="order.article || '-'"></div>
                                    </div>
                                    <div>
                                        <div class="text-xs text-gray-500 mb-1">Штрихкод</div>
                                        <div class="font-mono text-sm text-gray-900" x-text="order.sku || '-'"></div>
                                    </div>
                                    <div>
                                        <div class="text-xs text-gray-500 mb-1">NM ID</div>
                                        <div class="font-medium text-gray-900" x-text="order.nm_id || '-'"></div>
                                    </div>
                                    <div>
                                        <div class="text-xs text-gray-500 mb-1">Склад</div>
                                        <div class="font-medium text-gray-900" x-text="order.details?.warehouse_name || 'Склад WB'"></div>
                                    </div>
                                    <div>
                                        <div class="text-xs text-gray-500 mb-1">Регион</div>
                                        <div class="font-medium text-gray-900" x-text="order.details?.region_name || '-'"></div>
                                    </div>
                                </div>

                                <!-- FBO Financial Details -->
                                <div class="mt-4 p-3 bg-purple-50 rounded-lg">
                                    <div class="grid grid-cols-2 md:grid-cols-4 gap-4 text-sm">
                                        <div>
                                            <span class="text-purple-600">Цена:</span>
                                            <span class="font-medium text-gray-900" x-text="formatPrice(order.details?.price)"></span>
                                        </div>
                                        <div>
                                            <span class="text-purple-600">К выплате:</span>
                                            <span class="font-medium text-green-700" x-text="formatPrice(order.details?.for_pay)"></span>
                                        </div>
                                        <div>
                                            <span class="text-purple-600">Скидка:</span>
                                            <span class="font-medium text-gray-900" x-text="(order.details?.discount_percent || 0) + '%'"></span>
                                        </div>
                                        <div>
                                            <span class="text-purple-600">СПП:</span>
                                            <span class="font-medium text-gray-900" x-text="(order.details?.spp || 0) + '%'"></span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </template>
                </div>
            </div>
        </main>
    </div>

    <!-- Order Details Modal -->
    <div x-show="showOrderModal" x-cloak
         class="fixed inset-0 z-50 overflow-y-auto"
         @keydown.escape.window="showOrderModal = false">
        <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20">
            <div class="fixed inset-0 bg-black/60 backdrop-blur-sm transition-opacity" @click="showOrderModal = false"></div>

            <div class="relative bg-white rounded-2xl shadow-2xl max-w-4xl w-full max-h-[90vh] overflow-hidden">
                <!-- Header -->
                <div class="sticky top-0 bg-gradient-to-r from-blue-600 to-blue-700 px-6 py-4 border-b border-blue-500">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center space-x-3 text-white">
                            <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"/>
                            </svg>
                            <div>
                                <h2 class="text-2xl font-bold">
                                    Заказ #<span x-text="selectedOrder?.external_order_id"></span>
                                </h2>
                                <p class="text-blue-100 text-sm" x-text="formatDateTime(selectedOrder?.ordered_at)"></p>
                            </div>
                        </div>
                        <button @click="showOrderModal = false"
                                class="text-white/80 hover:text-white hover:bg-white/10 p-2 rounded-lg transition">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                            </svg>
                        </button>
                    </div>
                </div>

                <!-- Content -->
                <div class="p-6 overflow-y-auto max-h-[calc(90vh-100px)]">
                    <!-- Product Image & Status -->
                    <div class="flex items-start space-x-6 mb-6 p-6 bg-gradient-to-br from-gray-50 to-gray-100 rounded-xl border border-gray-200">
                        <img x-show="loadImages && (selectedOrder?.['Фото товара'] || selectedOrder?.['NM ID'])"
                             :src="loadImages ? (selectedOrder?.['Фото товара'] || getWbProductImageUrl(selectedOrder?.['NM ID'])) : ''"
                             :alt="selectedOrder?.['Артикул']"
                             class="w-32 h-32 object-cover rounded-lg border-2 border-white shadow-lg"
                             loading="lazy"
                             x-on:error.once="handleImageError($event)">
                        <div class="flex-1">
                            <!-- Название товара и мета -->
                            <div class="mb-4">
                                <h3 class="text-xl font-bold text-gray-900 mb-1" x-text="selectedOrder?.['Название товара'] || selectedOrder?.['Артикул']"></h3>
                                <p class="text-sm text-gray-600" x-text="selectedOrder?.['Метаинформация']"></p>
                            </div>
                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <div class="text-xs text-gray-500 mb-1">Статус</div>
                                    <span class="inline-block px-3 py-1 text-sm font-semibold rounded-full bg-blue-100 text-blue-800"
                                          x-text="selectedOrder?.['Статус'] || selectedOrder?.status || 'N/A'"></span>
                                </div>
                                <div>
                                    <div class="text-xs text-gray-500 mb-1">Группа статусов</div>
                                    <span class="inline-block px-3 py-1 text-sm font-semibold rounded-full"
                                          :class="{
                                              'bg-gray-100 text-gray-800': selectedOrder?.['Группа статусов']?.includes('Новые'),
                                              'bg-blue-100 text-blue-800': selectedOrder?.['Группа статусов']?.includes('сборке'),
                                              'bg-purple-100 text-purple-800': selectedOrder?.['Группа статусов']?.includes('доставке'),
                                              'bg-green-100 text-green-800': selectedOrder?.['Группа статусов']?.includes('Архив'),
                                              'bg-red-100 text-red-800': selectedOrder?.['Группа статусов']?.includes('Отменён')
                                          }"
                                          x-text="selectedOrder?.['Группа статусов'] || 'N/A'"></span>
                                </div>
                                <div>
                                    <div class="text-xs text-gray-500 mb-1">Время с момента заказа</div>
                                    <div class="font-semibold text-gray-900" x-text="selectedOrder?.['Время с момента заказа'] || '-'"></div>
                                </div>
                                <div>
                                    <div class="text-xs text-gray-500 mb-1">Валюта</div>
                                    <div class="font-semibold text-gray-900" x-text="selectedOrder?.['Валюта'] || 'RUB'"></div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Main Info Grid -->
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
                        <div class="bg-purple-50 rounded-xl p-4 border border-purple-100">
                            <div class="text-xs text-purple-600 font-semibold uppercase mb-1">Тип доставки</div>
                            <div class="text-lg font-bold text-purple-900" x-text="selectedOrder?.['Тип доставки'] || '-'"></div>
                        </div>
                        <div class="bg-green-50 rounded-xl p-4 border border-green-100">
                            <div class="text-xs text-green-600 font-semibold uppercase mb-1">Офис доставки</div>
                            <div class="text-lg font-bold text-green-900" x-text="selectedOrder?.['Офис доставки'] || '-'"></div>
                            <div class="text-xs text-green-600 mt-1">Склад: <span x-text="selectedOrder?.['Склад'] || '-'"></span></div>
                        </div>
                        <div class="bg-blue-50 rounded-xl p-4 border border-blue-100">
                            <div class="text-xs text-blue-600 font-semibold uppercase mb-1">Поставка</div>
                            <div class="text-sm font-mono font-bold text-blue-900 break-all" x-text="selectedOrder?.['Поставка'] || '-'"></div>
                        </div>
                    </div>

                    <!-- Product Info -->
                    <div class="bg-gradient-to-br from-gray-50 to-gray-100 rounded-xl p-6 mb-6 border border-gray-200">
                        <h3 class="text-lg font-bold text-gray-900 mb-4 flex items-center">
                            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
                            </svg>
                            Информация о товаре
                        </h3>
                        <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                            <div>
                                <div class="text-xs text-gray-500 mb-1">Артикул</div>
                                <div class="font-semibold text-gray-900" x-text="selectedOrder?.['Артикул'] || '-'"></div>
                            </div>
                            <div>
                                <div class="text-xs text-gray-500 mb-1">NM ID</div>
                                <div class="font-semibold text-gray-900" x-text="selectedOrder?.['NM ID'] || '-'"></div>
                            </div>
                            <div>
                                <div class="text-xs text-gray-500 mb-1">SKU</div>
                                <div class="font-mono text-sm text-gray-900" x-text="selectedOrder?.['SKU'] || '-'"></div>
                            </div>
                            <div>
                                <div class="text-xs text-gray-500 mb-1">CHRT ID</div>
                                <div class="font-semibold text-gray-900" x-text="selectedOrder?.['CHRT ID'] || '-'"></div>
                            </div>
                        </div>
                    </div>

                    <!-- Prices -->
                    <div class="bg-gradient-to-br from-green-50 to-emerald-50 rounded-xl p-6 mb-6 border border-green-200">
                        <h3 class="text-lg font-bold text-gray-900 mb-4 flex items-center">
                            <svg class="w-5 h-5 mr-2 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                            Финансовая информация
                        </h3>
                        <div class="grid grid-cols-2 md:grid-cols-3 gap-4">
                            <div class="text-center p-3 bg-white rounded-lg">
                                <div class="text-xs text-gray-500 mb-1">Сумма заказа</div>
                                <div class="text-xl font-bold text-green-600" x-text="selectedOrder?.['Сумма заказа'] || '-'"></div>
                            </div>
                            <div class="text-center p-3 bg-white rounded-lg" x-show="selectedOrder?.['Цена']">
                                <div class="text-xs text-gray-500 mb-1">Цена товара</div>
                                <div class="text-lg font-bold text-gray-900" x-text="selectedOrder?.['Цена'] || '-'"></div>
                            </div>
                            <div class="text-center p-3 bg-white rounded-lg" x-show="selectedOrder?.['Цена сканирования']">
                                <div class="text-xs text-gray-500 mb-1">Цена сканирования</div>
                                <div class="text-lg font-bold text-blue-600" x-text="selectedOrder?.['Цена сканирования'] || '-'"></div>
                            </div>
                        </div>

                        <!-- Converted Prices -->
                        <div x-show="selectedOrder?.['Конвертированная цена']" class="mt-4 pt-4 border-t border-green-200">
                            <div class="text-sm font-semibold text-gray-700 mb-2">Конвертированная валюта:</div>
                            <div class="grid grid-cols-2 gap-3">
                                <div class="text-sm">
                                    <span class="text-gray-600">Конвертированная цена:</span>
                                    <span class="font-semibold ml-2" x-text="selectedOrder?.['Конвертированная цена'] || '-'"></span>
                                </div>
                                <div class="text-sm">
                                    <span class="text-gray-600">Код валюты:</span>
                                    <span class="font-semibold ml-2" x-text="selectedOrder?.['Код конвертированной валюты'] || '-'"></span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Additional Details -->
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-6">
                        <div class="bg-gray-50 rounded-xl p-4 border border-gray-200">
                            <h4 class="font-semibold text-gray-900 mb-3">Технические данные</h4>
                            <div class="space-y-2 text-sm">
                                <div class="flex justify-between">
                                    <span class="text-gray-600">RID:</span>
                                    <span class="font-mono text-gray-900 text-xs" x-text="selectedOrder?.['RID'] || '-'"></span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-gray-600">Order UID:</span>
                                    <span class="font-mono text-gray-900 text-xs" x-text="selectedOrder?.['Order UID'] || '-'"></span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-gray-600">Статус WB:</span>
                                    <span class="font-semibold text-gray-900" x-text="selectedOrder?.['Статус WB'] || '-'"></span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-gray-600">Статус поставщика:</span>
                                    <span class="font-semibold text-gray-900" x-text="selectedOrder?.['Статус поставщика'] || '-'"></span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-gray-600">Тип груза:</span>
                                    <span class="font-semibold text-gray-900" x-text="selectedOrder?.['Тип груза'] || '-'"></span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-gray-600">Код валюты:</span>
                                    <span class="font-semibold text-gray-900" x-text="selectedOrder?.['Код валюты'] || '-'"></span>
                                </div>
                            </div>
                        </div>

                        <div class="bg-gray-50 rounded-xl p-4 border border-gray-200">
                            <h4 class="font-semibold text-gray-900 mb-3">Дополнительно</h4>
                            <div class="space-y-2">
                                <div class="flex items-center justify-between p-2 bg-white rounded">
                                    <span class="text-sm text-gray-600">Нулевой заказ</span>
                                    <span class="px-2 py-1 text-xs font-semibold rounded"
                                          :class="selectedOrder?.['Нулевой заказ'] === 'Да' ? 'bg-red-100 text-red-700' : 'bg-gray-100 text-gray-600'"
                                          x-text="selectedOrder?.['Нулевой заказ'] || 'Нет'"></span>
                                </div>
                                <div class="flex items-center justify-between p-2 bg-white rounded">
                                    <span class="text-sm text-gray-600">B2B заказ</span>
                                    <span class="px-2 py-1 text-xs font-semibold rounded"
                                          :class="selectedOrder?.['B2B заказ'] === 'Да' ? 'bg-blue-100 text-blue-700' : 'bg-gray-100 text-gray-600'"
                                          x-text="selectedOrder?.['B2B заказ'] || 'Нет'"></span>
                                </div>
                                <div class="p-2 bg-white rounded">
                                    <div class="text-xs text-gray-500 mb-1">Бренд</div>
                                    <div class="font-semibold text-gray-900" x-text="selectedOrder?.['Бренд'] || '-'"></div>
                                </div>
                                <div class="p-2 bg-white rounded">
                                    <div class="text-xs text-gray-500 mb-1">Характеристики</div>
                                    <div class="font-semibold text-gray-900" x-text="selectedOrder?.['Характеристики'] || '-'"></div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Supply & Tare Info -->
                    <div x-show="selectedOrder?.supply_id || selectedOrder?.tare_id"
                         class="bg-gradient-to-br from-indigo-50 to-purple-50 rounded-xl p-6 mb-6 border border-indigo-200">
                        <h3 class="text-lg font-bold text-gray-900 mb-4 flex items-center">
                            <svg class="w-5 h-5 mr-2 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
                            </svg>
                            Поставка и упаковка
                        </h3>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div x-show="selectedOrder?.supply_id">
                                <div class="text-xs text-gray-500 mb-1">ID поставки</div>
                                <div class="font-mono font-semibold text-gray-900" x-text="selectedOrder?.supply_id"></div>
                            </div>
                            <div x-show="selectedOrder?.tare_id">
                                <div class="text-xs text-gray-500 mb-1">ID коробки (тары)</div>
                                <div class="font-mono font-semibold text-gray-900" x-text="selectedOrder?.tare_id"></div>
                            </div>
                        </div>
                    </div>

                    <!-- Dates & Timeline -->
                    <div class="bg-gradient-to-br from-amber-50 to-orange-50 rounded-xl p-6 mb-6 border border-amber-200">
                        <h3 class="text-lg font-bold text-gray-900 mb-4 flex items-center">
                            <svg class="w-5 h-5 mr-2 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                            </svg>
                            Временные метки
                        </h3>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div x-show="selectedOrder?.['Дата заказа']">
                                <div class="text-xs text-gray-500 mb-1">Дата заказа</div>
                                <div class="font-semibold text-gray-900" x-text="selectedOrder?.['Дата заказа'] || '-'"></div>
                            </div>
                            <div x-show="selectedOrder?.['Время с момента заказа']">
                                <div class="text-xs text-gray-500 mb-1">Прошло времени</div>
                                <div class="font-semibold text-blue-600" x-text="selectedOrder?.['Время с момента заказа'] || '-'"></div>
                            </div>
                            <div x-show="selectedOrder?.['Дата доставки']">
                                <div class="text-xs text-gray-500 mb-1">Дата доставки</div>
                                <div class="font-semibold text-green-600" x-text="selectedOrder?.['Дата доставки'] || '-'"></div>
                            </div>
                            <div x-show="selectedOrder?.created_at">
                                <div class="text-xs text-gray-500 mb-1">Создан в системе</div>
                                <div class="font-semibold text-gray-900" x-text="formatDateTime(selectedOrder?.created_at)"></div>
                            </div>
                        </div>
                    </div>

                    <!-- Delivery Address -->
                    <div x-show="selectedOrder?.delivery_address_full || selectedOrder?.wb_address_full"
                         class="bg-gradient-to-br from-teal-50 to-cyan-50 rounded-xl p-6 mb-6 border border-teal-200">
                        <h3 class="text-lg font-bold text-gray-900 mb-4 flex items-center">
                            <svg class="w-5 h-5 mr-2 text-teal-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
                            </svg>
                            Адрес доставки
                        </h3>

                        <!-- FBS Address (delivery_*) -->
                        <div x-show="selectedOrder?.delivery_address_full" class="mb-4">
                            <div class="text-sm font-semibold text-gray-700 mb-2">Полный адрес:</div>
                            <div class="text-gray-900 mb-3" x-text="selectedOrder?.delivery_address_full"></div>

                            <div class="grid grid-cols-2 md:grid-cols-3 gap-3 text-sm">
                                <div x-show="selectedOrder?.delivery_province">
                                    <span class="text-gray-600">Регион:</span>
                                    <span class="font-semibold ml-2" x-text="selectedOrder?.delivery_province"></span>
                                </div>
                                <div x-show="selectedOrder?.delivery_area">
                                    <span class="text-gray-600">Область:</span>
                                    <span class="font-semibold ml-2" x-text="selectedOrder?.delivery_area"></span>
                                </div>
                                <div x-show="selectedOrder?.delivery_city">
                                    <span class="text-gray-600">Город:</span>
                                    <span class="font-semibold ml-2" x-text="selectedOrder?.delivery_city"></span>
                                </div>
                                <div x-show="selectedOrder?.delivery_street">
                                    <span class="text-gray-600">Улица:</span>
                                    <span class="font-semibold ml-2" x-text="selectedOrder?.delivery_street"></span>
                                </div>
                                <div x-show="selectedOrder?.delivery_home">
                                    <span class="text-gray-600">Дом:</span>
                                    <span class="font-semibold ml-2" x-text="selectedOrder?.delivery_home"></span>
                                </div>
                                <div x-show="selectedOrder?.delivery_flat">
                                    <span class="text-gray-600">Квартира:</span>
                                    <span class="font-semibold ml-2" x-text="selectedOrder?.delivery_flat"></span>
                                </div>
                                <div x-show="selectedOrder?.delivery_entrance">
                                    <span class="text-gray-600">Подъезд:</span>
                                    <span class="font-semibold ml-2" x-text="selectedOrder?.delivery_entrance"></span>
                                </div>
                            </div>

                            <div x-show="selectedOrder?.delivery_latitude && selectedOrder?.delivery_longitude"
                                 class="mt-3 p-3 bg-white rounded-lg">
                                <span class="text-gray-600 text-sm">Координаты:</span>
                                <span class="font-mono text-sm ml-2" x-text="`${selectedOrder?.delivery_latitude}, ${selectedOrder?.delivery_longitude}`"></span>
                            </div>
                        </div>

                        <!-- FBO Address (wb_address_*) -->
                        <div x-show="selectedOrder?.wb_address_full && !selectedOrder?.delivery_address_full">
                            <div class="text-sm font-semibold text-gray-700 mb-2">Адрес (WB):</div>
                            <div class="text-gray-900 mb-3" x-text="selectedOrder?.wb_address_full"></div>

                            <div x-show="selectedOrder?.wb_address_lat && selectedOrder?.wb_address_lng"
                                 class="p-3 bg-white rounded-lg">
                                <span class="text-gray-600 text-sm">Координаты:</span>
                                <span class="font-mono text-sm ml-2" x-text="`${selectedOrder?.wb_address_lat}, ${selectedOrder?.wb_address_lng}`"></span>
                            </div>
                        </div>
                    </div>

                    <!-- Required & Optional Metadata -->
                    <div x-show="(selectedOrder?.required_meta && Object.keys(selectedOrder?.required_meta || {}).length > 0) ||
                                 (selectedOrder?.optional_meta && Object.keys(selectedOrder?.optional_meta || {}).length > 0) ||
                                 (selectedOrder?.wb_required_meta && Object.keys(selectedOrder?.wb_required_meta || {}).length > 0) ||
                                 (selectedOrder?.wb_optional_meta && Object.keys(selectedOrder?.wb_optional_meta || {}).length > 0)"
                         class="bg-gradient-to-br from-rose-50 to-pink-50 rounded-xl p-6 mb-6 border border-rose-200">
                        <h3 class="text-lg font-bold text-gray-900 mb-4 flex items-center">
                            <svg class="w-5 h-5 mr-2 text-rose-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                            </svg>
                            Метаданные товара
                        </h3>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <!-- Required Meta -->
                            <div x-show="(selectedOrder?.required_meta && Object.keys(selectedOrder?.required_meta || {}).length > 0) ||
                                         (selectedOrder?.wb_required_meta && Object.keys(selectedOrder?.wb_required_meta || {}).length > 0)">
                                <div class="bg-white rounded-lg p-4 border border-rose-200">
                                    <h4 class="font-semibold text-gray-900 mb-3 text-sm">Обязательные метаданные</h4>
                                    <div class="space-y-2 text-sm">
                                        <template x-for="[key, value] in Object.entries(selectedOrder?.required_meta || selectedOrder?.wb_required_meta || {})" :key="key">
                                            <div class="flex justify-between">
                                                <span class="text-gray-600" x-text="key + ':'"></span>
                                                <span class="font-mono text-gray-900" x-text="value"></span>
                                            </div>
                                        </template>
                                    </div>
                                </div>
                            </div>

                            <!-- Optional Meta -->
                            <div x-show="(selectedOrder?.optional_meta && Object.keys(selectedOrder?.optional_meta || {}).length > 0) ||
                                         (selectedOrder?.wb_optional_meta && Object.keys(selectedOrder?.wb_optional_meta || {}).length > 0)">
                                <div class="bg-white rounded-lg p-4 border border-rose-200">
                                    <h4 class="font-semibold text-gray-900 mb-3 text-sm">Опциональные метаданные</h4>
                                    <div class="space-y-2 text-sm">
                                        <template x-for="[key, value] in Object.entries(selectedOrder?.optional_meta || selectedOrder?.wb_optional_meta || {})" :key="key">
                                            <div class="flex justify-between">
                                                <span class="text-gray-600" x-text="key + ':'"></span>
                                                <span class="font-mono text-gray-900" x-text="value"></span>
                                            </div>
                                        </template>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Specific Meta Fields -->
                        <div class="mt-4 grid grid-cols-2 md:grid-cols-4 gap-3">
                            <div x-show="selectedOrder?.meta_sgtin" class="bg-white rounded-lg p-3">
                                <div class="text-xs text-gray-500 mb-1">SGTIN</div>
                                <div class="font-mono text-xs text-gray-900" x-text="Array.isArray(selectedOrder?.meta_sgtin) ? selectedOrder?.meta_sgtin.join(', ') : selectedOrder?.meta_sgtin"></div>
                            </div>
                            <div x-show="selectedOrder?.meta_uin" class="bg-white rounded-lg p-3">
                                <div class="text-xs text-gray-500 mb-1">UIN</div>
                                <div class="font-mono text-xs text-gray-900" x-text="selectedOrder?.meta_uin"></div>
                            </div>
                            <div x-show="selectedOrder?.meta_imei" class="bg-white rounded-lg p-3">
                                <div class="text-xs text-gray-500 mb-1">IMEI</div>
                                <div class="font-mono text-xs text-gray-900" x-text="selectedOrder?.meta_imei"></div>
                            </div>
                            <div x-show="selectedOrder?.meta_gtin" class="bg-white rounded-lg p-3">
                                <div class="text-xs text-gray-500 mb-1">GTIN</div>
                                <div class="font-mono text-xs text-gray-900" x-text="selectedOrder?.meta_gtin"></div>
                            </div>
                            <div x-show="selectedOrder?.meta_expiration_date" class="bg-white rounded-lg p-3">
                                <div class="text-xs text-gray-500 mb-1">Срок годности</div>
                                <div class="font-semibold text-xs text-gray-900" x-text="new Date(selectedOrder?.meta_expiration_date).toLocaleDateString('ru-RU')"></div>
                            </div>
                        </div>
                    </div>

                    <!-- Order Items / Товары -->
                    <div x-show="selectedOrder?.['Товары'] && selectedOrder?.['Товары'].length > 0"
                         class="bg-gradient-to-br from-blue-50 to-indigo-50 rounded-xl p-6 mb-6 border border-blue-200">
                        <h3 class="text-lg font-bold text-gray-900 mb-4 flex items-center">
                            <svg class="w-5 h-5 mr-2 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
                            </svg>
                            Товары в заказе
                        </h3>

                        <div class="space-y-3">
                            <template x-for="(item, index) in (selectedOrder?.['Товары'] || [])" :key="index">
                                <div class="bg-white rounded-lg p-4 border border-blue-100">
                                    <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                                        <div>
                                            <div class="text-xs text-gray-500 mb-1">Название</div>
                                            <div class="font-semibold text-gray-900" x-text="item['Название'] || '-'"></div>
                                        </div>
                                        <div>
                                            <div class="text-xs text-gray-500 mb-1">Артикул/SKU</div>
                                            <div class="font-mono text-sm text-gray-900" x-text="item['Артикул/SKU'] || '-'"></div>
                                        </div>
                                        <div>
                                            <div class="text-xs text-gray-500 mb-1">Количество</div>
                                            <div class="font-semibold text-gray-900" x-text="item['Количество'] || 1"></div>
                                        </div>
                                        <div>
                                            <div class="text-xs text-gray-500 mb-1">Цена</div>
                                            <div class="font-semibold text-green-600" x-text="item['Цена'] || '-'"></div>
                                        </div>
                                    </div>
                                </div>
                            </template>
                        </div>
                    </div>

                    <!-- Sticker Info -->
                    <div x-show="selectedOrder?.sticker_path"
                         class="bg-gradient-to-br from-violet-50 to-purple-50 rounded-xl p-6 mb-6 border border-violet-200">
                        <h3 class="text-lg font-bold text-gray-900 mb-4 flex items-center">
                            <svg class="w-5 h-5 mr-2 text-violet-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"/>
                            </svg>
                            Стикер заказа
                        </h3>
                        <div class="flex items-center justify-between">
                            <div>
                                <div class="text-sm text-gray-600 mb-1">Путь к файлу:</div>
                                <div class="font-mono text-sm text-gray-900" x-text="selectedOrder?.sticker_path"></div>
                            </div>
                            <a x-show="selectedOrder?.sticker_path"
                               :href="selectedOrder?.sticker_path"
                               target="_blank"
                               class="px-4 py-2 bg-violet-600 text-white rounded-lg hover:bg-violet-700 transition font-medium">
                                Открыть стикер
                            </a>
                        </div>
                    </div>

                    <!-- Status History -->
                    <div x-show="selectedOrder?.status_history && selectedOrder?.status_history.length > 0"
                         class="bg-gradient-to-br from-blue-50 to-indigo-50 rounded-xl p-6 mb-6 border border-blue-200">
                        <h3 class="text-lg font-bold text-gray-900 mb-4 flex items-center">
                            <svg class="w-5 h-5 mr-2 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                            История статусов
                        </h3>

                        <div class="relative">
                            <!-- Timeline -->
                            <div class="absolute left-4 top-0 bottom-0 w-0.5 bg-blue-300"></div>

                            <div class="space-y-4">
                                <template x-for="(historyItem, index) in (selectedOrder?.status_history || []).slice().reverse()" :key="index">
                                    <div class="relative pl-10 pb-4">
                                        <!-- Timeline dot -->
                                        <div class="absolute left-2.5 top-1 w-3 h-3 bg-blue-600 rounded-full border-2 border-white shadow"></div>

                                        <div class="bg-white rounded-lg p-4 shadow-sm border border-blue-100">
                                            <div class="flex items-start justify-between mb-2">
                                                <div class="flex-1">
                                                    <div class="flex items-center space-x-3 mb-1">
                                                        <span x-show="historyItem.wb_status"
                                                              class="inline-block px-3 py-1 bg-blue-100 text-blue-800 text-xs font-semibold rounded-full">
                                                            WB: <span x-text="historyItem.wb_status"></span>
                                                        </span>
                                                        <span x-show="historyItem.supplier_status"
                                                              class="inline-block px-3 py-1 bg-purple-100 text-purple-800 text-xs font-semibold rounded-full">
                                                            Поставщик: <span x-text="historyItem.supplier_status"></span>
                                                        </span>
                                                    </div>
                                                </div>
                                                <div class="text-xs text-gray-500 flex items-center">
                                                    <svg class="w-3.5 h-3.5 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                                    </svg>
                                                    <span x-text="formatDateTime(historyItem.updated_at)"></span>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </template>
                            </div>
                        </div>

                        <div x-show="!selectedOrder?.status_history || selectedOrder?.status_history.length === 0"
                             class="text-center py-8 text-gray-500">
                            <svg class="w-12 h-12 mx-auto mb-3 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                            <p>История статусов пока отсутствует</p>
                        </div>
                    </div>

                    <!-- Customer Info -->
                    <div x-show="selectedOrder?.customer_name || selectedOrder?.customer_phone"
                         class="bg-gradient-to-br from-sky-50 to-blue-50 rounded-xl p-6 mb-6 border border-sky-200">
                        <h3 class="text-lg font-bold text-gray-900 mb-4 flex items-center">
                            <svg class="w-5 h-5 mr-2 text-sky-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                            </svg>
                            Информация о клиенте
                        </h3>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div x-show="selectedOrder?.customer_name">
                                <div class="text-xs text-gray-500 mb-1">Имя клиента</div>
                                <div class="font-semibold text-gray-900" x-text="selectedOrder?.customer_name"></div>
                            </div>
                            <div x-show="selectedOrder?.customer_phone">
                                <div class="text-xs text-gray-500 mb-1">Телефон</div>
                                <div class="font-mono font-semibold text-gray-900" x-text="selectedOrder?.customer_phone"></div>
                            </div>
                        </div>
                    </div>

                    <!-- Return & Claim Status -->
                    <div x-show="selectedOrder?.return_status || selectedOrder?.claim_status"
                         class="bg-gradient-to-br from-orange-50 to-red-50 rounded-xl p-6 mb-6 border border-orange-200">
                        <h3 class="text-lg font-bold text-gray-900 mb-4 flex items-center">
                            <svg class="w-5 h-5 mr-2 text-orange-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                            </svg>
                            Возвраты и претензии
                        </h3>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div x-show="selectedOrder?.return_status">
                                <div class="text-xs text-gray-500 mb-1">Статус возврата</div>
                                <span class="inline-block px-3 py-1 bg-orange-100 text-orange-800 text-sm font-semibold rounded-full"
                                      x-text="selectedOrder?.return_status"></span>
                            </div>
                            <div x-show="selectedOrder?.claim_status">
                                <div class="text-xs text-gray-500 mb-1">Статус претензии</div>
                                <span class="inline-block px-3 py-1 bg-red-100 text-red-800 text-sm font-semibold rounded-full"
                                      x-text="selectedOrder?.claim_status"></span>
                            </div>
                        </div>
                    </div>

                    <!-- Comment if exists -->
                    <div x-show="selectedOrder?.wb_comment" class="bg-yellow-50 border border-yellow-200 rounded-xl p-4 mb-6">
                        <h4 class="font-semibold text-gray-900 mb-2 flex items-center">
                            <svg class="w-4 h-4 mr-2 text-yellow-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 8h10M7 12h4m1 8l-4-4H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-3l-4 4z"/>
                            </svg>
                            Комментарий
                        </h4>
                        <p class="text-gray-700" x-text="selectedOrder?.wb_comment"></p>
                    </div>

                    <!-- Raw JSON Data (collapsible) -->
                    <div class="bg-gray-900 rounded-xl overflow-hidden">
                        <button @click="showRaw = !showRaw"
                                class="w-full px-4 py-3 flex items-center justify-between text-white hover:bg-gray-800 transition">
                            <span class="font-semibold">Raw JSON данные</span>
                            <svg class="w-5 h-5 transition-transform" :class="showRaw ? 'rotate-180' : ''"
                                 fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                            </svg>
                        </button>
                        <div x-show="showRaw" class="p-4 bg-gray-950">
                            <pre class="text-xs text-green-400 overflow-x-auto" x-text="JSON.stringify(selectedOrder?.raw_payload, null, 2)"></pre>
                        </div>
                    </div>
                </div>

                <!-- Footer -->
                <div class="sticky bottom-0 bg-gray-50 px-6 py-4 border-t border-gray-200 flex justify-end space-x-3">
                    <button @click="showOrderModal = false"
                            class="px-6 py-2 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300 transition font-medium">
                        Закрыть
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Create Supply Modal -->
    <div x-show="showCreateSupplyModal"
         x-transition:enter="transition ease-out duration-300"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition ease-in duration-200"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         class="fixed inset-0 z-50 overflow-y-auto"
         style="display: none;">
        <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:block sm:p-0">
            <!-- Background overlay -->
            <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" @click="showCreateSupplyModal = false"></div>

            <!-- Center trick -->
            <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>

            <!-- Modal panel -->
            <div class="inline-block align-bottom bg-white rounded-2xl text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full relative z-50">
                <div class="p-6">
                    <h3 class="text-lg font-medium leading-6 text-gray-900 mb-4">
                        Создать новую поставку
                    </h3>

                    <div class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Название поставки</label>
                        <input type="text"
                               x-model="newSupply.name"
                               class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                               placeholder="Например: Поставка 12.05.2025">
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Описание (необязательно)</label>
                        <textarea x-model="newSupply.description"
                                  rows="3"
                                  class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                  placeholder="Дополнительная информация о поставке"></textarea>
                    </div>
                    </div>

                    <div class="mt-6 flex justify-end space-x-3">
                        <button @click="showCreateSupplyModal = false"
                                class="px-4 py-2 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300 transition">
                            Отмена
                        </button>
                        <button @click="createSupply()"
                                :disabled="suppliesLoading"
                                class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition disabled:opacity-50">
                            <span x-show="!suppliesLoading">Создать</span>
                            <span x-show="suppliesLoading">Создание...</span>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Add to Supply Modal -->
    <div x-show="showAddToSupplyModal"
         x-transition:enter="transition ease-out duration-300"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition ease-in duration-200"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         class="fixed inset-0 z-50 overflow-y-auto"
         x-cloak
         style="display: none;">
        <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:p-0">
            <!-- Background overlay -->
            <div class="fixed inset-0 transition-opacity bg-gray-500 bg-opacity-75" @click="showAddToSupplyModal = false"></div>

            <!-- Modal panel -->
            <div class="relative inline-block w-full max-w-2xl p-6 my-8 overflow-hidden text-left align-middle transition-all transform bg-white shadow-xl rounded-2xl z-10">
                <h3 class="text-lg font-medium leading-6 text-gray-900 mb-4">
                    Добавить заказ в поставку
                </h3>

                <div x-show="selectedOrderForSupply" class="mb-4 p-4 bg-gray-50 rounded-lg">
                    <p class="text-sm text-gray-600">Заказ:</p>
                    <p class="font-medium" x-text="'#' + selectedOrderForSupply?.external_order_id"></p>
                </div>

                <div class="mb-4">
                    <div class="flex items-center justify-between mb-2">
                        <label class="block text-sm font-medium text-gray-700">Выберите поставку</label>
                        <button @click="openCreateSupplyModal(); showAddToSupplyModal = false;"
                                class="text-sm text-blue-600 hover:text-blue-700">
                            + Создать новую
                        </button>
                    </div>

                    <div class="space-y-2 max-h-96 overflow-y-auto">
                        <template x-for="supply in openSupplies" :key="supply.id">
                            <div @click="selectedSupplyId = supply.id"
                                 :class="selectedSupplyId === supply.id ? 'border-blue-500 bg-blue-50' : 'border-gray-200 bg-white hover:border-gray-300'"
                                 class="p-4 border-2 rounded-lg cursor-pointer transition">
                                <div class="flex items-center justify-between">
                                    <div class="flex-1">
                                        <p class="font-medium text-gray-900" x-text="supply.name"></p>
                                        <p class="text-sm text-gray-600 mt-1" x-text="supply.description"></p>
                                        <div class="flex items-center space-x-4 mt-2">
                                            <span class="text-xs text-gray-500">
                                                Заказов: <span x-text="supply.orders_count"></span>
                                            </span>
                                            <span class="text-xs text-gray-500">
                                                Сумма: <span x-text="supply.total_amount"></span> ₽
                                            </span>
                                            <span class="text-xs px-2 py-1 rounded-full"
                                                  :class="{
                                                      'bg-gray-100 text-gray-700': supply.status === 'draft',
                                                      'bg-blue-100 text-blue-700': supply.status === 'in_assembly'
                                                  }"
                                                  x-text="supply.status === 'draft' ? 'Черновик' : 'На сборке'"></span>
                                        </div>
                                    </div>
                                    <div x-show="selectedSupplyId === supply.id" class="ml-4">
                                        <svg class="w-6 h-6 text-blue-600" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                                        </svg>
                                    </div>
                                </div>
                            </div>
                        </template>

                        <div x-show="openSupplies.length === 0" class="text-center py-8 text-gray-500">
                            <p class="mb-2">Нет доступных поставок</p>
                            <button @click="openCreateSupplyModal(); showAddToSupplyModal = false;"
                                    class="text-blue-600 hover:text-blue-700">
                                Создать первую поставку
                            </button>
                        </div>
                    </div>
                </div>

                <div class="mt-6 flex justify-end space-x-3">
                    <button @click="showAddToSupplyModal = false"
                            class="px-4 py-2 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300 transition">
                        Отмена
                    </button>
                    <button @click="addOrderToSupply()"
                            :disabled="!selectedSupplyId || suppliesLoading"
                            class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition disabled:opacity-50">
                        <span x-show="!suppliesLoading">Добавить</span>
                        <span x-show="suppliesLoading">Добавление...</span>
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- View Supply Orders Modal -->
    <div x-show="showSupplyModal"
         x-transition:enter="transition ease-out duration-300"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition ease-in duration-200"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         class="fixed inset-0 z-50 overflow-y-auto"
         style="display: none;">
        <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:block sm:p-0">
            <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" @click="showSupplyModal = false"></div>

            <div class="inline-block align-bottom bg-white rounded-2xl text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-4xl sm:w-full">
                <!-- Header -->
                <div class="bg-gradient-to-r from-blue-600 to-indigo-600 px-6 py-4">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center space-x-3">
                            <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
                            </svg>
                            <div>
                                <h3 class="text-xl font-bold text-white">Поставка: <span x-text="selectedSupply?.name"></span></h3>
                                <p class="text-blue-100 text-sm" x-text="`ID: ${selectedSupply?.id}`"></p>
                            </div>
                        </div>
                        <button @click="showSupplyModal = false" class="text-white hover:text-gray-200 transition">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                            </svg>
                        </button>
                    </div>
                </div>

                <!-- Content -->
                <div class="px-6 py-4">
                    <!-- Supply Info -->
                    <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6 p-4 bg-gray-50 rounded-lg">
                        <div>
                            <div class="text-xs text-gray-500 mb-1">Статус</div>
                            <span class="px-2 py-1 text-xs font-semibold rounded-full"
                                  :class="{
                                      'bg-gray-100 text-gray-800': selectedSupply?.status === 'draft',
                                      'bg-blue-100 text-blue-800': selectedSupply?.status === 'in_assembly',
                                      'bg-green-100 text-green-800': selectedSupply?.status === 'ready'
                                  }"
                                  x-text="selectedSupply?.status === 'draft' ? 'Черновик' : selectedSupply?.status === 'in_assembly' ? 'На сборке' : 'Готова'"></span>
                        </div>
                        <div>
                            <div class="text-xs text-gray-500 mb-1">Заказов</div>
                            <div class="font-bold text-gray-900" x-text="selectedSupply?.orders_count || 0"></div>
                        </div>
                        <div>
                            <div class="text-xs text-gray-500 mb-1">Общая сумма</div>
                            <div class="font-bold text-gray-900" x-text="formatMoney((selectedSupply?.total_amount || 0) / 100)"></div>
                        </div>
                        <div>
                            <div class="text-xs text-gray-500 mb-1">Создана</div>
                            <div class="font-medium text-gray-900" x-text="selectedSupply?.created_at ? new Date(selectedSupply.created_at).toLocaleDateString('ru-RU') : '-'"></div>
                        </div>
                    </div>

                    <!-- Orders List -->
                    <div>
                        <h4 class="font-semibold text-gray-900 mb-3">Заказы в поставке</h4>
                        <div class="max-h-96 overflow-y-auto space-y-2">
                            <template x-for="order in supplyOrders" :key="order.id">
                                <div class="border border-gray-200 rounded-lg p-3 hover:bg-gray-50 transition flex items-center justify-between">
                                    <div class="flex items-center space-x-3">
                                        <img x-show="loadImages && order.wb_nm_id"
                                             :src="loadImages ? getWbProductImageUrl(order.wb_nm_id) : ''"
                                             :alt="order.wb_article"
                                             :data-nmid="order.wb_nm_id"
                                             data-size="tm"
                                             class="w-12 h-12 object-cover rounded border border-gray-200"
                                             loading="lazy"
                                             x-on:error.once="handleImageError($event)">
                                        <div>
                                            <div class="font-semibold text-gray-900">Заказ #<span x-text="order.external_order_id"></span></div>
                                            <div class="text-xs text-gray-500">
                                                <span x-show="order.wb_article">Артикул: <span x-text="order.wb_article"></span></span>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="text-right">
                                        <div class="font-bold text-gray-900" x-text="formatMoney((order.wb_final_price || 0) / 100)"></div>
                                        <button @click="removeOrderFromSupplyInModal(order)"
                                                :disabled="removingOrderFromSupplyId === order.id"
                                                class="mt-1 text-xs text-red-600 hover:text-red-700 disabled:opacity-50 flex items-center">
                                            <svg x-show="removingOrderFromSupplyId === order.id" class="animate-spin w-3 h-3 mr-1" fill="none" viewBox="0 0 24 24">
                                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                            </svg>
                                            <span x-text="removingOrderFromSupplyId === order.id ? 'Удаление...' : 'Убрать'"></span>
                                        </button>
                                    </div>
                                </div>
                            </template>

                            <div x-show="supplyOrders.length === 0" class="text-center py-12 text-gray-500">
                                <svg class="w-12 h-12 mx-auto text-gray-400 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4"/>
                                </svg>
                                <p>Нет заказов в поставке</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Footer -->
                <div class="bg-gray-50 px-6 py-4 flex justify-between">
                    <button x-show="selectedSupply?.orders_count === 0"
                            @click="deleteSupply(selectedSupply.id); showSupplyModal = false;"
                            :disabled="deletingSupplyId === selectedSupply?.id"
                            class="px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700 transition disabled:opacity-50 flex items-center space-x-2">
                        <svg x-show="deletingSupplyId === selectedSupply?.id" class="animate-spin w-4 h-4" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                        <span x-text="deletingSupplyId === selectedSupply?.id ? 'Удаление...' : 'Удалить поставку'"></span>
                    </button>
                    <div x-show="selectedSupply?.orders_count > 0" class="text-sm text-gray-500">
                        <!-- Placeholder to maintain spacing -->
                    </div>
                    <button @click="showSupplyModal = false"
                            class="px-4 py-2 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300 transition">
                        Закрыть
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Deliver Supply Confirmation Modal -->
    <div x-show="showDeliverSupplyModal"
         x-transition:enter="transition ease-out duration-300"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition ease-in duration-200"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         class="fixed inset-0 z-50 overflow-y-auto"
         style="display: none;">
        <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:block sm:p-0">
            <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" @click="closeDeliverModal()"></div>

            <div class="inline-block align-bottom bg-white rounded-2xl text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">
                <!-- Header -->
                <div class="bg-gradient-to-r from-purple-600 to-indigo-600 px-6 py-4">
                    <div class="flex items-center space-x-3">
                        <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6"/>
                        </svg>
                        <h3 class="text-xl font-bold text-white">Передать поставку в доставку</h3>
                    </div>
                </div>

                <!-- Content -->
                <div class="px-6 py-4">
                    <div class="mb-4">
                        <p class="text-gray-700 mb-3">
                            Вы уверены, что хотите передать поставку в доставку?
                        </p>

                        <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-4 mb-4">
                            <div class="flex items-start space-x-3">
                                <svg class="w-5 h-5 text-yellow-600 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                                </svg>
                                <div class="text-sm text-yellow-800">
                                    <p class="font-semibold mb-1">Внимание!</p>
                                    <p>После передачи в доставку поставку нельзя будет изменить.</p>
                                </div>
                            </div>
                        </div>

                        <div class="bg-gray-50 rounded-lg p-4" x-show="supplyToDeliver">
                            <div class="grid grid-cols-2 gap-3">
                                <div>
                                    <div class="text-xs text-gray-500 mb-1">Название</div>
                                    <div class="font-semibold text-gray-900" x-text="supplyToDeliver?.name"></div>
                                </div>
                                <div>
                                    <div class="text-xs text-gray-500 mb-1">ID WB</div>
                                    <div class="font-mono text-sm text-gray-900" x-text="supplyToDeliver?.external_supply_id"></div>
                                </div>
                                <div>
                                    <div class="text-xs text-gray-500 mb-1">Заказов</div>
                                    <div class="font-bold text-gray-900" x-text="supplyToDeliver?.orders_count || 0"></div>
                                </div>
                                <div>
                                    <div class="text-xs text-gray-500 mb-1">Сумма</div>
                                    <div class="font-bold text-gray-900" x-text="formatMoney((supplyToDeliver?.total_amount || 0) / 100)"></div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Footer -->
                <div class="bg-gray-50 px-6 py-4 flex justify-end space-x-3">
                    <button @click="closeDeliverModal()"
                            class="px-5 py-2 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300 transition font-medium">
                        Отмена
                    </button>
                    <button @click="deliverSupply()"
                            :disabled="deliveringSupply"
                            class="px-5 py-2 bg-purple-600 hover:bg-purple-700 text-white rounded-lg transition font-medium flex items-center space-x-2 disabled:opacity-50 disabled:cursor-not-allowed">
                        <svg x-show="!deliveringSupply" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6"/>
                        </svg>
                        <svg x-show="deliveringSupply" class="animate-spin w-4 h-4" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                        <span x-text="deliveringSupply ? 'Передача...' : 'Передать в доставку'"></span>
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Cancel Order Confirmation Modal -->
    <div x-show="showCancelModal"
         x-transition:enter="transition ease-out duration-300"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition ease-in duration-200"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         class="fixed inset-0 z-50 overflow-y-auto"
         style="display: none;">
        <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:block sm:p-0">
            <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" @click="closeCancelModal()"></div>

            <div class="inline-block align-bottom bg-white rounded-2xl text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">
                <!-- Header -->
                <div class="bg-gradient-to-r from-red-600 to-red-700 px-6 py-4">
                    <div class="flex items-center space-x-3">
                        <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                        <h3 class="text-xl font-bold text-white">Отменить заказ</h3>
                    </div>
                </div>

                <!-- Content -->
                <div class="px-6 py-4">
                    <div class="mb-4">
                        <p class="text-gray-700 mb-3">
                            Вы уверены, что хотите отменить этот заказ?
                        </p>

                        <div class="bg-red-50 border border-red-200 rounded-lg p-4 mb-4">
                            <div class="flex items-start space-x-3">
                                <svg class="w-5 h-5 text-red-600 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                                </svg>
                                <div class="text-sm text-red-800">
                                    <p class="font-semibold mb-1">Внимание!</p>
                                    <p>Отменённый заказ нельзя будет восстановить. Это действие необратимо.</p>
                                </div>
                            </div>
                        </div>

                        <div class="bg-gray-50 rounded-lg p-4" x-show="orderToCancel">
                            <div class="grid grid-cols-2 gap-3">
                                <div>
                                    <div class="text-xs text-gray-500 mb-1">Номер заказа</div>
                                    <div class="font-semibold text-gray-900" x-text="'#' + (orderToCancel?.external_order_id || '')"></div>
                                </div>
                                <div>
                                    <div class="text-xs text-gray-500 mb-1">Артикул</div>
                                    <div class="font-medium text-gray-900" x-text="orderToCancel?.wb_article || '-'"></div>
                                </div>
                                <div>
                                    <div class="text-xs text-gray-500 mb-1">Статус</div>
                                    <span class="px-2 py-1 text-xs font-semibold rounded-full inline-block"
                                          :class="statusClass(orderToCancel)"
                                          x-text="statusLabel(orderToCancel)"></span>
                                </div>
                                <div>
                                    <div class="text-xs text-gray-500 mb-1">Сумма</div>
                                    <div class="font-bold text-gray-900" x-text="formatPrice(orderToCancel?.wb_final_price)"></div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Footer -->
                <div class="bg-gray-50 px-6 py-4 flex justify-end space-x-3">
                    <button @click="closeCancelModal()"
                            class="px-5 py-2 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300 transition font-medium">
                        Отмена
                    </button>
                    <button @click="cancelOrder()"
                            :disabled="cancelingOrder"
                            class="px-5 py-2 bg-red-600 hover:bg-red-700 text-white rounded-lg transition font-medium flex items-center space-x-2 disabled:bg-gray-400 disabled:cursor-not-allowed">
                        <svg x-show="!cancelingOrder" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                        <svg x-show="cancelingOrder" class="animate-spin w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                        </svg>
                        <span x-text="cancelingOrder ? 'Отмена...' : 'Отменить заказ'"></span>
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Create Tare Modal -->
    <div x-show="showCreateTareModal"
         x-transition:enter="transition ease-out duration-300"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition ease-in duration-200"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         class="fixed inset-0 z-50 overflow-y-auto"
         style="display: none;">
        <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:block sm:p-0">
            <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" @click="showCreateTareModal = false"></div>
            <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>

            <div class="inline-block align-bottom bg-white rounded-2xl text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full relative z-50">
                <div class="p-6">
                    <h3 class="text-lg font-medium leading-6 text-gray-900 mb-4">
                        Создать коробку
                    </h3>

                    <p class="text-sm text-gray-600 mb-4">
                        Штрихкод и ID короба будут сгенерированы автоматически системой Wildberries.
                    </p>

                    <div class="bg-blue-50 border-l-4 border-blue-400 p-4 mb-4">
                        <div class="flex">
                            <div class="flex-shrink-0">
                                <svg class="h-5 w-5 text-blue-400" viewBox="0 0 20 20" fill="currentColor">
                                    <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"/>
                                </svg>
                            </div>
                            <div class="ml-3">
                                <p class="text-sm text-blue-700">
                                    После создания короба вы сможете распечатать его штрихкод
                                </p>
                            </div>
                        </div>
                    </div>

                    <div class="mt-6 flex justify-end space-x-3">
                        <button @click="showCreateTareModal = false"
                                class="px-4 py-2 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300 transition">
                            Отмена
                        </button>
                        <button @click="createTare()"
                                :disabled="taresLoading"
                                class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition disabled:opacity-50">
                            <span x-show="!taresLoading">Создать короб</span>
                            <span x-show="taresLoading">Создание...</span>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Tare Details Modal -->
    <div x-show="showTareModal"
         x-transition:enter="transition ease-out duration-300"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition ease-in duration-200"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         class="fixed inset-0 z-50 overflow-y-auto"
         style="display: none;">
        <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:block sm:p-0">
            <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" @click="showTareModal = false"></div>
            <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>

            <div class="inline-block align-bottom bg-white rounded-2xl text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-4xl sm:w-full relative z-50">
                <div class="p-6">
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="text-lg font-medium leading-6 text-gray-900">
                            Коробка <span x-text="selectedTare?.barcode || selectedTare?.external_tare_id || '#' + selectedTare?.id"></span>
                        </h3>
                        <div class="flex items-center space-x-2">
                            <a :href="`/api/marketplace/tares/${selectedTare?.id}/barcode?type=png`"
                               target="_blank"
                               class="px-3 py-1 bg-blue-600 text-white text-sm rounded-lg hover:bg-blue-700 transition flex items-center space-x-1">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/>
                                </svg>
                                <span>Печать стикера</span>
                            </a>
                            <button @click="deleteTare(selectedTare)"
                                    class="px-3 py-1 bg-red-600 text-white text-sm rounded-lg hover:bg-red-700 transition">
                                Удалить коробку
                            </button>
                        </div>
                    </div>

                    <div class="mb-4 p-4 bg-gray-50 rounded-lg">
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <p class="text-sm text-gray-600">Штрихкод:</p>
                                <p class="font-medium" x-text="selectedTare?.barcode || '-'"></p>
                            </div>
                            <div>
                                <p class="text-sm text-gray-600">ID WB:</p>
                                <p class="font-medium" x-text="selectedTare?.external_tare_id || '-'"></p>
                            </div>
                            <div>
                                <p class="text-sm text-gray-600">Заказов в коробке:</p>
                                <p class="font-medium" x-text="selectedTare?.orders_count || 0"></p>
                            </div>
                        </div>
                    </div>

                    <div class="mb-4">
                        <h4 class="font-medium text-gray-900 mb-2">Заказы в коробке:</h4>
                        <div class="max-h-96 overflow-y-auto space-y-2">
                            <template x-if="!selectedTare?.orders || selectedTare.orders.length === 0">
                                <p class="text-gray-500 text-sm">Нет заказов в коробке</p>
                            </template>
                            <template x-for="order in selectedTare?.orders" :key="order.id">
                                <div class="flex items-center justify-between p-3 bg-white border border-gray-200 rounded-lg">
                                    <div class="flex items-center space-x-3">
                                        <img x-show="order.wb_nm_id"
                                             :src="getWbProductImageUrl(order.wb_nm_id)"
                                             :data-nmid="order.wb_nm_id"
                                             data-size="tm"
                                             class="w-12 h-12 object-cover rounded border"
                                             loading="lazy"
                                             x-on:error.once="handleImageError($event)">
                                        <div>
                                            <p class="font-medium" x-text="'#' + order.external_order_id"></p>
                                            <p class="text-sm text-gray-600" x-text="order.wb_article"></p>
                                        </div>
                                    </div>
                                    <button @click="removeOrderFromTare(order.id)"
                                            class="px-3 py-1 bg-red-50 text-red-600 text-sm rounded hover:bg-red-100 transition">
                                        Убрать
                                    </button>
                                </div>
                            </template>
                        </div>
                    </div>

                    <div class="mt-6 flex justify-end">
                        <button @click="showTareModal = false"
                                class="px-4 py-2 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300 transition">
                            Закрыть
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- PWA MODE --}}
<div class="pwa-only min-h-screen" x-data="{
    orders: [],
    stats: null,
    loading: true,
    selectedOrder: null,
    showOrderModal: false,
    activeTab: 'new',
    dateFrom: '',
    dateTo: '',
    searchQuery: '',
    accountId: {{ $accountId }},
    accountMarketplace: '{{ $accountMarketplace ?? 'wb' }}',
    accountName: '{{ addslashes($accountName ?? '') }}',
    defaultCurrency: '{{ ($accountMarketplace ?? 'wb') === 'uzum' ? 'UZS' : 'RUB' }}',

    isWb() { return this.accountMarketplace === 'wb'; },
    isUzum() { return this.accountMarketplace === 'uzum'; },

    getToken() {
        if (this.$store.auth.token) return this.$store.auth.token;
        const persistToken = localStorage.getItem('_x_auth_token');
        if (persistToken) {
            try { return JSON.parse(persistToken); } catch (e) { return persistToken; }
        }
        return localStorage.getItem('auth_token') || localStorage.getItem('token');
    },
    getAuthHeaders() {
        return { 'Authorization': 'Bearer ' + this.getToken(), 'Accept': 'application/json' };
    },

    async init() {
        await this.$nextTick();
        if (!this.getToken()) { window.location.href = '/login'; return; }
        const today = new Date();
        const monthAgo = new Date(today.getTime() - 30 * 24 * 60 * 60 * 1000);
        this.dateTo = today.toLocaleDateString('en-CA');
        this.dateFrom = monthAgo.toLocaleDateString('en-CA');
        if (this.accountMarketplace === 'uzum') { this.dateFrom = ''; this.dateTo = ''; }
        await Promise.all([this.loadOrders(), this.loadStats()]);
    },

    async loadOrders() {
        this.loading = true;
        let url = '/api/marketplace/orders?company_id=' + this.$store.auth.currentCompany.id + '&marketplace_account_id={{ $accountId }}';
        if (this.dateFrom) url += '&from=' + this.dateFrom;
        if (this.dateTo) url += '&to=' + this.dateTo;
        const res = await fetch(url, { headers: this.getAuthHeaders() });
        if (res.ok) {
            const data = await res.json();
            this.orders = data.orders || [];
        } else if (res.status === 401) {
            window.location.href = '/login';
        }
        this.loading = false;
    },

    async loadStats() {
        let url = '/api/marketplace/orders/stats?company_id=' + this.$store.auth.currentCompany.id + '&marketplace_account_id={{ $accountId }}';
        if (this.dateFrom) url += '&from=' + this.dateFrom;
        if (this.dateTo) url += '&to=' + this.dateTo;
        const res = await fetch(url, { headers: this.getAuthHeaders() });
        if (res.ok) { this.stats = await res.json(); }
    },

    async loadFboOrders() {
        // FBO доступен для WB и Uzum
        this.fboLoading = true;
        try {
            let url = '/api/marketplace/orders/fbo?company_id=' + this.$store.auth.currentCompany.id + '&marketplace_account_id={{ $accountId }}';
            if (this.dateFrom) url += '&from=' + this.dateFrom;
            if (this.dateTo) url += '&to=' + this.dateTo;
            const res = await fetch(url, { headers: this.getAuthHeaders() });
            if (res.ok) {
                const data = await res.json();
                this.fboOrders = data.orders || [];
                this.fboStats = data.stats || { total: 0, by_status: {} };
            } else if (res.status === 401) {
                window.location.href = '/login';
            }
        } catch (e) {
            console.error('Error loading FBO orders:', e);
        }
        this.fboLoading = false;
    },

    tabLabel(tab) {
        if (this.accountMarketplace === 'uzum') {
            const map = { 'new': 'Новые', 'in_assembly': 'В сборке', 'in_supply': 'В поставке', 'accepted_uzum': 'Приняты', 'waiting_pickup': 'Выдача', 'issued': 'Выданы', 'cancelled': 'Отменены', 'returns': 'Возвраты' };
            return map[tab] || tab;
        }
        const map = { 'new': 'Новые', 'in_assembly': 'Сборка', 'in_delivery': 'Доставка', 'completed': 'Архив', 'cancelled': 'Отмена' };
        return map[tab] || tab;
    },

    normalizeStatus(order) {
        if (!order) return '';
        const st = (order.status || '').toLowerCase();
        if (this.accountMarketplace === 'uzum') {
            const apiStatus = order.raw_payload?.status;
            if (apiStatus === 10 || st === 'new' || st === 'awaiting_accept') return 'new';
            if (apiStatus === 20 || st === 'in_assembly' || st === 'processing') return 'in_assembly';
            if (apiStatus === 30 || st === 'in_supply' || st === 'awaiting_shipping') return 'in_supply';
            if (apiStatus === 40 || st === 'accepted_uzum' || st === 'shipped_to_uzum') return 'accepted_uzum';
            if (apiStatus === 50 || st === 'waiting_pickup') return 'waiting_pickup';
            if (apiStatus === 60 || st === 'issued' || st === 'completed') return 'issued';
            if (apiStatus === 70 || st === 'cancelled' || st === 'canceled') return 'cancelled';
            if (st === 'return' || st === 'returned') return 'returns';
            return st;
        }
        if (st === 'new' || st === 'pending') return 'new';
        if (st === 'in_assembly' || st === 'confirm' || st === 'complete' || st === 'sorted' || st === 'receive') return 'in_assembly';
        if (st === 'in_delivery' || st === 'send') return 'in_delivery';
        if (st === 'completed' || st === 'delivered' || st === 'done') return 'completed';
        if (st === 'cancelled' || st === 'canceled' || st === 'cancel') return 'cancelled';
        return st;
    },

    tabs() {
        if (this.accountMarketplace === 'uzum') {
            return ['new', 'in_assembly', 'in_supply', 'accepted_uzum', 'waiting_pickup', 'issued', 'cancelled', 'returns'];
        }
        return ['new', 'in_assembly', 'in_delivery', 'completed', 'cancelled'];
    },

    filteredOrders() {
        let result = this.orders.filter(o => this.normalizeStatus(o) === this.activeTab);
        if (this.searchQuery.trim()) {
            const q = this.searchQuery.toLowerCase().trim();
            result = result.filter(o =>
                (o.external_order_id || '').toLowerCase().includes(q) ||
                (o.wb_article || '').toLowerCase().includes(q) ||
                (o.product_name || '').toLowerCase().includes(q)
            );
        }
        return result;
    },

    tabCount(tab) {
        return this.orders.filter(o => this.normalizeStatus(o) === tab).length;
    },

    formatMoney(amount, currency = null) {
        const cur = currency || this.defaultCurrency || 'RUB';
        return new Intl.NumberFormat('ru-RU', { style: 'currency', currency: cur }).format(amount || 0);
    },

    formatPrice(kopecks) {
        return this.formatMoney((kopecks || 0) / 100);
    },

    formatDateTime(dateString) {
        if (!dateString) return '-';
        return new Date(dateString).toLocaleString('ru-RU', { day: '2-digit', month: '2-digit', hour: '2-digit', minute: '2-digit' });
    },

    getWbProductImageUrl(nmId, size = 'tm') {
        if (!nmId) return null;
        const vol = Math.floor(nmId / 100000);
        const part = Math.floor(nmId / 1000);
        const basket = ((nmId % 10) + 1).toString().padStart(2, '0');
        return 'https://basket-' + basket + '.wbbasket.ru/vol' + vol + '/part' + part + '/' + nmId + '/images/' + size + '/1.jpg';
    },

    viewOrder(order) {
        this.selectedOrder = order;
        this.showOrderModal = true;
        if(window.haptic) window.haptic.light();
    }
}" style="background: #f2f2f7;">
    <x-pwa-header title="Заказы" :backUrl="'/marketplace/' . $accountId">
        <button @click="loadOrders()" class="native-header-btn text-blue-600" onclick="if(window.haptic) window.haptic.light()">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
            </svg>
        </button>
    </x-pwa-header>

    <main class="native-scroll" style="padding-top: calc(44px + env(safe-area-inset-top, 0px)); padding-bottom: calc(70px + env(safe-area-inset-bottom, 0px)); padding-left: calc(12px + env(safe-area-inset-left, 0px)); padding-right: calc(12px + env(safe-area-inset-right, 0px)); min-height: 100vh;" x-pull-to-refresh="loadOrders">

        {{-- Tabs --}}
        <div class="mb-3 -mx-3 overflow-x-auto" style="scrollbar-width: none;">
            <div class="flex space-x-2 px-3" style="min-width: max-content;">
                <template x-for="tab in tabs()" :key="tab">
                    <button @click="activeTab = tab; if(window.haptic) window.haptic.light()"
                            :class="activeTab === tab ? 'bg-blue-600 text-white' : 'bg-white text-gray-700'"
                            class="px-3 py-1.5 rounded-full text-sm font-medium whitespace-nowrap shadow-sm">
                        <span x-text="tabLabel(tab)"></span>
                        <span class="ml-1 opacity-75" x-text="'(' + tabCount(tab) + ')'"></span>
                    </button>
                </template>
            </div>
        </div>

        {{-- Search --}}
        <div class="native-card mb-3">
            <input type="text" x-model="searchQuery" placeholder="Поиск по номеру, артикулу..."
                   class="native-input w-full">
        </div>

        {{-- Loading State --}}
        <template x-if="loading">
            <div class="native-card">
                <div class="flex items-center justify-center py-8">
                    <svg class="animate-spin h-8 w-8 text-blue-600" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                </div>
            </div>
        </template>

        {{-- Empty State --}}
        <template x-if="!loading && filteredOrders().length === 0">
            <div class="native-card">
                <div class="text-center py-8 text-gray-500">
                    <svg class="w-12 h-12 mx-auto mb-3 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
                    </svg>
                    <p class="native-body">Нет заказов</p>
                </div>
            </div>
        </template>

        {{-- Orders List --}}
        <template x-if="!loading && filteredOrders().length > 0">
            <div class="space-y-2">
                <template x-for="order in filteredOrders()" :key="order.id">
                    <div @click="viewOrder(order)" class="native-card active:bg-gray-50 cursor-pointer">
                        <div class="flex items-start space-x-3">
                            {{-- Product Image --}}
                            <div class="w-14 h-14 rounded-lg bg-gray-100 flex-shrink-0 overflow-hidden">
                                <template x-if="isWb() && order.raw_payload?.nmId">
                                    <img :src="getWbProductImageUrl(order.raw_payload?.nmId)"
                                         class="w-full h-full object-cover"
                                         onerror="this.style.display='none'">
                                </template>
                                <template x-if="!isWb() || !order.raw_payload?.nmId">
                                    <div class="w-full h-full flex items-center justify-center text-gray-400">
                                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
                                        </svg>
                                    </div>
                                </template>
                            </div>

                            {{-- Order Info --}}
                            <div class="flex-1 min-w-0">
                                <div class="flex items-center justify-between mb-1">
                                    <span class="font-semibold text-gray-900 text-sm" x-text="'#' + order.external_order_id"></span>
                                    <span class="text-xs font-medium px-2 py-0.5 rounded-full"
                                          :class="{
                                              'bg-blue-100 text-blue-700': normalizeStatus(order) === 'new',
                                              'bg-orange-100 text-orange-700': normalizeStatus(order) === 'in_assembly',
                                              'bg-purple-100 text-purple-700': normalizeStatus(order) === 'in_delivery' || normalizeStatus(order) === 'in_supply',
                                              'bg-green-100 text-green-700': normalizeStatus(order) === 'completed' || normalizeStatus(order) === 'issued',
                                              'bg-red-100 text-red-700': normalizeStatus(order) === 'cancelled'
                                          }"
                                          x-text="tabLabel(normalizeStatus(order))"></span>
                                </div>
                                <p class="text-sm text-gray-600 truncate" x-text="order.product_name || order.wb_article || '-'"></p>
                                <div class="flex items-center justify-between mt-1">
                                    <span class="text-xs text-gray-500" x-text="formatDateTime(order.ordered_at || order.created_at)"></span>
                                    <span class="text-sm font-medium text-gray-900" x-text="isWb() ? formatPrice(order.raw_payload?.convertedPrice || order.raw_payload?.price) : formatMoney(order.total_amount)"></span>
                                </div>
                            </div>

                            {{-- Arrow --}}
                            <svg class="w-5 h-5 text-gray-400 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                            </svg>
                        </div>
                    </div>
                </template>
            </div>
        </template>
    </main>

    {{-- Order Detail Modal --}}
    <div x-show="showOrderModal" x-cloak
         class="fixed inset-0 z-50"
         x-transition:enter="transition ease-out duration-300"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition ease-in duration-200"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0">
        <div class="absolute inset-0 bg-black/50" @click="showOrderModal = false"></div>
        <div class="absolute bottom-0 left-0 right-0 bg-white rounded-t-2xl max-h-[85vh] overflow-y-auto"
             style="padding-bottom: env(safe-area-inset-bottom, 20px);"
             x-transition:enter="transition ease-out duration-300"
             x-transition:enter-start="translate-y-full"
             x-transition:enter-end="translate-y-0"
             x-transition:leave="transition ease-in duration-200"
             x-transition:leave-start="translate-y-0"
             x-transition:leave-end="translate-y-full">
            <div class="sticky top-0 bg-white border-b border-gray-200 px-4 py-3 rounded-t-2xl">
                <div class="w-10 h-1 bg-gray-300 rounded-full mx-auto mb-3"></div>
                <div class="flex items-center justify-between">
                    <h3 class="text-lg font-semibold" x-text="'Заказ #' + (selectedOrder?.external_order_id || '')"></h3>
                    <button @click="showOrderModal = false" class="p-2 text-gray-500">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                    </button>
                </div>
            </div>

            <div class="p-4 space-y-4" x-show="selectedOrder">
                {{-- Status Badge --}}
                <div class="flex justify-center">
                    <span class="px-4 py-2 rounded-full text-sm font-medium"
                          :class="{
                              'bg-blue-100 text-blue-700': normalizeStatus(selectedOrder) === 'new',
                              'bg-orange-100 text-orange-700': normalizeStatus(selectedOrder) === 'in_assembly',
                              'bg-purple-100 text-purple-700': normalizeStatus(selectedOrder) === 'in_delivery' || normalizeStatus(selectedOrder) === 'in_supply',
                              'bg-green-100 text-green-700': normalizeStatus(selectedOrder) === 'completed' || normalizeStatus(selectedOrder) === 'issued',
                              'bg-red-100 text-red-700': normalizeStatus(selectedOrder) === 'cancelled'
                          }"
                          x-text="tabLabel(normalizeStatus(selectedOrder))"></span>
                </div>

                {{-- Product Info --}}
                <div class="native-card">
                    <div class="flex items-start space-x-4">
                        <div class="w-20 h-20 rounded-lg bg-gray-100 flex-shrink-0 overflow-hidden">
                            <template x-if="isWb() && selectedOrder?.raw_payload?.nmId">
                                <img :src="getWbProductImageUrl(selectedOrder?.raw_payload?.nmId, 'c246x328')"
                                     class="w-full h-full object-cover"
                                     onerror="this.style.display='none'">
                            </template>
                        </div>
                        <div class="flex-1">
                            <p class="font-medium text-gray-900" x-text="selectedOrder?.product_name || selectedOrder?.wb_article || '-'"></p>
                            <p class="text-sm text-gray-500 mt-1" x-text="'Артикул: ' + (selectedOrder?.wb_article || selectedOrder?.raw_payload?.article || '-')"></p>
                            <p class="text-lg font-semibold text-gray-900 mt-2" x-text="isWb() ? formatPrice(selectedOrder?.raw_payload?.convertedPrice || selectedOrder?.raw_payload?.price) : formatMoney(selectedOrder?.total_amount)"></p>
                        </div>
                    </div>
                </div>

                {{-- Order Details --}}
                <div class="native-card">
                    <h4 class="font-semibold text-gray-900 mb-3">Детали заказа</h4>
                    <div class="native-list">
                        <div class="native-list-item">
                            <span class="native-caption">Номер заказа</span>
                            <span class="native-body" x-text="selectedOrder?.external_order_id || '-'"></span>
                        </div>
                        <div class="native-list-item">
                            <span class="native-caption">Дата заказа</span>
                            <span class="native-body" x-text="formatDateTime(selectedOrder?.ordered_at || selectedOrder?.created_at)"></span>
                        </div>
                        <template x-if="isWb()">
                            <div class="native-list-item">
                                <span class="native-caption">Склад</span>
                                <span class="native-body" x-text="selectedOrder?.raw_payload?.warehouseName || '-'"></span>
                            </div>
                        </template>
                        <template x-if="selectedOrder?.raw_payload?.deliveryType">
                            <div class="native-list-item">
                                <span class="native-caption">Тип доставки</span>
                                <span class="native-body" x-text="selectedOrder?.raw_payload?.deliveryType?.toUpperCase() || '-'"></span>
                            </div>
                        </template>
                        <template x-if="selectedOrder?.raw_payload?.address">
                            <div class="native-list-item flex-col items-start">
                                <span class="native-caption">Адрес доставки</span>
                                <span class="native-body mt-1" x-text="selectedOrder?.raw_payload?.address || '-'"></span>
                            </div>
                        </template>
                    </div>
                </div>

                {{-- Actions --}}
                <div class="space-y-2 pb-4">
                    <button @click="showOrderModal = false" class="native-btn w-full bg-gray-200 text-gray-800">
                        Закрыть
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
