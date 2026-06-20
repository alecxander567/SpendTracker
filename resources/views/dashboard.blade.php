@extends('layouts.app')

@section('title', 'Dashboard - SpendTracker')

@push('styles')
    <link rel="stylesheet" href="{{ asset('css/dashboard.css') }}">
@endpush

@section('content')

    <x-sidebar active="dashboard" />

    {{-- Fullscreen loading spinner (shown until page fully loads) --}}
    <x-loading-spinner :fullscreen="true" id="pageLoader" />

    <!-- ===== Main content (hidden until loaded) ===== -->
    <div class="main-content" id="mainContent" style="display: none;">
        <div class="container-fluid px-3 px-md-4">

            <!-- Topbar -->
            <div class="topbar">
                <div>
                    <h1 class="h5 fw-bold mb-0" id="greetingText">{{ $greeting }},
                        {{ explode(' ', auth()->user()->name)[0] }} 👋</h1>
                    <p class="text-muted small mb-0">Here's what's happening with your money.</p>
                </div>
                <div class="topbar-avatar">{{ $userInitials }}</div>
            </div>

            <!-- Hero balance card -->
            <div class="hero-card mb-4">
                <div class="row align-items-center">
                    <div class="col-12 col-md-7">
                        <div class="hero-eyebrow mb-2">Net cash flow</div>
                        <div class="hero-balance mb-1" id="heroBalanceValue">₱0.00</div>
                        <p class="hero-sub mb-3 mb-md-0" id="heroBalanceSub">Calculating this month's cash flow&hellip;</p>
                    </div>
                    <div class="col-12 col-md-5 mt-3 mt-md-0">
                        <div class="d-flex gap-2 justify-content-md-end">
                            <a href="{{ route('incomes.index') }}" class="btn btn-hero flex-fill flex-md-grow-0 px-3">
                                <i class="fas fa-arrow-up me-1"></i> Add income
                            </a>
                            <a href="{{ route('expenses.index') }}"
                                class="btn btn-hero-primary flex-fill flex-md-grow-0 px-3">
                                <i class="fas fa-plus me-1"></i> Add expense
                            </a>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Stat cards -->
            <div class="row g-3 mb-4">
                <div class="col-12 col-sm-4">
                    <div class="stat-card">
                        <div class="stat-icon bg-budget"><i class="fas fa-wallet"></i></div>
                        <div class="stat-label">Total budget</div>
                        <div class="stat-value" id="totalBudgetValue">₱0.00</div>
                        <div class="stat-trend up" id="totalBudgetTrend"><i class="fas fa-arrow-up"></i> 0% used</div>
                    </div>
                </div>
                <div class="col-12 col-sm-4">
                    <div class="stat-card">
                        <div class="stat-icon bg-income"><i class="fas fa-arrow-up"></i></div>
                        <div class="stat-label">Income this month</div>
                        <div class="stat-value" id="incomeMonthValue">₱0.00</div>
                        <div class="stat-trend up text-muted" id="incomeMonthTrend"><i class="fas fa-minus"></i> No data yet
                        </div>
                    </div>
                </div>
                <div class="col-12 col-sm-4">
                    <div class="stat-card">
                        <div class="stat-icon bg-expense"><i class="fas fa-arrow-down"></i></div>
                        <div class="stat-label">Expenses this month</div>
                        <div class="stat-value" id="expenseMonthValue">₱0.00</div>
                        <div class="stat-trend down text-muted" id="expenseMonthTrend"><i class="fas fa-minus"></i> No data
                            yet</div>
                    </div>
                </div>
            </div>

            <div class="row g-3 mb-4">
                <!-- Weekly spend chart -->
                <div class="col-12 col-lg-7">
                    <div class="mock-chart h-100">
                        <div class="d-flex justify-content-between align-items-center">
                            <h2 class="section-title">Spending this week</h2>
                            <a href="#" class="section-link">View report</a>
                        </div>

                        {{-- dashboard.js populates this with the day-by-day bar chart --}}
                        <div id="weeklySpendChart"></div>

                        {{-- dashboard.js shows this by default and hides it once chart data loads --}}
                        <div id="weeklySpendEmpty" class="text-center py-5">
                            <i class="fas fa-chart-bar" style="font-size: 40px; color: var(--text-muted);"></i>
                            <p class="text-muted small mt-3 mb-0">No transaction data yet. Once you start logging
                                expenses, your weekly spend will show up here.</p>
                        </div>
                    </div>
                </div>

                <!-- Top categories -->
                <div class="col-12 col-lg-5">
                    <div class="mock-chart h-100">
                        <h2 class="section-title mb-3">Top categories</h2>
                        <div id="topCategoriesList">
                            <div class="text-center py-4">
                                <div class="spinner-border spinner-border-sm" style="color: var(--magma-core);"
                                    role="status">
                                    <span class="visually-hidden">Loading...</span>
                                </div>
                            </div>
                        </div>
                        <div id="topCategoriesEmpty" class="text-center py-4 d-none">
                            <i class="fas fa-tags" style="font-size: 32px; color: var(--text-muted);"></i>
                            <p class="text-muted small mt-2 mb-0">No active budgets yet. Set a budget to see your top
                                categories here.</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Recent Transactions & Savings Goals (70/30 split) -->
            <div class="row g-3 mb-4">
                <!-- Recent Transactions - 70% -->
                <div class="col-12 col-lg-8">
                    <div class="stat-card">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <h2 class="section-title">Recent transactions</h2>
                            <a href="{{ route('expenses.index') }}" class="section-link">See all</a>
                        </div>

                        <div class="scrollable-content" id="recentTransactionsContainer">
                            <div id="recentTransactionsList">
                                <div class="text-center py-4">
                                    <div class="spinner-border spinner-border-sm" style="color: var(--magma-core);"
                                        role="status">
                                        <span class="visually-hidden">Loading...</span>
                                    </div>
                                </div>
                            </div>
                            <div id="recentTransactionsEmpty" class="text-center py-5 d-none">
                                <i class="fas fa-receipt" style="font-size: 40px; color: var(--text-muted);"></i>
                                <p class="text-muted small mt-3 mb-0">No transactions recorded yet.</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Savings Goals (Wishlist) - 30% -->
                <div class="col-12 col-lg-4">
                    <div class="stat-card">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <h2 class="section-title">Wishlist</h2>
                            <button type="button" class="btn btn-sm btn-hero-primary" id="addGoalBtn">
                                <i class="fas fa-plus"></i>
                            </button>
                        </div>

                        <div class="scrollable-content" id="wishlistContainer">
                            <div id="savingsGoalsList">
                                <div class="text-center py-4">
                                    <div class="spinner-border spinner-border-sm" style="color: var(--magma-core);"
                                        role="status">
                                        <span class="visually-hidden">Loading...</span>
                                    </div>
                                </div>
                            </div>
                            <div id="savingsGoalsEmpty" class="text-center py-4 d-none">
                                <i class="fas fa-shopping-cart" style="font-size: 32px; color: var(--text-muted);"></i>
                                <p class="text-muted small mt-2 mb-0">No items in your wishlist.</p>
                                <button class="btn btn-sm btn-hero-primary mt-2" id="emptyStateAddBtn">
                                    <i class="fas fa-plus me-1"></i> Add Item
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </div>

    <x-bottom-nav active="dashboard" />

    <x-logout-modal />

    <!-- Add/Edit Savings Goal Modal -->
    <div class="modal fade" id="savingsModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="savingsModalTitle">New Savings Goal</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form id="savingsForm">
                        @csrf
                        <input type="hidden" id="goalId" name="goal_id">

                        <!-- Row 1: Goal Name (full width) -->
                        <div class="mb-3">
                            <label for="goalName" class="form-label fw-semibold">Item Name</label>
                            <input type="text" class="form-control" id="goalName" name="name"
                                placeholder="e.g., New iPhone, Vacation, Emergency Fund" required>
                            <div class="invalid-feedback" id="goalNameError"></div>
                        </div>

                        <!-- Row 2: Target Amount -->
                        <div class="mb-3">
                            <label for="goalTargetAmount" class="form-label fw-semibold">Price</label>
                            <div class="input-group">
                                <span class="input-group-text">{{ auth()->user()->getCurrencySymbol() }}</span>
                                <input type="number" class="form-control" id="goalTargetAmount" name="target_amount"
                                    min="0.01" step="0.01" placeholder="0.00" required>
                            </div>
                            <div class="invalid-feedback" id="goalTargetAmountError"></div>
                        </div>

                        <!-- Row 3: Target Date + Category (side by side) -->
                        <div class="row g-3 mb-3">
                            <div class="col-12 col-sm-6">
                                <label for="goalTargetDate" class="form-label fw-semibold">Target Date</label>
                                <input type="date" class="form-control" id="goalTargetDate" name="target_date"
                                    required>
                                <div class="invalid-feedback" id="goalTargetDateError"></div>
                            </div>
                            <div class="col-12 col-sm-6">
                                <label for="goalCategory" class="form-label fw-semibold">Category</label>
                                <select class="form-select" id="goalCategory" name="category" required>
                                    <option value="">Select category</option>
                                    <option value="emergency">🛡️ Emergency</option>
                                    <option value="vacation">🏖️ Vacation</option>
                                    <option value="education">🎓 Education</option>
                                    <option value="home">🏠 Home</option>
                                    <option value="vehicle">🚗 Vehicle</option>
                                    <option value="retirement">🚀 Retirement</option>
                                    <option value="other">📦 Other</option>
                                </select>
                                <div class="invalid-feedback" id="goalCategoryError"></div>
                            </div>
                        </div>

                        <!-- Row 4: Priority (full width) -->
                        <div class="mb-3">
                            <label for="goalPriority" class="form-label fw-semibold">Priority</label>
                            <div class="d-flex gap-3">
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="priority_radio"
                                        id="priorityLow" value="low">
                                    <label class="form-check-label" for="priorityLow">
                                        <span class="badge bg-info">Low</span>
                                    </label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="priority_radio"
                                        id="priorityMedium" value="medium" checked>
                                    <label class="form-check-label" for="priorityMedium">
                                        <span class="badge bg-warning">Medium</span>
                                    </label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="priority_radio"
                                        id="priorityHigh" value="high">
                                    <label class="form-check-label" for="priorityHigh">
                                        <span class="badge bg-danger">High</span>
                                    </label>
                                </div>
                            </div>
                            <input type="hidden" id="goalPriority" name="priority" value="medium">
                            <div class="invalid-feedback" id="goalPriorityError"></div>
                        </div>

                        <!-- Row 5: Description (full width) -->
                        <div class="mb-3">
                            <label for="goalDescription" class="form-label fw-semibold">Description <span
                                    class="text-muted fw-normal">(optional)</span></label>
                            <textarea class="form-control" id="goalDescription" name="description" rows="2"
                                placeholder="Add some details about this goal..."></textarea>
                            <div class="invalid-feedback" id="goalDescriptionError"></div>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-hero-primary" id="saveGoalBtn">
                        <span id="saveBtnText">Create Goal</span>
                        <span id="saveBtnSpinner" class="spinner-border spinner-border-sm d-none" role="status"></span>
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Delete Confirmation Modal -->
    <div class="modal fade" id="deleteGoalModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title text-danger">Delete Item</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body text-center py-4">
                    <i class="fas fa-exclamation-triangle" style="font-size: 48px; color: var(--magma-deep);"></i>
                    <p class="mt-3 mb-0">Are you sure you want to delete <strong id="deleteGoalName"></strong>?</p>
                    <p class="text-muted small">This action cannot be undone.</p>
                    <input type="hidden" id="deleteGoalId">
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-danger" id="confirmDeleteGoalBtn">
                        <span id="deleteBtnText">Delete</span>
                        <span id="deleteBtnSpinner" class="spinner-border spinner-border-sm d-none"
                            role="status"></span>
                    </button>
                </div>
            </div>
        </div>
    </div>

    {{-- Success Modal --}}
    <x-success-modal id="savingsSuccessModal" title="Success!" icon="check-circle" iconColor="success"
        buttonText="Continue" buttonClass="btn-success" />

    {{-- Error Modal --}}
    <x-error-modal id="savingsErrorModal" title="Error!" icon="exclamation-circle" iconColor="danger"
        buttonText="Close" buttonClass="btn-danger" />

@endsection

@push('scripts')
    <script src="{{ asset('js/dashboard.js') }}"></script>
@endpush
