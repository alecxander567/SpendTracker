/**
 * Expenses Module - Handles expense management
 */

const Expenses = (() => {
    "use strict";

    // DOM Elements
    const elements = {
        tableBody: document.getElementById("expensesTableBody"),
        emptyState: document.getElementById("emptyState"),
        addBtn: document.getElementById("addExpenseBtn"),
        emptyStateAddBtn: document.getElementById("emptyStateAddBtn"),
        modal: document.getElementById("expenseModal"),
        modalTitle: document.getElementById("expenseModalTitle"),
        form: document.getElementById("expenseForm"),
        expenseId: document.getElementById("expenseId"),
        amount: document.getElementById("expenseAmount"),
        category: document.getElementById("expenseCategory"),
        budget: document.getElementById("expenseBudget"),
        budgetRemainingInfo: document.getElementById("budgetRemainingInfo"),
        date: document.getElementById("expenseDate"),
        description: document.getElementById("expenseDescription"),
        isRecurring: document.getElementById("expenseIsRecurring"),
        recurringFrequencyWrap: document.getElementById(
            "recurringFrequencyWrap",
        ),
        recurringFrequency: document.getElementById(
            "expenseRecurringFrequency",
        ),
        saveBtn: document.getElementById("saveExpenseBtn"),
        saveBtnText: document.getElementById("saveBtnText"),
        saveBtnSpinner: document.getElementById("saveBtnSpinner"),
        deleteModal: document.getElementById("deleteExpenseModal"),
        deleteExpenseId: document.getElementById("deleteExpenseId"),
        deleteExpenseName: document.getElementById("deleteExpenseName"),
        confirmDeleteBtn: document.getElementById("confirmDeleteBtn"),
        deleteBtnText: document.getElementById("deleteBtnText"),
        deleteBtnSpinner: document.getElementById("deleteBtnSpinner"),
        tabs: document.querySelectorAll("#expenseTabs .nav-link"),
        loader: document.getElementById("pageLoader"),
        content: document.getElementById("mainContent"),
    };

    // Error elements
    const errorElements = {
        amount: document.getElementById("expenseAmountError"),
        category_id: document.getElementById("expenseCategoryError"),
        budget_id: document.getElementById("expenseBudgetError"),
        date: document.getElementById("expenseDateError"),
        payment_method: document.getElementById("expensePaymentMethodError"),
        description: document.getElementById("expenseDescriptionError"),
        recurring_frequency: document.getElementById(
            "expenseRecurringFrequencyError",
        ),
    };

    // Modal instances
    let expenseModal = null;
    let deleteModal = null;
    let successModal = null;
    let errorModal = null;

    // Current filter type
    let currentFilter = "all";

    // Cache available budgets
    let availableBudgets = [];

    const csrfToken = () =>
        document.querySelector('meta[name="csrf-token"]')?.content || "";

    /**
     * Reveal the page (hide loader, show content)
     */
    const revealPage = () => {
        console.log("Revealing expenses page...");
        if (elements.loader) elements.loader.style.display = "none";
        if (elements.content) elements.content.style.display = "block";
    };

    /**
     * Show success modal
     */
    const showSuccess = (message) => {
        const messageEl = document.querySelector("#expenseSuccessModal .lead");
        if (messageEl) messageEl.textContent = message;
        if (successModal) successModal.show();
    };

    /**
     * Show error modal
     */
    const showError = (message) => {
        const messageEl = document.querySelector("#expenseErrorModal .lead");
        if (messageEl) messageEl.textContent = message;
        if (errorModal) errorModal.show();
    };

    /**
     * Initialize the module
     */
    const init = () => {
        console.log("Expenses module initializing...");

        try {
            expenseModal = new bootstrap.Modal(elements.modal);
            deleteModal = new bootstrap.Modal(elements.deleteModal);
            successModal = new bootstrap.Modal(
                document.getElementById("expenseSuccessModal"),
            );
            errorModal = new bootstrap.Modal(
                document.getElementById("expenseErrorModal"),
            );

            // Set default date to today
            if (elements.date) {
                elements.date.value = new Date().toISOString().split("T")[0];
            }

            // Event listeners
            if (elements.addBtn)
                elements.addBtn.addEventListener("click", () => openModal());
            if (elements.emptyStateAddBtn)
                elements.emptyStateAddBtn.addEventListener("click", () =>
                    openModal(),
                );
            if (elements.saveBtn)
                elements.saveBtn.addEventListener("click", handleSave);
            if (elements.confirmDeleteBtn)
                elements.confirmDeleteBtn.addEventListener(
                    "click",
                    handleDelete,
                );
            if (elements.isRecurring)
                elements.isRecurring.addEventListener(
                    "change",
                    toggleRecurringFrequency,
                );
            if (elements.category)
                elements.category.addEventListener("change", onCategoryChange);

            // Tab clicks
            elements.tabs.forEach((tab) => {
                tab.addEventListener("click", function () {
                    currentFilter = this.dataset.type;
                    loadExpenses(currentFilter);
                });
            });

            // Load categories and expenses
            Promise.all([loadCategories(), loadBudgets(), loadExpenses("all")])
                .catch((err) => {
                    console.error("Error during initialization:", err);
                })
                .finally(() => {
                    revealPage();
                });
        } catch (err) {
            console.error("Init error:", err);
            revealPage();
        }
    };

    /**
     * Toggle recurring frequency field
     */
    const toggleRecurringFrequency = () => {
        if (elements.isRecurring.checked) {
            elements.recurringFrequencyWrap.classList.remove("d-none");
        } else {
            elements.recurringFrequencyWrap.classList.add("d-none");
        }
    };

    /**
     * Load categories into dropdown
     */
    const loadCategories = async () => {
        try {
            console.log("Loading categories...");
            const response = await fetch("/api/categories/type/expense", {
                headers: {
                    Accept: "application/json",
                    "X-CSRF-TOKEN": csrfToken(),
                },
            });

            const result = await response.json();

            if (result.success) {
                const select = elements.category;
                select.innerHTML =
                    '<option value="">Select a category</option>';
                result.data.forEach((category) => {
                    const opt = document.createElement("option");
                    opt.value = category.id;
                    opt.textContent = category.name;
                    select.appendChild(opt);
                });
                console.log("Categories loaded:", result.data.length);
            }
        } catch (error) {
            console.error("Error loading categories:", error);
        }
    };

    /**
     * Load available budgets for dropdown
     */
    const loadBudgets = async () => {
        try {
            console.log("Loading budgets...");
            const response = await fetch("/api/budgets/active-budgets", {
                headers: {
                    Accept: "application/json",
                    "X-CSRF-TOKEN": csrfToken(),
                },
            });

            const result = await response.json();

            if (result.success) {
                availableBudgets = result.data;
                console.log("Budgets loaded:", availableBudgets.length);
            }
        } catch (error) {
            console.error("Error loading budgets:", error);
        }
    };

    /**
     * Handle category change - update budget dropdown
     */
    const onCategoryChange = () => {
        const categoryId = elements.category.value;
        const budgetSelect = elements.budget;
        const remainingInfo = elements.budgetRemainingInfo;

        // Clear budget dropdown
        budgetSelect.innerHTML =
            '<option value="">No budget (track separately)</option>';

        if (!categoryId) {
            remainingInfo.textContent =
                "Select a category first to see available budgets.";
            return;
        }

        // Filter budgets by category
        const filteredBudgets = availableBudgets.filter(
            (b) => b.category_id == categoryId && b.remaining > 0,
        );

        if (filteredBudgets.length === 0) {
            remainingInfo.textContent =
                "No active budgets with remaining balance for this category.";
            return;
        }

        // Add budget options
        filteredBudgets.forEach((budget) => {
            const opt = document.createElement("option");
            opt.value = budget.id;
            opt.textContent = `${budget.category_name} - ${formatCurrency(budget.remaining)} remaining (${budget.period_label})`;
            opt.dataset.remaining = budget.remaining;
            budgetSelect.appendChild(opt);
        });

        // Update info text
        if (filteredBudgets.length === 1) {
            remainingInfo.textContent = `1 budget available with ${formatCurrency(filteredBudgets[0].remaining)} remaining.`;
        } else {
            remainingInfo.textContent = `${filteredBudgets.length} budgets available. Select one to track against.`;
        }
    };

    /**
     * Format currency
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
     * Load expenses from API
     */
    const loadExpenses = async (filter = "all") => {
        try {
            console.log("Loading expenses with filter:", filter);
            showLoading();

            let url = "/api/expenses";
            if (filter !== "all") {
                url = `/api/expenses/type/${filter}`;
            }

            const response = await fetch(url, {
                headers: {
                    Accept: "application/json",
                    "X-CSRF-TOKEN": csrfToken(),
                },
            });

            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }

            const result = await response.json();

            if (result.success) {
                renderExpenses(result.data);
            } else {
                showError("Failed to load expenses.");
            }
        } catch (error) {
            console.error("Error loading expenses:", error);
            showError("An error occurred while loading expenses.");
        }
    };

    /**
     * Render expenses in table
     */
    const renderExpenses = (expenses) => {
        const tbody = elements.tableBody;

        if (!expenses || expenses.length === 0) {
            tbody.innerHTML = "";
            if (elements.emptyState)
                elements.emptyState.classList.remove("d-none");
            return;
        }

        if (elements.emptyState) elements.emptyState.classList.add("d-none");

        let html = "";
        expenses.forEach((expense, index) => {
            const amountClass =
                expense.type === "Income" ? "text-success" : "text-danger";
            const amountPrefix = expense.type === "Income" ? "+" : "-";
            const rowLabel = expense.description || expense.category_name;

            // Budget info
            let budgetInfo = "-";
            if (expense.budget_id) {
                budgetInfo = `<span class="badge bg-info">Budget: ${formatCurrency(expense.budget_amount || 0)}</span>`;
                if (expense.budget_remaining !== undefined) {
                    budgetInfo += `<br><small class="text-muted">Remaining: ${formatCurrency(expense.budget_remaining)}</small>`;
                }
            }

            html += `
                <tr>
                    <td>${index + 1}</td>
                    <td>${new Date(expense.date).toLocaleDateString()}</td>
                    <td>
                        <span class="badge" style="background-color: ${expense.category_color || "#6C757D"}; color: white;">
                            ${expense.category_name}
                        </span>
                    </td>
                    <td>${budgetInfo}</td>
                    <td>${expense.description || "-"}</td>
                    <td class="fw-bold ${amountClass}">${amountPrefix}${expense.formatted_amount || expense.amount}</td>
                    <td>
                        <i class="fas ${expense.payment_method_icon || "fa-credit-card"}"></i>
                        <small>${expense.payment_method_label || expense.payment_method}</small>
                    </td>
                    <td>
                        <button class="btn btn-sm btn-outline-primary edit-expense" data-id="${expense.id}" title="Edit">
                            <i class="fas fa-edit"></i>
                        </button>
                        <button class="btn btn-sm btn-outline-danger delete-expense" data-id="${expense.id}" data-name="${escapeHtml(rowLabel)}" title="Delete">
                            <i class="fas fa-trash"></i>
                        </button>
                    </td>
                </tr>
            `;
        });

        tbody.innerHTML = html;

        // Edit buttons
        document.querySelectorAll(".edit-expense").forEach((btn) => {
            btn.addEventListener("click", function () {
                openModal(this.dataset.id);
            });
        });

        // Delete buttons
        document.querySelectorAll(".delete-expense").forEach((btn) => {
            btn.addEventListener("click", function () {
                openDeleteModal(this.dataset.id, this.dataset.name);
            });
        });
    };

    /**
     * Escape HTML for safe interpolation into data attributes
     */
    const escapeHtml = (str) => {
        const div = document.createElement("div");
        div.textContent = str ?? "";
        return div.innerHTML;
    };

    /**
     * Open expense modal for add/edit
     */
    const openModal = async (id = null) => {
        elements.form.reset();
        clearErrors();
        elements.expenseId.value = "";
        elements.recurringFrequencyWrap.classList.add("d-none");
        elements.isRecurring.checked = false;
        elements.date.value = new Date().toISOString().split("T")[0];

        // Reset budget dropdown
        const budgetSelect = elements.budget;
        budgetSelect.innerHTML =
            '<option value="">No budget (track separately)</option>';
        if (elements.budgetRemainingInfo) {
            elements.budgetRemainingInfo.textContent =
                "Select a category first to see available budgets.";
        }

        if (id) {
            elements.modalTitle.textContent = "Edit Expense";
            elements.saveBtnText.textContent = "Update Expense";

            try {
                const response = await fetch(`/api/expenses/${id}`, {
                    headers: {
                        Accept: "application/json",
                        "X-CSRF-TOKEN": csrfToken(),
                    },
                });

                const result = await response.json();
                if (result.success) {
                    const expense = result.data;
                    elements.expenseId.value = expense.id;
                    elements.amount.value = expense.amount;
                    elements.category.value = expense.category_id;
                    elements.date.value = expense.date;
                    elements.description.value = expense.description || "";
                    elements.isRecurring.checked = expense.is_recurring;

                    // Trigger category change to load budgets
                    await onCategoryChange();

                    // Set budget if exists
                    if (expense.budget_id) {
                        elements.budget.value = expense.budget_id;
                    }

                    if (expense.is_recurring) {
                        elements.recurringFrequencyWrap.classList.remove(
                            "d-none",
                        );
                        elements.recurringFrequency.value =
                            expense.recurring_frequency || "monthly";
                    }

                    // Set payment method
                    const paymentMethod = document.querySelector(
                        `input[name="payment_method"][value="${expense.payment_method}"]`,
                    );
                    if (paymentMethod) paymentMethod.checked = true;
                }
            } catch (error) {
                console.error("Error loading expense:", error);
                showError("Failed to load expense data.");
                return;
            }
        } else {
            elements.modalTitle.textContent = "Add Expense";
            elements.saveBtnText.textContent = "Save Expense";
        }

        expenseModal.show();
    };

    /**
     * Handle save expense
     */
    const handleSave = async () => {
        clearErrors();

        const paymentMethod = document.querySelector(
            'input[name="payment_method"]:checked',
        );
        const isRecurring = elements.isRecurring.checked;
        const budgetId = elements.budget.value;

        const formData = {
            category_id: elements.category.value,
            budget_id: budgetId || null,
            amount: elements.amount.value,
            description: elements.description.value.trim(),
            date: elements.date.value,
            payment_method: paymentMethod ? paymentMethod.value : null,
            is_recurring: isRecurring,
            recurring_frequency: isRecurring
                ? elements.recurringFrequency.value
                : null,
        };

        const id = elements.expenseId.value;
        const isEdit = id !== "";
        const url = isEdit ? `/api/expenses/${id}` : "/api/expenses";
        const method = isEdit ? "PUT" : "POST";

        // Basic validation
        if (!formData.category_id) {
            setError("category_id", "Please select a category");
            return;
        }
        if (!formData.amount || formData.amount <= 0) {
            setError("amount", "Please enter a valid amount");
            return;
        }
        if (!formData.date) {
            setError("date", "Please select a date");
            return;
        }
        if (!formData.payment_method) {
            setError("payment_method", "Please select a payment method");
            return;
        }

        // Check if budget has enough remaining
        if (budgetId) {
            const selectedBudget = availableBudgets.find(
                (b) => b.id == budgetId,
            );
            if (
                selectedBudget &&
                Number(formData.amount) > Number(selectedBudget.remaining)
            ) {
                setError(
                    "budget_id",
                    `Insufficient budget. Only ${formatCurrency(selectedBudget.remaining)} remaining.`,
                );
                return;
            }
        }

        setLoading(true);

        try {
            const response = await fetch(url, {
                method: method,
                headers: {
                    "Content-Type": "application/json",
                    Accept: "application/json",
                    "X-CSRF-TOKEN": csrfToken(),
                },
                body: JSON.stringify(formData),
            });

            const result = await response.json();

            if (response.status === 201 || response.status === 200) {
                expenseModal.hide();
                // Refresh budgets and expenses
                await loadBudgets();
                loadExpenses(currentFilter);
                showSuccess(result.message || "Expense saved successfully.");
            } else if (response.status === 422) {
                displayValidationErrors(result.errors);
            } else {
                showError(result.message || "Failed to save expense.");
            }
        } catch (error) {
            console.error("Error saving expense:", error);
            showError("An error occurred while saving the expense.");
        } finally {
            setLoading(false);
        }
    };

    /**
     * Open delete confirmation
     */
    const openDeleteModal = (id, name) => {
        elements.deleteExpenseId.value = id;
        if (elements.deleteExpenseName) {
            elements.deleteExpenseName.textContent = name || "this transaction";
        }
        deleteModal.show();
    };

    /**
     * Handle delete expense
     */
    const handleDelete = async () => {
        const id = elements.deleteExpenseId.value;

        setDeleteLoading(true);

        try {
            const response = await fetch(`/api/expenses/${id}`, {
                method: "DELETE",
                headers: {
                    Accept: "application/json",
                    "X-CSRF-TOKEN": csrfToken(),
                },
            });

            const result = await response.json();

            if (result.success) {
                deleteModal.hide();
                await loadBudgets();
                loadExpenses(currentFilter);
                showSuccess(result.message || "Expense deleted successfully.");
            } else {
                showError(result.message || "Failed to delete expense.");
            }
        } catch (error) {
            console.error("Error deleting expense:", error);
            showError("An error occurred while deleting the expense.");
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
        const inputMap = {
            amount: elements.amount,
            category_id: elements.category,
            budget_id: elements.budget,
            date: elements.date,
            description: elements.description,
            recurring_frequency: elements.recurringFrequency,
        };
        const input = inputMap[field];
        if (input) input.classList.add("is-invalid");
        if (field === "payment_method") {
            document
                .getElementById("paymentMethodGrid")
                ?.classList.add("is-invalid");
        }
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

    /**
     * Show loading state
     */
    const showLoading = () => {
        elements.tableBody.innerHTML = `
            <tr>
                <td colspan="8" class="text-center py-5">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">Loading...</span>
                    </div>
                </td>
            </tr>
        `;
    };

    /**
     * Set loading state on save button
     */
    const setLoading = (loading) => {
        if (loading) {
            elements.saveBtn.disabled = true;
            elements.saveBtnText.textContent = "Saving...";
            elements.saveBtnSpinner.classList.remove("d-none");
        } else {
            elements.saveBtn.disabled = false;
            elements.saveBtnText.textContent = elements.expenseId.value
                ? "Update Expense"
                : "Save Expense";
            elements.saveBtnSpinner.classList.add("d-none");
        }
    };

    /**
     * Set loading state on delete button
     */
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

// Initialize when DOM is ready
document.addEventListener("DOMContentLoaded", Expenses.init);
