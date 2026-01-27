import axios from 'axios';
window.axios = axios;

window.axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';

// Global WebSocket connection for Reverb
let wsConnection = null;
let wsSocketId = null;
let wsConnected = false;
const subscribedChannels = new Set();
const requestedChannels = new Set(); // keep requested subscriptions across reconnects
let reconnectAttempts = 0;
const MAX_RECONNECT_ATTEMPTS = 5;
const BASE_RECONNECT_DELAY = 5000; // 5 seconds

function buildWsUrl() {
    // Prefer Vite envs (standard Laravel Reverb defaults)
    const scheme = (import.meta.env.VITE_REVERB_SCHEME || 'http').toLowerCase();
    let host = import.meta.env.VITE_REVERB_HOST || window.location.hostname;
    const port = import.meta.env.VITE_REVERB_PORT || (scheme === 'https' ? 443 : 80);
    const key = import.meta.env.VITE_REVERB_APP_KEY;
    const path = import.meta.env.VITE_REVERB_APP_PATH || '';

    if (!key || key === 'undefined' || key === '') {
        // WebSocket disabled - no key configured
        return null;
    }

    // In production, if host is localhost but we're on a real domain, disable WebSocket
    // This prevents connection errors when Reverb is not configured for production
    if (host === 'localhost' && window.location.hostname !== 'localhost' && window.location.hostname !== '127.0.0.1') {
        console.info('ℹ️ WebSocket disabled: Reverb not configured for production');
        return null;
    }

    const protocol = (scheme === 'https' || scheme === 'wss') ? 'wss' : 'ws';
    const normalizedPath = path ? `/${path.replace(/^\//, '')}` : '';

    return `${protocol}://${host}:${port}${normalizedPath}/app/${key}?protocol=7&client=js&version=8.4.0-rc2&flash=false`;
}

function connectGlobalWebSocket() {
    const wsUrl = buildWsUrl();
    if (!wsUrl) return;

    try {
        wsConnection = new WebSocket(wsUrl);

        wsConnection.onopen = () => {
            console.log('✅ Global WebSocket connected');
            wsConnected = true;
            reconnectAttempts = 0; // Reset on successful connection

            // Trigger custom event for pages that need to know
            window.dispatchEvent(new CustomEvent('websocket:connected'));

            // Re-subscribe to previously requested channels
            subscribedChannels.clear();
            requestedChannels.forEach(channel => {
                window.subscribeToChannel(channel);
            });
        };

        wsConnection.onmessage = (event) => {
            let data;
            try {
                data = JSON.parse(event.data);
            } catch (e) {
                console.error('❌ Failed to parse WebSocket message', e);
                return;
            }

            // Get socket ID on connection
            if (data.event === 'pusher:connection_established') {
                const connectionData = JSON.parse(data.data);
                wsSocketId = connectionData.socket_id;
                console.log('Global Socket ID:', wsSocketId);
            }

            // Handle subscription success
            if (data.event === 'pusher:subscription_succeeded') {
                console.log('✅ Subscription succeeded:', data.channel);
            }

            // Relay all channel events to pages via custom events
            if (data.event && data.channel && !data.event.startsWith('pusher:')) {
                window.dispatchEvent(new CustomEvent('websocket:message', {
                    detail: {
                        channel: data.channel,
                        event: data.event,
                        data: data.data ? JSON.parse(data.data) : null
                    }
                }));
            }
        };

        wsConnection.onerror = (error) => {
            // Only log error if we've successfully connected before (to avoid spam on initial failure)
            if (reconnectAttempts === 0) {
                console.warn('⚠️ WebSocket connection failed (will retry silently)');
            }
            wsConnected = false;
        };

        wsConnection.onclose = () => {
            wsConnected = false;
            subscribedChannels.clear();
            wsConnection = null;

            // Trigger custom event
            window.dispatchEvent(new CustomEvent('websocket:disconnected'));

            // Reconnect with exponential backoff
            reconnectAttempts++;

            if (reconnectAttempts <= MAX_RECONNECT_ATTEMPTS) {
                const delay = BASE_RECONNECT_DELAY * Math.pow(1.5, reconnectAttempts - 1);

                if (reconnectAttempts === 1) {
                    console.log('🔌 WebSocket disconnected, will retry...');
                }

                setTimeout(() => connectGlobalWebSocket(), delay);
            } else {
                console.warn('⚠️ WebSocket connection failed after', MAX_RECONNECT_ATTEMPTS, 'attempts. Real-time updates disabled.');
            }
        };
    } catch (error) {
        console.error('Failed to connect Global WebSocket:', error);
        wsConnected = false;
    }
}

// Global function to subscribe to channels
window.subscribeToChannel = function(channelName) {
    requestedChannels.add(channelName);

    if (!wsConnection || wsConnection.readyState !== WebSocket.OPEN) {
        // Silently queue for later - don't spam console
        return false;
    }

    // Avoid duplicate subscriptions
    if (subscribedChannels.has(channelName)) {
        return true;
    }

    // Subscribe to public channel (no authentication needed)
    wsConnection.send(JSON.stringify({
        event: 'pusher:subscribe',
        data: {
            channel: channelName
        }
    }));

    subscribedChannels.add(channelName);
    return true;
};

// Global function to unsubscribe from channels
window.unsubscribeFromChannel = function(channelName) {
    requestedChannels.delete(channelName);

    if (!wsConnection || wsConnection.readyState !== WebSocket.OPEN) {
        return;
    }

    wsConnection.send(JSON.stringify({
        event: 'pusher:unsubscribe',
        data: {
            channel: channelName
        }
    }));

    subscribedChannels.delete(channelName);
};

// Getters for WebSocket state
window.getWebSocketState = function() {
    return {
        connected: wsConnected,
        socketId: wsSocketId,
        connection: wsConnection,
        reconnectAttempts: reconnectAttempts
    };
};

// Force reconnect (reset attempt counter)
window.reconnectWebSocket = function() {
    if (wsConnection) {
        wsConnection.close();
    }
    reconnectAttempts = 0;
    connectGlobalWebSocket();
};

// Auto-connect when page loads
connectGlobalWebSocket();
