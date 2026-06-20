@props(['active' => 'dashboard'])

<aside class="sidebar">
    <div class="sidebar-brand">SpendTrack</div>

    <nav class="sidebar-nav">
        <a href="{{ route('dashboard') }}" class="sidebar-link {{ $active === 'dashboard' ? 'active' : '' }}">
            <i class="fas fa-chart-pie"></i> Dashboard
        </a>
        <a href="{{ route('incomes.index') }}" class="sidebar-link {{ $active === 'incomes' ? 'active' : '' }}">
            <i class="fas fa-arrow-up"></i> Income
        </a>
        <a href="{{ route('expenses.index') }}" class="sidebar-link {{ $active === 'expenses' ? 'active' : '' }}">
            <i class="fas fa-receipt"></i> Expenses
        </a>
        <a href="{{ route('categories.index') }}" class="sidebar-link {{ $active === 'categories' ? 'active' : '' }}">
            <i class="fas fa-tags"></i> Categories
        </a>
        <a href="{{ route('budgets.index') }}" class="sidebar-link {{ $active === 'budgets' ? 'active' : '' }}">
            <i class="fas fa-bullseye"></i> Budgets
        </a>
        <!-- Inside the sidebar-nav div -->
        <a class="sidebar-link {{ $active === 'settings' ? 'active' : '' }}" href="{{ route('settings.index') }}">
            <i class="fas fa-cog"></i>
            <span>Settings</span>
        </a>
    </nav>

    <div class="sidebar-logout">
        <button type="button" class="sidebar-link w-100 border-0 bg-transparent text-start" id="logoutBtn">
            <i class="fas fa-sign-out-alt"></i> Logout
        </button>
    </div>
</aside>
