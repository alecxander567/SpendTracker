/**
 * Settings Module - Handles user settings
 */

const Settings = (() => {
    "use strict";

    const csrfToken = () =>
        document.querySelector('meta[name="csrf-token"]')?.content || "";

    // DOM Elements
    const elements = {
        // Profile
        profileForm: document.getElementById("profileForm"),
        profileName: document.getElementById("profileName"),
        profileEmail: document.getElementById("profileEmail"),
        profileCurrency: document.getElementById("profileCurrency"),
        profileTimezone: document.getElementById("profileTimezone"),
        saveProfileBtn: document.getElementById("saveProfileBtn"),
        profileBtnText: document.getElementById("profileBtnText"),
        profileBtnSpinner: document.getElementById("profileBtnSpinner"),

        // Password
        passwordForm: document.getElementById("passwordForm"),
        currentPassword: document.getElementById("currentPassword"),
        newPassword: document.getElementById("newPassword"),
        newPasswordConfirmation: document.getElementById(
            "newPasswordConfirmation",
        ),
        savePasswordBtn: document.getElementById("savePasswordBtn"),
        passwordBtnText: document.getElementById("passwordBtnText"),
        passwordBtnSpinner: document.getElementById("passwordBtnSpinner"),

        // Delete Account
        deleteAccountBtn: document.getElementById("deleteAccountBtn"),
        deleteAccountModal: document.getElementById("deleteAccountModal"),
        deleteAccountForm: document.getElementById("deleteAccountForm"),
        deletePassword: document.getElementById("deletePassword"),
        deleteConfirm: document.getElementById("deleteConfirm"),
        confirmDeleteAccountBtn: document.getElementById(
            "confirmDeleteAccountBtn",
        ),
        deleteAccountBtnText: document.getElementById("deleteAccountBtnText"),
        deleteAccountBtnSpinner: document.getElementById(
            "deleteAccountBtnSpinner",
        ),

        // Error elements - Profile
        profileNameError: document.getElementById("profileNameError"),
        profileEmailError: document.getElementById("profileEmailError"),
        profileCurrencyError: document.getElementById("profileCurrencyError"),
        profileTimezoneError: document.getElementById("profileTimezoneError"),

        // Error elements - Password
        currentPasswordError: document.getElementById("currentPasswordError"),
        newPasswordError: document.getElementById("newPasswordError"),
        newPasswordConfirmationError: document.getElementById(
            "newPasswordConfirmationError",
        ),

        // Error elements - Delete
        deletePasswordError: document.getElementById("deletePasswordError"),
        deleteConfirmError: document.getElementById("deleteConfirmError"),

        // Modals
        loader: document.getElementById("pageLoader"),
        content: document.getElementById("mainContent"),
    };

    let successModal = null;
    let errorModal = null;
    let deleteModal = null;

    /**
     * Reveal the page
     */
    const revealPage = () => {
        if (elements.loader) elements.loader.style.display = "none";
        if (elements.content) elements.content.style.display = "block";
    };

    /**
     * Show success modal
     */
    const showSuccess = (message) => {
        const messageEl = document.querySelector("#settingsSuccessModal .lead");
        if (messageEl) messageEl.textContent = message;
        if (successModal) successModal.show();
    };

    /**
     * Show error modal
     */
    const showError = (message) => {
        const messageEl = document.querySelector("#settingsErrorModal .lead");
        if (messageEl) messageEl.textContent = message;
        if (errorModal) errorModal.show();
    };

    /**
     * Clear all error states
     */
    const clearErrors = () => {
        // Profile errors
        const profileErrorElements = [
            elements.profileNameError,
            elements.profileEmailError,
            elements.profileCurrencyError,
            elements.profileTimezoneError,
        ];
        profileErrorElements.forEach((el) => {
            if (el) {
                el.style.display = "none";
                el.textContent = "";
            }
        });

        // Password errors
        const passwordErrorElements = [
            elements.currentPasswordError,
            elements.newPasswordError,
            elements.newPasswordConfirmationError,
        ];
        passwordErrorElements.forEach((el) => {
            if (el) {
                el.style.display = "none";
                el.textContent = "";
            }
        });

        // Delete errors
        const deleteErrorElements = [
            elements.deletePasswordError,
            elements.deleteConfirmError,
        ];
        deleteErrorElements.forEach((el) => {
            if (el) {
                el.style.display = "none";
                el.textContent = "";
            }
        });

        // Remove is-invalid class from all inputs
        document.querySelectorAll(".is-invalid").forEach((el) => {
            el.classList.remove("is-invalid");
        });
    };

    /**
     * Display validation errors
     */
    const displayValidationErrors = (errors, prefix = "") => {
        clearErrors();

        for (const [field, messages] of Object.entries(errors)) {
            const errorElement = document.getElementById(
                prefix + field + "Error",
            );
            if (errorElement) {
                errorElement.textContent = messages[0];
                errorElement.style.display = "block";

                // Find the corresponding input and mark it as invalid
                const input = document.querySelector(`[name="${field}"]`);
                if (input) {
                    input.classList.add("is-invalid");
                }
            }
        }
    };

    /**
     * Initialize the module
     */
    const init = () => {
        successModal = new bootstrap.Modal(
            document.getElementById("settingsSuccessModal"),
        );
        errorModal = new bootstrap.Modal(
            document.getElementById("settingsErrorModal"),
        );
        deleteModal = new bootstrap.Modal(elements.deleteAccountModal);

        // Load data
        loadCurrencies();
        loadTimezones();
        loadProfile();

        // Event listeners
        elements.profileForm?.addEventListener("submit", handleProfileUpdate);
        elements.passwordForm?.addEventListener("submit", handlePasswordUpdate);
        elements.deleteAccountBtn?.addEventListener("click", () =>
            deleteModal.show(),
        );
        elements.confirmDeleteAccountBtn?.addEventListener(
            "click",
            handleDeleteAccount,
        );

        revealPage();
    };

    /**
     * Load user profile
     */
    const loadProfile = async () => {
        try {
            const response = await fetch("/api/settings/profile", {
                headers: {
                    Accept: "application/json",
                    "X-CSRF-TOKEN": csrfToken(),
                },
            });

            const result = await response.json();

            if (result.success) {
                const data = result.data;
                elements.profileName.value = data.name || "";
                elements.profileEmail.value = data.email || "";
                elements.profileCurrency.value = data.currency || "USD";
                elements.profileTimezone.value = data.timezone || "UTC";
            }
        } catch (error) {
            console.error("Error loading profile:", error);
            showError("Failed to load profile data.");
        }
    };

    /**
     * Load currencies
     */
    const loadCurrencies = async () => {
        try {
            const response = await fetch("/api/settings/currencies", {
                headers: {
                    Accept: "application/json",
                    "X-CSRF-TOKEN": csrfToken(),
                },
            });

            const result = await response.json();

            if (result.success) {
                const select = elements.profileCurrency;
                select.innerHTML = '<option value="">Select currency</option>';
                result.data.forEach((currency) => {
                    const option = document.createElement("option");
                    option.value = currency.code;
                    option.textContent = `${currency.code} - ${currency.name} (${currency.symbol})`;
                    select.appendChild(option);
                });
            }
        } catch (error) {
            console.error("Error loading currencies:", error);
        }
    };

    /**
     * Load timezones
     */
    const loadTimezones = async () => {
        try {
            const response = await fetch("/api/settings/timezones", {
                headers: {
                    Accept: "application/json",
                    "X-CSRF-TOKEN": csrfToken(),
                },
            });

            const result = await response.json();

            if (result.success) {
                const select = elements.profileTimezone;
                select.innerHTML = '<option value="">Select timezone</option>';
                for (const [value, label] of Object.entries(result.data)) {
                    const option = document.createElement("option");
                    option.value = value;
                    option.textContent = label;
                    select.appendChild(option);
                }
            }
        } catch (error) {
            console.error("Error loading timezones:", error);
        }
    };

    /**
     * Handle profile update
     */
    const handleProfileUpdate = async (e) => {
        e.preventDefault();
        clearErrors();

        const formData = {
            name: elements.profileName.value.trim(),
            email: elements.profileEmail.value.trim(),
            currency: elements.profileCurrency.value,
            timezone: elements.profileTimezone.value,
        };

        // Basic validation
        if (!formData.name) {
            elements.profileNameError.textContent = "Name is required";
            elements.profileNameError.style.display = "block";
            elements.profileName.classList.add("is-invalid");
            return;
        }

        if (!formData.email) {
            elements.profileEmailError.textContent = "Email is required";
            elements.profileEmailError.style.display = "block";
            elements.profileEmail.classList.add("is-invalid");
            return;
        }

        if (!formData.currency) {
            elements.profileCurrencyError.textContent =
                "Please select a currency";
            elements.profileCurrencyError.style.display = "block";
            elements.profileCurrency.classList.add("is-invalid");
            return;
        }

        if (!formData.timezone) {
            elements.profileTimezoneError.textContent =
                "Please select a timezone";
            elements.profileTimezoneError.style.display = "block";
            elements.profileTimezone.classList.add("is-invalid");
            return;
        }

        setProfileLoading(true);

        try {
            const response = await fetch("/api/settings/profile", {
                method: "PUT",
                headers: {
                    "Content-Type": "application/json",
                    Accept: "application/json",
                    "X-CSRF-TOKEN": csrfToken(),
                },
                body: JSON.stringify(formData),
            });

            const result = await response.json();

            if (response.status === 200) {
                showSuccess(result.message || "Profile updated successfully!");
                // Reload profile to show updated data
                loadProfile();
            } else if (response.status === 422) {
                displayValidationErrors(result.errors);
            } else {
                showError(result.message || "Failed to update profile.");
            }
        } catch (error) {
            console.error("Error updating profile:", error);
            showError("An error occurred while updating profile.");
        } finally {
            setProfileLoading(false);
        }
    };

    /**
     * Handle password update
     */
    const handlePasswordUpdate = async (e) => {
        e.preventDefault();
        clearErrors();

        const formData = {
            current_password: elements.currentPassword.value,
            new_password: elements.newPassword.value,
            new_password_confirmation: elements.newPasswordConfirmation.value,
        };

        // Basic validation
        if (!formData.current_password) {
            elements.currentPasswordError.textContent =
                "Current password is required";
            elements.currentPasswordError.style.display = "block";
            elements.currentPassword.classList.add("is-invalid");
            return;
        }

        if (!formData.new_password || formData.new_password.length < 8) {
            elements.newPasswordError.textContent =
                "New password must be at least 8 characters";
            elements.newPasswordError.style.display = "block";
            elements.newPassword.classList.add("is-invalid");
            return;
        }

        if (formData.new_password !== formData.new_password_confirmation) {
            elements.newPasswordConfirmationError.textContent =
                "Password confirmation does not match";
            elements.newPasswordConfirmationError.style.display = "block";
            elements.newPasswordConfirmation.classList.add("is-invalid");
            return;
        }

        setPasswordLoading(true);

        try {
            const response = await fetch("/api/settings/password", {
                method: "PUT",
                headers: {
                    "Content-Type": "application/json",
                    Accept: "application/json",
                    "X-CSRF-TOKEN": csrfToken(),
                },
                body: JSON.stringify(formData),
            });

            const result = await response.json();

            if (response.status === 200) {
                showSuccess(result.message || "Password updated successfully!");
                elements.passwordForm.reset();
            } else if (response.status === 422) {
                displayValidationErrors(result.errors);
            } else {
                showError(result.message || "Failed to update password.");
            }
        } catch (error) {
            console.error("Error updating password:", error);
            showError("An error occurred while updating password.");
        } finally {
            setPasswordLoading(false);
        }
    };

    /**
     * Handle delete account
     */
    const handleDeleteAccount = async () => {
        clearErrors();

        const formData = {
            password: elements.deletePassword.value,
            confirm: elements.deleteConfirm.checked ? "1" : "0",
        };

        // Basic validation
        if (!formData.password) {
            elements.deletePasswordError.textContent = "Password is required";
            elements.deletePasswordError.style.display = "block";
            elements.deletePassword.classList.add("is-invalid");
            return;
        }

        if (!elements.deleteConfirm.checked) {
            elements.deleteConfirmError.textContent =
                "Please confirm you want to delete your account";
            elements.deleteConfirmError.style.display = "block";
            elements.deleteConfirm.classList.add("is-invalid");
            return;
        }

        setDeleteLoading(true);

        try {
            const response = await fetch("/api/settings/account", {
                method: "DELETE",
                headers: {
                    "Content-Type": "application/json",
                    Accept: "application/json",
                    "X-CSRF-TOKEN": csrfToken(),
                },
                body: JSON.stringify(formData),
            });

            const result = await response.json();

            if (response.status === 200) {
                showSuccess(result.message || "Account deleted successfully!");
                setTimeout(() => {
                    window.location.href = "/login";
                }, 2000);
            } else if (response.status === 422) {
                displayValidationErrors(result.errors);
            } else {
                showError(result.message || "Failed to delete account.");
            }
        } catch (error) {
            console.error("Error deleting account:", error);
            showError("An error occurred while deleting account.");
        } finally {
            setDeleteLoading(false);
            deleteModal.hide();
        }
    };

    /**
     * Set profile loading state
     */
    const setProfileLoading = (loading) => {
        if (loading) {
            elements.saveProfileBtn.disabled = true;
            elements.profileBtnText.textContent = "Saving...";
            elements.profileBtnSpinner.classList.remove("d-none");
        } else {
            elements.saveProfileBtn.disabled = false;
            elements.profileBtnText.textContent = "Save Profile";
            elements.profileBtnSpinner.classList.add("d-none");
        }
    };

    /**
     * Set password loading state
     */
    const setPasswordLoading = (loading) => {
        if (loading) {
            elements.savePasswordBtn.disabled = true;
            elements.passwordBtnText.textContent = "Updating...";
            elements.passwordBtnSpinner.classList.remove("d-none");
        } else {
            elements.savePasswordBtn.disabled = false;
            elements.passwordBtnText.textContent = "Update Password";
            elements.passwordBtnSpinner.classList.add("d-none");
        }
    };

    /**
     * Set delete loading state
     */
    const setDeleteLoading = (loading) => {
        if (loading) {
            elements.confirmDeleteAccountBtn.disabled = true;
            elements.deleteAccountBtnText.textContent = "Deleting...";
            elements.deleteAccountBtnSpinner.classList.remove("d-none");
        } else {
            elements.confirmDeleteAccountBtn.disabled = false;
            elements.deleteAccountBtnText.textContent = "Delete Account";
            elements.deleteAccountBtnSpinner.classList.add("d-none");
        }
    };

    return { init };
})();

// Initialize when DOM is ready
document.addEventListener("DOMContentLoaded", Settings.init);
