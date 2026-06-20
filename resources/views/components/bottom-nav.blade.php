@props(['active' => 'dashboard'])

<nav class="bottom-nav">
    <a href="{{ route('dashboard') }}" class="bottom-nav-link {{ $active === 'dashboard' ? 'active' : '' }}">
        <i class="fas fa-chart-pie"></i> Home
    </a>
    <a href="{{ route('categories.index') }}" class="bottom-nav-link {{ $active === 'categories' ? 'active' : '' }}">
        <i class="fas fa-tags"></i> Categories
    </a>
    <a href="#" class="bottom-nav-fab">
        <i class="fas fa-plus"></i>
    </a>
    <a href="{{ route('budgets.index') }}" class="bottom-nav-link {{ $active === 'budgets' ? 'active' : '' }}">
        <i class="fas fa-bullseye"></i> Budgets
    </a>
    <button type="button" class="bottom-nav-link border-0 bg-transparent" id="logoutBtnMobile">
        <i class="fas fa-sign-out-alt"></i> Logout
    </button>
</nav>
