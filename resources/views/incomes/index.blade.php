@extends('layouts.app')

@section('title', 'Income - SpendTracker')

@push('styles')
    <link rel="stylesheet" href="{{ asset('css/dashboard.css') }}">
    <link rel="stylesheet" href="{{ asset('css/income.css') }}">
@endpush

@section('content')

    <x-sidebar active="incomes" />

    <x-loading-spinner :fullscreen="true" id="pageLoader" />

    <div class="main-content" id="mainContent" style="display: none;">
        <div class="container-fluid px-3 px-md-4">

            <!-- Topbar -->
            <div class="topbar">
                <div>
                    <h1 class="h5 fw-bold mb-0">Income</h1>
                    <p class="text-muted small mb-0">Track every peso coming in.</p>
                </div>
                <button type="button" class="btn btn-hero-primary px-3" id="addIncomeBtn">
                    <i class="fas fa-plus me-1"></i> Add income
                </button>
            </div>

            <!-- Summary cards -->
            <div class="row g-3 mb-4">
                <div class="col-12 col-sm-4">
                    <div class="stat-card">
                        <div class="stat-icon bg-income"><i class="fas fa-arrow-up"></i></div>
                        <div class="stat-label">This month</div>
                        <div class="stat-value" id="summaryMonthTotal">₱0.00</div>
                        <div class="stat-trend up text-muted" id="summaryMonthEntries"><i class="fas fa-minus"></i> No data
                            yet</div>
                    </div>
                </div>
                <div class="col-12 col-sm-4">
                    <div class="stat-card">
                        <div class="stat-icon bg-budget"><i class="fas fa-rotate"></i></div>
                        <div class="stat-label">Recurring sources</div>
                        <div class="stat-value" id="summaryRecurringCount">0</div>
                        <div class="stat-trend up text-muted" id="summaryRecurringTotal">₱0.00 / mo</div>
                    </div>
                </div>
                <div class="col-12 col-sm-4">
                    <div class="stat-card">
                        <div class="stat-icon bg-expense"><i class="fas fa-tag"></i></div>
                        <div class="stat-label">Top source</div>
                        <div class="stat-value" id="summaryTopSource">—</div>
                        <div class="stat-trend up text-muted" id="summaryTopSourceAmount">₱0.00</div>
                    </div>
                </div>
            </div>

            <!-- Filters -->
            <div class="stat-card mb-3">
                <div class="row g-2 align-items-end">
                    <div class="col-6 col-md-3">
                        <label class="form-label small fw-semibold mb-1">Source</label>
                        <input type="text" class="form-control form-control-sm" id="filterSource"
                            placeholder="Search source...">
                    </div>
                    <div class="col-6 col-md-3">
                        <label class="form-label small fw-semibold mb-1">Payment method</label>
                        <select class="form-select form-select-sm" id="filterPaymentMethod">
                            <option value="">All methods</option>
                            <option value="cash">Cash</option>
                            <option value="bank_transfer">Bank Transfer</option>
                            <option value="direct_deposit">Direct Deposit</option>
                            <option value="check">Check</option>
                            <option value="mobile_money">Mobile Money</option>
                            <option value="crypto">Cryptocurrency</option>
                            <option value="other">Other</option>
                        </select>
                    </div>
                    <div class="col-6 col-md-2">
                        <label class="form-label small fw-semibold mb-1">Status</label>
                        <select class="form-select form-select-sm" id="filterActive">
                            <option value="">All</option>
                            <option value="1">Active</option>
                            <option value="0">Inactive</option>
                        </select>
                    </div>
                    <div class="col-6 col-md-2">
                        <label class="form-label small fw-semibold mb-1">Recurring</label>
                        <select class="form-select form-select-sm" id="filterRecurring">
                            <option value="">All</option>
                            <option value="1">Recurring</option>
                            <option value="0">One-time</option>
                        </select>
                    </div>
                    <div class="col-12 col-md-2 d-flex gap-2">
                        <button type="button" class="btn btn-sm btn-light flex-fill" id="clearFiltersBtn">Clear</button>
                        <a href="{{ url('/api/incomes/export') }}" class="btn btn-sm btn-outline-secondary flex-fill"
                            title="Export CSV">
                            <i class="fas fa-download"></i>
                        </a>
                    </div>
                </div>
            </div>

            <!-- Income list -->
            <div class="stat-card">
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <h2 class="section-title">All income</h2>
                    <span class="text-muted small" id="resultCount"></span>
                </div>

                <div id="incomeListLoading" class="text-center py-5">
                    <div class="spinner-border" style="color: var(--magma-core);" role="status">
                        <span class="visually-hidden">Loading...</span>
                    </div>
                </div>

                <div id="incomeListEmpty" class="text-center py-5 d-none">
                    <i class="fas fa-receipt" style="font-size: 40px; color: var(--text-muted);"></i>
                    <p class="text-muted small mt-3 mb-0">No income entries match your filters.</p>
                </div>

                <div id="incomeList" class="d-none"></div>

                <nav class="d-flex justify-content-center mt-3 d-none" id="paginationNav">
                    <ul class="pagination pagination-sm mb-0" id="paginationLinks"></ul>
                </nav>
            </div>

        </div>
    </div>

    <x-bottom-nav active="incomes" />
    <x-logout-modal />
    <x-add-income-modal />

    <!-- Delete confirmation modal -->
    <div class="modal fade" id="deleteIncomeModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-sm">
            <div class="modal-content">
                <div class="modal-body text-center pt-4">
                    <i class="fas fa-triangle-exclamation mb-2" style="font-size: 28px; color: var(--magma-deep);"></i>
                    <p class="mb-0 small">Delete this income entry? This can't be undone.</p>
                </div>
                <div class="modal-footer border-0 pt-0">
                    <button type="button" class="btn btn-light flex-fill" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn flex-fill text-white" style="background: var(--magma-deep);"
                        id="confirmDeleteBtn">Delete</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Result modals -->
    <x-success-modal id="incomeSuccessModal" title="Success!" message="Done." button-text="Continue"
        button-class="btn-success" />
    <x-error-modal id="incomeErrorModal" title="Error!" message="Something went wrong." button-text="Close"
        button-class="btn-danger" />

@endsection

@push('scripts')
    <script src="{{ asset('js/income-modal.js') }}"></script>
    <script src="{{ asset('js/income-index.js') }}"></script>
@endpush
