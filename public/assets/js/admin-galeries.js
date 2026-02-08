/* ═══════════════════════════════════════════════════════════════
   ADMIN GALERIES MANAGEMENT — JavaScript
   Full CRUD UI logic: modal, validation, delete alert, toasts,
   search/filter, artist tags, counter animations
   ═══════════════════════════════════════════════════════════════ */

document.addEventListener('DOMContentLoaded', () => {

    /* ─── DOM References ─────────────────────────────────────── */
    const galeriesTable = document.getElementById('galeriesTable');
    const tableBody = document.getElementById('galeriesTableBody');
    const galerieModal = document.getElementById('galerieModal');
    const galerieForm = document.getElementById('galerieForm');
    const addGalerieBtn = document.getElementById('addGalerieBtn');
    const saveGalerieBtn = document.getElementById('saveGalerieBtn');
    const saveGalText = document.getElementById('saveGalerieBtnText');
    const modalTitle = document.getElementById('galerieModalLabel');
    const modalSubtitle = document.getElementById('galerieModalSubtitle');
    const modalIcon = document.getElementById('galerieModalIcon');
    const searchInput = document.getElementById('galerieSearch');
    const categoryFilter = document.getElementById('categoryFilter');
    const selectAllCb = document.getElementById('selectAllGaleries');

    // Form fields
    const fId = document.getElementById('formGalerieId');
    const fGalerieId = document.getElementById('inputGalerieId');
    const fCategory = document.getElementById('inputGalerieCategory');
    const fName = document.getElementById('inputGalerieName');
    const fArtworks = document.getElementById('inputGalerieArtworks');
    const fStaff = document.getElementById('inputGalerieStaff');
    const fArtists = document.getElementById('inputGalerieArtists');

    // Delete alert
    const deleteOverlay = document.getElementById('deleteGalerieAlertOverlay');
    const deleteNameEl = document.getElementById('deleteGalerieName');
    const cancelDeleteBtn = document.getElementById('cancelGalerieDeleteBtn');
    const confirmDeleteBtn = document.getElementById('confirmGalerieDeleteBtn');

    // Bootstrap modal instance
    let bsModal = null;
    if (galerieModal) {
        bsModal = new bootstrap.Modal(galerieModal);
    }

    let currentDeleteId = null;
    let currentEditRow = null;
    let galerieCounter = tableBody ? tableBody.rows.length : 0;


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


    /* ─── MODAL: ADD MODE ────────────────────────────────────── */
    if (addGalerieBtn) {
        addGalerieBtn.addEventListener('click', () => {
            resetForm();
            modalTitle.textContent = 'Add Galerie';
            modalSubtitle.textContent = 'Fill in the details to create a new gallery';
            modalIcon.className = 'fas fa-palette';
            saveGalText.textContent = 'Save Galerie';
            galerieCounter++;
            fGalerieId.value = '#GAL-' + String(galerieCounter).padStart(3, '0');
            fId.value = '';
            currentEditRow = null;
            // Bootstrap handles modal opening via data-bs-toggle attribute
        });
    }


    /* ─── MODAL: EDIT MODE ───────────────────────────────────── */
    if (tableBody) {
        tableBody.addEventListener('click', (e) => {
            const editBtn = e.target.closest('.edit-galerie-btn');
            if (!editBtn) return;

            const row = editBtn.closest('tr');
            if (!row) return;

            currentEditRow = row;
            const galerieId = row.getAttribute('data-galerie-id');

            modalTitle.textContent = 'Edit Galerie';
            modalSubtitle.textContent = 'Modify the gallery details below';
            modalIcon.className = 'fas fa-pen-to-square';
            saveGalText.textContent = 'Update Galerie';

            // Populate form from row data
            fId.value = galerieId;
            fGalerieId.value = row.querySelector('.gal-id')?.textContent.trim() || '';
            fName.value = row.querySelector('.gal-name')?.textContent.trim() || '';

            // Category
            const categoryBadge = row.querySelector('.category-badge');
            if (categoryBadge) {
                const catText = categoryBadge.textContent.trim().toLowerCase();
                fCategory.value = catText;
            }

            // Artworks count
            const artworkText = row.querySelector('.gal-artwork-count')?.textContent.trim() || '0';
            fArtworks.value = artworkText.replace(/\D/g, '');

            // Staff count
            const staffText = row.querySelector('.gal-staff-count')?.textContent.trim() || '0';
            fStaff.value = staffText.replace(/\D/g, '');

            // Artists
            const artistTags = row.querySelectorAll('.artist-tag');
            const artistsArray = Array.from(artistTags).map(tag => tag.textContent.trim());
            fArtists.value = artistsArray.join(', ');

            // Remove previous validation states
            galerieForm.querySelectorAll('.is-invalid, .is-valid').forEach(el => {
                el.classList.remove('is-invalid', 'is-valid');
            });

            if (bsModal) {
                bsModal.show();
            }
        });
    }


    /* ─── FORM VALIDATION ────────────────────────────────────── */
    function validateForm() {
        let isValid = true;
        const required = [fCategory, fName, fArtworks, fStaff, fArtists].filter(f => f);

        required.forEach(field => {
            if (!field.value.trim()) {
                field.classList.add('is-invalid');
                field.classList.remove('is-valid');
                isValid = false;
            } else {
                field.classList.remove('is-invalid');
                field.classList.add('is-valid');
            }
        });

        // Validate numbers
        if (fArtworks && fArtworks.value && parseInt(fArtworks.value) < 0) {
            fArtworks.classList.add('is-invalid');
            fArtworks.classList.remove('is-valid');
            isValid = false;
        }

        if (fStaff && fStaff.value && parseInt(fStaff.value) < 0) {
            fStaff.classList.add('is-invalid');
            fStaff.classList.remove('is-valid');
            isValid = false;
        }

        return isValid;
    }


    /* ─── SAVE GALERIE ───────────────────────────────────────── */
    if (saveGalerieBtn) {
        saveGalerieBtn.addEventListener('click', () => {
            if (!validateForm()) {
                showToast('warning', 'Please fill in all required fields.');
                return;
            }

            const categoryVal = fCategory.value;
            const nameVal = fName.value.trim();
            const artworksVal = parseInt(fArtworks.value);
            const staffVal = parseInt(fStaff.value);
            const artistsVal = fArtists.value.trim();

            if (currentEditRow) {
                /* ── UPDATE ROW ── */
                updateRow(currentEditRow, categoryVal, nameVal, artworksVal, staffVal, artistsVal);
                bsModal.hide();
                showToast('success', `Gallery "${nameVal}" updated successfully!`);
            } else {
                /* ── ADD NEW ROW ── */
                const newRow = createRow(categoryVal, nameVal, artworksVal, staffVal, artistsVal);
                tableBody.appendChild(newRow);
                bsModal.hide();
                showToast('success', `Gallery "${nameVal}" created successfully!`);
            }
        });
    }


    /* ─── CREATE TABLE ROW ───────────────────────────────────── */
    function createRow(category, name, artworks, staff, artistsStr) {
        const tr = document.createElement('tr');
        const newId = galerieCounter;
        tr.setAttribute('data-galerie-id', newId);
        tr.setAttribute('data-category', category);
        tr.style.animation = 'galerieDetailExpand 0.35s ease';

        // Category badge
        const catClass = `cat-${category}`;
        const catText = category.charAt(0).toUpperCase() + category.slice(1);

        // Parse artists
        const artistsArray = artistsStr.split(',').map(a => a.trim()).filter(a => a);
        const displayArtists = artistsArray.slice(0, 3);
        const moreCount = artistsArray.length > 3 ? artistsArray.length - 3 : 0;

        let artistsHTML = '';
        displayArtists.forEach(artist => {
            artistsHTML += `<span class="artist-tag">${escapeHtml(artist)}</span>`;
        });
        if (moreCount > 0) {
            artistsHTML += `<span class="artist-more">+${moreCount}</span>`;
        }

        tr.innerHTML = `
            <td><input type="checkbox" class="form-check-input galerie-check"></td>
            <td class="gal-id">${fGalerieId.value}</td>
            <td>
                <div class="gal-name-cell">
                    <div class="gal-icon-avatar ${category}">
                        <i class="fas fa-building-columns"></i>
                    </div>
                    <span class="gal-name">${escapeHtml(name)}</span>
                </div>
            </td>
            <td><span class="category-badge ${catClass}">${catText}</span></td>
            <td><span class="gal-artwork-count"><i class="fas fa-palette me-1 text-muted"></i>${artworks}</span></td>
            <td>
                <div class="gal-artists-list">
                    ${artistsHTML}
                </div>
            </td>
            <td><span class="gal-staff-count"><i class="fas fa-user-tie me-1 text-muted"></i>${staff}</span></td>
            <td>
                <div class="table-actions">
                    <button class="table-action-btn view-galerie-btn" title="View" data-bs-toggle="tooltip" data-galerie-id="${newId}"><i class="fas fa-eye"></i></button>
                    <button class="table-action-btn edit-galerie-btn" title="Edit" data-bs-toggle="tooltip" data-galerie-id="${newId}"><i class="fas fa-pen-to-square"></i></button>
                    <button class="table-action-btn delete delete-galerie-btn" title="Delete" data-bs-toggle="tooltip" data-galerie-id="${newId}" data-galerie-name="${escapeHtml(name)}"><i class="fas fa-trash-can"></i></button>
                </div>
            </td>
        `;
        return tr;
    }


    /* ─── UPDATE EXISTING ROW ────────────────────────────────── */
    function updateRow(row, category, name, artworks, staff, artistsStr) {
        row.setAttribute('data-category', category);

        const nameCell = row.querySelector('.gal-name');
        const iconAvatar = row.querySelector('.gal-icon-avatar');
        const categoryBadge = row.querySelector('.category-badge');
        const artworkCount = row.querySelector('.gal-artwork-count');
        const staffCount = row.querySelector('.gal-staff-count');
        const artistsList = row.querySelector('.gal-artists-list');
        const deleteBtn = row.querySelector('.delete-galerie-btn');

        if (nameCell) nameCell.textContent = name;

        if (iconAvatar) {
            iconAvatar.className = `gal-icon-avatar ${category}`;
        }

        if (categoryBadge) {
            const catClass = `cat-${category}`;
            const catText = category.charAt(0).toUpperCase() + category.slice(1);
            categoryBadge.className = `category-badge ${catClass}`;
            categoryBadge.textContent = catText;
        }

        if (artworkCount) {
            artworkCount.innerHTML = `<i class="fas fa-palette me-1 text-muted"></i>${artworks}`;
        }

        if (staffCount) {
            staffCount.innerHTML = `<i class="fas fa-user-tie me-1 text-muted"></i>${staff}`;
        }

        if (artistsList) {
            const artistsArray = artistsStr.split(',').map(a => a.trim()).filter(a => a);
            const displayArtists = artistsArray.slice(0, 3);
            const moreCount = artistsArray.length > 3 ? artistsArray.length - 3 : 0;

            let artistsHTML = '';
            displayArtists.forEach(artist => {
                artistsHTML += `<span class="artist-tag">${escapeHtml(artist)}</span>`;
            });
            if (moreCount > 0) {
                artistsHTML += `<span class="artist-more">+${moreCount}</span>`;
            }
            artistsList.innerHTML = artistsHTML;
        }

        if (deleteBtn) deleteBtn.setAttribute('data-galerie-name', name);

        // Flash animation
        row.style.animation = 'none';
        row.offsetHeight; // trigger reflow
        row.style.animation = 'galerieDetailExpand 0.35s ease';
    }


    /* ─── DELETE FLOW ────────────────────────────────────────── */
    if (tableBody) {
        tableBody.addEventListener('click', (e) => {
            const deleteBtn = e.target.closest('.delete-galerie-btn');
            if (!deleteBtn) return;

            currentDeleteId = deleteBtn.getAttribute('data-galerie-id');
            const name = deleteBtn.getAttribute('data-galerie-name') || 'this gallery';
            deleteNameEl.textContent = name;
            deleteOverlay.classList.add('show');
        });
    }

    if (cancelDeleteBtn) {
        cancelDeleteBtn.addEventListener('click', () => {
            deleteOverlay.classList.remove('show');
            currentDeleteId = null;
        });
    }

    if (confirmDeleteBtn) {
        confirmDeleteBtn.addEventListener('click', () => {
            if (!currentDeleteId) return;

            const row = tableBody.querySelector(`tr[data-galerie-id="${currentDeleteId}"]`);
            if (row) {
                const name = row.querySelector('.gal-name')?.textContent || 'Gallery';
                row.classList.add('galerie-row-removing');
                row.addEventListener('animationend', () => row.remove());
                showToast('success', `Gallery "${name}" deleted successfully.`);
            }

            deleteOverlay.classList.remove('show');
            currentDeleteId = null;
        });
    }

    // Close overlay on background click
    if (deleteOverlay) {
        deleteOverlay.addEventListener('click', (e) => {
            if (e.target === deleteOverlay) {
                deleteOverlay.classList.remove('show');
                currentDeleteId = null;
            }
        });
    }


    /* ─── VIEW GALERIE DETAIL ────────────────────────────────── */
    if (tableBody) {
        tableBody.addEventListener('click', (e) => {
            const viewBtn = e.target.closest('.view-galerie-btn');
            if (!viewBtn) return;

            const row = viewBtn.closest('tr');
            if (!row) return;

            // Toggle: if detail row already exists, remove it
            const nextRow = row.nextElementSibling;
            if (nextRow && nextRow.classList.contains('galerie-detail-row')) {
                nextRow.remove();
                return;
            }

            // Get data from row
            const galerieId = row.querySelector('.gal-id')?.textContent || '-';
            const name = row.querySelector('.gal-name')?.textContent || '-';
            const category = row.querySelector('.category-badge')?.textContent.replace(/\s+/g, ' ').trim() || '-';
            const artworks = row.querySelector('.gal-artwork-count')?.textContent.replace(/[^\d]/g, '') || '0';
            const staff = row.querySelector('.gal-staff-count')?.textContent.replace(/[^\d]/g, '') || '0';

            const artistTags = row.querySelectorAll('.artist-tag');
            const artists = Array.from(artistTags).map(tag => tag.textContent.trim()).join(', ') || '-';

            const colCount = row.children.length;
            const detailTr = document.createElement('tr');
            detailTr.classList.add('galerie-detail-row');
            detailTr.innerHTML = `
                <td colspan="${colCount}">
                    <div class="galerie-detail-content">
                        <div class="galerie-detail-grid">
                            <div class="galerie-detail-item">
                                <span class="galerie-detail-label">Galerie ID</span>
                                <span class="galerie-detail-value">${galerieId}</span>
                            </div>
                            <div class="galerie-detail-item">
                                <span class="galerie-detail-label">Name</span>
                                <span class="galerie-detail-value">${escapeHtml(name)}</span>
                            </div>
                            <div class="galerie-detail-item">
                                <span class="galerie-detail-label">Category</span>
                                <span class="galerie-detail-value">${category}</span>
                            </div>
                            <div class="galerie-detail-item">
                                <span class="galerie-detail-label">Available Artworks</span>
                                <span class="galerie-detail-value">${artworks}</span>
                            </div>
                            <div class="galerie-detail-item">
                                <span class="galerie-detail-label">Staff Members</span>
                                <span class="galerie-detail-value">${staff}</span>
                            </div>
                            <div class="galerie-detail-item">
                                <span class="galerie-detail-label">Featured Artists</span>
                                <span class="galerie-detail-value">${artists}</span>
                            </div>
                        </div>
                    </div>
                </td>
            `;
            row.after(detailTr);
        });
    }


    /* ─── SEARCH & FILTER ────────────────────────────────────── */
    function filterTable() {
        if (!tableBody) return;
        const query = searchInput ? searchInput.value.toLowerCase().trim() : '';
        const categoryVal = categoryFilter ? categoryFilter.value.toLowerCase() : '';

        const rows = tableBody.querySelectorAll('tr:not(.galerie-detail-row)');
        let visible = 0;

        rows.forEach(row => {
            const text = row.textContent.toLowerCase();
            const rowCategory = row.getAttribute('data-category') || '';

            const matchesSearch = !query || text.includes(query);
            const matchesCategory = !categoryVal || rowCategory === categoryVal;

            if (matchesSearch && matchesCategory) {
                row.style.display = '';
                visible++;
            } else {
                row.style.display = 'none';
                // Remove any open detail for hidden rows
                const next = row.nextElementSibling;
                if (next && next.classList.contains('galerie-detail-row')) next.remove();
            }
        });

        // Update footer text
        const footerInfo = document.querySelector('.table-footer-info');
        if (footerInfo) {
            footerInfo.innerHTML = `Showing <strong>1–${visible}</strong> of <strong>${rows.length}</strong> galleries`;
        }
    }

    if (searchInput) searchInput.addEventListener('input', filterTable);
    if (categoryFilter) categoryFilter.addEventListener('change', filterTable);


    /* ─── SELECT ALL CHECKBOX ────────────────────────────────── */
    if (selectAllCb) {
        selectAllCb.addEventListener('change', () => {
            const checks = tableBody.querySelectorAll('.galerie-check');
            checks.forEach(cb => { cb.checked = selectAllCb.checked; });
        });
    }


    /* ─── TOAST HELPER ───────────────────────────────────────── */
    function showToast(type, message) {
        const toastMap = {
            success: { id: 'galerieToastSuccess', msgId: 'galerieToastSuccessMsg' },
            error:   { id: 'galerieToastError',   msgId: 'galerieToastErrorMsg' },
            warning: { id: 'galerieToastWarning', msgId: 'galerieToastWarningMsg' }
        };

        const t = toastMap[type];
        if (!t) return;

        const el = document.getElementById(t.id);
        const msgEl = document.getElementById(t.msgId);
        if (!el) return;

        if (msgEl) msgEl.textContent = message;

        // Reset progress bar animation
        const bar = el.querySelector('.toast-progress-bar');
        if (bar) {
            bar.style.animation = 'none';
            bar.offsetHeight;
            bar.style.animation = '';
        }

        const toast = new bootstrap.Toast(el);
        toast.show();
    }


    /* ─── FORM RESET ─────────────────────────────────────────── */
    function resetForm() {
        if (galerieForm) {
            galerieForm.reset();
            galerieForm.querySelectorAll('.is-invalid, .is-valid').forEach(el => {
                el.classList.remove('is-invalid', 'is-valid');
            });
        }
        if (fId) fId.value = '';
        if (fGalerieId) fGalerieId.value = '';
    }


    /* ─── UTILITY: Escape HTML ───────────────────────────────── */
    function escapeHtml(str) {
        const div = document.createElement('div');
        div.appendChild(document.createTextNode(str));
        return div.innerHTML;
    }


    /* ─── LIVE VALIDATION (remove invalid on input) ──────────── */
    const allInputs = galerieForm ? galerieForm.querySelectorAll('input, select, textarea') : [];
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


    /* ─── Bootstrap Tooltips ─────────────────────────────────── */
    const tooltipTriggers = document.querySelectorAll('[data-bs-toggle="tooltip"]');
    tooltipTriggers.forEach(el => new bootstrap.Tooltip(el));

    console.log('✅ Galeries Management System Initialized');
    console.log('Modal:', galerieModal ? 'Found' : 'Not Found');
    console.log('Add Button:', addGalerieBtn ? 'Found' : 'Not Found');
    console.log('Table Body:', tableBody ? 'Found' : 'Not Found');
    console.log('Total Rows:', galerieCounter);

});
