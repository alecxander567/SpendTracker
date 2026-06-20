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

    // ---- Income summary (refreshes the "Income this month" stat card) ----
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
        .catch(() => {
            // Leave the placeholder values in place on failure.
        });

    // ---- Expense summary (feeds the hero "Net cash flow" card + "Expenses this month" stat card) ----
    fetch("/api/expenses/summary", { headers: jsonHeaders })
        .then((res) => (res.ok ? res.json() : Promise.reject()))
        .then((result) => {
            if (!result.success) return;
            const totalIncome = Number(result.data.total_income) || 0;
            const totalExpenses = Number(result.data.total_expenses) || 0;
            const netCashFlow = Number(result.data.net_cash_flow) || 0;

            // Hero card
            const heroEl = document.getElementById("heroBalanceValue");
            const heroSubEl = document.getElementById("heroBalanceSub");
            if (heroEl) {
                heroEl.textContent = formatCurrency(netCashFlow);
                heroEl.classList.toggle("text-danger", netCashFlow < 0);
            }
            if (heroSubEl) {
                heroSubEl.textContent = `${formatCurrency(totalIncome)} in, ${formatCurrency(totalExpenses)} out this month`;
            }

            // Expenses this month stat card
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

    // ---- Recent transactions (merges expenses + incomes, newest first) ----
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
});
