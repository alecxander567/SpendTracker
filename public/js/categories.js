/**
 * Categories Module - Handles category management
 */

const Categories = (() => {
    "use strict";

    // DOM Elements
    const elements = {
        tableBody: document.getElementById("categoriesTableBody"),
        emptyState: document.getElementById("emptyState"),
        addBtn: document.getElementById("addCategoryBtn"),
        emptyStateAddBtn: document.getElementById("emptyStateAddBtn"),
        modal: document.getElementById("categoryModal"),
        modalTitle: document.getElementById("categoryModalTitle"),
        form: document.getElementById("categoryForm"),
        categoryId: document.getElementById("categoryId"),
        categoryName: document.getElementById("categoryName"),
        categoryType: document.getElementById("categoryType"),
        categoryColor: document.getElementById("categoryColor"),
        categoryColorHex: document.getElementById("categoryColorHex"),
        saveBtn: document.getElementById("saveCategoryBtn"),
        saveBtnText: document.getElementById("saveBtnText"),
        saveBtnSpinner: document.getElementById("saveBtnSpinner"),
        deleteModal: document.getElementById("deleteCategoryModal"),
        deleteCategoryName: document.getElementById("deleteCategoryName"),
        deleteCategoryId: document.getElementById("deleteCategoryId"),
        confirmDeleteBtn: document.getElementById("confirmDeleteBtn"),
        deleteBtnText: document.getElementById("deleteBtnText"),
        deleteBtnSpinner: document.getElementById("deleteBtnSpinner"),
        tabs: document.querySelectorAll('[data-bs-toggle="tab"]'),
    };

    // Error elements
    const errorElements = {
        name: document.getElementById("categoryNameError"),
        type: document.getElementById("categoryTypeError"),
        color: document.getElementById("categoryColorError"),
    };

    // Modal instances
    let categoryModal = null;
    let deleteModal = null;
    let successModal = null;
    let errorModal = null;

    // Current filter type
    let currentFilter = "all";

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
        const messageEl = document.querySelector("#categorySuccessModal .lead");
        if (messageEl) messageEl.textContent = message;
        successModal.show();
    };

    /**
     * Show error modal with a message
     */
    const showError = (message) => {
        const messageEl = document.querySelector("#categoryErrorModal .lead");
        if (messageEl) messageEl.textContent = message;
        errorModal.show();
    };

    /**
     * Initialize the module
     */
    const init = () => {
        categoryModal = new bootstrap.Modal(elements.modal);
        deleteModal = new bootstrap.Modal(elements.deleteModal);
        successModal = new bootstrap.Modal(
            document.getElementById("categorySuccessModal"),
        );
        errorModal = new bootstrap.Modal(
            document.getElementById("categoryErrorModal"),
        );

        elements.addBtn?.addEventListener("click", () => openModal());
        elements.emptyStateAddBtn?.addEventListener("click", () => openModal());
        elements.saveBtn?.addEventListener("click", handleSave);
        elements.confirmDeleteBtn?.addEventListener("click", handleDelete);
        elements.categoryColor?.addEventListener("input", updateColorHex);
        elements.categoryColorHex?.addEventListener("input", updateColorPicker);

        elements.tabs.forEach((tab) => {
            tab.addEventListener("click", function () {
                currentFilter = this.dataset.type;
                loadCategories(currentFilter);
            });
        });

        loadCategories("all").then(() => {
            revealPage();
        });
    };

    /**
     * Load categories from the API
     */
    const loadCategories = async (filter = "all") => {
        try {
            showLoading();

            let url = "/api/categories";
            if (filter !== "all") {
                url = `/api/categories/type/${filter}`;
            }

            const response = await fetch(url, {
                headers: {
                    Accept: "application/json",
                    "X-CSRF-TOKEN": document.querySelector(
                        'meta[name="csrf-token"]',
                    ).content,
                },
            });

            const result = await response.json();

            if (result.success) {
                renderCategories(result.data);
            } else {
                showError("Failed to load categories.");
            }
        } catch (error) {
            console.error("Error loading categories:", error);
            showError("An error occurred while loading categories.");
        }
    };

    /**
     * Render categories in the table
     */
    const renderCategories = (categories) => {
        const tbody = elements.tableBody;

        if (!categories || categories.length === 0) {
            tbody.innerHTML = "";
            elements.emptyState?.classList.remove("d-none");
            return;
        }

        elements.emptyState?.classList.add("d-none");

        let html = "";
        categories.forEach((category, index) => {
            const isDefault = category.is_default;

            const typeBadge =
                category.type === "expense"
                    ? '<span class="badge bg-danger">Expense</span>'
                    : '<span class="badge bg-success">Income</span>';

            const defaultBadge = isDefault
                ? '<span class="badge bg-info">Default</span>'
                : '<span class="badge bg-secondary">Custom</span>';

            const actions = isDefault
                ? '<span class="text-muted small">Default</span>'
                : `
                    <button class="btn btn-sm btn-outline-primary edit-category" data-id="${category.id}" title="Edit">
                        <i class="fas fa-edit"></i>
                    </button>
                    <button class="btn btn-sm btn-outline-danger delete-category" data-id="${category.id}" data-name="${category.name}" title="Delete">
                        <i class="fas fa-trash"></i>
                    </button>
                `;

            html += `
    <tr>
        <td>${index + 1}</td>
        <td class="fw-semibold">${category.name}</td>
        <td>${typeBadge}</td>
        <td>
            <span class="d-inline-block rounded" style="width: 30px; height: 30px; background-color: ${category.color || "#6C757D"}; border: 1px solid #ddd;"></span>
        </td>
        <td>${defaultBadge}</td>
        <td>${actions}</td>
    </tr>
`;
        });

        tbody.innerHTML = html;

        document.querySelectorAll(".edit-category").forEach((btn) => {
            btn.addEventListener("click", function () {
                openModal(this.dataset.id);
            });
        });

        document.querySelectorAll(".delete-category").forEach((btn) => {
            btn.addEventListener("click", function () {
                openDeleteModal(this.dataset.id, this.dataset.name);
            });
        });
    };

    /**
     * Open the category modal for add/edit
     */
    const openModal = async (id = null) => {
        elements.form.reset();
        clearErrors();
        elements.categoryId.value = "";
        elements.categoryColor.value = "#6C757D";
        elements.categoryColorHex.value = "#6C757D";

        if (id) {
            elements.modalTitle.textContent = "Edit Category";
            elements.saveBtnText.textContent = "Update Category";

            try {
                const response = await fetch(`/api/categories/${id}`, {
                    headers: {
                        Accept: "application/json",
                        "X-CSRF-TOKEN": document.querySelector(
                            'meta[name="csrf-token"]',
                        ).content,
                    },
                });

                const result = await response.json();
                if (result.success) {
                    const category = result.data;
                    elements.categoryId.value = category.id;
                    elements.categoryName.value = category.name;
                    elements.categoryType.value = category.type;
                    elements.categoryColor.value = category.color || "#6C757D";
                    elements.categoryColorHex.value =
                        category.color || "#6C757D";
                }
            } catch (error) {
                console.error("Error loading category:", error);
                showError("Failed to load category data.");
                return;
            }
        } else {
            elements.modalTitle.textContent = "Add Category";
            elements.saveBtnText.textContent = "Save Category";
        }

        categoryModal.show();
    };

    /**
     * Handle save category (add or update)
     */
    const handleSave = async () => {
        clearErrors();

        const formData = {
            name: elements.categoryName.value.trim(),
            type: elements.categoryType.value,
            color: elements.categoryColorHex.value,
        };

        const id = elements.categoryId.value;
        const isEdit = id !== "";
        const url = isEdit ? `/api/categories/${id}` : "/api/categories";
        const method = isEdit ? "PUT" : "POST";

        if (!formData.name) {
            setError("name", "Category name is required");
            return;
        }
        if (!formData.type) {
            setError("type", "Category type is required");
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
                categoryModal.hide();
                loadCategories(currentFilter);
                showSuccess(result.message || "Category saved successfully.");
            } else if (response.status === 422) {
                displayValidationErrors(result.errors);
            } else {
                showError(result.message || "Failed to save category.");
            }
        } catch (error) {
            console.error("Error saving category:", error);
            showError("An error occurred while saving the category.");
        } finally {
            setLoading(false);
        }
    };

    /**
     * Open delete confirmation modal
     */
    const openDeleteModal = (id, name) => {
        elements.deleteCategoryId.value = id;
        elements.deleteCategoryName.textContent = name;
        deleteModal.show();
    };

    /**
     * Handle delete category
     */
    const handleDelete = async () => {
        const id = elements.deleteCategoryId.value;

        setDeleteLoading(true);

        try {
            const response = await fetch(`/api/categories/${id}`, {
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
                loadCategories(currentFilter);
                showSuccess(result.message || "Category deleted successfully.");
            } else {
                showError(result.message || "Failed to delete category.");
            }
        } catch (error) {
            console.error("Error deleting category:", error);
            showError("An error occurred while deleting the category.");
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
        const input = document.querySelector(
            `#category${field.charAt(0).toUpperCase() + field.slice(1)}`,
        );
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

    const updateColorHex = () => {
        elements.categoryColorHex.value = elements.categoryColor.value;
    };

    const updateColorPicker = () => {
        const hex = elements.categoryColorHex.value;
        if (/^#[0-9A-Fa-f]{6}$/.test(hex)) {
            elements.categoryColor.value = hex;
        }
    };

    const showLoading = () => {
        elements.tableBody.innerHTML = `
            <tr>
                <td colspan="6" class="text-center py-5">
                    <div class="spinner-border" style="color: var(--magma-core);" role="status">
                        <span class="visually-hidden">Loading...</span>
                    </div>
                </td>
            </tr>
        `;
    };

    const setLoading = (loading) => {
        if (loading) {
            elements.saveBtn.disabled = true;
            elements.saveBtnText.textContent = "Saving...";
            elements.saveBtnSpinner.classList.remove("d-none");
        } else {
            elements.saveBtn.disabled = false;
            elements.saveBtnText.textContent = elements.categoryId.value
                ? "Update Category"
                : "Save Category";
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

document.addEventListener("DOMContentLoaded", Categories.init);
