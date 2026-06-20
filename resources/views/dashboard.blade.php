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
                        <div class="text-center py-5">
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

            <!-- Recent transactions -->
            <div class="row mb-4">
                <div class="col-12">
                    <div class="stat-card">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <h2 class="section-title">Recent transactions</h2>
                            <a href="{{ route('expenses.index') }}" class="section-link">See all</a>
                        </div>

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

        </div>
    </div>

    <x-bottom-nav active="dashboard" />

    <x-logout-modal />

@endsection

@push('scripts')
    <script src="{{ asset('js/dashboard.js') }}"></script>
@endpush
