document.addEventListener('DOMContentLoaded', function () {
    const customSelects = document.querySelectorAll('.custom-select-wrapper');

    customSelects.forEach(wrapper => {
        const select = wrapper.querySelector('select');
        if (!select) return;

        // Hide original select
        select.style.display = 'none';

        // Create trigger
        const trigger = document.createElement('div');
        trigger.className = 'custom-select-trigger';

        const currentLabel = document.createElement('span');
        // Initial label
        const selectedOption = select.options[select.selectedIndex];
        currentLabel.textContent = selectedOption.textContent;
        if (select.value === "") currentLabel.style.color = "#999";

        const arrow = document.createElement('i');
        arrow.className = 'fa-solid fa-chevron-down arrow';

        trigger.appendChild(currentLabel);
        trigger.appendChild(arrow);
        wrapper.appendChild(trigger);

        // Create options container
        const optionsContainer = document.createElement('div');
        optionsContainer.className = 'custom-options';

        Array.from(select.options).forEach(option => {
            if (option.disabled && option.value === "") return;

            const customOption = document.createElement('div');
            customOption.className = 'custom-option';
            customOption.textContent = option.textContent;
            customOption.dataset.value = option.value;

            if (option.selected && option.value !== "") {
                customOption.classList.add('selected');
            }

            customOption.addEventListener('click', (e) => {
                e.stopPropagation();

                // Update original select
                select.value = option.value;
                select.dispatchEvent(new Event('change'));

                // Update UI
                currentLabel.textContent = option.textContent;
                currentLabel.style.color = "#333";

                optionsContainer.querySelectorAll('.custom-option').forEach(opt => opt.classList.remove('selected'));
                customOption.classList.add('selected');

                optionsContainer.classList.remove('open');
                wrapper.closest('.input-group').classList.remove('select-open');
            });

            optionsContainer.appendChild(customOption);
        });

        wrapper.appendChild(optionsContainer);

        // Toggle dropdown
        trigger.addEventListener('click', (e) => {
            e.stopPropagation();

            // Close other custom selects first
            document.querySelectorAll('.custom-options.open').forEach(openMenu => {
                if (openMenu !== optionsContainer) {
                    openMenu.classList.remove('open');
                    openMenu.closest('.input-group').classList.remove('select-open');
                }
            });

            optionsContainer.classList.toggle('open');
            wrapper.closest('.input-group').classList.toggle('select-open');
        });
    });

    // Close when clicking outside
    document.addEventListener('click', () => {
        document.querySelectorAll('.custom-options.open').forEach(openMenu => {
            openMenu.classList.remove('open');
            openMenu.closest('.input-group').classList.remove('select-open');
        });
    });

    // === Initialize Flatpickr for Birthday ===
    const birthdayInput = document.getElementById('birthday');
    if (birthdayInput) {
        flatpickr(birthdayInput, {
            dateFormat: "Y-m-d",
            altInput: true,
            altFormat: "F j, Y",
            allowInput: true,
            monthSelectorType: 'dropdown',
            yearSelectorType: 'dropdown',
            maxDate: "today", // Birthdays are usually in the past
            // Customizing for a formal look
            onOpen: function (selectedDates, dateStr, instance) {
                instance.calendarContainer.classList.add('premium-calendar');
            }
        });
    }
});
