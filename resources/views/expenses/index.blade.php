@extends('layouts.app')

@section('title', 'Expenses - SpendTracker')

@push('styles')
    <link rel="stylesheet" href="{{ asset('css/dashboard.css') }}">
    <link rel="stylesheet" href="{{ asset('css/expenses.css') }}">
@endpush

@section('content')

    <x-sidebar active="expenses" />

    {{-- Fullscreen loading spinner (shown until page fully loads) --}}
    <x-loading-spinner :fullscreen="true" id="pageLoader" />

    <!-- ===== Main content (hidden until loaded) ===== -->
    <div class="main-content" id="mainContent" style="display: none;">
        <div class="container-fluid px-3 px-md-4">

            <!-- Topbar -->
            <div class="topbar">
                <div>
                    <h1 class="h5 fw-bold mb-0">Expenses</h1>
                    <p class="text-muted small mb-0">Manage your income and expenses.</p>
                </div>
                <button type="button" class="btn btn-hero-primary px-3" id="addExpenseBtn">
                    <i class="fas fa-plus me-1"></i> Add expense
                </button>
            </div>

            <!-- Filter Tabs -->
            <div class="mock-chart mb-4">
                <ul class="nav nav-pills gap-2" id="expenseTabs" role="tablist">
                    <li class="nav-item" role="presentation">
                        <button class="nav-link active" id="all-tab" data-type="all" data-bs-toggle="tab" type="button"
                            role="tab">
                            All
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

            <!-- Expenses Table -->
            <div class="row mb-4">
                <div class="col-12">
                    <div class="stat-card p-0 overflow-hidden">
                        <div class="table-responsive">
                            <table class="table table-hover align-middle mb-0" id="expensesTable">
                                <thead>
                                    <tr>
                                        <th style="width: 50px;">#</th>
                                        <th>Date</th>
                                        <th>Category</th>
                                        <th>Description</th>
                                        <th>Amount</th>
                                        <th>Payment</th>
                                        <th style="width: 150px;">Actions</th>
                                    </tr>
                                </thead>
                                <tbody id="expensesTableBody">
                                    <tr>
                                        <td colspan="7" class="text-center py-5">
                                            <x-loading-spinner />
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- Empty State (hidden by default) -->
                    <div id="emptyState" class="stat-card text-center py-5 d-none mt-3">
                        <i class="fas fa-receipt" style="font-size: 56px; color: var(--text-muted);"></i>
                        <h2 class="section-title mt-3">No transactions found</h2>
                        <p class="text-muted small mb-3">Start by adding your first expense or income.</p>
                        <button class="btn btn-hero-primary px-3" id="emptyStateAddBtn">
                            <i class="fas fa-plus me-1"></i> Add expense
                        </button>
                    </div>
                </div>
            </div>

        </div>
    </div>

    <x-bottom-nav active="expenses" />

    <x-logout-modal />

    <!-- Add/Edit Expense Modal -->
    <div class="modal fade" id="expenseModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered modal-md">
            <div class="modal-content">
                <div class="modal-header border-0 pb-0">
                    <h5 class="modal-title fw-bold" id="expenseModalTitle">Add Expense</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body pt-3">
                    <form id="expenseForm">
                        @csrf
                        <input type="hidden" id="expenseId" name="expense_id">

                        <div class="row g-3">
                            <!-- Amount -->
                            <div class="col-6">
                                <label for="expenseAmount" class="form-label fw-semibold">Amount</label>
                                <div class="input-group">
                                    <span class="input-group-text bg-light border-end-0">₱</span>
                                    <input type="number" step="0.01" min="0.01" class="form-control border-start-0"
                                        id="expenseAmount" name="amount" placeholder="0.00" required>
                                </div>
                                <div class="invalid-feedback" id="expenseAmountError"></div>
                            </div>

                            <!-- Date -->
                            <div class="col-6">
                                <label for="expenseDate" class="form-label fw-semibold">Date</label>
                                <input type="date" class="form-control" id="expenseDate" name="date" required>
                                <div class="invalid-feedback" id="expenseDateError"></div>
                            </div>

                            <!-- Category -->
                            <div class="col-12">
                                <label for="expenseCategory" class="form-label fw-semibold">Category</label>
                                <select class="form-select" id="expenseCategory" name="category_id" required>
                                    <option value="">Select a category</option>
                                </select>
                                <div class="invalid-feedback" id="expenseCategoryError"></div>
                            </div>

                            <!-- Payment Method -->
                            <div class="col-12">
                                <label class="form-label fw-semibold">Payment Method</label>
                                <div class="payment-method-grid" id="paymentMethodGrid">
                                    <label class="payment-option">
                                        <input type="radio" name="payment_method" value="cash" checked>
                                        <span>
                                            <i class="fas fa-money-bill-wave"></i>
                                            <small>Cash</small>
                                        </span>
                                    </label>
                                    <label class="payment-option">
                                        <input type="radio" name="payment_method" value="credit_card">
                                        <span>
                                            <i class="fas fa-credit-card"></i>
                                            <small>Credit Card</small>
                                        </span>
                                    </label>
                                    <label class="payment-option">
                                        <input type="radio" name="payment_method" value="debit_card">
                                        <span>
                                            <i class="fas fa-credit-card"></i>
                                            <small>Debit Card</small>
                                        </span>
                                    </label>
                                    <label class="payment-option">
                                        <input type="radio" name="payment_method" value="bank_transfer">
                                        <span>
                                            <i class="fas fa-university"></i>
                                            <small>Bank Transfer</small>
                                        </span>
                                    </label>
                                    <label class="payment-option">
                                        <input type="radio" name="payment_method" value="mobile_money">
                                        <span>
                                            <i class="fas fa-mobile-alt"></i>
                                            <small>Mobile Money</small>
                                        </span>
                                    </label>
                                </div>
                                <div class="invalid-feedback" id="expensePaymentMethodError"></div>
                            </div>

                            <!-- Description -->
                            <div class="col-12">
                                <label for="expenseDescription" class="form-label fw-semibold">
                                    Description <span class="text-muted fw-normal">(optional)</span>
                                </label>
                                <textarea class="form-control" id="expenseDescription" name="description" rows="2"
                                    placeholder="What was this for?" maxlength="1000"></textarea>
                                <div class="invalid-feedback" id="expenseDescriptionError"></div>
                            </div>

                            <!-- Recurring -->
                            <div class="col-12">
                                <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" id="expenseIsRecurring"
                                        name="is_recurring">
                                    <label class="form-check-label fw-semibold" for="expenseIsRecurring">
                                        This is a recurring expense
                                    </label>
                                </div>
                                <div class="mt-2 d-none" id="recurringFrequencyWrap">
                                    <label for="expenseRecurringFrequency" class="form-label">Repeats</label>
                                    <select class="form-select" id="expenseRecurringFrequency"
                                        name="recurring_frequency">
                                        <option value="daily">Daily</option>
                                        <option value="weekly">Weekly</option>
                                        <option value="monthly" selected>Monthly</option>
                                        <option value="yearly">Yearly</option>
                                    </select>
                                    <div class="invalid-feedback" id="expenseRecurringFrequencyError"></div>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
                <div class="modal-footer border-0 pt-0">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-hero-primary" id="saveExpenseBtn">
                        <span id="saveBtnText">Save Expense</span>
                        <span id="saveBtnSpinner" class="spinner-border spinner-border-sm d-none" role="status"></span>
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Delete Confirmation Modal -->
    <div class="modal fade" id="deleteExpenseModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered modal-sm">
            <div class="modal-content">
                <div class="modal-header border-0 pb-0">
                    <h5 class="modal-title fw-bold" style="color: var(--magma-deep);">Delete Transaction</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body text-center py-4">
                    <i class="fas fa-exclamation-triangle" style="font-size: 48px; color: var(--magma-deep);"></i>
                    <p class="mt-3 mb-0">Are you sure you want to delete this transaction?</p>
                    <p class="text-muted small">This action cannot be undone.</p>
                    <input type="hidden" id="deleteExpenseId">
                </div>
                <div class="modal-footer border-0 justify-content-center">
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
    <x-success-modal id="expenseSuccessModal" title="Success!" icon="check-circle" iconColor="success"
        buttonText="Continue" buttonClass="btn-success" />

    {{-- Error Modal --}}
    <x-error-modal id="expenseErrorModal" title="Error!" icon="exclamation-circle" iconColor="danger"
        buttonText="Close" buttonClass="btn-danger" />

    <script>
        // Fallback: Ensure page loads even if JS fails
        (function() {
            const loader = document.getElementById('pageLoader');
            const content = document.getElementById('mainContent');

            setTimeout(function() {
                if (loader && loader.style.display !== 'none') {
                    console.log('Fallback: forcing page to show');
                    loader.style.display = 'none';
                    if (content) content.style.display = 'block';
                }
            }, 3000);
        })();
    </script>

@endsection

@push('scripts')
    <script src="{{ asset('js/expenses.js') }}"></script>
@endpush
