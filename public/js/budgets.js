/**
 * Budgets Module - Handles budget management
 */

const Budgets = (() => {
    "use strict";

    // DOM Elements
    const elements = {
        grid: document.getElementById("budgetsGrid"),
        emptyState: document.getElementById("emptyState"),
        addBtn: document.getElementById("addBudgetBtn"),
        emptyStateAddBtn: document.getElementById("emptyStateAddBtn"),
        modal: document.getElementById("budgetModal"),
        modalTitle: document.getElementById("budgetModalTitle"),
        form: document.getElementById("budgetForm"),
        budgetId: document.getElementById("budgetId"),
        budgetCategory: document.getElementById("budgetCategory"),
        budgetAmount: document.getElementById("budgetAmount"),
        budgetPeriod: document.getElementById("budgetPeriod"),
        budgetStartDate: document.getElementById("budgetStartDate"),
        budgetEndDate: document.getElementById("budgetEndDate"),
        budgetIsActive: document.getElementById("budgetIsActive"),
        saveBtn: document.getElementById("saveBudgetBtn"),
        saveBtnText: document.getElementById("saveBtnText"),
        saveBtnSpinner: document.getElementById("saveBtnSpinner"),
        deleteModal: document.getElementById("deleteBudgetModal"),
        deleteBudgetName: document.getElementById("deleteBudgetName"),
        deleteBudgetId: document.getElementById("deleteBudgetId"),
        confirmDeleteBtn: document.getElementById("confirmDeleteBtn"),
        deleteBtnText: document.getElementById("deleteBtnText"),
        deleteBtnSpinner: document.getElementById("deleteBtnSpinner"),
        tabs: document.querySelectorAll('[data-bs-toggle="tab"]'),
        summaryBudgeted: document.getElementById("summaryBudgeted"),
        summarySpent: document.getElementById("summarySpent"),
        summaryRemaining: document.getElementById("summaryRemaining"),
        summaryWarning: document.getElementById("summaryWarning"),
    };

    // Error elements
    const errorElements = {
        category_id: document.getElementById("budgetCategoryError"),
        amount: document.getElementById("budgetAmountError"),
        period: document.getElementById("budgetPeriodError"),
        start_date: document.getElementById("budgetStartDateError"),
        end_date: document.getElementById("budgetEndDateError"),
    };

    // Modal instances
    let budgetModal = null;
    let deleteModal = null;
    let successModal = null;
    let errorModal = null;

    // Current filter: all | active | inactive
    let currentFilter = "all";

    // Cache of all budgets fetched from the summary endpoint, keyed by id
    let allBudgets = [];

    /**
     * Hide the fullscreen page loader and reveal main content
     */
    const revealPage = () => {
        const loader = document.getElementById("pageLoader");
        const content = document.getElementById("mainContent");
        if (loader) loader.style.display = "none";
        if (content) content.style.display = "block";
    };

    /**
     * Show success modal with a message
     */
    const showSuccess = (message) => {
        const messageEl = document.querySelector("#budgetSuccessModal .lead");
        if (messageEl) messageEl.textContent = message;
        successModal.show();
    };

    /**
     * Show error modal with a message
     */
    const showError = (message) => {
        const messageEl = document.querySelector("#budgetErrorModal .lead");
        if (messageEl) messageEl.textContent = message;
        errorModal.show();
    };

    /**
     * Format a number as PHP currency
     */
    const formatCurrency = (value) => {
        const num = Number(value) || 0;
        return (
            "₱" +
            num.toLocaleString("en-US", {
                minimumFractionDigits: 2,
                maximumFractionDigits: 2,
            })
        );
    };

    /**
     * Format a date string (YYYY-MM-DD) into a short display form
     */
    const formatDate = (dateString) => {
        if (!dateString) return "No end date";
        const date = new Date(dateString);
        if (isNaN(date.getTime())) return dateString;
        return date.toLocaleDateString("en-US", {
            month: "short",
            day: "numeric",
            year: "numeric",
        });
    };

    /**
     * Initialize the module
     */
    const init = () => {
        budgetModal = new bootstrap.Modal(elements.modal);
        deleteModal = new bootstrap.Modal(elements.deleteModal);
        successModal = new bootstrap.Modal(
            document.getElementById("budgetSuccessModal"),
        );
        errorModal = new bootstrap.Modal(
            document.getElementById("budgetErrorModal"),
        );

        elements.addBtn?.addEventListener("click", () => openModal());
        elements.emptyStateAddBtn?.addEventListener("click", () => openModal());
        elements.saveBtn?.addEventListener("click", handleSave);
        elements.confirmDeleteBtn?.addEventListener("click", handleDelete);

        elements.tabs.forEach((tab) => {
            tab.addEventListener("click", function () {
                currentFilter = this.dataset.filter;
                renderBudgets();
            });
        });

        Promise.all([loadCategories(), loadBudgets()]).then(() => {
            revealPage();
        });
    };

    /**
     * Load categories into the modal's select dropdown
     */
    const loadCategories = async () => {
        try {
            const response = await fetch("/api/categories", {
                headers: {
                    Accept: "application/json",
                    "X-CSRF-TOKEN": document.querySelector(
                        'meta[name="csrf-token"]',
                    ).content,
                },
            });

            const result = await response.json();

            if (result.success) {
                const options = result.data
                    .map(
                        (category) =>
                            `<option value="${category.id}" data-type="${category.type}">${category.name} (${category.type === "expense" ? "Expense" : "Income"})</option>`,
                    )
                    .join("");
                elements.budgetCategory.innerHTML =
                    '<option value="">Select category</option>' + options;
            }
        } catch (error) {
            console.error("Error loading categories:", error);
        }
    };

    /**
     * Load budgets from the API (uses the summary endpoint for spend data)
     */
    const loadBudgets = async () => {
        try {
            showLoading();

            const response = await fetch("/api/budgets", {
                headers: {
                    Accept: "application/json",
                    "X-CSRF-TOKEN": document.querySelector(
                        'meta[name="csrf-token"]',
                    ).content,
                },
            });

            const result = await response.json();

            if (result.success) {
                allBudgets = result.data;
                renderBudgets();
                updateSummary();
            } else {
                showError("Failed to load budgets.");
            }
        } catch (error) {
            console.error("Error loading budgets:", error);
            showError("An error occurred while loading budgets.");
        }
    };

    /**
     * Update the summary stat cards based on currently loaded budgets
     */
    const updateSummary = () => {
        const activeBudgets = allBudgets.filter((b) => b.is_active);

        const totalBudgeted = activeBudgets.reduce(
            (sum, b) => sum + Number(b.amount),
            0,
        );
        const totalSpent = activeBudgets.reduce(
            (sum, b) => sum + Number(b.spent),
            0,
        );
        const totalRemaining = totalBudgeted - totalSpent;
        const needsAttention = activeBudgets.filter(
            (b) => b.status === "warning" || b.status === "exceeded",
        ).length;

        elements.summaryBudgeted.textContent = formatCurrency(totalBudgeted);
        elements.summarySpent.textContent = formatCurrency(totalSpent);
        elements.summaryRemaining.textContent = formatCurrency(totalRemaining);
        elements.summaryWarning.textContent = needsAttention;
    };

    /**
     * Filter and render the budgets grid based on the current tab
     */
    const renderBudgets = () => {
        let budgets = allBudgets;

        if (currentFilter === "active") {
            budgets = budgets.filter((b) => b.is_active);
        } else if (currentFilter === "inactive") {
            budgets = budgets.filter((b) => !b.is_active);
        }

        renderGrid(budgets);
    };

    /**
     * Render budget cards in the grid
     */
    const renderGrid = (budgets) => {
        const grid = elements.grid;

        if (!budgets || budgets.length === 0) {
            grid.innerHTML = "";
            elements.emptyState?.classList.remove("d-none");
            return;
        }

        elements.emptyState?.classList.add("d-none");

        let html = "";
        budgets.forEach((budget) => {
            const statusLabels = {
                good: "On track",
                moderate: "Moderate",
                warning: "Near limit",
                exceeded: "Exceeded",
            };

            const status = budget.status || "good";
            const statusLabel = statusLabels[status] || "On track";
            const remaining = Number(budget.remaining);
            const isInactive = !budget.is_active;

            html += `
                <div class="col-12 col-sm-6 col-lg-4">
                    <div class="budget-card ${isInactive ? "is-inactive" : ""}">
                        <div class="budget-card-header">
                            <div class="budget-card-category">
                                <span class="budget-card-dot" style="background-color: ${budget.category_color || "#6C757D"};"></span>
                                <div class="min-w-0">
                                    <p class="budget-card-name">${budget.category_name}</p>
                                    <p class="budget-card-period mb-0">${budget.period_label || capitalize(budget.period)}</p>
                                </div>
                            </div>
                            <div class="budget-card-actions">
                                <button class="btn btn-sm btn-outline-primary edit-budget" data-id="${budget.id}" title="Edit">
                                    <i class="fas fa-edit"></i>
                                </button>
                                <button class="btn btn-sm btn-outline-danger delete-budget" data-id="${budget.id}" data-name="${budget.category_name}" title="Delete">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </div>
                        </div>

                        <div class="budget-card-amounts">
                            <span class="budget-card-spent">${formatCurrency(budget.spent)}</span>
                            <span class="budget-card-total">of ${formatCurrency(budget.amount)}</span>
                        </div>

                        <div class="budget-progress">
                            <div class="budget-progress-bar status-${status}" style="width: ${Math.min(budget.percentage_used, 100)}%;"></div>
                        </div>

                        <div class="budget-card-footer">
                            <span class="budget-status-pill status-${status}">${statusLabel}</span>
                            <span class="budget-card-remaining ${remaining < 0 ? "is-negative" : ""}">
                                ${remaining < 0 ? "Over by " + formatCurrency(Math.abs(remaining)) : formatCurrency(remaining) + " left"}
                            </span>
                        </div>

                        <div class="budget-card-dates small">
                            ${formatDate(budget.start_date)} &rarr; ${budget.end_date ? formatDate(budget.end_date) : "Ongoing"}
                        </div>
                    </div>
                </div>
            `;
        });

        grid.innerHTML = html;

        document.querySelectorAll(".edit-budget").forEach((btn) => {
            btn.addEventListener("click", function () {
                openModal(this.dataset.id);
            });
        });

        document.querySelectorAll(".delete-budget").forEach((btn) => {
            btn.addEventListener("click", function () {
                openDeleteModal(this.dataset.id, this.dataset.name);
            });
        });
    };

    const capitalize = (str) =>
        str ? str.charAt(0).toUpperCase() + str.slice(1) : "";

    /**
     * Open the budget modal for add/edit
     */
    const openModal = async (id = null) => {
        elements.form.reset();
        clearErrors();
        elements.budgetId.value = "";
        elements.budgetIsActive.checked = true;

        if (id) {
            elements.modalTitle.textContent = "Edit Budget";
            elements.saveBtnText.textContent = "Update Budget";

            try {
                const response = await fetch(`/api/budgets/${id}`, {
                    headers: {
                        Accept: "application/json",
                        "X-CSRF-TOKEN": document.querySelector(
                            'meta[name="csrf-token"]',
                        ).content,
                    },
                });

                const result = await response.json();
                if (result.success) {
                    const budget = result.data;
                    elements.budgetId.value = budget.id;
                    elements.budgetCategory.value = budget.category_id;
                    elements.budgetAmount.value = budget.amount;
                    elements.budgetPeriod.value = budget.period;
                    elements.budgetStartDate.value = formatDateForInput(
                        budget.start_date,
                    );
                    elements.budgetEndDate.value = formatDateForInput(
                        budget.end_date,
                    );
                    elements.budgetIsActive.checked = !!budget.is_active;
                }
            } catch (error) {
                console.error("Error loading budget:", error);
                showError("Failed to load budget data.");
                return;
            }
        } else {
            elements.modalTitle.textContent = "Add Budget";
            elements.saveBtnText.textContent = "Save Budget";
        }

        budgetModal.show();
    };

    /**
     * Convert a date value (string or object) to YYYY-MM-DD for date inputs
     */
    const formatDateForInput = (dateValue) => {
        if (!dateValue) return "";
        const date = new Date(dateValue);
        if (isNaN(date.getTime())) return "";
        return date.toISOString().split("T")[0];
    };

    /**
     * Handle save budget (add or update)
     */
    const handleSave = async () => {
        clearErrors();

        const formData = {
            category_id: elements.budgetCategory.value,
            amount: elements.budgetAmount.value,
            period: elements.budgetPeriod.value,
            start_date: elements.budgetStartDate.value,
            end_date: elements.budgetEndDate.value || null,
            is_active: elements.budgetIsActive.checked,
        };

        const id = elements.budgetId.value;
        const isEdit = id !== "";
        const url = isEdit ? `/api/budgets/${id}` : "/api/budgets";
        const method = isEdit ? "PUT" : "POST";

        if (!formData.category_id) {
            setError("category_id", "Category is required");
            return;
        }
        if (!formData.amount || Number(formData.amount) <= 0) {
            setError("amount", "Budget amount must be greater than 0");
            return;
        }
        if (!formData.period) {
            setError("period", "Period is required");
            return;
        }
        if (!formData.start_date) {
            setError("start_date", "Start date is required");
            return;
        }

        setLoading(true);

        try {
            const response = await fetch(url, {
                method: method,
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

            if (response.status === 201 || response.status === 200) {
                budgetModal.hide();
                loadBudgets();
                showSuccess(result.message || "Budget saved successfully.");
            } else if (response.status === 422) {
                displayValidationErrors(result.errors);
            } else {
                showError(result.message || "Failed to save budget.");
            }
        } catch (error) {
            console.error("Error saving budget:", error);
            showError("An error occurred while saving the budget.");
        } finally {
            setLoading(false);
        }
    };

    /**
     * Open delete confirmation modal
     */
    const openDeleteModal = (id, name) => {
        elements.deleteBudgetId.value = id;
        elements.deleteBudgetName.textContent = name;
        deleteModal.show();
    };

    /**
     * Handle delete budget
     */
    const handleDelete = async () => {
        const id = elements.deleteBudgetId.value;

        setDeleteLoading(true);

        try {
            const response = await fetch(`/api/budgets/${id}`, {
                method: "DELETE",
                headers: {
                    Accept: "application/json",
                    "X-CSRF-TOKEN": document.querySelector(
                        'meta[name="csrf-token"]',
                    ).content,
                },
            });

            const result = await response.json();

            if (result.success) {
                deleteModal.hide();
                loadBudgets();
                showSuccess(result.message || "Budget deleted successfully.");
            } else {
                showError(result.message || "Failed to delete budget.");
            }
        } catch (error) {
            console.error("Error deleting budget:", error);
            showError("An error occurred while deleting the budget.");
        } finally {
            setDeleteLoading(false);
        }
    };

    /**
     * Display validation errors
     */
    const displayValidationErrors = (errors) => {
        for (const [field, messages] of Object.entries(errors)) {
            setError(field, messages[0]);
        }
    };

    /**
     * Set a field error
     */
    const setError = (field, message) => {
        const errorElement = errorElements[field];
        if (errorElement) {
            errorElement.textContent = message;
            errorElement.style.display = "block";
        }
        const fieldToInputId = {
            category_id: "budgetCategory",
            amount: "budgetAmount",
            period: "budgetPeriod",
            start_date: "budgetStartDate",
            end_date: "budgetEndDate",
        };
        const input = document.getElementById(fieldToInputId[field]);
        if (input) input.classList.add("is-invalid");
    };

    /**
     * Clear all errors
     */
    const clearErrors = () => {
        Object.values(errorElements).forEach((el) => {
            if (el) {
                el.style.display = "none";
                el.textContent = "";
            }
        });
        document.querySelectorAll(".is-invalid").forEach((el) => {
            el.classList.remove("is-invalid");
        });
    };

    const showLoading = () => {
        elements.grid.innerHTML = `
            <div class="col-12 text-center py-5">
                <div class="spinner-border" style="color: var(--magma-core);" role="status">
                    <span class="visually-hidden">Loading...</span>
                </div>
            </div>
        `;
    };

    const setLoading = (loading) => {
        if (loading) {
            elements.saveBtn.disabled = true;
            elements.saveBtnText.textContent = "Saving...";
            elements.saveBtnSpinner.classList.remove("d-none");
        } else {
            elements.saveBtn.disabled = false;
            elements.saveBtnText.textContent = elements.budgetId.value
                ? "Update Budget"
                : "Save Budget";
            elements.saveBtnSpinner.classList.add("d-none");
        }
    };

    const setDeleteLoading = (loading) => {
        if (loading) {
            elements.confirmDeleteBtn.disabled = true;
            elements.deleteBtnText.textContent = "Deleting...";
            elements.deleteBtnSpinner.classList.remove("d-none");
        } else {
            elements.confirmDeleteBtn.disabled = false;
            elements.deleteBtnText.textContent = "Delete";
            elements.deleteBtnSpinner.classList.add("d-none");
        }
    };

    return { init };
})();

document.addEventListener("DOMContentLoaded", Budgets.init);
