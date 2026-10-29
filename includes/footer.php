<?php
/**
 * Global Footer Component
 * Employee Management System
 */
?>
        <!-- Main Content Ends -->
        <footer class="footer mt-auto py-3 bg-white border-top text-center text-muted small">
            <div class="container-fluid">
                <span>&copy; <?= date('Y') ?> <?= APP_NAME ?>. Professional Workforce & Employee Management System.</span>
            </div>
        </footer>
    </div> <!-- .main-content -->
</div> <!-- .wrapper -->

<!-- Global Delete Confirmation Modal -->
<div class="modal fade" id="deleteConfirmModal" tabindex="-1" aria-labelledby="deleteModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title d-flex align-items-center gap-2" id="deleteModalLabel">
                    <i class="bi bi-exclamation-octagon-fill"></i> Confirm Deletion
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="deleteConfirmForm" method="POST" action="">
                <div class="modal-body py-4">
                    <p class="mb-2">Are you sure you want to permanently delete <strong id="deleteTargetName">this record</strong>?</p>
                    <p class="text-danger small mb-0"><i class="bi bi-info-circle"></i> This action is irreversible.</p>
                    <input type="hidden" name="csrf_token" value="<?= generateCsrfToken(); ?>">
                    <input type="hidden" name="id" id="deleteTargetId" value="">
                </div>
                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-danger">Confirm Delete</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Bootstrap 5.3.3 JS Bundle -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>

<!-- Custom Application Scripts -->
<script src="<?= BASE_URL ?>/assets/js/app.js"></script>
</body>
</html>
