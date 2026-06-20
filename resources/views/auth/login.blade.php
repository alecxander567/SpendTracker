@extends('layouts.app')

@section('title', 'Login - SpendTracker')

@push('styles')
    <link rel="stylesheet" href="{{ asset('css/auth.css') }}">
@endpush

@section('content')
    <div class="container auth-split">
        <div class="row min-vh-100 align-items-center justify-content-center py-4">

            <!-- Left: Brand / Title -->
            <div class="col-12 col-lg-6">
                <div class="auth-brand-panel">
                    <h1 class="auth-brand mb-0">SpendTracker</h1>
                    <p class="auth-tagline">
                        Track every expense, understand every peso. Log in to see where your money goes.
                    </p>
                </div>
            </div>

            <!-- Right: Login Form -->
            <div class="col-12 col-sm-9 col-md-7 col-lg-5">
                <div class="auth-card">

                    <div class="text-center text-lg-start mb-4">
                        <h2 class="h4 fw-bold mb-1">Welcome back</h2>
                        <p class="auth-subtitle mb-0">Please log in to your account</p>
                    </div>

                    <form id="loginForm" novalidate>
                        @csrf

                        <!-- Email Field -->
                        <div class="mb-3">
                            <label for="email" class="form-label fw-semibold">Email address</label>
                            <div class="input-group">
                                <span class="input-group-text">
                                    <i class="fas fa-envelope"></i>
                                </span>
                                <input type="email" class="form-control" id="email" name="email"
                                    placeholder="you@example.com" value="{{ old('email') }}" autocomplete="email" required>
                            </div>
                            <div class="invalid-feedback" id="emailError"></div>
                        </div>

                        <!-- Password Field -->
                        <div class="mb-3">
                            <label for="password" class="form-label fw-semibold">Password</label>
                            <div class="input-group">
                                <span class="input-group-text">
                                    <i class="fas fa-lock"></i>
                                </span>
                                <input type="password" class="form-control" id="password" name="password"
                                    placeholder="Enter your password" autocomplete="current-password" required>
                                <button class="btn btn-outline-secondary" type="button" id="togglePassword"
                                    aria-label="Show password">
                                    <i class="fas fa-eye"></i>
                                </button>
                            </div>
                            <div class="invalid-feedback" id="passwordError"></div>
                        </div>

                        <!-- Remember Me & Forgot Password -->
                        <div
                            class="d-flex flex-column flex-sm-row justify-content-between align-items-start align-items-sm-center gap-2 mb-4">
                            <div class="form-check">
                                <input type="checkbox" class="form-check-input" id="remember" name="remember">
                                <label class="form-check-label" for="remember">Remember me</label>
                            </div>
                            <a href="#" class="auth-link text-decoration-none">Forgot password?</a>
                        </div>

                        <!-- Submit Button -->
                        <button type="submit" class="btn btn-magma w-100" id="loginBtn">
                            <span id="btnText">Log in</span>
                            <span id="btnSpinner" class="spinner-border spinner-border-sm d-none ms-2"
                                role="status"></span>
                        </button>
                    </form>

                    <!-- Register Link -->
                    <div class="text-center mt-4">
                        <p class="auth-footer-text mb-0">
                            Don't have an account?
                            <a href="{{ route('register') }}" class="text-decoration-none">Create one here</a>
                        </p>
                    </div>

                </div>
            </div>

        </div>
    </div>

    <!-- Forgot Password Modal (Step 1: Email) -->
    <div class="modal fade" id="forgotPasswordModal" tabindex="-1" aria-labelledby="forgotPasswordModalLabel"
        aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header border-0">
                    <h5 class="modal-title fw-bold" id="forgotPasswordModalLabel">Forgot password?</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p class="auth-subtitle mb-3">Enter the email address linked to your account.</p>

                    <form id="forgotPasswordForm" novalidate>
                        <div class="mb-3">
                            <label for="forgotEmail" class="form-label fw-semibold">Email address</label>
                            <div class="input-group">
                                <span class="input-group-text">
                                    <i class="fas fa-envelope"></i>
                                </span>
                                <input type="email" class="form-control" id="forgotEmail" name="email"
                                    placeholder="you@example.com" autocomplete="email" required>
                            </div>
                            <div class="invalid-feedback" id="forgotEmailError"></div>
                        </div>

                        <div id="forgotPasswordAlert" class="alert d-none" role="alert"></div>

                        <button type="submit" class="btn btn-magma w-100" id="forgotPasswordBtn">
                            <span id="forgotBtnText">Continue</span>
                            <span id="forgotBtnSpinner" class="spinner-border spinner-border-sm d-none ms-2"
                                role="status"></span>
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Reset Password Modal (Step 2: New Password) -->
    <div class="modal fade" id="resetPasswordModal" tabindex="-1" aria-labelledby="resetPasswordModalLabel"
        aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header border-0">
                    <h5 class="modal-title fw-bold" id="resetPasswordModalLabel">Reset your password</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p class="auth-subtitle mb-3">Account verified. Choose a new password below.</p>

                    <form id="resetPasswordForm" novalidate>
                        <!-- Hidden field carries the verified email from step 1 -->
                        <input type="hidden" id="resetEmail" name="email">

                        <div class="mb-3">
                            <label for="newPassword" class="form-label fw-semibold">New password</label>
                            <div class="input-group">
                                <span class="input-group-text">
                                    <i class="fas fa-lock"></i>
                                </span>
                                <input type="password" class="form-control" id="newPassword" name="password"
                                    placeholder="At least 8 characters, letters and numbers" autocomplete="new-password"
                                    required>
                            </div>
                            <div class="invalid-feedback" id="newPasswordError"></div>
                        </div>

                        <div class="mb-3">
                            <label for="confirmPassword" class="form-label fw-semibold">Confirm new password</label>
                            <div class="input-group">
                                <span class="input-group-text">
                                    <i class="fas fa-lock"></i>
                                </span>
                                <input type="password" class="form-control" id="confirmPassword"
                                    name="password_confirmation" placeholder="Re-enter new password"
                                    autocomplete="new-password" required>
                            </div>
                            <div class="invalid-feedback" id="confirmPasswordError"></div>
                        </div>

                        <div id="resetPasswordAlert" class="alert d-none" role="alert"></div>

                        <button type="submit" class="btn btn-magma w-100" id="resetPasswordBtn">
                            <span id="resetBtnText">Reset password</span>
                            <span id="resetBtnSpinner" class="spinner-border spinner-border-sm d-none ms-2"
                                role="status"></span>
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Error Modal Component -->
    <x-error-modal id="loginErrorModal" title="Login Failed" message="Unable to log in. Please check your credentials."
        icon="exclamation-circle" iconColor="danger" buttonText="Close" buttonClass="btn-danger" />
@endsection

@push('scripts')
    <script src="{{ asset('js/login.js') }}"></script>
    <script src="{{ asset('js/forgot-password.js') }}"></script>
@endpush
