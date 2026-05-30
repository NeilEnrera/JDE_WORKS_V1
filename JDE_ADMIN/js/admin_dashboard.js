/**
 * Admin Dashboard Premium UI Essentials
 * Handles SSE updates, sophisticated animations, and visual feedback.
 */

document.addEventListener('DOMContentLoaded', function () {
    const apiPath = '../../JDE_USER/backend/chat_stream.php';
    const statsApiPath = '../../JDE_ADMIN/backend/admin_api.php';

    // 1. Sophisticated Entrance Animations (Intersection Observer)
    const observerOptions = {
        threshold: 0.1,
        rootMargin: '0px 0px -50px 0px'
    };

    const entranceObserver = new IntersectionObserver((entries) => {
        entries.forEach((entry, index) => {
            if (entry.isIntersecting) {
                setTimeout(() => {
                    entry.target.classList.add('reveal');
                }, index * 100);
                entranceObserver.unobserve(entry.target);
            }
        });
    }, observerOptions);

    const animElements = document.querySelectorAll('.alert-card, .nav-card, .stat-card, .orders-section, .welcome-section');
    animElements.forEach(el => {
        el.classList.add('prepare-reveal');
        entranceObserver.observe(el);
    });

    // SSE initialization removed (now handled globally in scripts.php)
    // Local listeners removed to prevent connection conflicts

    function refreshDashboardStats() {
        const timestamp = new Date().getTime();
        fetch(`${statsApiPath}?action=get_dashboard_stats&_=${timestamp}`)
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    console.log('Dashboard Stats Refreshed:', data.stats);
                    updateStatsUI(data.stats);
                    updateRecentOrdersTable(data.stats.recentOrders);
                }
            })
            .catch(err => console.error('Error fetching stats:', err));
    }
    window.refreshDashboardStats = refreshDashboardStats;

    function updateStatsUI(stats) {
        if (stats.totalOrders !== undefined) setCount('totalOrdersCount', stats.totalOrders);
        if (stats.pendingOrders !== undefined) setCount('pendingOrdersCount', stats.pendingOrders);
        if (stats.totalUsers !== undefined) setCount('totalUsersCount', stats.totalUsers);

        // Combined Unread Messages (Inquiries + Chats)
        const inquiries = stats.totalInquiries || 0;
        const chats = stats.unreadChatUsers || 0;
        const totalUnread = inquiries + chats;
        setCount('unreadInquiriesCount', totalUnread);

        // Revenue Updates
        if (stats.dailyRevenue !== undefined) {
            updateValue('dailyRevenueValue', stats.dailyRevenue.toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 }));
        }
        if (stats.monthlyRevenue !== undefined) {
            updateValue('monthlyRevenueValue', stats.monthlyRevenue.toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 }));
        }

        // Status Breakdown & Progress Bars
        if (stats.statusBreakdown && stats.totalOrders) {
            const statuses = ['Pending', 'Confirmed', 'Pickup', 'Delivery', 'Completed', 'Cancelled'];
            statuses.forEach(stat => {
                const count = stats.statusBreakdown[stat] || 0;
                const percentage = (count / stats.totalOrders) * 100;
                const id = stat.toLowerCase();

                setCount(`statusCount_${id}`, count);
                const bar = document.getElementById(`statusProgress_${id}`);
                if (bar) bar.style.width = `${percentage}%`;
            });
        }

        // Also update the global sidebar badge if function exists (from scripts.php)
        if (typeof updateSidebarBadgeUI === 'function') {
            updateSidebarBadgeUI(totalUnread);
        }

        if (stats.upcomingAppointments !== undefined) setCount('todayAppointmentsCount', stats.upcomingAppointments);

        // Recent Activity Feed
        if (stats.recentActivity) {
            updateActivityFeedUI(stats.recentActivity);
        }
    }
    window.updateStatsUI = updateStatsUI;

    function updateValue(id, val) {
        const el = document.getElementById(id);
        if (el && el.textContent != val) {
            el.textContent = val;
            el.classList.add('count-update');
            setTimeout(() => el.classList.remove('count-update'), 500);
        }
    }

    function setCount(id, val) {
        const el = document.getElementById(id);
        if (el && el.textContent != val) {
            el.textContent = val;
            el.classList.add('count-update');
            setTimeout(() => el.classList.remove('count-update'), 500);
        }
    }

    function flashCard(id) {
        const el = document.getElementById(id);
        if (el) {
            const card = el.closest('.alert-card, .nav-card, .stat-card');
            if (card) {
                card.classList.add('premium-glow');
                setTimeout(() => card.classList.remove('premium-glow'), 2000);
            }
        }
    }

    let previousOrderIDs = [];

    function updateRecentOrdersTable(orders) {
        const tableBody = document.querySelector('#recentOrdersTable tbody');
        if (!tableBody) return;

        if (!orders || orders.length === 0) {
            tableBody.innerHTML = '<tr><td colspan="4" style="text-align: center;">No orders currently in processing</td></tr>';
            previousOrderIDs = [];
            return;
        }

        const currentOrderIDs = orders.map(o => o.orderID);
        let html = '';

        orders.forEach(order => {
            const formattedID = String(order.orderID).padStart(2, '0');
            const date = order.date !== 'N/A' ? new Date(order.date).toISOString().split('T')[0] : 'N/A';
            const statusClass = order.status.toLowerCase().replace(' ', '-');
            const isNew = previousOrderIDs.length > 0 && !previousOrderIDs.includes(order.orderID);
            const rowClass = isNew ? 'class="new-row-reveal"' : '';

            html += `
                <tr ${rowClass}>
                    <td><strong>#${formattedID}</strong></td>
                    <td>${order.customerName}</td>
                    <td>${date}</td>
                    <td><span class="status-badge ${statusClass}">${order.status}</span></td>
                </tr>
            `;
        });

        tableBody.innerHTML = html;
        previousOrderIDs = currentOrderIDs;
    }

    // pulseLiveIndicator removed (replaced by sseStatusIndicator in scripts.php)

    function updateActivityFeedUI(activities) {
        console.log('Updating Activity Feed UI with', activities?.length || 0, 'items');
        const feed = document.getElementById('activityFeed');
        if (!feed) return;

        // Add a temporary highlight class to show an update happened
        feed.classList.add('feed-update-pulse');
        setTimeout(() => feed.classList.remove('feed-update-pulse'), 1000);

        if (!activities || activities.length === 0) {
            feed.innerHTML = '<div class="empty-feed">No recent activity detected.</div>';
            return;
        }

        let html = '';
        activities.forEach(act => {
            let icon = 'bi-envelope';
            if (act.type === 'order') icon = 'bi-bag-check';
            else if (act.type === 'appointment') icon = 'bi-calendar-plus';
            else if (act.type === 'upcoming') icon = 'bi-calendar-check';
            else if (act.type === 'chat') icon = 'bi-chat-dots';

            let mainText = '';
            switch (act.type) {
                case 'order': mainText = 'placed a new order'; break;
                case 'appointment': mainText = 'booked an appointment'; break;
                case 'upcoming': mainText = 'has an upcoming appointment'; break;
                case 'chat': mainText = `sent a message: ${act.status}`; break;
                case 'message': mainText = `sent an inquiry: ${act.status}`; break;
            }

            // Consistent time formatting with a more robust parser for MySQL dates
            let time = 'N/A';
            if (act.timestamp) {
                // Replace space with T for ISO compatibility (YYYY-MM-DD HH:MM:SS -> YYYY-MM-DDTHH:MM:SS)
                const isoTimestamp = act.timestamp.replace(' ', 'T');
                const dateObj = new Date(isoTimestamp);
                if (!isNaN(dateObj.getTime())) {
                    time = dateObj.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
                }
            }

            // Redirect Link Logic
            let link = '#';
            if (act.type === 'order') link = `orders.php?id=${act.id}`;
            else if (act.type === 'appointment' || act.type === 'upcoming') link = `appointments.php?id=${act.id}`;
            else if (act.type === 'chat') {
                const custId = act.customerID || act.id;
                link = `../../JDE_ADMIN/backend/admin_chat.php?customerID=${custId}`;
            }
            else if (act.type === 'message') link = `inquiries.php?id=${act.id}`;

            html += `
                <div class="activity-item ${act.type}">
                    <div class="activity-type-icon"><i class="bi ${icon}"></i></div>
                    <div class="activity-body">
                        <div class="activity-main">
                            <strong>${act.customer}</strong>
                            <span>${mainText}</span>
                        </div>
                        <div class="activity-time">${time}</div>
                    </div>
                    <div class="activity-actions">
                        <a href="${link}" class="btn-quick-action" title="View Details">
                            <i class="bi bi-chevron-right"></i>
                        </a>
                    </div>
                </div>
            `;
        });

        feed.innerHTML = html;
    }

    // SSE error handling removed (handled globally in scripts.php)

    // 6. Initialize Charts
    if (typeof Chart !== 'undefined') {
        initCategoryDistChart();
    }

    function initCategoryDistChart() {
        const ctx = document.getElementById('categoryDistChart');
        if (!ctx) return;

        const labels = categoryDistData.map(d => d.categoryName);
        const counts = categoryDistData.map(d => parseInt(d.count));

        new Chart(ctx, {
            type: 'doughnut',
            data: {
                labels: labels,
                datasets: [{
                    data: counts,
                    backgroundColor: [
                        '#d6b25e',
                        '#0b2e46',
                        '#3498db',
                        '#2ecc71',
                        '#9b59b6'
                    ],
                    borderWidth: 0,
                    hoverOffset: 10
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'bottom',
                        labels: {
                            usePointStyle: true,
                            padding: 20,
                            color: 'rgba(0,0,0,0.6)',
                            font: { weight: '600', size: 12 }
                        }
                    }
                },
                cutout: '70%'
            }
        });
    }
});
