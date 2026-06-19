@extends('layouts.app')

@section('title', 'Categories - SpendTracker')

@push('styles')
    <link rel="stylesheet" href="{{ asset('css/dashboard.css') }}">
    <link rel="stylesheet" href="{{ asset('css/categories.css') }}">
@endpush

@section('content')

    <x-sidebar active="categories" />

    {{-- Fullscreen loading spinner (shown until page fully loads) --}}
    <x-loading-spinner :fullscreen="true" id="pageLoader" />

    <!-- ===== Main content (hidden until loaded) ===== -->
    <div class="main-content" id="mainContent" style="display: none;">
        <div class="container-fluid px-3 px-md-4">

            <!-- Topbar -->
            <div class="topbar">
                <div>
                    <h1 class="h5 fw-bold mb-0">Categories</h1>
                    <p class="text-muted small mb-0">Manage your income and expense categories.</p>
                </div>
                <button type="button" class="btn btn-hero-primary px-3" id="addCategoryBtn">
                    <i class="fas fa-plus me-1"></i> Add category
                </button>
            </div>

            <!-- Filter Tabs -->
            <div class="mock-chart mb-4">
                <ul class="nav nav-pills gap-2" id="categoryTabs" role="tablist">
                    <li class="nav-item" role="presentation">
                        <button class="nav-link active" id="all-tab" data-type="all" data-bs-toggle="tab" type="button"
                            role="tab">
                            All categories
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="expense-tab" data-type="expense" data-bs-toggle="tab" type="button"
                            role="tab">
                            Expense
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="income-tab" data-type="income" data-bs-toggle="tab" type="button"
                            role="tab">
                            Income
                        </button>
                    </li>
                </ul>
            </div>

            <!-- Categories Table -->
            <div class="row mb-4">
                <div class="col-12">
                    <div class="stat-card p-0 overflow-hidden">
                        <div class="table-responsive">
                            <table class="table table-hover align-middle mb-0" id="categoriesTable">
                                <thead>
                                    <tr>
                                        <th style="width: 50px;">#</th>
                                        <th>Name</th>
                                        <th style="width: 120px;">Type</th>
                                        <th style="width: 120px;">Color</th>
                                        <th style="width: 100px;">Default</th>
                                        <th style="width: 150px;">Actions</th>
                                    </tr>
                                </thead>
                                <tbody id="categoriesTableBody">
                                    <tr>
                                        <td colspan="6" class="text-center py-5">
                                            <x-loading-spinner />
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- Empty State (hidden by default) -->
                    <div id="emptyState" class="stat-card text-center py-5 d-none mt-3">
                        <i class="fas fa-tags" style="font-size: 56px; color: var(--text-muted);"></i>
                        <h2 class="section-title mt-3">No categories found</h2>
                        <p class="text-muted small mb-3">Start by adding your first category.</p>
                        <button class="btn btn-hero-primary px-3" id="emptyStateAddBtn">
                            <i class="fas fa-plus me-1"></i> Add category
                        </button>
                    </div>
                </div>
            </div>

        </div>
    </div>

    <x-bottom-nav active="categories" />

    <x-logout-modal />

    <!-- Add/Edit Category Modal -->
    <div class="modal fade" id="categoryModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="categoryModalTitle">Add Category</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form id="categoryForm">
                        @csrf
                        <input type="hidden" id="categoryId" name="category_id">

                        <div class="mb-3">
                            <label for="categoryName" class="form-label fw-semibold">Category Name</label>
                            <input type="text" class="form-control" id="categoryName" name="name"
                                placeholder="Enter category name" required>
                            <div class="invalid-feedback" id="categoryNameError"></div>
                        </div>

                        <div class="mb-3">
                            <label for="categoryType" class="form-label fw-semibold">Type</label>
                            <select class="form-select" id="categoryType" name="type" required>
                                <option value="">Select type</option>
                                <option value="expense">Expense</option>
                                <option value="income">Income</option>
                            </select>
                            <div class="invalid-feedback" id="categoryTypeError"></div>
                        </div>

                        <div class="mb-3">
                            <label for="categoryColor" class="form-label fw-semibold">Color</label>
                            <div class="input-group">
                                <input type="color" class="form-control form-control-color" id="categoryColor"
                                    name="color" value="#ff4d1c" style="width: 60px; padding: 3px;">
                                <input type="text" class="form-control" id="categoryColorHex" placeholder="#ff4d1c"
                                    value="#ff4d1c">
                            </div>
                            <div class="invalid-feedback" id="categoryColorError"></div>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-hero-primary" id="saveCategoryBtn">
                        <span id="saveBtnText">Save Category</span>
                        <span id="saveBtnSpinner" class="spinner-border spinner-border-sm d-none" role="status"></span>
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Delete Confirmation Modal -->
    <div class="modal fade" id="deleteCategoryModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" style="color: var(--magma-deep);">Delete Category</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body text-center py-4">
                    <i class="fas fa-exclamation-triangle" style="font-size: 48px; color: var(--magma-deep);"></i>
                    <p class="mt-3 mb-0">Are you sure you want to delete <strong id="deleteCategoryName"></strong>?</p>
                    <p class="text-muted small">This action cannot be undone.</p>
                    <input type="hidden" id="deleteCategoryId">
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-danger" id="confirmDeleteBtn">
                        <span id="deleteBtnText">Delete</span>
                        <span id="deleteBtnSpinner" class="spinner-border spinner-border-sm d-none"
                            role="status"></span>
                    </button>
                </div>
            </div>
        </div>
    </div>

    {{-- Success Modal --}}
    <x-success-modal id="categorySuccessModal" title="Success!" icon="check-circle" iconColor="success"
        buttonText="Continue" buttonClass="btn-success" />

    {{-- Error Modal --}}
    <x-error-modal id="categoryErrorModal" title="Error!" icon="exclamation-circle" iconColor="danger"
        buttonText="Close" buttonClass="btn-danger" />

@endsection

@push('scripts')
    <script src="{{ asset('js/categories.js') }}"></script>
@endpush
