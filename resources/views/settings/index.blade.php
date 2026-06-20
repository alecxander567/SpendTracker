@extends('layouts.app')

@section('title', 'Settings - SpendTracker')

@push('styles')
    <link rel="stylesheet" href="{{ asset('css/dashboard.css') }}">
    <link rel="stylesheet" href="{{ asset('css/settings.css') }}">
@endpush

@section('content')

    <x-sidebar active="settings" />

    {{-- Fullscreen loading spinner (shown until page fully loads) --}}
    <x-loading-spinner :fullscreen="true" id="pageLoader" />

    <!-- ===== Main content (hidden until loaded) ===== -->
    <div class="main-content" id="mainContent" style="display: none;">
        <div class="container-fluid px-3 px-md-4">

            <!-- Topbar -->
            <div class="topbar">
                <div>
                    <h1 class="h5 fw-bold mb-0">Settings</h1>
                    <p class="text-muted small mb-0">Manage your account preferences.</p>
                </div>
                <div class="topbar-avatar">{{ auth()->user()->initials ?? 'U' }}</div>
            </div>

            <!-- Filter Tabs -->
            <div class="mock-chart mb-4">
                <ul class="nav nav-pills gap-2" id="settingsTabs" role="tablist">
                    <li class="nav-item" role="presentation">
                        <button class="nav-link active" id="profile-tab" data-type="profile" data-bs-toggle="tab"
                            data-bs-target="#profile" type="button" role="tab">
                            <i class="fas fa-user me-1"></i> Profile
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="security-tab" data-type="security" data-bs-toggle="tab"
                            data-bs-target="#security" type="button" role="tab">
                            <i class="fas fa-shield-alt me-1"></i> Security
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="danger-tab" data-type="danger" data-bs-toggle="tab"
                            data-bs-target="#danger" type="button" role="tab">
                            <i class="fas fa-exclamation-triangle me-1"></i> Danger
                        </button>
                    </li>
                </ul>
            </div>

            <!-- Tab Content -->
            <div class="tab-content">
                <!-- Profile Tab -->
                <div class="tab-pane fade show active" id="profile" role="tabpanel">
                    <div class="row g-4">
                        <div class="col-12">
                            <div class="stat-card">
                                <h5 class="fw-bold mb-3">Profile Information</h5>
                                <form id="profileForm">
                                    @csrf
                                    <div class="row g-3">
                                        <div class="col-12 col-md-6">
                                            <label class="form-label fw-semibold">Full Name</label>
                                            <input type="text" class="form-control" id="profileName" name="name"
                                                required placeholder="Enter your full name">
                                            <div class="invalid-feedback" id="profileNameError"></div>
                                        </div>
                                        <div class="col-12 col-md-6">
                                            <label class="form-label fw-semibold">Email Address</label>
                                            <input type="email" class="form-control" id="profileEmail" name="email"
                                                required placeholder="Enter your email">
                                            <div class="invalid-feedback" id="profileEmailError"></div>
                                        </div>
                                        <div class="col-12 col-md-6">
                                            <label class="form-label fw-semibold">Currency</label>
                                            <select class="form-select" id="profileCurrency" name="currency">
                                                <option value="">Select currency</option>
                                            </select>
                                            <div class="invalid-feedback" id="profileCurrencyError"></div>
                                        </div>
                                        <div class="col-12 col-md-6">
                                            <label class="form-label fw-semibold">Time Zone</label>
                                            <select class="form-select" id="profileTimezone" name="timezone">
                                                <option value="">Select timezone</option>
                                            </select>
                                            <div class="invalid-feedback" id="profileTimezoneError"></div>
                                        </div>
                                        <div class="col-12">
                                            <button type="submit" class="btn btn-hero-primary" id="saveProfileBtn">
                                                <span id="profileBtnText">Save Profile</span>
                                                <span id="profileBtnSpinner" class="spinner-border spinner-border-sm d-none"
                                                    role="status"></span>
                                            </button>
                                        </div>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Security Tab -->
                <div class="tab-pane fade" id="security" role="tabpanel">
                    <div class="row g-4">
                        <div class="col-12">
                            <div class="stat-card">
                                <h5 class="fw-bold mb-3">Change Password</h5>
                                <form id="passwordForm">
                                    @csrf
                                    <div class="row g-3">
                                        <div class="col-12 col-md-4">
                                            <label class="form-label fw-semibold">Current Password</label>
                                            <input type="password" class="form-control" id="currentPassword"
                                                name="current_password" required placeholder="Enter current password">
                                            <div class="invalid-feedback" id="currentPasswordError"></div>
                                        </div>
                                        <div class="col-12 col-md-4">
                                            <label class="form-label fw-semibold">New Password</label>
                                            <input type="password" class="form-control" id="newPassword"
                                                name="new_password" required placeholder="Enter new password">
                                            <div class="invalid-feedback" id="newPasswordError"></div>
                                        </div>
                                        <div class="col-12 col-md-4">
                                            <label class="form-label fw-semibold">Confirm Password</label>
                                            <input type="password" class="form-control" id="newPasswordConfirmation"
                                                name="new_password_confirmation" required
                                                placeholder="Confirm new password">
                                            <div class="invalid-feedback" id="newPasswordConfirmationError"></div>
                                        </div>
                                        <div class="col-12">
                                            <button type="submit" class="btn btn-hero-primary" id="savePasswordBtn">
                                                <span id="passwordBtnText">Update Password</span>
                                                <span id="passwordBtnSpinner"
                                                    class="spinner-border spinner-border-sm d-none" role="status"></span>
                                            </button>
                                        </div>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Danger Tab -->
                <div class="tab-pane fade" id="danger" role="tabpanel">
                    <div class="row g-4">
                        <div class="col-12">
                            <div class="stat-card border-danger">
                                <h5 class="fw-bold text-danger mb-3">
                                    <i class="fas fa-exclamation-triangle me-2"></i>Danger Zone
                                </h5>
                                <p class="text-muted small">Once you delete your account, there is no going back. Please be
                                    certain.</p>
                                <button class="btn btn-danger" id="deleteAccountBtn">
                                    <i class="fas fa-trash me-1"></i> Delete Account
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </div>

    <x-bottom-nav active="settings" />
    <x-logout-modal />

    <!-- Delete Account Modal -->
    <div class="modal fade" id="deleteAccountModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title text-danger">
                        <i class="fas fa-exclamation-triangle me-2"></i>Delete Account
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p class="fw-semibold">Are you sure you want to delete your account?</p>
                    <p class="text-muted small">This action cannot be undone. All your data will be permanently removed.
                    </p>
                    <form id="deleteAccountForm">
                        @csrf
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Enter your password to confirm</label>
                            <input type="password" class="form-control" id="deletePassword" name="password" required
                                placeholder="Enter your password">
                            <div class="invalid-feedback" id="deletePasswordError"></div>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="deleteConfirm" name="confirm"
                                value="1">
                            <label class="form-check-label" for="deleteConfirm">
                                I understand this action cannot be undone
                            </label>
                            <div class="invalid-feedback" id="deleteConfirmError"></div>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-danger" id="confirmDeleteAccountBtn">
                        <span id="deleteAccountBtnText">Delete Account</span>
                        <span id="deleteAccountBtnSpinner" class="spinner-border spinner-border-sm d-none"
                            role="status"></span>
                    </button>
                </div>
            </div>
        </div>
    </div>

    {{-- Success Modal --}}
    <x-success-modal id="settingsSuccessModal" title="Success!" icon="check-circle" iconColor="success"
        buttonText="Continue" buttonClass="btn-success" />

    {{-- Error Modal --}}
    <x-error-modal id="settingsErrorModal" title="Error!" icon="exclamation-circle" iconColor="danger"
        buttonText="Close" buttonClass="btn-danger" />

@endsection

@push('scripts')
    <script src="{{ asset('js/settings.js') }}"></script>
@endpush
