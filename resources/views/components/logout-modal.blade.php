{{--
    Logout confirmation modal + the JS that wires it up to #logoutBtn
    and #logoutBtnMobile (from the sidebar and bottom-nav components).

    Usage: <x-logout-modal />
    Include it once on any page that also includes <x-sidebar /> and/or
    <x-bottom-nav />.
--}}
<div class="modal fade" id="logoutConfirmModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Confirm Logout</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body text-center py-4">
                <i class="fas fa-question-circle text-warning" style="font-size: 48px;"></i>
                <p class="mt-3 mb-0">Are you sure you want to logout?</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-danger" id="confirmLogoutBtn">
                    <span id="logoutText">Yes, Logout</span>
                    <span id="logoutSpinner" class="spinner-border spinner-border-sm d-none" role="status"></span>
                </button>
            </div>
        </div>
    </div>
</div>

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const logoutBtn = document.getElementById('logoutBtn');
            const logoutBtnMobile = document.getElementById('logoutBtnMobile');
            const confirmLogoutBtn = document.getElementById('confirmLogoutBtn');
            const logoutText = document.getElementById('logoutText');
            const logoutSpinner = document.getElementById('logoutSpinner');

            function openLogoutModal() {
                const modal = new bootstrap.Modal(document.getElementById('logoutConfirmModal'));
                modal.show();
            }

            if (logoutBtn) logoutBtn.addEventListener('click', openLogoutModal);
            if (logoutBtnMobile) logoutBtnMobile.addEventListener('click', openLogoutModal);

            if (confirmLogoutBtn) {
                confirmLogoutBtn.addEventListener('click', function() {
                    confirmLogoutBtn.disabled = true;
                    logoutText.textContent = 'Logging out...';
                    logoutSpinner.classList.remove('d-none');

                    const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute(
                        'content');

                    fetch('/logout', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': csrfToken,
                                'Accept': 'application/json',
                            },
                        })
                        .then(response => response.json())
                        .then(data => {
                            if (data.success) {
                                window.location.href = data.redirect || '/login';
                            } else {
                                alert('Logout failed. Please try again.');
                                confirmLogoutBtn.disabled = false;
                                logoutText.textContent = 'Yes, Logout';
                                logoutSpinner.classList.add('d-none');
                            }
                        })
                        .catch(error => {
                            console.error('Logout error:', error);
                            alert('An error occurred during logout. Please try again.');
                            confirmLogoutBtn.disabled = false;
                            logoutText.textContent = 'Yes, Logout';
                            logoutSpinner.classList.add('d-none');
                        });
                });
            }
        });
    </script>
@endpush
