{{-- Marketplace Auth Helper - Include this in marketplace pages for consistent token handling --}}
<script>
// Universal token getter for marketplace pages
window.getMarketplaceToken = function() {
    // Try Alpine store first (if Alpine is loaded)
    if (window.Alpine && Alpine.store && Alpine.store('auth') && Alpine.store('auth').token) {
        return Alpine.store('auth').token;
    }

    // Try Alpine persist format
    const persistToken = localStorage.getItem('_x_auth_token');
    if (persistToken) {
        try {
            return JSON.parse(persistToken);
        } catch (e) {
            return persistToken;
        }
    }

    // Fallback to plain tokens
    return localStorage.getItem('auth_token') || localStorage.getItem('token');
};

// Universal auth headers getter
window.getMarketplaceAuthHeaders = function() {
    const token = window.getMarketplaceToken();
    return {
        'Authorization': 'Bearer ' + token,
        'Accept': 'application/json',
        'Content-Type': 'application/json'
    };
};

// Check if user is authenticated
window.checkMarketplaceAuth = async function() {
    const token = window.getMarketplaceToken();
    if (!token) {
        console.warn('No auth token found, redirecting to login');
        window.location.href = '/login';
        return false;
    }
    return true;
};
</script>
