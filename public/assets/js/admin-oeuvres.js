/* ═══════════════════════════════════════════════════════════════
   ŒUVRES (ARTWORKS) MANAGEMENT - JAVASCRIPT
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
    const modal = document.getElementById('oeuvreModal');
    const modalLabel = document.getElementById('oeuvreModalLabel');
    const modalSubtitle = document.getElementById('oeuvreModalSubtitle');
    const modalIcon = document.getElementById('oeuvreModalIcon');
    const saveBtnText = document.getElementById('saveOeuvreBtnText');
    const oeuvreForm = document.getElementById('oeuvreForm');

    // Form fields
    const fOeuvreId = document.getElementById('formOeuvreId');
    const fInputId = document.getElementById('inputOeuvreId');
    const fTitre = document.getElementById('inputTitre');
    const fNomTableau = document.getElementById('inputNomTableau');
    const fGalerie = document.getElementById('inputGalerie');
    const fArtiste = document.getElementById('inputArtiste');
    const fPrix = document.getElementById('inputPrix');
    const fEtat = document.getElementById('inputEtat');
    const fAnnee = document.getElementById('inputAnnee');

    // Initialize Bootstrap tooltips
    const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });

    // ───────────────────────────────────────────────────────────
    // ADD ŒUVRE BUTTON
    // ───────────────────────────────────────────────────────────
    document.getElementById('addOeuvreBtn').addEventListener('click', function() {
        resetForm();
        modalLabel.textContent = 'Add Œuvre';
        modalSubtitle.textContent = 'Fill in the details to create a new artwork';
        saveBtnText.textContent = 'Add Œuvre';
        modalIcon.className = 'fas fa-palette';
        fOeuvreId.value = '';
        fInputId.value = 'Auto-generated';
    });

    // ───────────────────────────────────────────────────────────
    // EDIT ŒUVRE BUTTON
    // ───────────────────────────────────────────────────────────
    document.addEventListener('click', function(e) {
        if (e.target.closest('.edit-oeuvre-btn')) {
            const btn = e.target.closest('.edit-oeuvre-btn');
            const oeuvreId = btn.getAttribute('data-oeuvre-id');
            const row = document.querySelector(`tr[data-oeuvre-id="${oeuvreId}"]`);

            if (row) {
                resetForm();
                modalLabel.textContent = 'Edit Œuvre';
                modalSubtitle.textContent = 'Update the artwork details';
                saveBtnText.textContent = 'Save Changes';
                modalIcon.className = 'fas fa-pen-to-square';

                // Populate form
                fOeuvreId.value = oeuvreId;
                fInputId.value = row.querySelector('.oeuv-id')?.textContent || '';
                fTitre.value = row.querySelector('.oeuv-titre')?.textContent || '';
                fNomTableau.value = row.querySelector('.oeuv-nom')?.textContent || '';

                // Gallery select
                const galerieText = row.querySelector('.oeuv-galerie-badge')?.textContent.trim() || '';
                const galerieOptions = fGalerie.querySelectorAll('option');
                galerieOptions.forEach(opt => {
                    if (opt.textContent.trim() === galerieText.replace(/\s+/g, ' ').trim()) {
                        fGalerie.value = opt.value;
                    }
                });

                // Artist select
                const artistText = row.querySelector('.oeuv-artist-info span')?.textContent.trim() || '';
                const artistOptions = fArtiste.querySelectorAll('option');
                artistOptions.forEach(opt => {
                    if (opt.textContent.trim() === artistText) {
                        fArtiste.value = opt.value;
                    }
                });

                // Price
                const prixText = row.querySelector('.oeuv-prix')?.textContent.trim() || '';
                fPrix.value = prixText.replace(/[^\d]/g, '');

                // Condition
                const conditionBadge = row.querySelector('.condition-badge');
                if (conditionBadge) {
                    const condText = conditionBadge.textContent.trim().toLowerCase();
                    fEtat.value = condText;
                }

                // Year
                const yearText = row.querySelector('.oeuv-year')?.textContent.trim() || '';
                fAnnee.value = yearText;

                // Show modal
                const bsModal = new bootstrap.Modal(modal);
                bsModal.show();
            }
        }
    });

    // ───────────────────────────────────────────────────────────
    // SAVE ŒUVRE (ADD OR UPDATE)
    // ───────────────────────────────────────────────────────────
    document.getElementById('saveOeuvreBtn').addEventListener('click', function() {
        // Validate form
        if (!oeuvreForm.checkValidity()) {
            oeuvreForm.classList.add('was-validated');
            showToast('warning', 'Please fill in all required fields correctly.');
            return;
        }

        const oeuvreId = fOeuvreId.value;
        const titre = fTitre.value.trim();
        const nomTableau = fNomTableau.value.trim();
        const galerieId = fGalerie.value;
        const artisteId = fArtiste.value;
        const prix = parseFloat(fPrix.value);
        const etat = fEtat.value;
        const annee = fAnnee.value;

        // Get text values for display
        const galerieText = fGalerie.options[fGalerie.selectedIndex].text;
        const artisteText = fArtiste.options[fArtiste.selectedIndex].text;

        if (oeuvreId) {
            // Update existing artwork
            updateOeuvreRow(oeuvreId, titre, nomTableau, galerieText, artisteText, prix, etat, annee);
            showToast('success', `Artwork "${titre}" updated successfully!`);
        } else {
            // Add new artwork
            addNewOeuvre(titre, nomTableau, galerieText, artisteText, prix, etat, annee);
            showToast('success', `Artwork "${titre}" added successfully!`);

            // Update counter
            const counterEl = document.querySelector('.oeuv-stat-card .oeuv-stat-value');
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
    // ADD NEW ŒUVRE ROW
    // ───────────────────────────────────────────────────────────
    function addNewOeuvre(titre, nomTableau, galerie, artiste, prix, etat, annee) {
        const tableBody = document.getElementById('oeuvreTableBody');
        const newId = tableBody.querySelectorAll('tr').length + 1;

        // Determine artwork type for icon
        const types = ['contemporary', 'classical', 'modern', 'sculpture', 'photography', 'abstract'];
        const randomType = types[Math.floor(Math.random() * types.length)];

        // Condition badge class
        const condClass = `cond-${etat}`;
        const condText = etat.charAt(0).toUpperCase() + etat.slice(1);

        // Icon based on type
        const icons = {
            'contemporary': 'fa-paint-brush',
            'classical': 'fa-palette',
            'modern': 'fa-spray-can',
            'sculpture': 'fa-gem',
            'photography': 'fa-camera',
            'abstract': 'fa-shapes'
        };
        const iconClass = icons[randomType] || 'fa-paint-brush';

        const tr = document.createElement('tr');
        tr.setAttribute('data-oeuvre-id', newId);
        tr.setAttribute('data-condition', etat);
        tr.innerHTML = `
            <td><input type="checkbox" class="form-check-input oeuvre-check"></td>
            <td class="oeuv-id">${fInputId.value || newId}</td>
            <td>
                <div class="oeuv-titre-cell">
                    <div class="oeuv-icon-avatar ${randomType}">
                        <i class="fas ${iconClass}"></i>
                    </div>
                    <span class="oeuv-titre">${escapeHtml(titre)}</span>
                </div>
            </td>
            <td><span class="oeuv-nom">${escapeHtml(nomTableau)}</span></td>
            <td><span class="oeuv-galerie-badge"><i class="fas fa-building-columns me-1"></i>${escapeHtml(galerie)}</span></td>
            <td>
                <div class="oeuv-artist-info">
                    <i class="fas fa-user-circle me-1"></i>
                    <span>${escapeHtml(artiste)}</span>
                </div>
            </td>
            <td><span class="oeuv-prix">${prix.toLocaleString()} €</span></td>
            <td><span class="condition-badge ${condClass}">${condText}</span></td>
            <td><span class="oeuv-year">${annee}</span></td>
            <td>
                <div class="table-actions">
                    <button class="table-action-btn view-oeuvre-btn" title="View" data-bs-toggle="tooltip" data-oeuvre-id="${newId}"><i class="fas fa-eye"></i></button>
                    <button class="table-action-btn edit-oeuvre-btn" title="Edit" data-bs-toggle="tooltip" data-oeuvre-id="${newId}"><i class="fas fa-pen-to-square"></i></button>
                    <button class="table-action-btn delete delete-oeuvre-btn" title="Delete" data-bs-toggle="tooltip" data-oeuvre-id="${newId}" data-oeuvre-titre="${escapeHtml(titre)}"><i class="fas fa-trash-can"></i></button>
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
    // UPDATE ŒUVRE ROW
    // ───────────────────────────────────────────────────────────
    function updateOeuvreRow(oeuvreId, titre, nomTableau, galerie, artiste, prix, etat, annee) {
        const row = document.querySelector(`tr[data-oeuvre-id="${oeuvreId}"]`);
        if (!row) return;

        row.setAttribute('data-condition', etat);

        // Update titre
        const titreCell = row.querySelector('.oeuv-titre');
        if (titreCell) titreCell.textContent = titre;

        // Update nom
        const nomCell = row.querySelector('.oeuv-nom');
        if (nomCell) nomCell.textContent = nomTableau;

        // Update galerie
        const galerieCell = row.querySelector('.oeuv-galerie-badge');
        if (galerieCell) {
            galerieCell.innerHTML = `<i class="fas fa-building-columns me-1"></i>${galerie}`;
        }

        // Update artiste
        const artisteCell = row.querySelector('.oeuv-artist-info span');
        if (artisteCell) artisteCell.textContent = artiste;

        // Update prix
        const prixCell = row.querySelector('.oeuv-prix');
        if (prixCell) prixCell.textContent = `${prix.toLocaleString()} €`;

        // Update condition
        const condBadge = row.querySelector('.condition-badge');
        if (condBadge) {
            const condClass = `cond-${etat}`;
            const condText = etat.charAt(0).toUpperCase() + etat.slice(1);
            condBadge.className = `condition-badge ${condClass}`;
            condBadge.textContent = condText;
        }

        // Update year
        const yearCell = row.querySelector('.oeuv-year');
        if (yearCell) yearCell.textContent = annee;

        // Update delete button data attribute
        const deleteBtn = row.querySelector('.delete-oeuvre-btn');
        if (deleteBtn) {
            deleteBtn.setAttribute('data-oeuvre-titre', titre);
        }
    }

    // ───────────────────────────────────────────────────────────
    // DELETE ŒUVRE
    // ───────────────────────────────────────────────────────────
    const deleteAlert = document.getElementById('deleteOeuvreAlert');
    const deleteNameEl = document.getElementById('deleteOeuvreName');
    let currentDeleteId = null;

    document.addEventListener('click', function(e) {
        if (e.target.closest('.delete-oeuvre-btn')) {
            const btn = e.target.closest('.delete-oeuvre-btn');
            currentDeleteId = btn.getAttribute('data-oeuvre-id');
            const oeuvreTitle = btn.getAttribute('data-oeuvre-titre') || 'this artwork';
            deleteNameEl.textContent = oeuvreTitle;
            deleteAlert.classList.add('show');
        }
    });

    document.getElementById('cancelDeleteOeuvreBtn').addEventListener('click', function() {
        deleteAlert.classList.remove('show');
        currentDeleteId = null;
    });

    document.getElementById('confirmDeleteOeuvreBtn').addEventListener('click', function() {
        if (currentDeleteId) {
            const tableBody = document.getElementById('oeuvreTableBody');
            const row = tableBody.querySelector(`tr[data-oeuvre-id="${currentDeleteId}"]`);
            if (row) {
                const titre = row.querySelector('.oeuv-titre')?.textContent || 'Artwork';
                row.classList.add('oeuvre-row-removing');
                row.addEventListener('animationend', () => row.remove());
                showToast('success', `Artwork "${titre}" deleted successfully.`);

                // Update counter
                const counterEl = document.querySelector('.oeuv-stat-card .oeuv-stat-value');
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
    // VIEW ŒUVRE
    // ───────────────────────────────────────────────────────────
    document.addEventListener('click', function(e) {
        if (e.target.closest('.view-oeuvre-btn')) {
            const btn = e.target.closest('.view-oeuvre-btn');
            const oeuvreId = btn.getAttribute('data-oeuvre-id');
            const row = document.querySelector(`tr[data-oeuvre-id="${oeuvreId}"]`);

            if (row) {
                // Remove any other open detail rows
                document.querySelectorAll('.oeuvre-detail-row').forEach(r => r.remove());

                const id = row.querySelector('.oeuv-id')?.textContent || '-';
                const titre = row.querySelector('.oeuv-titre')?.textContent || '-';
                const nom = row.querySelector('.oeuv-nom')?.textContent || '-';
                const galerie = row.querySelector('.oeuv-galerie-badge')?.textContent.trim() || '-';
                const artiste = row.querySelector('.oeuv-artist-info span')?.textContent || '-';
                const prix = row.querySelector('.oeuv-prix')?.textContent || '-';
                const condition = row.querySelector('.condition-badge')?.textContent || '-';
                const year = row.querySelector('.oeuv-year')?.textContent || '-';

                const detailRow = document.createElement('tr');
                detailRow.className = 'oeuvre-detail-row';
                detailRow.innerHTML = `
                    <td colspan="10" style="background: rgba(212, 175, 55, 0.05); border-left: 3px solid #d4af37;">
                        <div style="padding: 1.5rem;">
                            <h5 style="color: #d4af37; margin-bottom: 1rem;"><i class="fas fa-info-circle me-2"></i>Artwork Details</h5>
                            <div class="row g-3">
                                <div class="col-md-4">
                                    <strong style="color: rgba(255,255,255,0.6);">ID:</strong>
                                    <span style="color: #fff; margin-left: 0.5rem;">${id}</span>
                                </div>
                                <div class="col-md-4">
                                    <strong style="color: rgba(255,255,255,0.6);">Title:</strong>
                                    <span style="color: #fff; margin-left: 0.5rem;">${titre}</span>
                                </div>
                                <div class="col-md-4">
                                    <strong style="color: rgba(255,255,255,0.6);">Artwork Name:</strong>
                                    <span style="color: #fff; margin-left: 0.5rem;">${nom}</span>
                                </div>
                                <div class="col-md-4">
                                    <strong style="color: rgba(255,255,255,0.6);">Gallery:</strong>
                                    <span style="color: #fff; margin-left: 0.5rem;">${galerie}</span>
                                </div>
                                <div class="col-md-4">
                                    <strong style="color: rgba(255,255,255,0.6);">Artist:</strong>
                                    <span style="color: #fff; margin-left: 0.5rem;">${artiste}</span>
                                </div>
                                <div class="col-md-4">
                                    <strong style="color: rgba(255,255,255,0.6);">Price:</strong>
                                    <span style="color: #40e0d0; margin-left: 0.5rem;">${prix}</span>
                                </div>
                                <div class="col-md-4">
                                    <strong style="color: rgba(255,255,255,0.6);">Condition:</strong>
                                    <span style="color: #fff; margin-left: 0.5rem;">${condition}</span>
                                </div>
                                <div class="col-md-4">
                                    <strong style="color: rgba(255,255,255,0.6);">Year Created:</strong>
                                    <span style="color: #fff; margin-left: 0.5rem;">${year}</span>
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
    const searchInput = document.getElementById('oeuvreSearchInput');
    const conditionFilter = document.getElementById('oeuvreConditionFilter');

    if (searchInput) {
        searchInput.addEventListener('input', filterTable);
    }

    if (conditionFilter) {
        conditionFilter.addEventListener('change', filterTable);
    }

    function filterTable() {
        const searchTerm = searchInput.value.toLowerCase();
        const selectedCondition = conditionFilter.value.toLowerCase();
        const tableBody = document.getElementById('oeuvreTableBody');
        const rows = tableBody.querySelectorAll('tr:not(.oeuvre-detail-row)');

        let visibleCount = 0;

        rows.forEach(row => {
            const titre = row.querySelector('.oeuv-titre')?.textContent.toLowerCase() || '';
            const nom = row.querySelector('.oeuv-nom')?.textContent.toLowerCase() || '';
            const galerie = row.querySelector('.oeuv-galerie-badge')?.textContent.toLowerCase() || '';
            const artiste = row.querySelector('.oeuv-artist-info span')?.textContent.toLowerCase() || '';
            const condition = row.getAttribute('data-condition') || '';

            const matchesSearch = titre.includes(searchTerm) || 
                                nom.includes(searchTerm) || 
                                galerie.includes(searchTerm) || 
                                artiste.includes(searchTerm);

            const matchesCondition = !selectedCondition || condition === selectedCondition;

            if (matchesSearch && matchesCondition) {
                row.style.display = '';
                visibleCount++;
            } else {
                row.style.display = 'none';
            }
        });

        // Update footer
        const footerInfo = document.querySelector('.admin-table-info');
        if (footerInfo) {
            footerInfo.innerHTML = `Showing <strong>1–${visibleCount}</strong> of <strong>${rows.length}</strong> artworks`;
        }
    }

    // ───────────────────────────────────────────────────────────
    // SELECT ALL CHECKBOX
    // ───────────────────────────────────────────────────────────
    const selectAllCheckbox = document.getElementById('selectAllOeuvres');
    if (selectAllCheckbox) {
        selectAllCheckbox.addEventListener('change', function() {
            const checkboxes = document.querySelectorAll('.oeuvre-check');
            checkboxes.forEach(cb => cb.checked = this.checked);
        });
    }

    // ───────────────────────────────────────────────────────────
    // TOAST NOTIFICATIONS
    // ───────────────────────────────────────────────────────────
    function showToast(type, message) {
        let toastEl, msgEl;

        if (type === 'success') {
            toastEl = document.getElementById('oeuvreToastSuccess');
            msgEl = document.getElementById('oeuvreToastSuccessMsg');
        } else if (type === 'error') {
            toastEl = document.getElementById('oeuvreToastError');
            msgEl = document.getElementById('oeuvreToastErrorMsg');
        } else if (type === 'warning') {
            toastEl = document.getElementById('oeuvreToastWarning');
            msgEl = document.getElementById('oeuvreToastWarningMsg');
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
        oeuvreForm.reset();
        oeuvreForm.classList.remove('was-validated');
        fOeuvreId.value = '';
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
        const tableBody = document.getElementById('oeuvreTableBody');
        const totalRows = tableBody.querySelectorAll('tr:not(.oeuvre-detail-row)').length;
        const footerInfo = document.querySelector('.admin-table-info');
        if (footerInfo) {
            footerInfo.innerHTML = `Showing <strong>1–${Math.min(6, totalRows)}</strong> of <strong>${totalRows}</strong> artworks`;
        }

        // Update badge count
        const countBadge = document.getElementById('oeuvreCount');
        if (countBadge) {
            countBadge.textContent = totalRows;
        }
    }

    // ───────────────────────────────────────────────────────────
    // EXPORT FUNCTIONALITY
    // ───────────────────────────────────────────────────────────
    document.getElementById('exportGaleriesBtn')?.addEventListener('click', function() {
        showToast('success', 'Artworks exported successfully!');
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
