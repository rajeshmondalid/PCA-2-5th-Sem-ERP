/**
 * StaffPulse - Employee Management System
 * Front-end Scripts & UI Interactions
 */

document.addEventListener('DOMContentLoaded', function () {
    // 1. Mobile Sidebar Toggle
    const sidebar = document.getElementById('appSidebar');
    const sidebarToggleBtn = document.getElementById('sidebarToggleBtn');
    const sidebarCloseBtn = document.getElementById('sidebarCloseBtn');
    const sidebarOverlay = document.getElementById('sidebarOverlay');

    function toggleSidebar() {
        if (sidebar && sidebarOverlay) {
            sidebar.classList.toggle('show');
            sidebarOverlay.classList.toggle('show');
        }
    }

    if (sidebarToggleBtn) sidebarToggleBtn.addEventListener('click', toggleSidebar);
    if (sidebarCloseBtn) sidebarCloseBtn.addEventListener('click', toggleSidebar);
    if (sidebarOverlay) sidebarOverlay.addEventListener('click', toggleSidebar);

    // 2. Generic Delete Confirmation Modal Hook
    const deleteModalEl = document.getElementById('deleteConfirmModal');
    if (deleteModalEl) {
        deleteModalEl.addEventListener('show.bs.modal', function (event) {
            const button = event.relatedTarget;
            const targetId = button.getAttribute('data-id');
            const targetName = button.getAttribute('data-name');
            const targetAction = button.getAttribute('data-action');

            const modalTargetName = deleteModalEl.querySelector('#deleteTargetName');
            const modalTargetId = deleteModalEl.querySelector('#deleteTargetId');
            const modalForm = deleteModalEl.querySelector('#deleteConfirmForm');

            if (modalTargetName) modalTargetName.textContent = targetName || 'this record';
            if (modalTargetId) modalTargetId.value = targetId || '';
            if (modalForm && targetAction) modalForm.action = targetAction;
        });
    }

    // 3. Auto-dismiss Flash Alerts after 5 seconds
    const alerts = document.querySelectorAll('.alert-dismissible');
    alerts.forEach(function (alert) {
        setTimeout(function () {
            const bsAlert = bootstrap.Alert.getOrCreateInstance(alert);
            if (bsAlert) bsAlert.close();
        }, 5000);
    });

    // 4. Password Visibility Toggle
    const togglePasswordBtn = document.getElementById('togglePasswordBtn');
    const passwordInput = document.getElementById('passwordInput');
    if (togglePasswordBtn && passwordInput) {
        togglePasswordBtn.addEventListener('click', function () {
            const type = passwordInput.getAttribute('type') === 'password' ? 'text' : 'password';
            passwordInput.setAttribute('type', type);
            const icon = togglePasswordBtn.querySelector('i');
            if (icon) {
                icon.classList.toggle('bi-eye');
                icon.classList.toggle('bi-eye-slash');
            }
        });
    }
});
