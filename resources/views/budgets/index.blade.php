@extends('layouts.app')

@section('title', 'Budgets - SpendTracker')

@push('styles')
    <link rel="stylesheet" href="{{ asset('css/dashboard.css') }}">
    <link rel="stylesheet" href="{{ asset('css/budgets.css') }}">
@endpush

@section('content')

    <x-sidebar active="budgets" />

    {{-- Fullscreen loading spinner (shown until page fully loads) --}}
    <x-loading-spinner :fullscreen="true" id="pageLoader" />

    <!-- ===== Main content (hidden until loaded) ===== -->
    <div class="main-content" id="mainContent" style="display: none;">
        <div class="container-fluid px-3 px-md-4">

            <!-- Topbar -->
            <div class="topbar">
                <div>
                    <h1 class="h5 fw-bold mb-0">Budgets</h1>
                    <p class="text-muted small mb-0">Set spending limits and track them by category.</p>
                </div>
                <button type="button" class="btn btn-hero-primary px-3" id="addBudgetBtn">
                    <i class="fas fa-plus me-1"></i> Add budget
                </button>
            </div>

            <!-- Summary cards -->
            <div class="row g-3 mb-4">
                <div class="col-12 col-sm-6 col-lg-3">
                    <div class="stat-card">
                        <div class="stat-icon bg-budget"><i class="fas fa-wallet"></i></div>
                        <div class="stat-label">Total budgeted</div>
                        <div class="stat-value" id="summaryBudgeted">₱0.00</div>
                    </div>
                </div>
                <div class="col-12 col-sm-6 col-lg-3">
                    <div class="stat-card">
                        <div class="stat-icon bg-expense"><i class="fas fa-arrow-down"></i></div>
                        <div class="stat-label">Total spent</div>
                        <div class="stat-value" id="summarySpent">₱0.00</div>
                    </div>
                </div>
                <div class="col-12 col-sm-6 col-lg-3">
                    <div class="stat-card">
                        <div class="stat-icon bg-income"><i class="fas fa-piggy-bank"></i></div>
                        <div class="stat-label">Total remaining</div>
                        <div class="stat-value" id="summaryRemaining">₱0.00</div>
                    </div>
                </div>
                <div class="col-12 col-sm-6 col-lg-3">
                    <div class="stat-card">
                        <div class="stat-icon bg-warning-soft"><i class="fas fa-exclamation-triangle"></i></div>
                        <div class="stat-label">Needs attention</div>
                        <div class="stat-value" id="summaryWarning">0</div>
                    </div>
                </div>
            </div>

            <!-- Filter Tabs -->
            <div class="mock-chart mb-4">
                <ul class="nav nav-pills gap-2" id="budgetTabs" role="tablist">
                    <li class="nav-item" role="presentation">
                        <button class="nav-link active" id="all-tab" data-filter="all" data-bs-toggle="tab" type="button"
                            role="tab">
                            All budgets
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="active-tab" data-filter="active" data-bs-toggle="tab" type="button"
                            role="tab">
                            Active
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="inactive-tab" data-filter="inactive" data-bs-toggle="tab"
                            type="button" role="tab">
                            Inactive
                        </button>
                    </li>
                </ul>
            </div>

            <!-- Budgets Grid -->
            <div class="row g-3 mb-4" id="budgetsGrid">
                <div class="col-12 text-center py-5">
                    <x-loading-spinner />
                </div>
            </div>

            <!-- Empty State (hidden by default) -->
            <div id="emptyState" class="stat-card text-center py-5 d-none">
                <i class="fas fa-wallet" style="font-size: 56px; color: var(--text-muted);"></i>
                <h2 class="section-title mt-3">No budgets found</h2>
                <p class="text-muted small mb-3">Start by setting a budget for one of your categories.</p>
                <button class="btn btn-hero-primary px-3" id="emptyStateAddBtn">
                    <i class="fas fa-plus me-1"></i> Add budget
                </button>
            </div>

        </div>
    </div>

    <x-bottom-nav active="budgets" />

    <x-logout-modal />

    <!-- Add/Edit Budget Modal -->
    <div class="modal fade" id="budgetModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="budgetModalTitle">Add Budget</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form id="budgetForm">
                        @csrf
                        <input type="hidden" id="budgetId" name="budget_id">

                        <div class="mb-3">
                            <label for="budgetCategory" class="form-label fw-semibold">Category</label>
                            <select class="form-select" id="budgetCategory" name="category_id" required>
                                <option value="">Select category</option>
                            </select>
                            <div class="invalid-feedback" id="budgetCategoryError"></div>
                        </div>

                        <div class="mb-3">
                            <label for="budgetAmount" class="form-label fw-semibold">Budget Amount</label>
                            <div class="input-group">
                                <span class="input-group-text">₱</span>
                                <input type="number" class="form-control" id="budgetAmount" name="amount"
                                    min="0.01" step="0.01" placeholder="0.00" required>
                            </div>
                            <div class="invalid-feedback" id="budgetAmountError"></div>
                        </div>

                        <div class="mb-3">
                            <label for="budgetPeriod" class="form-label fw-semibold">Period</label>
                            <select class="form-select" id="budgetPeriod" name="period" required>
                                <option value="">Select period</option>
                                <option value="weekly">Weekly</option>
                                <option value="monthly">Monthly</option>
                                <option value="yearly">Yearly</option>
                            </select>
                            <div class="invalid-feedback" id="budgetPeriodError"></div>
                        </div>

                        <div class="row">
                            <div class="col-12 col-sm-6 mb-3">
                                <label for="budgetStartDate" class="form-label fw-semibold">Start Date</label>
                                <input type="date" class="form-control" id="budgetStartDate" name="start_date"
                                    required>
                                <div class="invalid-feedback" id="budgetStartDateError"></div>
                            </div>
                            <div class="col-12 col-sm-6 mb-3">
                                <label for="budgetEndDate" class="form-label fw-semibold">End Date <span
                                        class="text-muted small fw-normal">(optional)</span></label>
                                <input type="date" class="form-control" id="budgetEndDate" name="end_date">
                                <div class="invalid-feedback" id="budgetEndDateError"></div>
                            </div>
                        </div>

                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" id="budgetIsActive" name="is_active"
                                checked>
                            <label class="form-check-label fw-semibold" for="budgetIsActive">Active</label>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-hero-primary" id="saveBudgetBtn">
                        <span id="saveBtnText">Save Budget</span>
                        <span id="saveBtnSpinner" class="spinner-border spinner-border-sm d-none" role="status"></span>
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Delete Confirmation Modal -->
    <div class="modal fade" id="deleteBudgetModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" style="color: var(--magma-deep);">Delete Budget</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body text-center py-4">
                    <i class="fas fa-exclamation-triangle" style="font-size: 48px; color: var(--magma-deep);"></i>
                    <p class="mt-3 mb-0">Are you sure you want to delete the budget for
                        <strong id="deleteBudgetName"></strong>?
                    </p>
                    <p class="text-muted small">This action cannot be undone.</p>
                    <input type="hidden" id="deleteBudgetId">
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
    <x-success-modal id="budgetSuccessModal" title="Success!" icon="check-circle" iconColor="success"
        buttonText="Continue" buttonClass="btn-success" />

    {{-- Error Modal --}}
    <x-error-modal id="budgetErrorModal" title="Error!" icon="exclamation-circle" iconColor="danger" buttonText="Close"
        buttonClass="btn-danger" />

@endsection

@push('scripts')
    <script src="{{ asset('js/budgets.js') }}"></script>
@endpush
