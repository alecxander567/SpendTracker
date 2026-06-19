@extends('layouts.app')

@section('title', 'Register - SpendTracker')

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
                        Create your account and start tracking every peso, the moment it leaves your wallet.
                    </p>
                </div>
            </div>

            <!-- Right: Register Form -->
            <div class="col-12 col-sm-9 col-md-7 col-lg-5">
                <div class="auth-card">

                    <div class="text-center text-lg-start mb-4">
                        <h2 class="h4 fw-bold mb-1">Create your account</h2>
                        <p class="auth-subtitle mb-0">It only takes a minute</p>
                    </div>

                    <form id="registerForm" novalidate>
                        @csrf

                        <!-- Name Field -->
                        <div class="mb-3">
                            <label for="name" class="form-label fw-semibold">Full name</label>
                            <div class="input-group">
                                <span class="input-group-text">
                                    <i class="fas fa-user"></i>
                                </span>
                                <input type="text" class="form-control" id="name" name="name"
                                    placeholder="Enter your full name" autocomplete="name" required>
                            </div>
                            <div class="invalid-feedback" id="nameError"></div>
                        </div>

                        <!-- Email Field -->
                        <div class="mb-3">
                            <label for="email" class="form-label fw-semibold">Email address</label>
                            <div class="input-group">
                                <span class="input-group-text">
                                    <i class="fas fa-envelope"></i>
                                </span>
                                <input type="email" class="form-control" id="email" name="email"
                                    placeholder="you@example.com" autocomplete="email" required>
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
                                    placeholder="Enter your password" autocomplete="new-password" required>
                                <button class="btn btn-outline-secondary" type="button" id="togglePassword"
                                    aria-label="Show password">
                                    <i class="fas fa-eye"></i>
                                </button>
                            </div>
                            <div class="invalid-feedback" id="passwordError"></div>
                            <div class="form-text">
                                Must be at least 8 characters with letters and numbers.
                            </div>
                        </div>

                        <!-- Confirm Password Field -->
                        <div class="mb-3">
                            <label for="password_confirmation" class="form-label fw-semibold">Confirm password</label>
                            <div class="input-group">
                                <span class="input-group-text">
                                    <i class="fas fa-check"></i>
                                </span>
                                <input type="password" class="form-control" id="password_confirmation"
                                    name="password_confirmation" placeholder="Confirm your password"
                                    autocomplete="new-password" required>
                            </div>
                            <div class="invalid-feedback" id="passwordConfirmationError"></div>
                        </div>

                        <!-- Currency & Timezone: stack on mobile, side by side from sm -->
                        <div class="row">
                            <div class="col-12 col-sm-6 mb-3">
                                <label for="currency" class="form-label fw-semibold">Currency</label>
                                <select class="form-select" id="currency" name="currency">
                                    <option value="USD">$ USD - US Dollar</option>
                                    <option value="EUR">€ EUR - Euro</option>
                                    <option value="GBP">£ GBP - British Pound</option>
                                    <option value="JPY">¥ JPY - Japanese Yen</option>
                                    <option value="PHP" selected>₱ PHP - Philippine Peso</option>
                                    <option value="AUD">A$ AUD - Australian Dollar</option>
                                    <option value="CAD">C$ CAD - Canadian Dollar</option>
                                </select>
                            </div>
                            <div class="col-12 col-sm-6 mb-3">
                                <label for="timezone" class="form-label fw-semibold">Timezone</label>
                                <select class="form-select" id="timezone" name="timezone">
                                    <option value="UTC">UTC</option>
                                    <option value="Asia/Manila" selected>Asia/Manila</option>
                                    <option value="Asia/Singapore">Asia/Singapore</option>
                                    <option value="Asia/Tokyo">Asia/Tokyo</option>
                                    <option value="America/New_York">America/New_York</option>
                                    <option value="America/Los_Angeles">America/Los_Angeles</option>
                                    <option value="Europe/London">Europe/London</option>
                                    <option value="Europe/Paris">Europe/Paris</option>
                                    <option value="Australia/Sydney">Australia/Sydney</option>
                                </select>
                            </div>
                        </div>

                        <!-- Submit Button -->
                        <button type="submit" class="btn btn-magma w-100 mt-2" id="registerBtn">
                            <span id="btnText">Create account</span>
                            <span id="btnSpinner" class="spinner-border spinner-border-sm d-none ms-2"
                                role="status"></span>
                        </button>
                    </form>

                    <!-- Login Link -->
                    <div class="text-center mt-4">
                        <p class="auth-footer-text mb-0">
                            Already have an account?
                            <a href="{{ route('login') }}" class="text-decoration-none">Log in</a>
                        </p>
                    </div>

                </div>
            </div>

        </div>
    </div>

    <!-- Success Modal Component -->
    <x-success-modal id="registrationSuccessModal" title="Registration Successful!"
        message="Your account has been created successfully. You can now log in to start tracking your expenses."
        icon="check-circle" iconColor="success" buttonText="Go to Login" buttonClass="btn-success"
        redirectUrl="{{ route('login') }}?registered=success" />

    <!-- Error Modal Component -->
    <x-error-modal id="registrationErrorModal" title="Registration Failed"
        message="Unable to create your account. Please check the errors below." icon="exclamation-circle"
        iconColor="danger" buttonText="Close" buttonClass="btn-danger" />
@endsection

@push('scripts')
    <script src="{{ asset('js/auth.js') }}"></script>
@endpush
