/**
 * JDE_ADMIN/js/admin-chat-controller.js
 * Initialization and event handling for the Admin Chat Page.
 */

(function() {
    // 1. Initialize logic for Chat API base URL
    window.JDE_CHAT_BASE = (function() {
        const parts = window.location.pathname.split('/');
        const rootIdx = parts.findIndex(p => p === 'JDE_WORKS');
        if (rootIdx === -1) return '/JDE_USER/backend/'; // Fallback
        return window.location.origin + parts.slice(0, rootIdx + 1).join('/') + '/JDE_USER/backend/';
    })();


    // 3. UI Helper for Mobile/Viewport Adjustments
    window.addEventListener('resize', function() {
        const chatMain = document.querySelector('.chat-main-wrapper');
        if (chatMain) {
            chatMain.style.height = (window.innerHeight - 75) + 'px';
        }
    });
})();
