/**
 * Auth Module - Registration Handler
 * Manages user registration form with validation and AJAX submission
 */

const Auth = (() => {
    "use strict";

    // DOM Elements
    const elements = {
        form: document.getElementById("registerForm"),
        name: document.getElementById("name"),
        email: document.getElementById("email"),
        password: document.getElementById("password"),
        passwordConfirmation: document.getElementById("password_confirmation"),
        currency: document.getElementById("currency"),
        timezone: document.getElementById("timezone"),
        btn: document.getElementById("registerBtn"),
        btnText: document.getElementById("btnText"),
        btnSpinner: document.getElementById("btnSpinner"),
        togglePassword: document.getElementById("togglePassword"),
        nameError: document.getElementById("nameError"),
        emailError: document.getElementById("emailError"),
        passwordError: document.getElementById("passwordError"),
        passwordConfirmationError: document.getElementById(
            "passwordConfirmationError",
        ),
    };

    // Modal instances
    let successModal = null;
    let errorModal = null;

    // Field error mapping
    const errorMapping = {
        name: elements.nameError,
        email: elements.emailError,
        password: elements.passwordError,
        password_confirmation: elements.passwordConfirmationError,
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
        elements.name?.addEventListener("input", clearFieldError);
        elements.email?.addEventListener("input", clearFieldError);
        elements.password?.addEventListener("input", clearFieldError);
        elements.passwordConfirmation?.addEventListener(
            "input",
            clearFieldError,
        );

        // Handle modal hidden events
        document
            .getElementById("registrationSuccessModal")
            ?.addEventListener("hidden.bs.modal", () => {
                // Optional: redirect or action after modal closes
            });
    };

    /**
     * Initialize Bootstrap modals
     */
    const initModals = () => {
        const successModalEl = document.getElementById(
            "registrationSuccessModal",
        );
        const errorModalEl = document.getElementById("registrationErrorModal");

        if (successModalEl) {
            successModal = new bootstrap.Modal(successModalEl, {
                backdrop: "static",
                keyboard: false,
            });

            // Handle the "Go to Login" button click
            const successButton = successModalEl.querySelector(".btn-success");
            if (successButton) {
                successButton.addEventListener("click", function (e) {
                    e.preventDefault();
                    // Redirect to login page with success message
                    window.location.href = "/login?registered=success";
                });
            }
        }

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
            name: elements.name.value.trim(),
            email: elements.email.value.trim(),
            password: elements.password.value,
            password_confirmation: elements.passwordConfirmation.value,
            currency: elements.currency.value,
            timezone: elements.timezone.value,
        };

        // Basic client-side validation
        if (!validateForm(formData)) {
            return;
        }

        // Show loading state
        setLoading(true);

        try {
            const response = await fetch("/api/register", {
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

            if (response.status === 201 || result.success) {
                // Registration successful - show success modal
                showSuccessModal(result.message || "Registration successful!");
                // Reset form
                resetForm();
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
                        result.message ||
                            "Registration failed. Please try again.",
                    );
                }
            }
        } catch (error) {
            console.error("Registration error:", error);
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

        // Validate name
        if (!data.name || data.name.length < 2) {
            setFieldError("name", "Name must be at least 2 characters.");
            isValid = false;
        }

        // Validate email
        const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        if (!data.email || !emailRegex.test(data.email)) {
            setFieldError("email", "Please enter a valid email address.");
            isValid = false;
        }

        // Validate password
        const passwordRegex = /^(?=.*[A-Za-z])(?=.*\d).{8,}$/;
        if (!data.password || !passwordRegex.test(data.password)) {
            setFieldError(
                "password",
                "Password must be at least 8 characters with letters and numbers.",
            );
            isValid = false;
        }

        // Validate password confirmation
        if (data.password !== data.password_confirmation) {
            setFieldError("password_confirmation", "Passwords do not match.");
            isValid = false;
        }

        return isValid;
    };

    /**
     * Display validation errors from API
     * @param {Object} errors - Error object from API
     */
    const displayValidationErrors = (errors) => {
        // Clear all previous field errors first
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
     * Reset the form
     */
    const resetForm = () => {
        elements.form.reset();
        clearAllErrors();
        // Reset currency and timezone to defaults
        elements.currency.value = "PHP";
        elements.timezone.value = "Asia/Manila";
    };

    /**
     * Toggle password visibility
     */
    const togglePasswordVisibility = () => {
        const type =
            elements.password.type === "password" ? "text" : "password";
        elements.password.type = type;
        elements.passwordConfirmation.type = type;
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
            elements.btnText.textContent = "Creating Account...";
            elements.btnSpinner.classList.remove("d-none");
        } else {
            elements.btn.disabled = false;
            elements.btnText.textContent = "Create Account";
            elements.btnSpinner.classList.add("d-none");
        }
    };

    /**
     * Show success modal
     * @param {string} message - Success message
     */
    const showSuccessModal = (message) => {
        // Update modal message dynamically
        const modalBody = document.querySelector(
            "#registrationSuccessModal .modal-body p",
        );
        if (modalBody) {
            modalBody.textContent = message;
        }

        if (successModal) {
            successModal.show();
        }
    };

    /**
     * Show error modal
     * @param {string} message - Error message
     * @param {Object} errors - Validation errors (optional)
     */
    const showErrorModal = (message, errors = null) => {
        // Update modal message
        const modalBody = document.querySelector(
            "#registrationErrorModal .modal-body",
        );
        if (modalBody) {
            const messageElement = modalBody.querySelector(".lead");
            if (messageElement) {
                messageElement.textContent = message;
            }

            // Show/hide error list
            const errorList = modalBody.querySelector(".text-start");
            if (errorList) {
                if (errors && Object.keys(errors).length > 0) {
                    errorList.style.display = "block";
                    const list = errorList.querySelector("ul");
                    if (list) {
                        list.innerHTML = "";
                        // Flatten all error messages
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

    // Public API
    return {
        init,
    };
})();

// Initialize when DOM is ready
document.addEventListener("DOMContentLoaded", Auth.init);
