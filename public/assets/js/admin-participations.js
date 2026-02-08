/* ═══════════════════════════════════════════════════════════════
   ADMIN PARTICIPATIONS MANAGEMENT — JavaScript
   Form-based CRUD (no AJAX/JSON). Handles modal form switching,
   delete confirmation, search/filter, counter animations, view detail,
   conditional paiement field, client-side validation.
   ═══════════════════════════════════════════════════════════════ */

document.addEventListener('DOMContentLoaded', () => {

    /* ─── ROUTE BASES (used by JS to set form action) ────────── */
    const STORE_URL  = '/admin/participation/store';
    const UPDATE_URL = '/admin/participation';  // + /{id}/update

    /* ─── DOM References ─────────────────────────────────────── */
    const tableBody           = document.getElementById('participationsTableBody');
    const participationModal  = document.getElementById('participationModal');
    const participationForm   = document.getElementById('participationForm');
    const addParticipationBtn = document.getElementById('addParticipationBtn');
    const savePartText        = document.getElementById('saveParticipationBtnText');
    const modalTitle          = document.getElementById('participationModalLabel');
    const modalSubtitle       = document.getElementById('partModalSubtitle');
    const modalIcon           = document.getElementById('partModalIcon');
    const selectAllCb         = document.getElementById('selectAllParticipations');

    // Form fields
    const fPartId     = document.getElementById('inputPartId');
    const fEvent      = document.getElementById('inputPartEvent');
    const fDate       = document.getElementById('inputPartDate');
    const fStatus     = document.getElementById('inputPartStatus');
    const fNbr        = document.getElementById('inputPartNbr');
    const fPaiement   = document.getElementById('inputPartPaiement');
    const paiementField = document.getElementById('paiementModeField');

    // Delete alert
    const deleteOverlay   = document.getElementById('deletePartAlertOverlay');
    const deleteNameEl    = document.getElementById('deletePartName');
    const cancelDeleteBtn = document.getElementById('cancelPartDeleteBtn');
    const deleteForm      = document.getElementById('deletePartForm');

    // Bootstrap modal instance
    let bsModal = null;
    if (participationModal) {
        bsModal = new bootstrap.Modal(participationModal);
    }


    /* ─── COUNTER ANIMATION ──────────────────────────────────── */
    const counters = document.querySelectorAll('[data-counter]');
    const animateCounter = (el) => {
        const target = parseInt(el.getAttribute('data-counter'), 10);
        const duration = 1200;
        const start = performance.now();

        const tick = (now) => {
            const elapsed = now - start;
            const progress = Math.min(elapsed / duration, 1);
            const eased = 1 - Math.pow(1 - progress, 3);
            el.textContent = Math.floor(eased * target).toLocaleString();
            if (progress < 1) requestAnimationFrame(tick);
        };
        requestAnimationFrame(tick);
    };

    const counterObserver = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                animateCounter(entry.target);
                counterObserver.unobserve(entry.target);
            }
        });
    }, { threshold: 0.3 });

    counters.forEach(c => counterObserver.observe(c));


    /* ─── CONDITIONAL PAIEMENT FIELD + DATE CONSTRAINTS ────── */
    if (fEvent && paiementField) {
        fEvent.addEventListener('change', () => {
            const selectedOption = fEvent.options[fEvent.selectedIndex];
            const isPaid = selectedOption && selectedOption.getAttribute('data-paid') === '1';

            if (isPaid) {
                paiementField.style.display = 'block';
            } else {
                paiementField.style.display = 'none';
                if (fPaiement) { fPaiement.value = ''; fPaiement.classList.remove('is-invalid', 'is-valid'); }
            }

            // Update date min/max constraints
            if (fDate) {
                const today = new Date().toISOString().split('T')[0];
                fDate.setAttribute('min', today);
                const eventDate = selectedOption ? selectedOption.getAttribute('data-date') : '';
                if (eventDate) {
                    fDate.setAttribute('max', eventDate);
                } else {
                    fDate.removeAttribute('max');
                }
                // Clear date if it's now out of range
                if (fDate.value && (fDate.value < today || (eventDate && fDate.value > eventDate))) {
                    fDate.value = '';
                    fDate.classList.remove('is-valid');
                }
            }
        });
    }


    /* ─── MODAL: ADD MODE ────────────────────────────────────── */
    if (addParticipationBtn) {
        addParticipationBtn.addEventListener('click', () => {
            resetForm();
            modalTitle.textContent = 'Add Participation';
            modalSubtitle.textContent = 'Fill in the details to register a new participation';
            modalIcon.className = 'fas fa-user-plus';
            savePartText.textContent = 'Save Participation';
            fPartId.value = 'Auto-generated';

            // Set form action to STORE route
            participationForm.action = STORE_URL;

            paiementField.style.display = 'none';
        });
    }


    /* ─── MODAL: EDIT MODE ───────────────────────────────────── */
    if (tableBody) {
        tableBody.addEventListener('click', (e) => {
            const editBtn = e.target.closest('.edit-part-btn');
            if (!editBtn) return;

            const row = editBtn.closest('tr');
            if (!row) return;

            const participationId = row.getAttribute('data-participation-id');

            modalTitle.textContent = 'Edit Participation';
            modalSubtitle.textContent = 'Modify the participation details below';
            modalIcon.className = 'fas fa-pen-to-square';
            savePartText.textContent = 'Update Participation';

            // Populate form from row data attributes
            fPartId.value  = row.querySelector('.part-id')?.textContent.trim() || '';
            fEvent.value   = row.getAttribute('data-id-evenement') || '';
            fDate.value    = row.getAttribute('data-date') || '';
            fStatus.value  = row.getAttribute('data-status') || '';
            fNbr.value     = row.getAttribute('data-nbr') || '';

            // Toggle paiement field
            const isPaid = row.getAttribute('data-event-paid') === '1';
            if (isPaid) {
                paiementField.style.display = 'block';
                fPaiement.value = row.getAttribute('data-mode-paiement') || '';
            } else {
                paiementField.style.display = 'none';
                fPaiement.value = '';
            }

            // Set form action to UPDATE route
            participationForm.action = `${UPDATE_URL}/${participationId}/update`;

            // Clear validation states
            clearValidationStates();

            bsModal.show();
        });
    }


    /* ─── CLIENT-SIDE VALIDATION ─────────────────────────────── */
    if (participationForm) {
        participationForm.addEventListener('submit', (e) => {
            let hasError = false;
            clearValidationStates();

            // Événement required
            if (!fEvent.value) {
                fEvent.classList.add('is-invalid');
                hasError = true;
            }

            // Date required + must be between today and event date
            if (!fDate.value.trim()) {
                fDate.classList.add('is-invalid');
                hasError = true;
            } else {
                const today = new Date().toISOString().split('T')[0];
                if (fDate.value < today) {
                    fDate.classList.add('is-invalid');
                    hasError = true;
                } else {
                    const selectedOption = fEvent.options[fEvent.selectedIndex];
                    const eventDate = selectedOption ? selectedOption.getAttribute('data-date') : '';
                    if (eventDate && fDate.value > eventDate) {
                        fDate.classList.add('is-invalid');
                        hasError = true;
                    }
                }
            }

            // Statut required
            if (!fStatus.value) {
                fStatus.classList.add('is-invalid');
                hasError = true;
            }

            // Nbr participation required & min 1
            const nbrVal = parseInt(fNbr.value, 10);
            if (!fNbr.value.trim() || isNaN(nbrVal) || nbrVal < 1) {
                fNbr.classList.add('is-invalid');
                hasError = true;
            }

            // Mode paiement required if event is paid
            if (paiementField.style.display !== 'none' && !fPaiement.value) {
                fPaiement.classList.add('is-invalid');
                hasError = true;
            }

            if (hasError) {
                e.preventDefault();
                return;
            }

            // Form will submit normally (POST)
        });
    }


    /* ─── DELETE FLOW (form-based POST) ──────────────────────── */
    if (tableBody) {
        tableBody.addEventListener('click', (e) => {
            const deleteBtn = e.target.closest('.delete-part-btn');
            if (!deleteBtn) return;

            const partName = deleteBtn.getAttribute('data-participation-name') || 'this participation';
            const deleteUrl = deleteBtn.getAttribute('data-delete-url') || '';

            if (deleteNameEl) deleteNameEl.textContent = partName;
            if (deleteForm) deleteForm.action = deleteUrl;
            if (deleteOverlay) deleteOverlay.classList.add('show');
        });
    }

    if (cancelDeleteBtn) {
        cancelDeleteBtn.addEventListener('click', () => {
            if (deleteOverlay) deleteOverlay.classList.remove('show');
        });
    }

    // Close overlay on background click
    if (deleteOverlay) {
        deleteOverlay.addEventListener('click', (e) => {
            if (e.target === deleteOverlay) {
                deleteOverlay.classList.remove('show');
            }
        });
    }


    /* ─── VIEW PARTICIPATION DETAIL (expand row) ─────────────── */
    if (tableBody) {
        tableBody.addEventListener('click', (e) => {
            const viewBtn = e.target.closest('.view-part-btn');
            if (!viewBtn) return;

            const row = viewBtn.closest('tr');
            if (!row) return;

            // Toggle: if detail row already exists, remove it
            const nextRow = row.nextElementSibling;
            if (nextRow && nextRow.classList.contains('part-detail-row')) {
                nextRow.remove();
                return;
            }

            // Remove any other open detail rows
            document.querySelectorAll('.part-detail-row').forEach(r => r.remove());

            const partId    = row.querySelector('.part-id')?.textContent || '-';
            const eventName = row.getAttribute('data-event-name') || '-';
            const date      = row.getAttribute('data-date') || '-';
            const status    = row.getAttribute('data-status') || '-';
            const nbr       = row.getAttribute('data-nbr') || '-';
            const paiement  = row.getAttribute('data-mode-paiement') || 'Gratuit';

            const colCount = row.children.length;
            const detailTr = document.createElement('tr');
            detailTr.classList.add('part-detail-row');
            detailTr.innerHTML = `
                <td colspan="${colCount}">
                    <div class="part-detail-content">
                        <div class="part-detail-grid">
                            <div class="part-detail-item">
                                <span class="part-detail-label">ID</span>
                                <span class="part-detail-value">${escapeHtml(partId)}</span>
                            </div>
                            <div class="part-detail-item">
                                <span class="part-detail-label">Événement</span>
                                <span class="part-detail-value">${escapeHtml(eventName)}</span>
                            </div>
                            <div class="part-detail-item">
                                <span class="part-detail-label">Date</span>
                                <span class="part-detail-value">${escapeHtml(date)}</span>
                            </div>
                            <div class="part-detail-item">
                                <span class="part-detail-label">Statut</span>
                                <span class="part-detail-value">${escapeHtml(status)}</span>
                            </div>
                            <div class="part-detail-item">
                                <span class="part-detail-label">Places</span>
                                <span class="part-detail-value">${escapeHtml(nbr)}</span>
                            </div>
                            <div class="part-detail-item">
                                <span class="part-detail-label">Paiement</span>
                                <span class="part-detail-value">${escapeHtml(paiement || 'Gratuit')}</span>
                            </div>
                        </div>
                    </div>
                </td>
            `;
            row.after(detailTr);
        });
    }


    /* ─── SEARCH & FILTER: now handled server-side (PHP) ───── */
    /* The form in the template submits GET params to the controller */


    /* ─── SELECT ALL CHECKBOX ────────────────────────────────── */
    if (selectAllCb) {
        selectAllCb.addEventListener('change', () => {
            const checks = tableBody.querySelectorAll('.part-check');
            checks.forEach(cb => { cb.checked = selectAllCb.checked; });
        });
    }


    /* ─── FORM RESET ─────────────────────────────────────────── */
    function resetForm() {
        if (participationForm) participationForm.reset();
        fPartId.value = '';
        clearValidationStates();
        if (paiementField) { paiementField.style.display = 'none'; }
    }

    function clearValidationStates() {
        if (participationForm) {
            participationForm.querySelectorAll('.is-invalid, .is-valid').forEach(el => {
                el.classList.remove('is-invalid', 'is-valid');
            });
        }
    }


    /* ─── UTILITY: Escape HTML ───────────────────────────────── */
    function escapeHtml(str) {
        const div = document.createElement('div');
        div.appendChild(document.createTextNode(str));
        return div.innerHTML;
    }


    /* ─── LIVE VALIDATION (remove invalid on input) ──────────── */
    const allInputs = participationForm ? participationForm.querySelectorAll('.modal-input') : [];
    allInputs.forEach(input => {
        input.addEventListener('input', () => {
            if (input.value.trim()) {
                input.classList.remove('is-invalid');
                input.classList.add('is-valid');
            }
        });
        input.addEventListener('change', () => {
            if (input.value.trim()) {
                input.classList.remove('is-invalid');
                input.classList.add('is-valid');
            }
        });
    });


    /* ─── AUTO-DISMISS FLASH ALERTS ──────────────────────────── */
    document.querySelectorAll('.alert-dismissible').forEach(alert => {
        setTimeout(() => {
            const bsAlert = bootstrap.Alert.getOrCreateInstance(alert);
            if (bsAlert) bsAlert.close();
        }, 5000);
    });


    /* ─── Bootstrap Tooltips ─────────────────────────────────── */
    const tooltipTriggers = document.querySelectorAll('[data-bs-toggle="tooltip"]');
    tooltipTriggers.forEach(el => new bootstrap.Tooltip(el));

});
