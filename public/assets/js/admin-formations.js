/* ═══════════════════════════════════════════════════════════════
   FORMATIONS MANAGEMENT - JAVASCRIPT
   – Counter animations, modal handling, form validation
   – CRUD operations (Add, Edit, Delete)
   – Search & filter functionality
   – Toast notifications
═══════════════════════════════════════════════════════════════ */

document.addEventListener('DOMContentLoaded', function() {

    // ───────────────────────────────────────────────────────────
    // COUNTER ANIMATION
    // ───────────────────────────────────────────────────────────
    const counterElements = document.querySelectorAll('[data-counter]');
    counterElements.forEach(el => {
        const target = parseInt(el.getAttribute('data-counter'));
        const duration = 1500;
        const increment = target / (duration / 16);
        let current = 0;

        const updateCounter = () => {
            current += increment;
            if (current < target) {
                el.textContent = Math.floor(current).toLocaleString();
                requestAnimationFrame(updateCounter);
            } else {
                el.textContent = target.toLocaleString();
            }
        };

        updateCounter();
    });

    // ───────────────────────────────────────────────────────────
    // MODAL HANDLING
    // ───────────────────────────────────────────────────────────
    const modal = document.getElementById('formationModal');
    const modalLabel = document.getElementById('formationModalLabel');
    const modalSubtitle = document.getElementById('formationModalSubtitle');
    const modalIcon = document.getElementById('formationModalIcon');
    const saveBtnText = document.getElementById('saveFormationBtnText');
    const formationForm = document.getElementById('formationForm');

    // Form fields
    const fFormationId = document.getElementById('formFormationId');
    const fInputId = document.getElementById('inputFormationId');
    const fNomForm = document.getElementById('inputNomForm');
    const fDateForm = document.getElementById('inputDateForm');
    const fType = document.getElementById('inputType');
    const fDescription = document.getElementById('inputDescription');

    // Initialize Bootstrap tooltips
    const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });

    // ───────────────────────────────────────────────────────────
    // ADD FORMATION BUTTON
    // ───────────────────────────────────────────────────────────
    document.getElementById('addFormationBtn').addEventListener('click', function() {
        resetForm();
        modalLabel.textContent = 'Add Formation';
        modalSubtitle.textContent = 'Fill in the details to create a new formation';
        saveBtnText.textContent = 'Add Formation';
        modalIcon.className = 'fas fa-graduation-cap';
        fFormationId.value = '';
        fInputId.value = 'Auto-generated';
    });

    // ───────────────────────────────────────────────────────────
    // EDIT FORMATION BUTTON
    // ───────────────────────────────────────────────────────────
    document.addEventListener('click', function(e) {
        if (e.target.closest('.edit-formation-btn')) {
            const btn = e.target.closest('.edit-formation-btn');
            const formationId = btn.getAttribute('data-formation-id');
            const row = document.querySelector(`tr[data-formation-id="${formationId}"]`);

            if (row) {
                resetForm();
                modalLabel.textContent = 'Edit Formation';
                modalSubtitle.textContent = 'Update the formation details';
                saveBtnText.textContent = 'Save Changes';
                modalIcon.className = 'fas fa-pen-to-square';

                // Populate form
                fFormationId.value = formationId;
                fInputId.value = row.querySelector('.form-id')?.textContent || '';
                fNomForm.value = row.querySelector('.form-nom')?.textContent || '';

                // Date
                const dateText = row.querySelector('.form-date')?.textContent.trim() || '';
                // Extract date from text (format: "2026-03-15")
                const dateMatch = dateText.match(/\d{4}-\d{2}-\d{2}/);
                if (dateMatch) {
                    fDateForm.value = dateMatch[0];
                }

                // Type
                const typeBadge = row.querySelector('.type-badge');
                if (typeBadge) {
                    const typeText = typeBadge.textContent.trim().toLowerCase();
                    fType.value = typeText;
                }

                // Description
                fDescription.value = row.querySelector('.form-description')?.textContent.trim() || '';

                // Show modal
                const bsModal = new bootstrap.Modal(modal);
                bsModal.show();
            }
        }
    });

    // ───────────────────────────────────────────────────────────
    // SAVE FORMATION (ADD OR UPDATE)
    // ───────────────────────────────────────────────────────────
    document.getElementById('saveFormationBtn').addEventListener('click', function() {
        // Validate form
        if (!formationForm.checkValidity()) {
            formationForm.classList.add('was-validated');
            showToast('warning', 'Please fill in all required fields correctly.');
            return;
        }

        const formationId = fFormationId.value;
        const nomForm = fNomForm.value.trim();
        const dateForm = fDateForm.value;
        const type = fType.value;
        const description = fDescription.value.trim();

        // Get text values for display
        const typeText = fType.options[fType.selectedIndex].text;

        if (formationId) {
            // Update existing formation
            updateFormationRow(formationId, nomForm, dateForm, type, typeText, description);
            showToast('success', `Formation "${nomForm}" updated successfully!`);
        } else {
            // Add new formation
            addNewFormation(nomForm, dateForm, type, typeText, description);
            showToast('success', `Formation "${nomForm}" added successfully!`);

            // Update counter
            const counterEl = document.querySelector('.form-stat-card .form-stat-value');
            if (counterEl) {
                const current = parseInt(counterEl.textContent.replace(/,/g, ''));
                counterEl.textContent = (current + 1).toLocaleString();
            }
        }

        // Close modal
        const bsModal = bootstrap.Modal.getInstance(modal);
        bsModal.hide();
        resetForm();
    });

    // ───────────────────────────────────────────────────────────
    // ADD NEW FORMATION ROW
    // ───────────────────────────────────────────────────────────
    function addNewFormation(nomForm, dateForm, type, typeText, description) {
        const tableBody = document.getElementById('formationTableBody');
        const newId = tableBody.querySelectorAll('tr').length + 1;

        // Type class
        const typeClass = `type-${type}`;

        // Icons based on type
        const icons = {
            'beginner': 'fa-graduation-cap',
            'intermediate': 'fa-palette',
            'advanced': 'fa-medal',
            'workshop': 'fa-hands',
            'masterclass': 'fa-star'
        };
        const iconClass = icons[type] || 'fa-graduation-cap';

        const tr = document.createElement('tr');
        tr.setAttribute('data-formation-id', newId);
        tr.setAttribute('data-type', type);
        tr.innerHTML = `
            <td><input type="checkbox" class="form-check-input formation-check"></td>
            <td class="form-id">${fInputId.value || newId}</td>
            <td>
                <div class="form-nom-cell">
                    <div class="form-icon-avatar ${type}">
                        <i class="fas ${iconClass}"></i>
                    </div>
                    <span class="form-nom">${escapeHtml(nomForm)}</span>
                </div>
            </td>
            <td><span class="form-date"><i class="fas fa-calendar me-1"></i>${dateForm}</span></td>
            <td><span class="type-badge ${typeClass}">${typeText}</span></td>
            <td><span class="form-description">${escapeHtml(description)}</span></td>
            <td>
                <div class="table-actions">
                    <button class="table-action-btn view-formation-btn" title="View" data-bs-toggle="tooltip" data-formation-id="${newId}"><i class="fas fa-eye"></i></button>
                    <button class="table-action-btn edit-formation-btn" title="Edit" data-bs-toggle="tooltip" data-formation-id="${newId}"><i class="fas fa-pen-to-square"></i></button>
                    <button class="table-action-btn delete delete-formation-btn" title="Delete" data-bs-toggle="tooltip" data-formation-id="${newId}" data-formation-nom="${escapeHtml(nomForm)}"><i class="fas fa-trash-can"></i></button>
                </div>
            </td>
        `;

        tableBody.insertBefore(tr, tableBody.firstChild);
        tr.classList.add('animate-in');

        // Reinitialize tooltips
        const newTooltips = tr.querySelectorAll('[data-bs-toggle="tooltip"]');
        newTooltips.forEach(el => new bootstrap.Tooltip(el));

        updateTableFooter();
    }

    // ───────────────────────────────────────────────────────────
    // UPDATE FORMATION ROW
    // ───────────────────────────────────────────────────────────
    function updateFormationRow(formationId, nomForm, dateForm, type, typeText, description) {
        const row = document.querySelector(`tr[data-formation-id="${formationId}"]`);
        if (!row) return;

        row.setAttribute('data-type', type);

        // Update nom
        const nomCell = row.querySelector('.form-nom');
        if (nomCell) nomCell.textContent = nomForm;

        // Update avatar type
        const avatar = row.querySelector('.form-icon-avatar');
        if (avatar) {
            avatar.className = `form-icon-avatar ${type}`;
            // Update icon
            const icons = {
                'beginner': 'fa-graduation-cap',
                'intermediate': 'fa-palette',
                'advanced': 'fa-medal',
                'workshop': 'fa-hands',
                'masterclass': 'fa-star'
            };
            const iconClass = icons[type] || 'fa-graduation-cap';
            const iconEl = avatar.querySelector('i');
            if (iconEl) iconEl.className = `fas ${iconClass}`;
        }

        // Update date
        const dateCell = row.querySelector('.form-date');
        if (dateCell) {
            dateCell.innerHTML = `<i class="fas fa-calendar me-1"></i>${dateForm}`;
        }

        // Update type badge
        const typeBadge = row.querySelector('.type-badge');
        if (typeBadge) {
            const typeClass = `type-${type}`;
            typeBadge.className = `type-badge ${typeClass}`;
            typeBadge.textContent = typeText;
        }

        // Update description
        const descCell = row.querySelector('.form-description');
        if (descCell) descCell.textContent = description;

        // Update delete button data attribute
        const deleteBtn = row.querySelector('.delete-formation-btn');
        if (deleteBtn) {
            deleteBtn.setAttribute('data-formation-nom', nomForm);
        }
    }

    // ───────────────────────────────────────────────────────────
    // DELETE FORMATION
    // ───────────────────────────────────────────────────────────
    const deleteAlert = document.getElementById('deleteFormationAlert');
    const deleteNameEl = document.getElementById('deleteFormationName');
    let currentDeleteId = null;

    document.addEventListener('click', function(e) {
        if (e.target.closest('.delete-formation-btn')) {
            const btn = e.target.closest('.delete-formation-btn');
            currentDeleteId = btn.getAttribute('data-formation-id');
            const formationNom = btn.getAttribute('data-formation-nom') || 'this formation';
            deleteNameEl.textContent = formationNom;
            deleteAlert.classList.add('show');
        }
    });

    document.getElementById('cancelDeleteFormationBtn').addEventListener('click', function() {
        deleteAlert.classList.remove('show');
        currentDeleteId = null;
    });

    document.getElementById('confirmDeleteFormationBtn').addEventListener('click', function() {
        if (currentDeleteId) {
            const tableBody = document.getElementById('formationTableBody');
            const row = tableBody.querySelector(`tr[data-formation-id="${currentDeleteId}"]`);
            if (row) {
                const nom = row.querySelector('.form-nom')?.textContent || 'Formation';
                row.classList.add('formation-row-removing');
                row.addEventListener('animationend', () => row.remove());
                showToast('success', `Formation "${nom}" deleted successfully.`);

                // Update counter
                const counterEl = document.querySelector('.form-stat-card .form-stat-value');
                if (counterEl) {
                    const current = parseInt(counterEl.textContent.replace(/,/g, ''));
                    counterEl.textContent = Math.max(0, current - 1).toLocaleString();
                }

                updateTableFooter();
            }
        }
        deleteAlert.classList.remove('show');
        currentDeleteId = null;
    });

    // Close on overlay click
    deleteAlert.addEventListener('click', function(e) {
        if (e.target === deleteAlert) {
            deleteAlert.classList.remove('show');
            currentDeleteId = null;
        }
    });

    // ───────────────────────────────────────────────────────────
    // VIEW FORMATION
    // ───────────────────────────────────────────────────────────
    document.addEventListener('click', function(e) {
        if (e.target.closest('.view-formation-btn')) {
            const btn = e.target.closest('.view-formation-btn');
            const formationId = btn.getAttribute('data-formation-id');
            const row = document.querySelector(`tr[data-formation-id="${formationId}"]`);

            if (row) {
                // Remove any other open detail rows
                document.querySelectorAll('.formation-detail-row').forEach(r => r.remove());

                const id = row.querySelector('.form-id')?.textContent || '-';
                const nom = row.querySelector('.form-nom')?.textContent || '-';
                const date = row.querySelector('.form-date')?.textContent.trim() || '-';
                const type = row.querySelector('.type-badge')?.textContent || '-';
                const description = row.querySelector('.form-description')?.textContent || '-';

                const detailRow = document.createElement('tr');
                detailRow.className = 'formation-detail-row';
                detailRow.innerHTML = `
                    <td colspan="7" style="background: rgba(212, 175, 55, 0.05); border-left: 3px solid #d4af37;">
                        <div style="padding: 1.5rem;">
                            <h5 style="color: #d4af37; margin-bottom: 1rem;"><i class="fas fa-info-circle me-2"></i>Formation Details</h5>
                            <div class="row g-3">
                                <div class="col-md-4">
                                    <strong style="color: rgba(255,255,255,0.6);">ID:</strong>
                                    <span style="color: #fff; margin-left: 0.5rem;">${id}</span>
                                </div>
                                <div class="col-md-8">
                                    <strong style="color: rgba(255,255,255,0.6);">Formation Name:</strong>
                                    <span style="color: #fff; margin-left: 0.5rem;">${nom}</span>
                                </div>
                                <div class="col-md-4">
                                    <strong style="color: rgba(255,255,255,0.6);">Date:</strong>
                                    <span style="color: #fff; margin-left: 0.5rem;">${date}</span>
                                </div>
                                <div class="col-md-4">
                                    <strong style="color: rgba(255,255,255,0.6);">Type:</strong>
                                    <span style="color: #fff; margin-left: 0.5rem;">${type}</span>
                                </div>
                                <div class="col-md-12">
                                    <strong style="color: rgba(255,255,255,0.6);">Description:</strong>
                                    <p style="color: #fff; margin-top: 0.5rem; line-height: 1.6;">${description}</p>
                                </div>
                            </div>
                            <button class="btn btn-admin-outline btn-sm mt-3 close-detail-btn">
                                <i class="fas fa-times me-1"></i> Close
                            </button>
                        </div>
                    </td>
                `;

                row.parentNode.insertBefore(detailRow, row.nextSibling);

                // Close detail row
                detailRow.querySelector('.close-detail-btn').addEventListener('click', function() {
                    detailRow.remove();
                });
            }
        }
    });

    // ───────────────────────────────────────────────────────────
    // SEARCH FUNCTIONALITY
    // ───────────────────────────────────────────────────────────
    const searchInput = document.getElementById('formationSearchInput');
    const typeFilter = document.getElementById('formationTypeFilter');

    if (searchInput) {
        searchInput.addEventListener('input', filterTable);
    }

    if (typeFilter) {
        typeFilter.addEventListener('change', filterTable);
    }

    function filterTable() {
        const searchTerm = searchInput.value.toLowerCase();
        const selectedType = typeFilter.value.toLowerCase();
        const tableBody = document.getElementById('formationTableBody');
        const rows = tableBody.querySelectorAll('tr:not(.formation-detail-row)');

        let visibleCount = 0;

        rows.forEach(row => {
            const nom = row.querySelector('.form-nom')?.textContent.toLowerCase() || '';
            const description = row.querySelector('.form-description')?.textContent.toLowerCase() || '';
            const date = row.querySelector('.form-date')?.textContent.toLowerCase() || '';
            const type = row.getAttribute('data-type') || '';

            const matchesSearch = nom.includes(searchTerm) || 
                                description.includes(searchTerm) || 
                                date.includes(searchTerm);

            const matchesType = !selectedType || type === selectedType;

            if (matchesSearch && matchesType) {
                row.style.display = '';
                visibleCount++;
            } else {
                row.style.display = 'none';
            }
        });

        // Update footer
        const footerInfo = document.querySelector('.admin-table-info');
        if (footerInfo) {
            footerInfo.innerHTML = `Showing <strong>1–${visibleCount}</strong> of <strong>${rows.length}</strong> formations`;
        }
    }

    // ───────────────────────────────────────────────────────────
    // SELECT ALL CHECKBOX
    // ───────────────────────────────────────────────────────────
    const selectAllCheckbox = document.getElementById('selectAllFormations');
    if (selectAllCheckbox) {
        selectAllCheckbox.addEventListener('change', function() {
            const checkboxes = document.querySelectorAll('.formation-check');
            checkboxes.forEach(cb => cb.checked = this.checked);
        });
    }

    // ───────────────────────────────────────────────────────────
    // TOAST NOTIFICATIONS
    // ───────────────────────────────────────────────────────────
    function showToast(type, message) {
        let toastEl, msgEl;

        if (type === 'success') {
            toastEl = document.getElementById('formationToastSuccess');
            msgEl = document.getElementById('formationToastSuccessMsg');
        } else if (type === 'error') {
            toastEl = document.getElementById('formationToastError');
            msgEl = document.getElementById('formationToastErrorMsg');
        } else if (type === 'warning') {
            toastEl = document.getElementById('formationToastWarning');
            msgEl = document.getElementById('formationToastWarningMsg');
        }

        if (toastEl && msgEl) {
            msgEl.textContent = message;
            const toast = new bootstrap.Toast(toastEl, { delay: 3000 });
            toast.show();
        }
    }

    // ───────────────────────────────────────────────────────────
    // UTILITY FUNCTIONS
    // ───────────────────────────────────────────────────────────
    function resetForm() {
        formationForm.reset();
        formationForm.classList.remove('was-validated');
        fFormationId.value = '';
    }

    function escapeHtml(text) {
        const map = {
            '&': '&amp;',
            '<': '&lt;',
            '>': '&gt;',
            '"': '&quot;',
            "'": '&#039;'
        };
        return text.replace(/[&<>"']/g, m => map[m]);
    }

    function updateTableFooter() {
        const tableBody = document.getElementById('formationTableBody');
        const totalRows = tableBody.querySelectorAll('tr:not(.formation-detail-row)').length;
        const footerInfo = document.querySelector('.admin-table-info');
        if (footerInfo) {
            footerInfo.innerHTML = `Showing <strong>1–${Math.min(6, totalRows)}</strong> of <strong>${totalRows}</strong> formations`;
        }

        // Update badge count
        const countBadge = document.getElementById('formationCount');
        if (countBadge) {
            countBadge.textContent = totalRows;
        }
    }

    // ───────────────────────────────────────────────────────────
    // EXPORT FUNCTIONALITY
    // ───────────────────────────────────────────────────────────
    document.getElementById('exportFormationsBtn')?.addEventListener('click', function() {
        showToast('success', 'Formations exported successfully!');
    });

    // ───────────────────────────────────────────────────────────
    // SORTABLE TABLE HEADERS
    // ───────────────────────────────────────────────────────────
    const sortableHeaders = document.querySelectorAll('.sortable');
    sortableHeaders.forEach(header => {
        header.style.cursor = 'pointer';
        header.addEventListener('click', function() {
            const sortKey = this.getAttribute('data-sort');
            console.log('Sorting by:', sortKey);
            showToast('success', `Sorted by ${sortKey}!`);
        });
    });

});
