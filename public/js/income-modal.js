/**
 * Income modal controller.
 * Requires Bootstrap 5 JS and the #incomeModal markup from
 * resources/views/components/add-income-modal.blade.php to be present on the page.
 *
 * Usage:
 *   IncomeModal.openCreate();
 *   IncomeModal.openEdit(incomeObject);
 *
 * Fires a `income:saved` CustomEvent on `document` with { detail: { income, isNew } }
 * whenever a save succeeds, so any page can listen and refresh its own data.
 */
/**
 * Shared helper to drive the <x-success-modal> / <x-error-modal> components.
 * Expects elements with ids "incomeSuccessModal" and "incomeErrorModal" to be
 * present on the page (see resources/views/incomes/index.blade.php).
 *
 * Mirrors the pattern used in categories.js: modal instances are created once
 * up front with `new bootstrap.Modal(...)`, not lazily, so they don't race
 * with another modal's hide() transition / backdrop cleanup.
 */
const ResultModal = (() => {
    let successModal = null;
    let errorModal = null;

    function init() {
        const successEl = document.getElementById("incomeSuccessModal");
        const errorEl = document.getElementById("incomeErrorModal");
        if (successEl) successModal = new bootstrap.Modal(successEl);
        if (errorEl) errorModal = new bootstrap.Modal(errorEl);
    }

    function setErrorList(errors) {
        if (!errors || !errors.length) return;
        const list = document.getElementById("incomeErrorModalErrorList");
        const wrap = document.getElementById("incomeErrorModalErrorListWrap");
        if (list) {
            list.innerHTML = errors
                .map(
                    (e) =>
                        `<li class="text-danger small"><i class="fas fa-times-circle me-1"></i>${e}</li>`,
                )
                .join("");
        }
        if (wrap) wrap.classList.remove("d-none");
    }

    function success(message) {
        if (!successModal) init();
        const messageEl = document.querySelector("#incomeSuccessModal .lead");
        if (messageEl) messageEl.textContent = message;
        successModal?.show();
    }

    function error(message, errors) {
        if (!errorModal) init();
        const messageEl = document.querySelector("#incomeErrorModal .lead");
        if (messageEl) messageEl.textContent = message;
        setErrorList(errors);
        errorModal?.show();
    }

    document.addEventListener("DOMContentLoaded", init);

    return { success, error };
})();

const IncomeModal = (() => {
    let modalEl,
        modalInstance,
        formEl,
        alertEl,
        submitBtn,
        categoriesLoaded = false;

    const csrfToken = () =>
        document.querySelector('meta[name="csrf-token"]')?.content;

    const fields = {
        id: "#income_id",
        source: "#income_source",
        amount: "#income_amount",
        date: "#income_date",
        category: "#income_category",
        payment_method: "#income_payment_method",
        description: "#income_description",
        is_recurring: "#income_is_recurring",
        recurring_frequency: "#income_recurring_frequency",
        recurring_end_date: "#income_recurring_end_date",
    };

    function el(selector) {
        return document.querySelector(selector);
    }

    function init() {
        modalEl = document.getElementById("incomeModal");
        if (!modalEl) return false;

        modalInstance = bootstrap.Modal.getOrCreateInstance(modalEl);
        formEl = document.getElementById("incomeForm");
        alertEl = document.getElementById("incomeFormAlert");
        submitBtn = document.getElementById("incomeSubmitBtn");

        el(fields.is_recurring).addEventListener("change", (e) => {
            document
                .getElementById("recurringFields")
                .classList.toggle("d-none", !e.target.checked);
        });

        submitBtn.addEventListener("click", handleSubmit);

        modalEl.addEventListener("hidden.bs.modal", resetForm);

        loadCategories();

        return true;
    }

    function loadCategories() {
        if (categoriesLoaded) return;
        fetch("/api/categories", {
            headers: {
                Accept: "application/json",
                "X-CSRF-TOKEN": csrfToken(),
            },
        })
            .then((res) => (res.ok ? res.json() : Promise.reject()))
            .then((result) => {
                if (!result.success) return;
                const select = el(fields.category);
                (result.data || []).forEach((cat) => {
                    const opt = document.createElement("option");
                    opt.value = cat.id;
                    opt.textContent = cat.name;
                    select.appendChild(opt);
                });
                categoriesLoaded = true;
            })
            .catch(() => {
                // Categories are optional on the income form; fail silently.
            });
    }

    function resetForm() {
        formEl.reset();
        el(fields.id).value = "";
        document.getElementById("recurringFields").classList.add("d-none");
        clearErrors();
        document.getElementById("incomeModalLabel").textContent = "Add income";
        submitBtn.querySelector(".btn-label").textContent = "Save income";
    }

    function clearErrors() {
        alertEl.classList.add("d-none");
        alertEl.textContent = "";
        document
            .querySelectorAll("#incomeForm .is-invalid")
            .forEach((i) => i.classList.remove("is-invalid"));
        document
            .querySelectorAll("#incomeForm .invalid-feedback")
            .forEach((i) => (i.textContent = ""));
    }

    function todayISO() {
        return new Date().toISOString().slice(0, 10);
    }

    function openCreate() {
        if (!modalInstance && !init()) return;
        resetForm();
        el(fields.date).value = todayISO();
        modalInstance.show();
    }

    function openEdit(income) {
        if (!modalInstance && !init()) return;
        resetForm();
        document.getElementById("incomeModalLabel").textContent = "Edit income";
        submitBtn.querySelector(".btn-label").textContent = "Update income";

        el(fields.id).value = income.id;
        el(fields.source).value = income.source ?? "";
        el(fields.amount).value = income.amount ?? "";
        el(fields.date).value = (income.date ?? "").slice(0, 10);
        el(fields.category).value = income.category_id ?? "";
        el(fields.payment_method).value = income.payment_method ?? "cash";
        el(fields.description).value = income.description ?? "";

        const recurring = !!income.is_recurring;
        el(fields.is_recurring).checked = recurring;
        document
            .getElementById("recurringFields")
            .classList.toggle("d-none", !recurring);
        if (income.recurring_frequency)
            el(fields.recurring_frequency).value = income.recurring_frequency;
        if (income.recurring_end_date)
            el(fields.recurring_end_date).value =
                income.recurring_end_date.slice(0, 10);

        modalInstance.show();
    }

    function setLoading(loading) {
        submitBtn.disabled = loading;
        submitBtn
            .querySelector(".spinner-border")
            .classList.toggle("d-none", !loading);
    }

    function showErrors(errors) {
        alertEl.textContent = "Please fix the errors below and try again.";
        alertEl.classList.remove("d-none");
        Object.entries(errors).forEach(([key, messages]) => {
            const input = document.getElementById("income_" + key);
            const feedback = document.querySelector(
                `[data-error-for="${key}"]`,
            );
            if (input) input.classList.add("is-invalid");
            if (feedback)
                feedback.textContent = Array.isArray(messages)
                    ? messages[0]
                    : messages;
        });
    }

    function handleSubmit() {
        clearErrors();

        const id = el(fields.id).value;
        const isNew = !id;
        const isRecurring = el(fields.is_recurring).checked;

        const payload = {
            source: el(fields.source).value.trim(),
            amount: el(fields.amount).value,
            date: el(fields.date).value,
            category_id: el(fields.category).value || null,
            payment_method: el(fields.payment_method).value,
            description: el(fields.description).value.trim() || null,
            is_recurring: isRecurring,
            recurring_frequency: isRecurring
                ? el(fields.recurring_frequency).value
                : null,
            recurring_end_date:
                isRecurring && el(fields.recurring_end_date).value
                    ? el(fields.recurring_end_date).value
                    : null,
        };

        const url = isNew ? "/api/incomes" : `/api/incomes/${id}`;

        setLoading(true);

        fetch(url, {
            method: isNew ? "POST" : "PUT",
            headers: {
                "Content-Type": "application/json",
                Accept: "application/json",
                "X-CSRF-TOKEN": csrfToken(),
            },
            body: JSON.stringify(payload),
        })
            .then(async (res) => {
                const result = await res.json();
                if (!res.ok) {
                    if (res.status === 422 && result.errors) {
                        showErrors(result.errors);
                        return;
                    }
                    throw new Error(result.message || "Something went wrong");
                }

                modalInstance.hide();
                document.dispatchEvent(
                    new CustomEvent("income:saved", {
                        detail: { income: result.data, isNew },
                    }),
                );
                ResultModal.success(
                    isNew
                        ? "Income added successfully."
                        : "Income updated successfully.",
                );
            })
            .catch((err) => {
                ResultModal.error(
                    err.message || "Something went wrong. Please try again.",
                );
            })
            .finally(() => setLoading(false));
    }

    return { openCreate, openEdit };
})();
