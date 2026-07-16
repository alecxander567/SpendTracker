@props(['active' => 'dashboard'])

<nav class="bottom-nav">
    <a href="{{ route('dashboard') }}" class="bottom-nav-link {{ $active === 'dashboard' ? 'active' : '' }}">
        <i class="fas fa-chart-pie"></i> Home
    </a>
    <a href="{{ route('incomes.index') }}" class="bottom-nav-link {{ $active === 'incomes' ? 'active' : '' }}">
        <i class="fas fa-arrow-up"></i> Incomes
    </a>
    <a href="{{ route('expenses.index') }}" class="bottom-nav-fab">
        <i class="fas fa-plus"></i>
    </a>
    <a href="{{ route('budgets.index') }}" class="bottom-nav-link {{ $active === 'budgets' ? 'active' : '' }}">
        <i class="fas fa-bullseye"></i> Budgets
    </a>
    <button type="button" class="bottom-nav-link border-0 bg-transparent" id="logoutBtnMobile">
        <i class="fas fa-sign-out-alt"></i> Logout
    </button>
</nav>
