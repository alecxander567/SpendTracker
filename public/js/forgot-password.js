/**
 * Forgot Password Module - Handles the two-step password reset flow:
 * 1. User enters email -> checked against backend
 * 2. If found, reset password modal opens for the new password
 */
const ForgotPassword = (() => {
    "use strict";

    const elements = {
        // Trigger
        forgotLink: document.querySelector('.auth-link[href="#"]'),

        // Step 1 modal
        forgotForm: document.getElementById("forgotPasswordForm"),
        forgotEmail: document.getElementById("forgotEmail"),
        forgotEmailError: document.getElementById("forgotEmailError"),
        forgotAlert: document.getElementById("forgotPasswordAlert"),
        forgotBtn: document.getElementById("forgotPasswordBtn"),
        forgotBtnText: document.getElementById("forgotBtnText"),
        forgotBtnSpinner: document.getElementById("forgotBtnSpinner"),

        // Step 2 modal
        resetForm: document.getElementById("resetPasswordForm"),
        resetEmail: document.getElementById("resetEmail"),
        newPassword: document.getElementById("newPassword"),
        newPasswordError: document.getElementById("newPasswordError"),
        confirmPassword: document.getElementById("confirmPassword"),
        confirmPasswordError: document.getElementById("confirmPasswordError"),
        resetAlert: document.getElementById("resetPasswordAlert"),
        resetBtn: document.getElementById("resetPasswordBtn"),
        resetBtnText: document.getElementById("resetBtnText"),
        resetBtnSpinner: document.getElementById("resetBtnSpinner"),
    };

    let forgotModal = null;
    let resetModal = null;

    const init = () => {
        const forgotModalEl = document.getElementById("forgotPasswordModal");
        const resetModalEl = document.getElementById("resetPasswordModal");

        if (!forgotModalEl || !resetModalEl) return;

        forgotModal = new bootstrap.Modal(forgotModalEl, {
            backdrop: "static",
            keyboard: true,
        });
        resetModal = new bootstrap.Modal(resetModalEl, {
            backdrop: "static",
            keyboard: true,
        });

        // "Forgot password?" link opens step 1
        elements.forgotLink?.addEventListener("click", (event) => {
            event.preventDefault();
            resetForgotForm();
            forgotModal.show();
        });

        elements.forgotForm?.addEventListener("submit", handleCheckEmail);
        elements.resetForm?.addEventListener("submit", handleResetPassword);

        elements.forgotEmail?.addEventListener("input", () =>
            clearFieldError(elements.forgotEmail, elements.forgotEmailError),
        );
        elements.newPassword?.addEventListener("input", () =>
            clearFieldError(elements.newPassword, elements.newPasswordError),
        );
        elements.confirmPassword?.addEventListener("input", () =>
            clearFieldError(
                elements.confirmPassword,
                elements.confirmPasswordError,
            ),
        );

        // Reset step-1 form state whenever its modal is fully hidden
        document
            .getElementById("forgotPasswordModal")
            ?.addEventListener("hidden.bs.modal", resetForgotForm);
        document
            .getElementById("resetPasswordModal")
            ?.addEventListener("hidden.bs.modal", resetResetForm);
    };

    const csrfToken = () =>
        document.querySelector('meta[name="csrf-token"]')?.content || "";

    /**
     * Step 1: check whether the email exists
     */
    const handleCheckEmail = async (event) => {
        event.preventDefault();
        hideAlert(elements.forgotAlert);

        const email = elements.forgotEmail.value.trim();
        const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;

        if (!email || !emailRegex.test(email)) {
            setFieldError(
                elements.forgotEmail,
                elements.forgotEmailError,
                "Please enter a valid email address.",
            );
            return;
        }

        setLoading(
            elements.forgotBtn,
            elements.forgotBtnText,
            elements.forgotBtnSpinner,
            true,
            "Checking...",
        );

        try {
            const response = await fetch("/forgot-password/check-email", {
                method: "POST",
                headers: {
                    "Content-Type": "application/json",
                    Accept: "application/json",
                    "X-CSRF-TOKEN": csrfToken(),
                },
                body: JSON.stringify({ email }),
            });

            const result = await response.json();

            if (response.status === 200 && result.success) {
                // Carry the verified email into step 2, then swap modals
                elements.resetEmail.value = email;
                forgotModal.hide();
                resetModal.show();
            } else if (response.status === 404) {
                showAlert(
                    elements.forgotAlert,
                    result.message ||
                        "No account found with that email address.",
                    "danger",
                );
            } else if (response.status === 422 && result.errors?.email) {
                setFieldError(
                    elements.forgotEmail,
                    elements.forgotEmailError,
                    result.errors.email[0],
                );
            } else {
                showAlert(
                    elements.forgotAlert,
                    result.message || "Something went wrong. Please try again.",
                    "danger",
                );
            }
        } catch (error) {
            console.error("Forgot password error:", error);
            showAlert(
                elements.forgotAlert,
                "An unexpected error occurred. Please try again.",
                "danger",
            );
        } finally {
            setLoading(
                elements.forgotBtn,
                elements.forgotBtnText,
                elements.forgotBtnSpinner,
                false,
                "Continue",
            );
        }
    };

    /**
     * Step 2: submit the new password
     */
    const handleResetPassword = async (event) => {
        event.preventDefault();
        hideAlert(elements.resetAlert);

        const email = elements.resetEmail.value;
        const password = elements.newPassword.value;
        const passwordConfirmation = elements.confirmPassword.value;

        let valid = true;

        if (!password || password.length < 8) {
            setFieldError(
                elements.newPassword,
                elements.newPasswordError,
                "Password must be at least 8 characters.",
            );
            valid = false;
        }

        if (password !== passwordConfirmation) {
            setFieldError(
                elements.confirmPassword,
                elements.confirmPasswordError,
                "Passwords do not match.",
            );
            valid = false;
        }

        if (!valid) return;

        setLoading(
            elements.resetBtn,
            elements.resetBtnText,
            elements.resetBtnSpinner,
            true,
            "Resetting...",
        );

        try {
            const response = await fetch("/forgot-password/reset", {
                method: "POST",
                headers: {
                    "Content-Type": "application/json",
                    Accept: "application/json",
                    "X-CSRF-TOKEN": csrfToken(),
                },
                body: JSON.stringify({
                    email,
                    password,
                    password_confirmation: passwordConfirmation,
                }),
            });

            const result = await response.json();

            if (response.status === 200 && result.success) {
                resetModal.hide();
                // Surface success on the login page itself via a simple alert.
                // Swap this for a toast/banner if you have one available.
                window.alert(
                    result.message ||
                        "Password reset successfully. You may now log in.",
                );
            } else if (response.status === 422 && result.errors) {
                if (result.errors.password) {
                    setFieldError(
                        elements.newPassword,
                        elements.newPasswordError,
                        result.errors.password[0],
                    );
                }
            } else {
                showAlert(
                    elements.resetAlert,
                    result.message || "Something went wrong. Please try again.",
                    "danger",
                );
            }
        } catch (error) {
            console.error("Reset password error:", error);
            showAlert(
                elements.resetAlert,
                "An unexpected error occurred. Please try again.",
                "danger",
            );
        } finally {
            setLoading(
                elements.resetBtn,
                elements.resetBtnText,
                elements.resetBtnSpinner,
                false,
                "Reset password",
            );
        }
    };

    const setFieldError = (input, errorEl, message) => {
        input?.classList.add("is-invalid");
        if (errorEl) {
            errorEl.textContent = message;
            errorEl.style.display = "block";
        }
    };

    const clearFieldError = (input, errorEl) => {
        input?.classList.remove("is-invalid");
        if (errorEl) {
            errorEl.style.display = "none";
            errorEl.textContent = "";
        }
    };

    const showAlert = (alertEl, message, type = "danger") => {
        if (!alertEl) return;
        alertEl.textContent = message;
        alertEl.className = `alert alert-${type}`;
        alertEl.classList.remove("d-none");
    };

    const hideAlert = (alertEl) => {
        if (!alertEl) return;
        alertEl.classList.add("d-none");
        alertEl.textContent = "";
    };

    const setLoading = (btn, textEl, spinnerEl, loading, label) => {
        if (!btn) return;
        btn.disabled = loading;
        if (textEl) textEl.textContent = label;
        spinnerEl?.classList.toggle("d-none", !loading);
    };

    const resetForgotForm = () => {
        elements.forgotForm?.reset();
        clearFieldError(elements.forgotEmail, elements.forgotEmailError);
        hideAlert(elements.forgotAlert);
    };

    const resetResetForm = () => {
        elements.resetForm?.reset();
        clearFieldError(elements.newPassword, elements.newPasswordError);
        clearFieldError(
            elements.confirmPassword,
            elements.confirmPasswordError,
        );
        hideAlert(elements.resetAlert);
    };

    return { init };
})();

document.addEventListener("DOMContentLoaded", ForgotPassword.init);
