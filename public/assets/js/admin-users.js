/**
 * ═══════════════════════════════════════════════════════════════
 *  Tun'Arche – Admin Users Management JavaScript
 *  Handles:
 *    – Add / Edit user modal logic
 *    – Form validation & submission
 *    – Custom animated delete confirmation
 *    – Bootstrap Toast notifications
 *    – Table search & role filtering
 *    – Select-all checkbox logic
 *    – Password visibility toggle
 *    – Counter animations for stat cards
 * ═══════════════════════════════════════════════════════════════
 */

document.addEventListener('DOMContentLoaded', () => {

    /* ══════════════════════════════════════════════════════════
       ELEMENT REFERENCES
    ══════════════════════════════════════════════════════════ */
    const userModal         = document.getElementById('userModal');
    const userForm          = document.getElementById('userForm');
    const modalLabel        = document.getElementById('userModalLabel');
    const modalHeaderIcon   = document.querySelector('.modal-header-icon i');
    const modalSubtitle     = document.querySelector('.modal-subtitle');
    const saveBtn           = document.getElementById('saveUserBtn');
    const saveBtnText       = document.getElementById('saveUserBtnText');
    const addUserBtn        = document.getElementById('addUserBtn');

    /* Delete alert elements */
    const deleteOverlay     = document.getElementById('deleteAlertOverlay');
    const deleteUserName    = document.getElementById('deleteUserName');
    const confirmDeleteBtn  = document.getElementById('confirmDeleteBtn');
    const cancelDeleteBtn   = document.getElementById('cancelDeleteBtn');

    /* Table elements */
    const usersTableBody    = document.getElementById('usersTableBody');
    const userSearch        = document.getElementById('userSearch');
    const roleFilter        = document.getElementById('roleFilter');
    const selectAllCheckbox = document.getElementById('selectAllUsers');

    /* Form fields */
    const inputIdUser       = document.getElementById('inputIdUser');
    const inputNom          = document.getElementById('inputNom');
    const inputPrenom       = document.getElementById('inputPrenom');
    const inputEmail        = document.getElementById('inputEmail');
    const inputPassword     = document.getElementById('inputPassword');
    const inputTelephone    = document.getElementById('inputTelephone');
    const inputRole         = document.getElementById('inputRole');
    const formUserId        = document.getElementById('formUserId');

    /* State */
    let currentDeleteRow    = null;
    let isEditMode          = false;
    let nextUserId          = 8; // placeholder auto-increment


    /* ══════════════════════════════════════════════════════════
       TOAST NOTIFICATION SYSTEM
    ══════════════════════════════════════════════════════════ */

    /**
     * Show a Bootstrap toast notification with animation.
     * @param {'success'|'error'|'warning'} type
     * @param {string} message
     */
    function showToast(type, message) {
        const toastMap = {
            success: { el: document.getElementById('toastSuccess'), msg: document.getElementById('toastSuccessMsg') },
            error:   { el: document.getElementById('toastError'),   msg: document.getElementById('toastErrorMsg') },
            warning: { el: document.getElementById('toastWarning'), msg: document.getElementById('toastWarningMsg') }
        };

        const toast = toastMap[type];
        if (!toast) return;

        /* Set message */
        toast.msg.textContent = message;

        /* Reset progress bar animation */
        const progressBar = toast.el.querySelector('.toast-progress-bar');
        if (progressBar) {
            progressBar.style.animation = 'none';
            progressBar.offsetHeight; /* trigger reflow */
            progressBar.style.animation = '';
        }

        /* Show toast using Bootstrap API */
        const bsToast = new bootstrap.Toast(toast.el);
        bsToast.show();
    }


    /* ══════════════════════════════════════════════════════════
       MODAL – Open for ADD (reset form)
    ══════════════════════════════════════════════════════════ */
    if (addUserBtn) {
        addUserBtn.addEventListener('click', () => {
            isEditMode = false;
            resetForm();

            /* Set modal header for "Add" mode */
            modalLabel.textContent = 'Add New User';
            modalSubtitle.textContent = 'Fill in the details to create a new user account';
            modalHeaderIcon.className = 'fas fa-user-plus';
            saveBtnText.textContent = 'Save User';

            /* Auto-generate next ID */
            inputIdUser.value = '#' + String(nextUserId).padStart(3, '0');
        });
    }


    /* ══════════════════════════════════════════════════════════
       MODAL – Open for EDIT (pre-fill form)
    ══════════════════════════════════════════════════════════ */
    document.addEventListener('click', (e) => {
        const editBtn = e.target.closest('.edit-btn');
        if (!editBtn) return;

        isEditMode = true;
        resetForm();

        const row = editBtn.closest('tr');
        const userId = editBtn.dataset.userId;

        /* Set modal header for "Edit" mode */
        modalLabel.textContent = 'Edit User';
        modalSubtitle.textContent = 'Modify user details and save changes';
        modalHeaderIcon.className = 'fas fa-pen-to-square';
        saveBtnText.textContent = 'Update User';

        /* Pre-fill form from table row data */
        formUserId.value = userId;
        inputIdUser.value = row.querySelector('.user-id').textContent;
        inputNom.value = row.querySelector('.table-user-name').textContent;
        inputPrenom.value = row.children[3].textContent.trim();
        inputEmail.value = row.querySelector('.user-email').textContent.trim();
        inputTelephone.value = row.querySelector('.user-phone').textContent.trim();
        inputPassword.value = '••••••••';
        inputPassword.required = false;

        /* Set role */
        const roleBadge = row.querySelector('.role-badge');
        if (roleBadge.classList.contains('role-admin'))       inputRole.value = 'admin';
        if (roleBadge.classList.contains('role-artiste'))      inputRole.value = 'artiste';
        if (roleBadge.classList.contains('role-participant'))   inputRole.value = 'participant';

        /* Open the modal */
        const modal = new bootstrap.Modal(userModal);
        modal.show();
    });


    /* ══════════════════════════════════════════════════════════
       MODAL – Save / Update User
    ══════════════════════════════════════════════════════════ */
    if (saveBtn) {
        saveBtn.addEventListener('click', () => {

            /* Validate form */
            if (!validateForm()) return;

            /* Disable button with spinner */
            saveBtn.disabled = true;
            const originalText = saveBtnText.textContent;
            saveBtnText.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> Saving…';

            /* Simulate async operation */
            setTimeout(() => {
                if (isEditMode) {
                    /* ── UPDATE existing row ── */
                    const row = usersTableBody.querySelector(`tr[data-user-id="${formUserId.value}"]`);
                    if (row) {
                        row.querySelector('.table-user-name').textContent = inputNom.value;
                        row.children[3].textContent = inputPrenom.value;
                        row.querySelector('.user-email').innerHTML = `<i class="fas fa-envelope me-1 text-muted"></i>${inputEmail.value}`;
                        row.querySelector('.user-phone').innerHTML = `<i class="fas fa-phone me-1 text-muted"></i>${inputTelephone.value}`;
                        updateRoleBadge(row, inputRole.value);
                        row.dataset.role = inputRole.value;

                        /* Update avatar */
                        const avatarName = `${inputNom.value}+${inputPrenom.value}`;
                        const colors = { admin: 'c9a84c', artiste: '7c5cbf', participant: '2ec4b6' };
                        const textColors = { admin: '0a0a12', artiste: 'fff', participant: 'fff' };
                        row.querySelector('.table-user-avatar').src =
                            `https://ui-avatars.com/api/?name=${avatarName}&background=${colors[inputRole.value]}&color=${textColors[inputRole.value]}&bold=true&size=36`;

                        /* Flash row to indicate update */
                        row.style.transition = 'background 0.6s ease';
                        row.style.background = 'rgba(201, 168, 76, 0.15)';
                        setTimeout(() => { row.style.background = ''; }, 1200);
                    }

                    showToast('success', `User "${inputPrenom.value} ${inputNom.value}" updated successfully!`);

                } else {
                    /* ── ADD new row ── */
                    const newRow = createUserRow(nextUserId, inputNom.value, inputPrenom.value, inputEmail.value, inputTelephone.value, inputRole.value);
                    usersTableBody.insertAdjacentHTML('beforeend', newRow);

                    /* Animate new row entrance */
                    const addedRow = usersTableBody.lastElementChild;
                    addedRow.style.animation = 'fadeInUp 0.4s ease-out';

                    /* Reinitialize tooltips for new row */
                    addedRow.querySelectorAll('[data-bs-toggle="tooltip"]').forEach(el => new bootstrap.Tooltip(el));

                    nextUserId++;
                    showToast('success', `User "${inputPrenom.value} ${inputNom.value}" added successfully!`);
                }

                /* Close modal & reset */
                const modal = bootstrap.Modal.getInstance(userModal);
                modal.hide();
                saveBtn.disabled = false;
                saveBtnText.textContent = originalText;

            }, 800);
        });
    }


    /* ══════════════════════════════════════════════════════════
       DELETE – Show Custom Alert
    ══════════════════════════════════════════════════════════ */
    document.addEventListener('click', (e) => {
        const deleteBtn = e.target.closest('.delete-btn');
        if (!deleteBtn) return;

        currentDeleteRow = deleteBtn.closest('tr');
        deleteUserName.textContent = deleteBtn.dataset.userName || 'this user';

        /* Show custom alert with animation */
        deleteOverlay.classList.add('active');
        document.body.style.overflow = 'hidden';
    });

    /* Cancel delete */
    if (cancelDeleteBtn) {
        cancelDeleteBtn.addEventListener('click', closeDeleteAlert);
    }

    /* Close on overlay click */
    if (deleteOverlay) {
        deleteOverlay.addEventListener('click', (e) => {
            if (e.target === deleteOverlay) closeDeleteAlert();
        });
    }

    /* Close on ESC */
    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape' && deleteOverlay.classList.contains('active')) {
            closeDeleteAlert();
        }
    });

    /* Confirm delete */
    if (confirmDeleteBtn) {
        confirmDeleteBtn.addEventListener('click', () => {
            if (!currentDeleteRow) return;

            const userName = deleteUserName.textContent;

            /* Animate row removal */
            currentDeleteRow.classList.add('removing');

            setTimeout(() => {
                currentDeleteRow.remove();
                currentDeleteRow = null;
                closeDeleteAlert();
                showToast('success', `User "${userName}" deleted successfully!`);
            }, 400);
        });
    }

    function closeDeleteAlert() {
        deleteOverlay.classList.remove('active');
        document.body.style.overflow = '';
        currentDeleteRow = null;
    }


    /* ══════════════════════════════════════════════════════════
       TABLE SEARCH – Filter by text
    ══════════════════════════════════════════════════════════ */
    if (userSearch) {
        userSearch.addEventListener('input', filterTable);
    }

    if (roleFilter) {
        roleFilter.addEventListener('change', filterTable);
    }

    function filterTable() {
        const searchTerm = userSearch.value.toLowerCase().trim();
        const roleValue = roleFilter.value.toLowerCase();
        const rows = usersTableBody.querySelectorAll('tr[data-user-id]');

        let visibleCount = 0;

        rows.forEach(row => {
            const text = row.textContent.toLowerCase();
            const rowRole = row.dataset.role;

            const matchSearch = !searchTerm || text.includes(searchTerm);
            const matchRole = !roleValue || rowRole === roleValue;

            if (matchSearch && matchRole) {
                row.style.display = '';
                visibleCount++;
            } else {
                row.style.display = 'none';
            }
        });

        /* Update footer count */
        const footerInfo = document.querySelector('.table-footer-info');
        if (footerInfo) {
            footerInfo.innerHTML = `Showing <strong>${visibleCount}</strong> of <strong>${rows.length}</strong> users`;
        }
    }


    /* ══════════════════════════════════════════════════════════
       SELECT ALL CHECKBOX
    ══════════════════════════════════════════════════════════ */
    if (selectAllCheckbox) {
        selectAllCheckbox.addEventListener('change', () => {
            const checkboxes = usersTableBody.querySelectorAll('.user-check');
            checkboxes.forEach(cb => {
                cb.checked = selectAllCheckbox.checked;
                cb.closest('tr').classList.toggle('selected', selectAllCheckbox.checked);
            });
        });

        /* Update "select all" when individual checkboxes change */
        usersTableBody.addEventListener('change', (e) => {
            if (!e.target.classList.contains('user-check')) return;
            e.target.closest('tr').classList.toggle('selected', e.target.checked);

            const all = usersTableBody.querySelectorAll('.user-check');
            const checked = usersTableBody.querySelectorAll('.user-check:checked');
            selectAllCheckbox.checked = all.length === checked.length;
            selectAllCheckbox.indeterminate = checked.length > 0 && checked.length < all.length;
        });
    }


    /* ══════════════════════════════════════════════════════════
       PASSWORD VISIBILITY TOGGLE
    ══════════════════════════════════════════════════════════ */
    const togglePasswordBtn = document.getElementById('togglePassword');
    if (togglePasswordBtn) {
        togglePasswordBtn.addEventListener('click', () => {
            const isPassword = inputPassword.type === 'password';
            inputPassword.type = isPassword ? 'text' : 'password';
            togglePasswordBtn.querySelector('i').className = isPassword ? 'fas fa-eye-slash' : 'fas fa-eye';
        });
    }


    /* ══════════════════════════════════════════════════════════
       VIEW USER – Expand/Collapse Detail Row
    ══════════════════════════════════════════════════════════ */
    document.addEventListener('click', (e) => {
        const viewBtn = e.target.closest('.view-btn');
        if (!viewBtn) return;

        const row = viewBtn.closest('tr');
        const userId = viewBtn.dataset.userId;
        const existingDetail = document.getElementById(`detail-${userId}`);

        /* Toggle off if already open */
        if (existingDetail) {
            existingDetail.classList.remove('visible');
            setTimeout(() => existingDetail.remove(), 300);
            return;
        }

        /* Close any other open detail rows */
        document.querySelectorAll('.user-detail-row').forEach(dr => dr.remove());

        /* Build detail row */
        const nom = row.querySelector('.table-user-name').textContent;
        const prenom = row.children[3].textContent.trim();
        const email = row.querySelector('.user-email').textContent.trim();
        const phone = row.querySelector('.user-phone').textContent.trim();
        const roleEl = row.querySelector('.role-badge');
        const role = roleEl.textContent.trim();

        const detailHTML = `
            <tr class="user-detail-row visible" id="detail-${userId}">
                <td colspan="8" class="user-detail-cell">
                    <div class="user-detail-grid">
                        <div class="detail-item">
                            <label><i class="fas fa-hashtag me-1"></i>User ID</label>
                            <span>${row.querySelector('.user-id').textContent}</span>
                        </div>
                        <div class="detail-item">
                            <label><i class="fas fa-user me-1"></i>Full Name</label>
                            <span>${prenom} ${nom}</span>
                        </div>
                        <div class="detail-item">
                            <label><i class="fas fa-envelope me-1"></i>Email</label>
                            <span>${email}</span>
                        </div>
                        <div class="detail-item">
                            <label><i class="fas fa-phone me-1"></i>Phone</label>
                            <span>${phone}</span>
                        </div>
                        <div class="detail-item">
                            <label><i class="fas fa-user-tag me-1"></i>Role</label>
                            <span>${role}</span>
                        </div>
                        <div class="detail-item">
                            <label><i class="fas fa-calendar me-1"></i>Joined</label>
                            <span>Feb 01, 2026</span>
                        </div>
                    </div>
                </td>
            </tr>`;

        row.insertAdjacentHTML('afterend', detailHTML);
    });


    /* ══════════════════════════════════════════════════════════
       COUNTER ANIMATIONS (User Stat Cards)
    ══════════════════════════════════════════════════════════ */
    const counterElements = document.querySelectorAll('[data-counter]');
    counterElements.forEach(el => {
        const target = parseInt(el.getAttribute('data-counter'), 10);
        const duration = 1200;
        const startTime = performance.now();

        const animate = (now) => {
            const elapsed = now - startTime;
            const progress = Math.min(elapsed / duration, 1);
            const eased = 1 - Math.pow(1 - progress, 3);
            el.textContent = Math.floor(eased * target).toLocaleString();
            if (progress < 1) requestAnimationFrame(animate);
            else el.textContent = target.toLocaleString();
        };

        requestAnimationFrame(animate);
    });


    /* ══════════════════════════════════════════════════════════
       HELPER FUNCTIONS
    ══════════════════════════════════════════════════════════ */

    /**
     * Reset all form fields and validation states.
     */
    function resetForm() {
        userForm.reset();
        userForm.classList.remove('was-validated');
        formUserId.value = '';
        inputPassword.required = true;
        inputPassword.type = 'password';

        /* Clear custom validation classes */
        userForm.querySelectorAll('.modal-input').forEach(input => {
            input.classList.remove('is-invalid', 'is-valid');
        });

        /* Reset password toggle icon */
        const toggleIcon = document.querySelector('#togglePassword i');
        if (toggleIcon) toggleIcon.className = 'fas fa-eye';
    }

    /**
     * Validate form fields with custom visual feedback.
     * @returns {boolean}
     */
    function validateForm() {
        let isValid = true;
        const requiredFields = userForm.querySelectorAll('[required]');

        requiredFields.forEach(field => {
            field.classList.remove('is-invalid', 'is-valid');

            if (!field.value || field.value.trim() === '' || (field.tagName === 'SELECT' && !field.value)) {
                field.classList.add('is-invalid');
                isValid = false;
            } else {
                /* Additional validation for email */
                if (field.type === 'email' && !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(field.value)) {
                    field.classList.add('is-invalid');
                    isValid = false;
                }
                /* Min-length for password (only in add mode) */
                else if (field.id === 'inputPassword' && !isEditMode && field.value.length < 6) {
                    field.classList.add('is-invalid');
                    isValid = false;
                }
                else {
                    field.classList.add('is-valid');
                }
            }
        });

        if (!isValid) {
            showToast('error', 'Please fill in all required fields correctly.');
        }

        return isValid;
    }

    /**
     * Update role badge in a table row.
     */
    function updateRoleBadge(row, role) {
        const badgeEl = row.querySelector('.role-badge');
        const roleConfig = {
            admin:       { class: 'role-admin',       icon: 'fa-shield-halved', label: 'Admin' },
            artiste:     { class: 'role-artiste',      icon: 'fa-palette',       label: 'Artiste' },
            participant: { class: 'role-participant',   icon: 'fa-user',          label: 'Participant' }
        };

        const cfg = roleConfig[role];
        badgeEl.className = `role-badge ${cfg.class}`;
        badgeEl.innerHTML = `<i class="fas ${cfg.icon} me-1"></i>${cfg.label}`;
    }

    /**
     * Create HTML for a new user table row.
     */
    function createUserRow(id, nom, prenom, email, telephone, role) {
        const colors = { admin: 'c9a84c', artiste: '7c5cbf', participant: '2ec4b6' };
        const textColors = { admin: '0a0a12', artiste: 'fff', participant: 'fff' };
        const roleConfig = {
            admin:       { class: 'role-admin',       icon: 'fa-shield-halved', label: 'Admin' },
            artiste:     { class: 'role-artiste',      icon: 'fa-palette',       label: 'Artiste' },
            participant: { class: 'role-participant',   icon: 'fa-user',          label: 'Participant' }
        };

        const cfg = roleConfig[role];
        const idStr = '#' + String(id).padStart(3, '0');
        const avatarUrl = `https://ui-avatars.com/api/?name=${nom}+${prenom}&background=${colors[role]}&color=${textColors[role]}&bold=true&size=36`;

        return `
        <tr data-user-id="${id}" data-role="${role}">
            <td><input type="checkbox" class="form-check-input user-check"></td>
            <td class="user-id">${idStr}</td>
            <td>
                <div class="table-user">
                    <img src="${avatarUrl}" class="table-user-avatar" alt="Avatar">
                    <span class="table-user-name">${nom}</span>
                </div>
            </td>
            <td>${prenom}</td>
            <td>
                <span class="user-email">
                    <i class="fas fa-envelope me-1 text-muted"></i>${email}
                </span>
            </td>
            <td><span class="user-phone"><i class="fas fa-phone me-1 text-muted"></i>${telephone}</span></td>
            <td><span class="role-badge ${cfg.class}"><i class="fas ${cfg.icon} me-1"></i>${cfg.label}</span></td>
            <td>
                <div class="table-actions">
                    <button class="table-action-btn view-btn" title="View" data-bs-toggle="tooltip" data-user-id="${id}"><i class="fas fa-eye"></i></button>
                    <button class="table-action-btn edit-btn" title="Edit" data-bs-toggle="tooltip" data-user-id="${id}"><i class="fas fa-pen-to-square"></i></button>
                    <button class="table-action-btn delete delete-btn" title="Delete" data-bs-toggle="tooltip" data-user-id="${id}" data-user-name="${prenom} ${nom}"><i class="fas fa-trash-can"></i></button>
                </div>
            </td>
        </tr>`;
    }

});
