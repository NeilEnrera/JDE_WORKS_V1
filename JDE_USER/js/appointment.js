// Appointment Page JavaScript

document.addEventListener('DOMContentLoaded', function () {
    // === Variables ===
    const dateInput = document.getElementById('appointment_date');
    const timeSelect = document.getElementById('appointment_time');
    const calendarDays = document.getElementById('calendarDays');
    const currentMonthEl = document.getElementById('currentMonth');
    const prevMonthBtn = document.getElementById('prevMonth');
    const nextMonthBtn = document.getElementById('nextMonth');

    // === Success Modal Handling ===
    const successModalEl = document.getElementById('successModal');
    if (successModalEl) {
        const successModal = new bootstrap.Modal(successModalEl);
        successModal.show();
        
        // Show Reminder Box when Success Modal is closed
        successModalEl.addEventListener('hidden.bs.modal', function () {
            const reminderBox = document.getElementById('importantReminderBox');
            if (reminderBox) {
                reminderBox.style.display = 'block';
                
                // Auto-hide after 5 seconds
                setTimeout(() => {
                    reminderBox.style.transition = 'opacity 0.5s ease';
                    reminderBox.style.opacity = '0';
                    setTimeout(() => {
                        reminderBox.style.display = 'none';
                        reminderBox.style.opacity = '1'; // Reset for next time
                    }, 500);
                }, 5000);
            }
        });
    }

    let currentDate = new Date(); // Current viewing month
    let selectedDate = null; // The selected date object
    let bookedSlots = []; // Array of {date, period, count} objects
    let blockedDates = []; // Array of strings (YYYY-MM-DD)

    // Set min date to tomorrow
    const minDate = new Date();
    minDate.setDate(minDate.getDate() + 1);
    minDate.setHours(0, 0, 0, 0);

    // === Initialization ===
    // Fetch data first, THEN init the calendar to avoid showing full dates as available
    fetchBookedSlots().then(() => {
        initCalendar();
    });

    // Real-time polling (every 30 seconds)
    setInterval(fetchBookedSlots, 30000);

    // Refresh when user returns to the tab
    document.addEventListener('visibilitychange', () => {
        if (document.visibilityState === 'visible') {
            fetchBookedSlots();
        }
    });

    // === Event Listeners ===
    prevMonthBtn.addEventListener('click', () => {
        currentDate.setMonth(currentDate.getMonth() - 1);
        renderCalendar();
    });

    nextMonthBtn.addEventListener('click', () => {
        currentDate.setMonth(currentDate.getMonth() + 1);
        renderCalendar();
    });

    // === Functions ===

    async function fetchBookedSlots() {
        try {
            const response = await fetch('appointment_api.php?action=get_booked_slots');
            const data = await response.json();

            if (data.success) {
                const oldBookings = JSON.stringify(bookedSlots);
                const oldBlocked = JSON.stringify(blockedDates);
                bookedSlots = data.bookings || [];
                blockedDates = data.blocked_dates || [];

                // Re-render and update time slots if data has changed or it's the first time
                if (oldBookings !== JSON.stringify(bookedSlots) || oldBlocked !== JSON.stringify(blockedDates) || calendarDays.children.length > 0) {
                    renderCalendar();
                    updateTimeSlots();
                }
            } else {
                console.error('Failed to fetch booked slots:', data.error);
            }
        } catch (error) {
            console.error('Error fetching booked slots:', error);
        }
    }

    function initCalendar() {
        // If there's already a value in the date input (e.g. after form error), use it
        if (dateInput.value) {
            const parts = dateInput.value.split('-');
            if (parts.length === 3) {
                // Use UTC-safe date creation
                selectedDate = new Date(parts[0], parts[1] - 1, parts[2]);
                selectedDate.setHours(0, 0, 0, 0);
                currentDate = new Date(selectedDate);
            }
        }

        renderCalendar();
        updateTimeSlots();
    }

    function renderCalendar() {
        // Clear existing days
        calendarDays.innerHTML = '';

        const year = currentDate.getFullYear();
        const month = currentDate.getMonth();

        // Update Header
        const monthName = new Intl.DateTimeFormat('en-US', { month: 'long', year: 'numeric' }).format(currentDate);
        currentMonthEl.textContent = monthName;

        // Days calculation
        const firstDayOfMonth = new Date(year, month, 1).getDay(); // 0 is Sunday
        const daysInMonth = new Date(year, month + 1, 0).getDate();

        // Previous month days to fill grid
        const daysInPrevMonth = new Date(year, month, 0).getDate();

        // --- Render Previous Month Padding ---
        for (let i = firstDayOfMonth; i > 0; i--) {
            const dayDiv = document.createElement('div');
            dayDiv.textContent = daysInPrevMonth - i + 1;
            dayDiv.classList.add('calendar-day', 'other-month');
            calendarDays.appendChild(dayDiv);
        }

        // --- Render Current Month Days ---
        for (let i = 1; i <= daysInMonth; i++) {
            const dayDiv = document.createElement('div');
            dayDiv.textContent = i;
            dayDiv.classList.add('calendar-day');

            // Create date object for this day
            const thisDate = new Date(year, month, i);
            thisDate.setHours(0, 0, 0, 0);

            // Get date string for checking bookings
            const dateString = getDateString(thisDate);

            // Check if selected
            if (selectedDate &&
                thisDate.getDate() === selectedDate.getDate() &&
                thisDate.getMonth() === selectedDate.getMonth() &&
                thisDate.getFullYear() === selectedDate.getFullYear()) {
                dayDiv.classList.add('selected');
            }

            // Check if today
            const today = new Date();
            today.setHours(0, 0, 0, 0);
            if (thisDate.getTime() === today.getTime()) {
                dayDiv.classList.add('today');
            }

            // Check if fully booked (all 10 slots are taken)
            const dayBookings = bookedSlots.filter(slot => slot.date === dateString);
            const totalBookedSlots = dayBookings.reduce((sum, slot) => sum + (slot.count >= 1 ? 1 : 0), 0);
            
            // We have 10 total specific time slots available in the form
            // Use count logic instead of strict comparison
            const isFullyBooked = totalBookedSlots >= 10;
            const isBlockedByAdmin = blockedDates.includes(dateString);

            // Check validity (min date)
            if (thisDate < minDate) {
                dayDiv.classList.add('disabled');
            } else if (thisDate.getDay() === 0) {
                // Sunday is closed
                dayDiv.classList.add('disabled', 'sunday-closed');
                dayDiv.title = 'Store is closed on Sundays';
            } else if (isFullyBooked) {
                // Date is fully booked
                dayDiv.classList.add('disabled', 'fully-booked');
                dayDiv.title = 'This date is fully booked';
            } else if (isBlockedByAdmin) {
                // Date is blocked by admin
                dayDiv.classList.add('disabled');
                dayDiv.title = 'This date is unavailable';
                dayDiv.style.cursor = 'not-allowed';
            } else {
                // Add click event for selectable valid dates
                dayDiv.addEventListener('click', () => {
                    selectDate(thisDate);
                });
            }

            calendarDays.appendChild(dayDiv);
        }

        // --- Render Next Month Padding ---
        const totalCells = firstDayOfMonth + daysInMonth;
        const nextMonthPadding = 7 - (totalCells % 7);

        if (nextMonthPadding < 7) {
            for (let i = 1; i <= nextMonthPadding; i++) {
                const dayDiv = document.createElement('div');
                dayDiv.textContent = i;
                dayDiv.classList.add('calendar-day', 'other-month');
                calendarDays.appendChild(dayDiv);
            }
        }
    }

    function getDateString(date) {
        if (!date) return '';
        const y = date.getFullYear();
        const m = String(date.getMonth() + 1).padStart(2, '0');
        const d = String(date.getDate()).padStart(2, '0');
        return `${y}-${m}-${d}`;
    }

    function selectDate(date) {
        selectedDate = new Date(date);
        selectedDate.setHours(0, 0, 0, 0);

        dateInput.value = getDateString(selectedDate);

        // Update time slots based on selected date
        updateTimeSlots();

        // Re-render to show selection styling
        renderCalendar();
    }

    function updateTimeSlots() {
        if (!selectedDate) return;

        const dateString = getDateString(selectedDate);
        const periodOptions = timeSelect.querySelectorAll('option');

        periodOptions.forEach(option => {
            if (option.value === '') return;

            // Normalize strings to ensure match (trim whitespace and collapse spaces)
            const normalizedOptionVal = option.value.trim().replace(/\s+/g, ' ').toLowerCase();
            
            const booking = bookedSlots.find(slot =>
                slot.date === dateString && 
                slot.period.trim().replace(/\s+/g, ' ').toLowerCase() === normalizedOptionVal
            );

            const count = booking ? booking.count : 0;
            const isBooked = count >= 1;

            if (isBooked) {
                option.disabled = true;
                option.style.color = '#dc3545';
                option.style.fontWeight = 'bold';
                if (!option.textContent.includes('(booked)')) {
                    option.textContent = option.text.split(' (')[0] + ' (booked)';
                }
            } else {
                option.disabled = false;
                option.style.color = '';
                option.style.fontWeight = '';
                option.textContent = option.text.split(' (')[0];
            }
        });

        // Reset selected period if it's now booked
        if (timeSelect.value) {
            const selectedOption = timeSelect.querySelector(`option[value="${timeSelect.value}"]`);
            if (selectedOption && selectedOption.disabled) {
                timeSelect.value = '';
            }
        }
    }

    // === Finalize ===
    const rescheduleModalEl = document.getElementById('rescheduleWarningModal');
    if (rescheduleModalEl) {
        new bootstrap.Modal(rescheduleModalEl).show();
    }
});

function syncRescheduleDismissal() {
    fetch('appointment_api.php?action=dismiss_reschedule_notice', { method: 'POST' })
        .catch(err => console.error('Failed to sync dismissal:', err));
}
