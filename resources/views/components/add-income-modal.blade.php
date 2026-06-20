{{-- Add / Edit Income modal. Include once per page: <x-add-income-modal /> --}}
<div class="modal fade" id="incomeModal" tabindex="-1" aria-labelledby="incomeModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content income-modal-content">
            <div class="modal-header border-0 pb-0">
                <h5 class="modal-title fw-bold" id="incomeModalLabel">Add income</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div id="incomeFormAlert" class="alert alert-danger d-none small mb-3" role="alert"></div>

                <form id="incomeForm" novalidate>
                    <input type="hidden" id="income_id" value="">

                    <div class="mb-3">
                        <label for="income_source" class="form-label small fw-semibold">Source</label>
                        <input type="text" class="form-control" id="income_source" maxlength="100"
                            placeholder="e.g. Salary, Freelance, Gift" required>
                        <div class="invalid-feedback" data-error-for="source"></div>
                    </div>

                    <div class="row g-3">
                        <div class="col-7">
                            <label for="income_amount" class="form-label small fw-semibold">Amount</label>
                            <div class="input-group">
                                <span class="input-group-text">₱</span>
                                <input type="number" step="0.01" min="0" class="form-control"
                                    id="income_amount" placeholder="0.00" required>
                            </div>
                            <div class="invalid-feedback" data-error-for="amount"></div>
                        </div>
                        <div class="col-5">
                            <label for="income_date" class="form-label small fw-semibold">Date</label>
                            <input type="date" class="form-control" id="income_date" required>
                            <div class="invalid-feedback" data-error-for="date"></div>
                        </div>
                    </div>

                    <div class="row g-3 mt-0">
                        <div class="col-6">
                            <label for="income_category" class="form-label small fw-semibold mt-2">Category</label>
                            <select class="form-select" id="income_category">
                                <option value="">Uncategorized</option>
                            </select>
                            <div class="invalid-feedback" data-error-for="category_id"></div>
                        </div>
                        <div class="col-6">
                            <label for="income_payment_method" class="form-label small fw-semibold mt-2">Payment
                                method</label>
                            <select class="form-select" id="income_payment_method" required>
                                <option value="cash">Cash</option>
                                <option value="bank_transfer">Bank Transfer</option>
                                <option value="direct_deposit">Direct Deposit</option>
                                <option value="check">Check</option>
                                <option value="mobile_money">Mobile Money</option>
                                <option value="crypto">Cryptocurrency</option>
                                <option value="other">Other</option>
                            </select>
                            <div class="invalid-feedback" data-error-for="payment_method"></div>
                        </div>
                    </div>

                    <div class="mb-3 mt-3">
                        <label for="income_description" class="form-label small fw-semibold">Description <span
                                class="text-muted fw-normal">(optional)</span></label>
                        <textarea class="form-control" id="income_description" rows="2" maxlength="255"></textarea>
                    </div>

                    <div class="form-check form-switch mb-2">
                        <input class="form-check-input" type="checkbox" role="switch" id="income_is_recurring">
                        <label class="form-check-label small fw-semibold" for="income_is_recurring">This income
                            repeats</label>
                    </div>

                    <div class="row g-3 d-none" id="recurringFields">
                        <div class="col-7">
                            <label for="income_recurring_frequency"
                                class="form-label small fw-semibold">Frequency</label>
                            <select class="form-select" id="income_recurring_frequency">
                                <option value="daily">Daily</option>
                                <option value="weekly">Weekly</option>
                                <option value="biweekly">Bi-weekly</option>
                                <option value="monthly" selected>Monthly</option>
                                <option value="quarterly">Quarterly</option>
                                <option value="yearly">Yearly</option>
                            </select>
                            <div class="invalid-feedback" data-error-for="recurring_frequency"></div>
                        </div>
                        <div class="col-5">
                            <label for="income_recurring_end_date" class="form-label small fw-semibold">Ends <span
                                    class="text-muted fw-normal">(optional)</span></label>
                            <input type="date" class="form-control" id="income_recurring_end_date">
                            <div class="invalid-feedback" data-error-for="recurring_end_date"></div>
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer border-0 pt-0">
                <button type="button" class="btn btn-light flex-fill" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-hero-primary flex-fill" id="incomeSubmitBtn">
                    <span class="btn-label">Save income</span>
                    <span class="spinner-border spinner-border-sm d-none ms-1" role="status"
                        aria-hidden="true"></span>
                </button>
            </div>
        </div>
    </div>
</div>
