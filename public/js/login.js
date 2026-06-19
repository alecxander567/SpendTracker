/**
 * Login Module - Handles user login
 */

const Login = (() => {
    "use strict";

    // DOM Elements
    const elements = {
        form: document.getElementById("loginForm"),
        email: document.getElementById("email"),
        password: document.getElementById("password"),
        remember: document.getElementById("remember"),
        btn: document.getElementById("loginBtn"),
        btnText: document.getElementById("btnText"),
        btnSpinner: document.getElementById("btnSpinner"),
        togglePassword: document.getElementById("togglePassword"),
        emailError: document.getElementById("emailError"),
        passwordError: document.getElementById("passwordError"),
    };

    // Modal instance
    let errorModal = null;

    // Field error mapping
    const errorMapping = {
        email: elements.emailError,
        password: elements.passwordError,
    };

    /**
     * Initialize the module
     */
    const init = () => {
        if (!elements.form) return;

        // Initialize modals
        initModals();

        elements.form.addEventListener("submit", handleSubmit);
        elements.togglePassword?.addEventListener(
            "click",
            togglePasswordVisibility,
        );
        elements.email?.addEventListener("input", clearFieldError);
        elements.password?.addEventListener("input", clearFieldError);

        // Clean up the URL if redirected from registration
        checkForSuccessMessage();
    };

    /**
     * Initialize Bootstrap modals
     */
    const initModals = () => {
        const errorModalEl = document.getElementById("loginErrorModal");
        if (errorModalEl) {
            errorModal = new bootstrap.Modal(errorModalEl, {
                backdrop: "static",
                keyboard: true,
            });
        }
    };

    /**
     * Handle form submission
     * @param {Event} event - The submit event
     */
    const handleSubmit = async (event) => {
        event.preventDefault();

        // Clear previous errors
        clearAllErrors();

        // Get form data
        const formData = {
            email: elements.email.value.trim(),
            password: elements.password.value,
            remember: elements.remember.checked,
        };

        // Basic client-side validation
        if (!validateForm(formData)) {
            return;
        }

        // Show loading state
        setLoading(true);

        try {
            const response = await fetch("/api/login", {
                method: "POST",
                headers: {
                    "Content-Type": "application/json",
                    Accept: "application/json",
                    "X-CSRF-TOKEN": document.querySelector(
                        'meta[name="csrf-token"]',
                    ).content,
                },
                body: JSON.stringify(formData),
            });

            const result = await response.json();

            if (response.status === 200 && result.success) {
                // Login successful - redirect to dashboard
                window.location.href = result.data.redirect || "/dashboard";
            } else {
                // Handle validation errors
                if (response.status === 422 && result.errors) {
                    displayValidationErrors(result.errors);
                    showErrorModal(
                        "Please correct the errors below and try again.",
                        result.errors,
                    );
                } else {
                    showErrorModal(
                        result.message || "Login failed. Please try again.",
                    );
                }
            }
        } catch (error) {
            console.error("Login error:", error);
            showErrorModal("An unexpected error occurred. Please try again.");
        } finally {
            setLoading(false);
        }
    };

    /**
     * Client-side validation before API call
     * @param {Object} data - Form data
     * @returns {boolean} - True if valid
     */
    const validateForm = (data) => {
        let isValid = true;

        // Validate email
        const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        if (!data.email || !emailRegex.test(data.email)) {
            setFieldError("email", "Please enter a valid email address.");
            isValid = false;
        }

        // Validate password
        if (!data.password || data.password.length < 1) {
            setFieldError("password", "Please enter your password.");
            isValid = false;
        }

        return isValid;
    };

    /**
     * Display validation errors from API
     * @param {Object} errors - Error object from API
     */
    const displayValidationErrors = (errors) => {
        clearAllErrors();

        for (const [field, messages] of Object.entries(errors)) {
            const errorElement = errorMapping[field];
            if (errorElement) {
                errorElement.textContent = messages[0];
                errorElement.style.display = "block";
                const input = document.querySelector(`[name="${field}"]`);
                if (input) {
                    input.classList.add("is-invalid");
                }
            }
        }
    };

    /**
     * Set a single field error
     * @param {string} field - Field name
     * @param {string} message - Error message
     */
    const setFieldError = (field, message) => {
        const errorElement = errorMapping[field];
        if (errorElement) {
            errorElement.textContent = message;
            errorElement.style.display = "block";
        }
        const input = document.querySelector(`[name="${field}"]`);
        if (input) {
            input.classList.add("is-invalid");
        }
    };

    /**
     * Clear a single field error on input
     * @param {Event} event - Input event
     */
    const clearFieldError = (event) => {
        const input = event.target;
        const field = input.name;
        input.classList.remove("is-invalid");
        const errorElement = errorMapping[field];
        if (errorElement) {
            errorElement.style.display = "none";
            errorElement.textContent = "";
        }
    };

    /**
     * Clear all field errors
     */
    const clearAllErrors = () => {
        document.querySelectorAll(".is-invalid").forEach((el) => {
            el.classList.remove("is-invalid");
        });
        Object.values(errorMapping).forEach((el) => {
            if (el) {
                el.style.display = "none";
                el.textContent = "";
            }
        });
    };

    /**
     * Toggle password visibility
     */
    const togglePasswordVisibility = () => {
        const type =
            elements.password.type === "password" ? "text" : "password";
        elements.password.type = type;
        const icon = elements.togglePassword.querySelector("i");
        icon.classList.toggle("fa-eye");
        icon.classList.toggle("fa-eye-slash");
    };

    /**
     * Set loading state on button
     * @param {boolean} loading - Loading state
     */
    const setLoading = (loading) => {
        if (loading) {
            elements.btn.disabled = true;
            elements.btnText.textContent = "Logging in...";
            elements.btnSpinner.classList.remove("d-none");
        } else {
            elements.btn.disabled = false;
            elements.btnText.textContent = "Login";
            elements.btnSpinner.classList.add("d-none");
        }
    };

    /**
     * Show error modal
     * @param {string} message - Error message
     * @param {Object} errors - Validation errors (optional)
     */
    const showErrorModal = (message, errors = null) => {
        const modalBody = document.querySelector(
            "#loginErrorModal .modal-body",
        );
        if (modalBody) {
            const messageElement = modalBody.querySelector(".lead");
            if (messageElement) {
                messageElement.textContent = message;
            }

            const errorList = modalBody.querySelector(".text-start");
            if (errorList) {
                if (errors && Object.keys(errors).length > 0) {
                    errorList.style.display = "block";
                    const list = errorList.querySelector("ul");
                    if (list) {
                        list.innerHTML = "";
                        const allErrors = Object.values(errors).flat();
                        allErrors.forEach((error) => {
                            const li = document.createElement("li");
                            li.className = "text-danger small";
                            li.innerHTML = `<i class="fas fa-times-circle me-1"></i> ${error}`;
                            list.appendChild(li);
                        });
                    }
                } else {
                    errorList.style.display = "none";
                }
            }
        }

        if (errorModal) {
            errorModal.show();
        }
    };

    /**
     * If redirected from registration, just clean the query param
     * from the URL. No alert is shown.
     */
    const checkForSuccessMessage = () => {
        const urlParams = new URLSearchParams(window.location.search);
        if (urlParams.get("registered") === "success") {
            const url = new URL(window.location);
            url.searchParams.delete("registered");
            window.history.replaceState({}, "", url);
        }
    };

    // Public API
    return {
        init,
    };
})();

// Initialize when DOM is ready
document.addEventListener("DOMContentLoaded", Login.init);
