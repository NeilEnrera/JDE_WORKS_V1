document.addEventListener('DOMContentLoaded', function () {
    // --- 1. SELECTIONS ---
    const form = document.getElementById('regForm');
    const phoneInput = document.getElementById('phoneInput');
    const passwordInput = document.getElementById('passwordInput');
    const confirmInput = document.getElementById('confirmPasswordInput');
    
    // Modal Elements
    const openTerms = document.getElementById("openTerms");
    const closeTerms = document.getElementById("closeTerms");
    const acceptTerms = document.getElementById("acceptTerms");
    const modal = document.getElementById("termsModal");
    const checkbox = document.getElementById("terms");

    // --- 2. MODAL LOGIC ---
    if (openTerms && modal) {
        openTerms.addEventListener("click", (e) => {
            e.preventDefault(); // Stop page jump
            modal.style.display = "flex";
        });
    }

    if (closeTerms) {
        closeTerms.addEventListener("click", () => {
            modal.style.display = "none"; // Fixed: Was set to flex
        });
    }

    if (acceptTerms) {
        acceptTerms.addEventListener("click", () => {
            checkbox.disabled = false;
            checkbox.checked = true;
            modal.style.display = "none";
            if (openTerms) openTerms.classList.remove('highlight-terms');
        });
    }



    window.addEventListener("click", (e) => {
        if (e.target === modal) {
            modal.style.display = "none";
        }
    });

    // --- 2a. BIRTHDAY PICKER (Flatpickr) ---
    const birthdayInput = document.getElementById('birthday');
    if (birthdayInput) {
        flatpickr("#birthday", {
            maxDate: new Date().setFullYear(new Date().getFullYear() - 18),
            dateFormat: "Y-m-d",
            altInput: true,
            altFormat: "F j, Y",
            disableMobile: true,
            monthSelectorType: "dropdown",
            scrollInput: false, // Prevents mouse wheel from changing the date/month
            static: true // Keeps the picker attached to the input during scrolling
        });
    }

    // --- 3. PHONE INPUT BLOCK ---
    if (phoneInput) {
        phoneInput.addEventListener('input', function() {
            this.value = this.value.replace(/\D/g, '');
        });
    }

    // --- 4. PASSWORD STRENGTH ---
    if (passwordInput) {
        let strengthIndicator = document.getElementById('password-strength-indicator');

        passwordInput.addEventListener('input', function () {
            const strength = calculateStrength(this.value);
            updateUI(strengthIndicator, strength);
        });
    }

    function calculateStrength(password) {
        if (password.length === 0) return 'empty';
        const hasLetters = /[a-zA-Z]/.test(password);
        const hasNumbers = /[0-9]/.test(password);
        const hasSpecial = /[!@#$%^&*(),.?":{}|<>]/.test(password);
        const hasMixedCase = /[a-z]/.test(password) && /[A-Z]/.test(password);

        if (password.length < 8) return 'Weak';
        if (password.length >= 8 && hasMixedCase && hasNumbers && hasSpecial) return 'Strong';
        if (password.length >= 8 && hasLetters && hasNumbers) return 'Moderate';
        return 'Weak';
    }

    function updateUI(element, strength) {
        if (!element) return;
        if (strength === 'empty') { 
            element.style.display = 'none'; 
            return; 
        }
        element.style.display = 'block';
        let color = strength === 'Strong' ? '#2ecc71' : (strength === 'Moderate' ? '#f1c40f' : '#e74c3c');
        element.innerHTML = `Password Strength: <span style="color: ${color}; font-weight: bold;">${strength}</span>`;
    }

    // --- 5. FINAL SUBMISSION GUARD ---
    if (form) {
        const errorContainer = document.getElementById('error-container');
        const errorText = document.getElementById('error-text');

        function showError(msg) {
            if (errorContainer && errorText) {
                errorText.textContent = msg;
                errorContainer.style.display = 'block';
                // Scroll to error
                errorContainer.scrollIntoView({ behavior: 'smooth', block: 'center' });
            }
        }

        form.addEventListener('submit', function (e) {
            // Check for empty fields (since novalidate is on)
            const inputs = form.querySelectorAll('input[required], select[required]');
            let allFilled = true;
            inputs.forEach(input => {
                if (!input.value.trim()) {
                    allFilled = false;
                }
            });

            if (!allFilled) {
                e.preventDefault();
                showError("Please fill in all required fields.");
                return;
            }

            const pass = passwordInput.value;
            const confirm = confirmInput.value;
            const phone = phoneInput.value;
            
            const strongRegex = /^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[!@#$%^&*(),.?":{}|<>])[A-Za-z\d!@#$%^&*(),.?":{}|<>]{8,16}$/;

            if (phone.length !== 11) {
                e.preventDefault();
                showError("Please enter a valid 11-digit phone number.");
            } else if (!strongRegex.test(pass)) {
                e.preventDefault();
                showError("Password must be 8-16 characters and include Uppercase, Lowercase, Number, and Special Character.");
            } else if (pass !== confirm) {
                e.preventDefault();
                showError("Passwords do not match!");
            } else if (!checkbox.checked) {
                e.preventDefault();
                showError("You must agree to the Terms and Conditions.");
            }
        });
    }
});