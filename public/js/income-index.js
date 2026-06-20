document.addEventListener("DOMContentLoaded", function () {
    const loader = document.getElementById("pageLoader");
    const content = document.getElementById("mainContent");
    const csrfToken = document.querySelector('meta[name="csrf-token"]').content;

    let currentPage = 1;
    let pendingDeleteId = null;

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

    const formatDate = (value) => {
        if (!value) return "";
        const d = new Date(value);
        return d.toLocaleDateString("en-US", {
            month: "short",
            day: "numeric",
            year: "numeric",
        });
    };

    const methodIcon = {
        cash: "fa-money-bill-wave",
        bank_transfer: "fa-university",
        direct_deposit: "fa-building-columns",
        check: "fa-money-check",
        mobile_money: "fa-mobile-alt",
        crypto: "fa-coins",
        other: "fa-credit-card",
    };

    const methodLabel = {
        cash: "Cash",
        bank_transfer: "Bank Transfer",
        direct_deposit: "Direct Deposit",
        check: "Check",
        mobile_money: "Mobile Money",
        crypto: "Cryptocurrency",
        other: "Other",
    };

    function authHeaders(extra = {}) {
        return Object.assign(
            { Accept: "application/json", "X-CSRF-TOKEN": csrfToken },
            extra,
        );
    }

    function buildQuery() {
        const params = new URLSearchParams();
        const source = document.getElementById("filterSource").value.trim();
        const method = document.getElementById("filterPaymentMethod").value;
        const active = document.getElementById("filterActive").value;
        const recurring = document.getElementById("filterRecurring").value;

        if (source) params.set("source", source);
        if (method) params.set("payment_method", method);
        if (active !== "") params.set("is_active", active);
        if (recurring !== "") params.set("is_recurring", recurring);
        params.set("page", currentPage);
        params.set("per_page", 10);
        params.set("sort", "date");
        params.set("direction", "desc");

        return params.toString();
    }

    function renderRow(income) {
        const icon = methodIcon[income.payment_method] || "fa-credit-card";
        const isRecurring = !!income.is_recurring;
        const isActive = income.is_active !== false;

        return `
            <div class="txn-row income-row" data-id="${income.id}">
                <div class="txn-icon"><i class="fas ${icon}"></i></div>
                <div class="flex-grow-1 min-w-0">
                    <p class="txn-title mb-0 text-truncate">${escapeHtml(income.source)}</p>
                    <div class="txn-meta">
                        ${formatDate(income.date)} &middot; ${methodLabel[income.payment_method] || income.payment_method}
                        ${isRecurring ? ' &middot; <span class="badge-recurring"><i class="fas fa-rotate"></i> ' + (income.recurring_frequency ? capitalize(income.recurring_frequency) : "Recurring") + "</span>" : ""}
                        ${!isActive ? ' &middot; <span class="text-muted">Inactive</span>' : ""}
                    </div>
                </div>
                <div class="text-end me-2">
                    <div class="txn-amount positive">+${formatCurrency(income.amount)}</div>
                    ${income.category ? `<div class="txn-meta">${escapeHtml(income.category.name)}</div>` : ""}
                </div>
                <div class="dropdown">
                    <button class="btn btn-sm btn-light rounded-circle" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                        <i class="fas fa-ellipsis-vertical"></i>
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end">
                        <li><a class="dropdown-item edit-income-link" href="#" data-id="${income.id}"><i class="fas fa-pen me-2"></i>Edit</a></li>
                        <li><a class="dropdown-item toggle-active-link" href="#" data-id="${income.id}"><i class="fas fa-power-off me-2"></i>${isActive ? "Mark inactive" : "Mark active"}</a></li>
                        <li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-item text-danger delete-income-link" href="#" data-id="${income.id}"><i class="fas fa-trash me-2"></i>Delete</a></li>
                    </ul>
                </div>
            </div>
        `;
    }

    function escapeHtml(str) {
        const div = document.createElement("div");
        div.textContent = str ?? "";
        return div.innerHTML;
    }

    function capitalize(str) {
        return str.charAt(0).toUpperCase() + str.slice(1);
    }

    function renderPagination(meta) {
        const nav = document.getElementById("paginationNav");
        const links = document.getElementById("paginationLinks");
        links.innerHTML = "";

        if (!meta || meta.last_page <= 1) {
            nav.classList.add("d-none");
            return;
        }

        nav.classList.remove("d-none");

        const addItem = (label, page, disabled, active) => {
            const li = document.createElement("li");
            li.className = `page-item ${disabled ? "disabled" : ""} ${active ? "active" : ""}`;
            const a = document.createElement("a");
            a.className = "page-link";
            a.href = "#";
            a.textContent = label;
            if (!disabled && !active) {
                a.addEventListener("click", (e) => {
                    e.preventDefault();
                    currentPage = page;
                    fetchIncomes();
                });
            }
            li.appendChild(a);
            links.appendChild(li);
        };

        addItem("‹", meta.current_page - 1, meta.current_page === 1, false);
        for (let p = 1; p <= meta.last_page; p++) {
            addItem(String(p), p, false, p === meta.current_page);
        }
        addItem(
            "›",
            meta.current_page + 1,
            meta.current_page === meta.last_page,
            false,
        );
    }

    function fetchIncomes() {
        const listEl = document.getElementById("incomeList");
        const loadingEl = document.getElementById("incomeListLoading");
        const emptyEl = document.getElementById("incomeListEmpty");

        listEl.classList.add("d-none");
        emptyEl.classList.add("d-none");
        loadingEl.classList.remove("d-none");

        fetch(`/api/incomes?${buildQuery()}`, { headers: authHeaders() })
            .then((res) => (res.ok ? res.json() : Promise.reject()))
            .then((result) => {
                loadingEl.classList.add("d-none");

                if (!result.success) return;

                const page = result.data;
                const items = page.data || [];

                document.getElementById("resultCount").textContent =
                    page.total != null
                        ? `${page.total} entr${page.total === 1 ? "y" : "ies"}`
                        : "";

                if (items.length === 0) {
                    emptyEl.classList.remove("d-none");
                    renderPagination(null);
                    return;
                }

                listEl.innerHTML = items.map(renderRow).join("");
                listEl.classList.remove("d-none");
                renderPagination(page);
            })
            .catch(() => {
                loadingEl.classList.add("d-none");
                emptyEl.classList.remove("d-none");
            });
    }

    function fetchSummary() {
        fetch("/api/incomes/summary", { headers: authHeaders() })
            .then((res) => (res.ok ? res.json() : Promise.reject()))
            .then((result) => {
                if (!result.success) return;
                const data = result.data;

                document.getElementById("summaryMonthTotal").textContent =
                    formatCurrency(data.total_income);
                const entries = data.total_entries ?? 0;
                document.getElementById("summaryMonthEntries").innerHTML =
                    entries > 0
                        ? `<i class="fas fa-arrow-up"></i> ${entries} entr${entries === 1 ? "y" : "ies"}`
                        : '<i class="fas fa-minus"></i> No data yet';

                const recurringStat = (data.recurring_stats || []).find(
                    (s) => s.is_recurring == 1 || s.is_recurring === true,
                );
                document.getElementById("summaryRecurringCount").textContent =
                    recurringStat ? recurringStat.count : 0;
                document.getElementById("summaryRecurringTotal").textContent =
                    formatCurrency(recurringStat ? recurringStat.total : 0) +
                    " / mo";

                const sources = data.income_sources || [];
                if (sources.length > 0) {
                    document.getElementById("summaryTopSource").textContent =
                        sources[0].source;
                    document.getElementById(
                        "summaryTopSourceAmount",
                    ).textContent = formatCurrency(sources[0].total);
                } else {
                    document.getElementById("summaryTopSource").textContent =
                        "—";
                    document.getElementById(
                        "summaryTopSourceAmount",
                    ).textContent = formatCurrency(0);
                }
            })
            .catch(() => {});
    }

    function refreshAll() {
        fetchIncomes();
        fetchSummary();
    }

    // ---- Filters ----
    [
        "filterSource",
        "filterPaymentMethod",
        "filterActive",
        "filterRecurring",
    ].forEach((id) => {
        const el = document.getElementById(id);
        const evt = el.tagName === "SELECT" ? "change" : "input";
        let debounce;
        el.addEventListener(evt, () => {
            clearTimeout(debounce);
            debounce = setTimeout(
                () => {
                    currentPage = 1;
                    fetchIncomes();
                },
                evt === "input" ? 350 : 0,
            );
        });
    });

    document.getElementById("clearFiltersBtn").addEventListener("click", () => {
        document.getElementById("filterSource").value = "";
        document.getElementById("filterPaymentMethod").value = "";
        document.getElementById("filterActive").value = "";
        document.getElementById("filterRecurring").value = "";
        currentPage = 1;
        fetchIncomes();
    });

    // ---- Add button ----
    document
        .getElementById("addIncomeBtn")
        .addEventListener("click", () => IncomeModal.openCreate());

    // ---- Row actions (event delegation) ----
    document.getElementById("incomeList").addEventListener("click", (e) => {
        const editLink = e.target.closest(".edit-income-link");
        const toggleLink = e.target.closest(".toggle-active-link");
        const deleteLink = e.target.closest(".delete-income-link");

        if (editLink) {
            e.preventDefault();
            const id = editLink.dataset.id;
            fetch(`/api/incomes/${id}`, { headers: authHeaders() })
                .then((res) => res.json())
                .then((result) => {
                    if (result.success) IncomeModal.openEdit(result.data);
                });
            return;
        }

        if (toggleLink) {
            e.preventDefault();
            const id = toggleLink.dataset.id;
            fetch(`/api/incomes/${id}/toggle-active`, {
                method: "PATCH",
                headers: authHeaders(),
            })
                .then((res) => res.json())
                .then((result) => {
                    if (result.success) refreshAll();
                });
            return;
        }

        if (deleteLink) {
            e.preventDefault();
            pendingDeleteId = deleteLink.dataset.id;
            bootstrap.Modal.getOrCreateInstance(
                document.getElementById("deleteIncomeModal"),
            ).show();
        }
    });

    document
        .getElementById("confirmDeleteBtn")
        .addEventListener("click", () => {
            if (!pendingDeleteId) return;
            fetch(`/api/incomes/${pendingDeleteId}`, {
                method: "DELETE",
                headers: authHeaders(),
            })
                .then((res) =>
                    res.json().then((result) => ({ ok: res.ok, result })),
                )
                .then(({ ok, result }) => {
                    bootstrap.Modal.getOrCreateInstance(
                        document.getElementById("deleteIncomeModal"),
                    ).hide();
                    pendingDeleteId = null;

                    if (ok && result.success) {
                        refreshAll();
                        ResultModal.success("Income deleted successfully.");
                    } else {
                        ResultModal.error(
                            result.message || "Failed to delete income.",
                        );
                    }
                })
                .catch(() => {
                    bootstrap.Modal.getOrCreateInstance(
                        document.getElementById("deleteIncomeModal"),
                    ).hide();
                    pendingDeleteId = null;
                    ResultModal.error(
                        "Failed to delete income. Please try again.",
                    );
                });
        });

    // ---- Modal save success ----
    document.addEventListener("income:saved", refreshAll);

    // ---- Reveal page ----
    const timeoutId = setTimeout(reveal, 3000);
    function reveal() {
        clearTimeout(timeoutId);
        if (loader) loader.style.display = "none";
        if (content) content.style.display = "block";
    }

    refreshAll();
    reveal();
});
