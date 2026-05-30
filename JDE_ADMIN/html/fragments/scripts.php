<!-- JDE_ADMIN/html/fragments/scripts.php -->
<script>
    document.addEventListener('DOMContentLoaded', function () {
        // Sidebar toggle functionality
        const sidebarToggle = document.getElementById('sidebarToggle');
        const adminLayout = document.querySelector('.admin-layout');

        if (sidebarToggle && adminLayout) {
            // Check for saved state
            const isCollapsed = localStorage.getItem('sidebarCollapsed') === 'true';
            if (isCollapsed) {
                adminLayout.classList.add('sidebar-collapsed');
            }

            sidebarToggle.addEventListener('click', function () {
                adminLayout.classList.toggle('sidebar-collapsed');
                localStorage.setItem('sidebarCollapsed', adminLayout.classList.contains('sidebar-collapsed'));
            });
        }

        // --- Sidebar Notification System ---
        const sidebarBadge = document.getElementById('sidebarUnreadBadge');
        if (sidebarBadge) {
            // Initial fetch from Admin API (using userBase since admin_api is in JDE_USER)
            fetch('<?php echo $adminBase; ?>admin_api.php?action=get_dashboard_stats')
                .then(r => r.json())
                .then(data => {
                    if (data.success && data.stats) {
                        // Correct keys to match admin_api.php response
                        updateSidebarBadgeUI(data.stats.totalInquiries + data.stats.unreadChatUsers);
                    }
                }).catch(e => console.error('Sidebar badge initial fetch failed:', e));

            // --- Silent Real-time System ---
            let ssePulse = Date.now();
            let fallbackTimer = null;
            let sseInstance = null;

            function initSSE() {
                if (sseInstance) sseInstance.close();

                // Building backend URL relative to current location
                const loc = window.location;
                const pathParts = loc.pathname.split('/');
                const rootIdx = pathParts.findIndex(p => p === 'JDE_WORKS');
                const backendBase = loc.origin + pathParts.slice(0, rootIdx + 1).join('/') + '/JDE_ADMIN/backend/';
                const dashSseUrl = backendBase + 'admin_dashboard_stream.php';

                console.log('JDE Real-time: Initiating...');
                sseInstance = new EventSource(dashSseUrl);

                sseInstance.onopen = () => {
                    ssePulse = Date.now();
                    stopFallbackPolling();
                };

                sseInstance.onerror = () => {
                    startFallbackPolling();
                    sseInstance.close();
                    setTimeout(initSSE, 10000); // Retry live connection after 10s
                };

                sseInstance.addEventListener('ping', () => ssePulse = Date.now());

                sseInstance.addEventListener('dashboard_stats', function (e) {
                    ssePulse = Date.now();
                    try {
                        const stats = JSON.parse(e.data);
                        updateSidebarBadgeUI((stats.totalInquiries || 0) + (stats.unreadChatUsers || 0));
                        if (typeof window.updateStatsUI === 'function') window.updateStatsUI(stats);
                    } catch (err) { }
                });

                const globalEvents = ['new_order', 'new_appointment', 'new_appointment_booked', 'new_inquiry', 'new_message', 'payment_verified'];
                globalEvents.forEach(evt => {
                    sseInstance.addEventListener(evt, (e) => {
                        ssePulse = Date.now();
                        if (typeof window.refreshDashboardStats === 'function') window.refreshDashboardStats();
                    });
                });
            }

            // Connection Health Monitor (Faster Failover)
            setInterval(() => {
                const now = Date.now();
                if (now - ssePulse > 8000) { // No signal for 8s
                    startFallbackPolling();
                }
            }, 4000);

            function startFallbackPolling() {
                if (fallbackTimer) return;
                console.log('JDE Real-time: Background mode active.');
                fallbackTimer = setInterval(() => {
                    if (typeof window.refreshDashboardStats === 'function') {
                        window.refreshDashboardStats();
                    }
                }, 5000); // 5s polling fallback
            }

            function stopFallbackPolling() {
                if (fallbackTimer) {
                    console.log('JDE Real-time: Live mode active.');
                    clearInterval(fallbackTimer);
                    fallbackTimer = null;
                }
            }

            initSSE();
        }

    });

    function updateSidebarBadgeUI(count) {
        const sidebarBadge = document.getElementById('sidebarUnreadBadge');
        if (!sidebarBadge) return;
        const total = parseInt(count) || 0;
        if (total > 0) {
            sidebarBadge.textContent = total;
            sidebarBadge.style.display = 'inline-block';
        } else {
            sidebarBadge.style.display = 'none';
        }
    }

    // Logout Confirmation Handler
    document.addEventListener('click', function (e) {
        const link = e.target.closest('.logout-link');
        if (!link) return;

        // If it's already a link with a target (like the one in the modal), let it proceed
        if (link.getAttribute('href') && link.getAttribute('href') !== 'javascript:void(0)') return;

        const modalEl = document.getElementById('logoutModal');
        if (modalEl) {
            e.preventDefault();
            modalEl.classList.add('active');
        } else {
            window.location.href = '../../JDE_USER/backend/logout.php';
        }
    }, false);

    window.closeLogoutModal = function () {
        const modalEl = document.getElementById('logoutModal');
        if (modalEl) modalEl.classList.remove('active');
    };

    // --- Pseudo-Cron: Automatic Appointment Reminders ---
    // Fires silently on every admin page load.
    // send_reminders.php handles all guards: time-window check + daily-once flag.
    (function pingReminderCron() {
        try {
            const loc = window.location;
            const pathParts = loc.pathname.split('/');
            const rootIdx = pathParts.findIndex(function(p) { return p === 'JDE_WORKS'; });
            if (rootIdx === -1) return;
            const cronUrl = loc.origin +
                pathParts.slice(0, rootIdx + 1).join('/') +
                '/JDE_ADMIN/backend/send_reminders.php?key=jde_internal_cron_2024';
            fetch(cronUrl, { method: 'GET', cache: 'no-store' }).catch(function() {});
        } catch(e) {}
    })();
</script>