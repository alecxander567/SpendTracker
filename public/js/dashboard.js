document.addEventListener("DOMContentLoaded", function () {
    const loader = document.getElementById("pageLoader");
    const content = document.getElementById("mainContent");
    const csrfToken = document.querySelector('meta[name="csrf-token"]').content;

    const jsonHeaders = {
        Accept: "application/json",
        "X-CSRF-TOKEN": csrfToken,
    };

    const formatCurrency = (value) => {
        const num = Number(value) || 0;
        const sign = num < 0 ? "-" : "";
        return (
            sign +
            "₱" +
            Math.abs(num).toLocaleString("en-US", {
                minimumFractionDigits: 2,
                maximumFractionDigits: 2,
            })
        );
    };

    const formatShortDate = (dateStr) => {
        const d = new Date(dateStr);
        if (isNaN(d.getTime())) return "";
        return d.toLocaleDateString("en-US", {
            month: "short",
            day: "numeric",
        });
    };

    const revealPage = () => {
        if (loader) loader.style.display = "none";
        if (content) content.style.display = "block";
    };

    // Always reveal the page after a maximum of 3 seconds
    const timeoutId = setTimeout(revealPage, 3000);

    // ============================================
    // BUDGETS
    // ============================================
    fetch("/api/budgets", { headers: jsonHeaders })
        .then((res) => {
            if (!res.ok) {
                throw new Error("Network response was not ok");
            }
            return res.json();
        })
        .then((result) => {
            if (!result.success) {
                console.warn("API returned unsuccessful:", result);
                return;
            }

            const budgets = result.data;
            const activeBudgets = budgets.filter((b) => b.is_active);

            // Total budget stat card
            const totalBudgeted = activeBudgets.reduce(
                (sum, b) => sum + Number(b.amount),
                0,
            );
            const totalSpent = activeBudgets.reduce(
                (sum, b) => sum + Number(b.spent),
                0,
            );
            const percentUsed =
                totalBudgeted > 0
                    ? Math.round((totalSpent / totalBudgeted) * 100)
                    : 0;

            const totalBudgetEl = document.getElementById("totalBudgetValue");
            const totalBudgetTrendEl =
                document.getElementById("totalBudgetTrend");
            if (totalBudgetEl)
                totalBudgetEl.textContent = formatCurrency(totalBudgeted);
            if (totalBudgetTrendEl) {
                totalBudgetTrendEl.innerHTML = `<i class="fas fa-arrow-up"></i> ${percentUsed}% used`;
            }

            // Top categories list
            const listEl = document.getElementById("topCategoriesList");
            const emptyEl = document.getElementById("topCategoriesEmpty");

            if (activeBudgets.length === 0) {
                if (listEl) listEl.innerHTML = "";
                if (emptyEl) emptyEl.classList.remove("d-none");
                return;
            }

            const top = [...activeBudgets]
                .sort((a, b) => Number(b.spent) - Number(a.spent))
                .slice(0, 4);

            let html = "";
            top.forEach((budget) => {
                const pct = Math.min(Number(budget.percentage_used) || 0, 100);
                const color = budget.category_color || "#6C757D";
                html += `
                    <div class="category-row">
                        <span class="category-dot" style="background: ${color};"></span>
                        <span class="small flex-shrink-0" style="width: 90px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;">${budget.category_name}</span>
                        <div class="category-progress">
                            <div class="category-progress-bar" style="width: ${pct}%; background: ${color};"></div>
                        </div>
                        <span class="small fw-semibold">${formatCurrency(budget.spent)}</span>
                    </div>
                `;
            });

            if (listEl) listEl.innerHTML = html;
            if (emptyEl) emptyEl.classList.add("d-none");
        })
        .catch((err) => {
            console.error("Error loading budget data:", err);
            const listEl = document.getElementById("topCategoriesList");
            const emptyEl = document.getElementById("topCategoriesEmpty");
            if (listEl) listEl.innerHTML = "";
            if (emptyEl) emptyEl.classList.remove("d-none");
        })
        .finally(() => {
            clearTimeout(timeoutId);
            revealPage();
        });

    // ============================================
    // INCOME SUMMARY
    // ============================================
    fetch("/api/incomes/summary", { headers: jsonHeaders })
        .then((res) => (res.ok ? res.json() : Promise.reject()))
        .then((result) => {
            if (!result.success) return;
            const valueEl = document.getElementById("incomeMonthValue");
            const trendEl = document.getElementById("incomeMonthTrend");
            if (valueEl)
                valueEl.textContent = formatCurrency(result.data.total_income);
            if (trendEl) {
                const count = result.data.total_entries ?? 0;
                trendEl.classList.remove("text-muted");
                trendEl.innerHTML =
                    count > 0
                        ? `<i class="fas fa-arrow-up"></i> ${count} entr${count === 1 ? "y" : "ies"}`
                        : '<i class="fas fa-minus"></i> No data yet';
            }
        })
        .catch(() => {});

    // ============================================
    // EXPENSE SUMMARY
    // ============================================
    fetch("/api/expenses/summary", { headers: jsonHeaders })
        .then((res) => (res.ok ? res.json() : Promise.reject()))
        .then((result) => {
            if (!result.success) return;
            const totalIncome = Number(result.data.total_income) || 0;
            const totalExpenses = Number(result.data.total_expenses) || 0;
            const netCashFlow = Number(result.data.net_cash_flow) || 0;

            const heroEl = document.getElementById("heroBalanceValue");
            const heroSubEl = document.getElementById("heroBalanceSub");
            if (heroEl) {
                heroEl.textContent = formatCurrency(netCashFlow);
                heroEl.classList.toggle("text-danger", netCashFlow < 0);
            }
            if (heroSubEl) {
                heroSubEl.textContent = `${formatCurrency(totalIncome)} in, ${formatCurrency(totalExpenses)} out this month`;
            }

            const expenseValueEl = document.getElementById("expenseMonthValue");
            const expenseTrendEl = document.getElementById("expenseMonthTrend");
            if (expenseValueEl)
                expenseValueEl.textContent = formatCurrency(totalExpenses);
            if (expenseTrendEl) {
                expenseTrendEl.classList.remove("text-muted");
                expenseTrendEl.innerHTML =
                    totalExpenses > 0
                        ? `<i class="fas fa-arrow-down"></i> ${formatCurrency(totalExpenses)} spent`
                        : '<i class="fas fa-minus"></i> No data yet';
            }
        })
        .catch((err) => {
            console.error("Error loading expense summary:", err);
            const heroSubEl = document.getElementById("heroBalanceSub");
            if (heroSubEl)
                heroSubEl.textContent =
                    "Across all accounts \u00b7 couldn't load this month's data";
        });

    // ============================================
    // WEEKLY SPENDING
    // ============================================
    fetch("/api/expenses/weekly", { headers: jsonHeaders })
        .then((res) => (res.ok ? res.json() : Promise.reject()))
        .then((result) => {
            if (!result.success) return;
            const days = result.data;
            const chartEl = document.getElementById("weeklySpendChart");
            const emptyEl = document.getElementById("weeklySpendEmpty");
            const total = days.reduce((sum, d) => sum + d.total, 0);

            if (total === 0) {
                if (chartEl) chartEl.innerHTML = "";
                if (emptyEl) emptyEl.classList.remove("d-none");
                return;
            }

            const max = Math.max(...days.map((d) => d.total), 1);
            const CHART_HEIGHT = 140;

            let html = `<div class="d-flex align-items-end justify-content-between" style="height: ${CHART_HEIGHT}px; gap: 10px;">`;
            days.forEach((d) => {
                const heightPct = Math.max((d.total / max) * 100, 4);
                const isToday = d.label === "Today" || d.isToday;
                html += `
                <div class="d-flex flex-column align-items-center flex-fill" style="height: 100%;">
                    <div class="flex-fill d-flex align-items-end" style="width: 100%;">
                        <div
                            class="weekly-bar-track${isToday ? " is-today" : ""}"
                            style="height: ${heightPct}%;"
                            title="${formatCurrency(d.total)}"
                        ></div>
                    </div>
                    <div class="weekly-bar-label${isToday ? " is-today" : ""}">${d.label}</div>
                </div>
            `;
            });
            html += "</div>";

            if (chartEl) chartEl.innerHTML = html;
            if (emptyEl) emptyEl.classList.add("d-none");
        })
        .catch((err) => {
            console.error("Error loading weekly spending:", err);
            const chartEl = document.getElementById("weeklySpendChart");
            const emptyEl = document.getElementById("weeklySpendEmpty");
            if (chartEl) chartEl.innerHTML = "";
            if (emptyEl) emptyEl.classList.remove("d-none");
        });

    // ============================================
    // RECENT TRANSACTIONS
    // ============================================
    Promise.all([
        fetch("/api/expenses", { headers: jsonHeaders }).then((res) =>
            res.ok ? res.json() : { success: false },
        ),
        fetch("/api/incomes?per_page=10&sort=date&direction=desc", {
            headers: jsonHeaders,
        }).then((res) => (res.ok ? res.json() : { success: false })),
    ])
        .then(([expenseResult, incomeResult]) => {
            const listEl = document.getElementById("recentTransactionsList");
            const emptyEl = document.getElementById("recentTransactionsEmpty");

            const expenseItems = (
                expenseResult.success ? expenseResult.data : []
            )
                .filter((e) => e.category_type === "expense")
                .map((e) => ({
                    kind: "expense",
                    amount: Number(e.amount),
                    label: e.description || e.category_name,
                    date: e.date,
                    color: e.category_color || "#6C757D",
                }));

            const incomeRows = incomeResult.success
                ? Array.isArray(incomeResult.data)
                    ? incomeResult.data
                    : incomeResult.data.data || []
                : [];
            const incomeItems = incomeRows.map((i) => ({
                kind: "income",
                amount: Number(i.amount),
                label: i.description || i.source,
                date: i.date,
                color: "#00B894",
            }));

            const merged = [...expenseItems, ...incomeItems]
                .sort((a, b) => new Date(b.date) - new Date(a.date))
                .slice(0, 6);

            if (merged.length === 0) {
                if (listEl) listEl.innerHTML = "";
                if (emptyEl) emptyEl.classList.remove("d-none");
                return;
            }

            let html = "";
            merged.forEach((tx) => {
                const isIncome = tx.kind === "income";
                const sign = isIncome ? "+" : "\u2212";
                const amountClass = isIncome ? "text-success" : "text-danger";
                html += `
                    <div class="d-flex align-items-center justify-content-between py-2 border-bottom">
                        <div class="d-flex align-items-center gap-2">
                            <span class="category-dot" style="background: ${tx.color};"></span>
                            <div>
                                <div class="small fw-semibold">${tx.label}</div>
                                <div class="text-muted" style="font-size: 12px;">${formatShortDate(tx.date)}</div>
                            </div>
                        </div>
                        <div class="fw-semibold ${amountClass}">${sign}${formatCurrency(tx.amount)}</div>
                    </div>
                `;
            });

            if (listEl) listEl.innerHTML = html;
            if (emptyEl) emptyEl.classList.add("d-none");
        })
        .catch((err) => {
            console.error("Error loading recent transactions:", err);
            const listEl = document.getElementById("recentTransactionsList");
            const emptyEl = document.getElementById("recentTransactionsEmpty");
            if (listEl) listEl.innerHTML = "";
            if (emptyEl) emptyEl.classList.remove("d-none");
        });

    // ============================================
    // SAVINGS GOALS (WISHLIST) - Load and Display
    // ============================================
    let monthlyIncome = 0;
    let allGoals = [];

    // Category -> Font Awesome icon map (icons, not emoji)
    const CATEGORY_ICONS = {
        emergency: "fa-shield-halved",
        vacation: "fa-umbrella-beach",
        education: "fa-graduation-cap",
        home: "fa-house",
        vehicle: "fa-car",
        retirement: "fa-rocket",
        other: "fa-box",
    };

    // Function to render wishlist items
    function renderWishlistItems(goals) {
        const listEl = document.getElementById("savingsGoalsList");
        const emptyEl = document.getElementById("savingsGoalsEmpty");

        if (!goals || goals.length === 0) {
            if (listEl) listEl.innerHTML = "";
            if (emptyEl) emptyEl.classList.remove("d-none");
            return;
        }

        // Display all goals (limit to 5)
        const displayGoals = goals.slice(0, 5);

        let html = "";
        displayGoals.forEach((goal) => {
            const targetAmount = Number(goal.target_amount) || 0;
            const isAffordable = monthlyIncome >= targetAmount;
            const remainingAfterPurchase = monthlyIncome - targetAmount;
            const amountNeeded = Math.max(0, targetAmount - monthlyIncome);
            const iconClass =
                CATEGORY_ICONS[goal.category] || goal.category_icon || "fa-box";

            // Progress is "monthly income vs target" since that's the
            // affordability signal already supplied by the API.
            const progressPct = Math.min(
                100,
                Math.round((monthlyIncome / targetAmount) * 100) || 0,
            );

            // Short affordability line + pill, with remaining balance after
            // purchase shown when the item can be afforded this month.
            const affordPill = isAffordable
                ? `<span class="wishlist-pill on-track"><i class="fas fa-check"></i> Affordable</span>`
                : `<span class="wishlist-pill behind"><i class="fas fa-xmark"></i> Not yet</span>`;

            const metaLine = isAffordable
                ? `Affordable &middot; ${formatCurrency(remainingAfterPurchase)} left after purchase`
                : `Need ${formatCurrency(amountNeeded)} more to afford this`;

            html += `
                <div class="wishlist-card">
                    <div class="wishlist-card-body">
                        <span class="wishlist-icon ${goal.category}">
                            <i class="fas ${iconClass}"></i>
                        </span>
                        <div class="wishlist-card-main">
                            <div class="wishlist-card-header">
                                <p class="wishlist-card-name">${goal.name}</p>
                                <span class="wishlist-pill priority-${goal.priority}">${goal.priority_label}</span>
                            </div>
                            <p class="wishlist-card-meta">${metaLine}</p>
                            <div class="wishlist-progress">
                                <div class="wishlist-progress-bar" style="width: ${progressPct}%;"></div>
                            </div>
                            <div class="wishlist-afford-row">
                                ${affordPill}
                            </div>
                        </div>
                    </div>
                    <div class="wishlist-card-actions">
                        <button class="wishlist-action-btn edit edit-goal-btn" data-id="${goal.id}" aria-label="Edit ${goal.name}">
                            <i class="fas fa-pen"></i> <span>Edit</span>
                        </button>
                        <button class="wishlist-action-btn delete delete-goal-btn" data-id="${goal.id}" data-name="${goal.name}" aria-label="Delete ${goal.name}">
                            <i class="fas fa-trash-alt"></i> <span>Delete</span>
                        </button>
                    </div>
                </div>
            `;
        });

        if (listEl) listEl.innerHTML = html;
        if (emptyEl) emptyEl.classList.add("d-none");

        // Add event listeners for Edit buttons
        document.querySelectorAll(".edit-goal-btn").forEach((btn) => {
            btn.addEventListener("click", function () {
                openEditGoalModal(this.dataset.id);
            });
        });

        // Add event listeners for Delete buttons
        document.querySelectorAll(".delete-goal-btn").forEach((btn) => {
            btn.addEventListener("click", function () {
                openDeleteGoalModal(this.dataset.id, this.dataset.name);
            });
        });
    }

    // Load monthly income first, then wishlist items
    fetch("/api/incomes/summary", { headers: jsonHeaders })
        .then((res) => (res.ok ? res.json() : Promise.reject()))
        .then((result) => {
            if (result.success) {
                monthlyIncome = result.data.total_income || 0;
            }
        })
        .catch(() => {})
        .finally(() => {
            // Now load savings goals
            fetch("/api/savings", {
                headers: jsonHeaders,
            })
                .then((res) => (res.ok ? res.json() : Promise.reject()))
                .then((result) => {
                    if (result.success) {
                        allGoals = result.data || [];
                        renderWishlistItems(allGoals);
                    } else {
                        renderWishlistItems([]);
                    }
                })
                .catch((err) => {
                    console.error("Error loading savings goals:", err);
                    renderWishlistItems([]);
                });
        });

    // ============================================
    // SAVINGS GOALS - Modal Functions
    // ============================================

    // Initialize modal instances
    const savingsModal = new bootstrap.Modal(
        document.getElementById("savingsModal"),
    );
    const deleteGoalModal = new bootstrap.Modal(
        document.getElementById("deleteGoalModal"),
    );
    const savingsSuccessModal = new bootstrap.Modal(
        document.getElementById("savingsSuccessModal"),
    );
    const savingsErrorModal = new bootstrap.Modal(
        document.getElementById("savingsErrorModal"),
    );

    // Show/hide functions for success/error modals
    function showSavingsSuccess(message) {
        const messageEl = document.querySelector("#savingsSuccessModal .lead");
        if (messageEl) messageEl.textContent = message;
        savingsSuccessModal.show();
    }

    function showSavingsError(message) {
        const messageEl = document.querySelector("#savingsErrorModal .lead");
        if (messageEl) messageEl.textContent = message;
        savingsErrorModal.show();
    }

    // Goal error handling
    function setGoalError(field, message) {
        const errorEl = document.getElementById(
            `goal${field.charAt(0).toUpperCase() + field.slice(1)}Error`,
        );
        if (errorEl) {
            errorEl.textContent = message;
            errorEl.style.display = "block";
        }
        const input = document.querySelector(`[name="${field}"]`);
        if (input) input.classList.add("is-invalid");
    }

    function clearGoalErrors() {
        document
            .querySelectorAll("#savingsForm .invalid-feedback")
            .forEach((el) => {
                el.style.display = "none";
                el.textContent = "";
            });
        document.querySelectorAll("#savingsForm .is-invalid").forEach((el) => {
            el.classList.remove("is-invalid");
        });
    }

    function displayGoalValidationErrors(errors) {
        clearGoalErrors();
        for (const [field, messages] of Object.entries(errors)) {
            setGoalError(field, messages[0]);
        }
    }

    // ============================================
    // SAVINGS GOALS - Priority Radio Buttons
    // ============================================

    // Handle priority radio buttons
    document
        .querySelectorAll('input[name="priority_radio"]')
        .forEach((radio) => {
            radio.addEventListener("change", function () {
                document.getElementById("goalPriority").value = this.value;
            });
        });

    // Loading states
    function setGoalLoading(loading) {
        const btn = document.getElementById("saveGoalBtn");
        const text = document.getElementById("saveBtnText");
        const spinner = document.getElementById("saveBtnSpinner");

        if (loading) {
            btn.disabled = true;
            text.textContent = "Saving...";
            spinner.classList.remove("d-none");
        } else {
            btn.disabled = false;
            const isEdit = document.getElementById("goalId").value !== "";
            text.textContent = isEdit ? "Update Item" : "Create Goal";
            spinner.classList.add("d-none");
        }
    }

    function setDeleteLoading(loading) {
        const btn = document.getElementById("confirmDeleteGoalBtn");
        const text = document.getElementById("deleteBtnText");
        const spinner = document.getElementById("deleteBtnSpinner");

        if (loading) {
            btn.disabled = true;
            text.textContent = "Deleting...";
            spinner.classList.remove("d-none");
        } else {
            btn.disabled = false;
            text.textContent = "Delete";
            spinner.classList.add("d-none");
        }
    }

    // Open Create Goal Modal
    window.openCreateGoalModal = function () {
        const form = document.getElementById("savingsForm");
        form.reset();
        clearGoalErrors();
        document.getElementById("goalId").value = "";
        document.getElementById("savingsModalTitle").textContent =
            "Add to Wishlist";
        document.getElementById("saveBtnText").textContent = "Create Goal";

        const date = new Date();
        date.setMonth(date.getMonth() + 3);
        document.getElementById("goalTargetDate").value = date
            .toISOString()
            .split("T")[0];

        // Set default priority
        document.getElementById("goalPriority").value = "medium";
        document.getElementById("priorityMedium").checked = true;

        savingsModal.show();
    };

    // Open Edit Goal Modal
    window.openEditGoalModal = async function (goalId) {
        try {
            const response = await fetch(`/api/savings/${goalId}`, {
                headers: {
                    Accept: "application/json",
                    "X-CSRF-TOKEN": csrfToken,
                },
            });

            const result = await response.json();

            if (result.success) {
                const goal = result.data;
                const form = document.getElementById("savingsForm");
                form.reset();
                clearGoalErrors();

                document.getElementById("goalId").value = goal.id;
                document.getElementById("goalName").value = goal.name;
                document.getElementById("goalTargetAmount").value =
                    goal.target_amount;
                document.getElementById("goalTargetDate").value =
                    goal.target_date;
                document.getElementById("goalCategory").value = goal.category;
                document.getElementById("goalDescription").value =
                    goal.description || "";

                // Set priority
                document.getElementById("goalPriority").value = goal.priority;
                document.querySelector(
                    `input[name="priority_radio"][value="${goal.priority}"]`,
                ).checked = true;

                document.getElementById("savingsModalTitle").textContent =
                    "Edit Wishlist Item";
                document.getElementById("saveBtnText").textContent =
                    "Update Item";

                savingsModal.show();
            }
        } catch (error) {
            console.error("Error loading goal for edit:", error);
            showSavingsError("Failed to load item for editing.");
        }
    };

    // Open Delete Goal Modal
    window.openDeleteGoalModal = function (goalId, goalName) {
        document.getElementById("deleteGoalId").value = goalId;
        document.getElementById("deleteGoalName").textContent = goalName;
        deleteGoalModal.show();
    };

    // Handle Save Goal (Create or Update)
    async function handleSaveGoal() {
        const form = document.getElementById("savingsForm");
        const formData = new FormData(form);
        const goalId = document.getElementById("goalId").value;
        const isEdit = goalId !== "";

        const data = {
            name: formData.get("name"),
            target_amount: formData.get("target_amount"),
            target_date: formData.get("target_date"),
            category: formData.get("category"),
            priority: document.getElementById("goalPriority").value,
            description: formData.get("description"),
        };

        if (!data.name) {
            setGoalError("name", "Please enter an item name");
            return;
        }
        if (!data.target_amount || parseFloat(data.target_amount) <= 0) {
            setGoalError("target_amount", "Please enter a valid price");
            return;
        }
        if (!data.target_date) {
            setGoalError("target_date", "Please select a target date");
            return;
        }
        if (!data.category) {
            setGoalError("category", "Please select a category");
            return;
        }

        setGoalLoading(true);

        try {
            const url = isEdit ? `/api/savings/${goalId}` : "/api/savings";
            const method = isEdit ? "PUT" : "POST";

            const response = await fetch(url, {
                method: method,
                headers: {
                    "Content-Type": "application/json",
                    Accept: "application/json",
                    "X-CSRF-TOKEN": csrfToken,
                },
                body: JSON.stringify(data),
            });

            const result = await response.json();

            if (response.status === 201 || response.status === 200) {
                savingsModal.hide();
                showSavingsSuccess(
                    result.message || isEdit
                        ? "Item updated successfully!"
                        : "Item added to wishlist!",
                );
                setTimeout(() => location.reload(), 1500);
            } else if (response.status === 422) {
                displayGoalValidationErrors(result.errors);
            } else {
                showSavingsError(result.message || "Failed to save item.");
            }
        } catch (error) {
            console.error("Error saving goal:", error);
            showSavingsError("An error occurred while saving the item.");
        } finally {
            setGoalLoading(false);
        }
    }

    // Handle Delete Goal
    async function handleDeleteGoal() {
        const goalId = document.getElementById("deleteGoalId").value;

        setDeleteLoading(true);

        try {
            const response = await fetch(`/api/savings/${goalId}`, {
                method: "DELETE",
                headers: {
                    Accept: "application/json",
                    "X-CSRF-TOKEN": csrfToken,
                },
            });

            const result = await response.json();

            if (response.status === 200) {
                deleteGoalModal.hide();
                showSavingsSuccess(
                    result.message || "Item deleted successfully!",
                );
                setTimeout(() => location.reload(), 1500);
            } else {
                showSavingsError(result.message || "Failed to delete item.");
            }
        } catch (error) {
            console.error("Error deleting goal:", error);
            showSavingsError("An error occurred while deleting the item.");
        } finally {
            setDeleteLoading(false);
        }
    }

    // ============================================
    // SAVINGS GOALS - Event Listeners
    // ============================================

    // Add Goal button
    document
        .getElementById("addGoalBtn")
        ?.addEventListener("click", window.openCreateGoalModal);
    document
        .getElementById("emptyStateAddBtn")
        ?.addEventListener("click", window.openCreateGoalModal);

    // Save Goal button
    document
        .getElementById("saveGoalBtn")
        ?.addEventListener("click", handleSaveGoal);

    // Delete Goal button
    document
        .getElementById("confirmDeleteGoalBtn")
        ?.addEventListener("click", handleDeleteGoal);
});
