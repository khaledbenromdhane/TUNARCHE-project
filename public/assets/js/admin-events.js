/* ═══════════════════════════════════════════════════════════════
   ADMIN ÉVÉNEMENTS MANAGEMENT — JavaScript
   Form-based CRUD (no AJAX/JSON). Handles modal form switching,
   delete confirmation, search/filter, counter animations, view detail.
   ═══════════════════════════════════════════════════════════════ */

document.addEventListener('DOMContentLoaded', () => {

    /* ─── ROUTE BASES (used by JS to set form action) ────────── */
    const STORE_URL  = '/admin/evenement/store';
    const UPDATE_URL = '/admin/evenement';  // + /{id}/update

    /* ─── DOM References ─────────────────────────────────────── */
    const eventsTable     = document.getElementById('eventsTable');
    const tableBody       = document.getElementById('eventsTableBody');
    const eventModal      = document.getElementById('eventModal');
    const eventForm       = document.getElementById('eventForm');
    const addEventBtn     = document.getElementById('addEventBtn');
    const saveEventBtn    = document.getElementById('saveEventBtn');
    const saveEventText   = document.getElementById('saveEventBtnText');
    const modalTitle      = document.getElementById('eventModalLabel');
    const modalSubtitle   = document.getElementById('evtModalSubtitle');
    const modalIcon       = document.getElementById('evtModalIcon');
    const selectAllCb     = document.getElementById('selectAllEvents');

    // Form fields
    const fId           = document.getElementById('formEventId');
    const fEvtId        = document.getElementById('inputEvtId');
    const fNom          = document.getElementById('inputEvtNom');
    const fType         = document.getElementById('inputEvtType');
    const fParticipants = document.getElementById('inputEvtParticipants');
    const fDate         = document.getElementById('inputEvtDate');
    const fHeure        = document.getElementById('inputEvtHeure');
    const fLieu         = document.getElementById('inputEvtLieu');
    const fDesc         = document.getElementById('inputEvtDesc');
    const fPaiement     = document.getElementById('inputEvtPaiement');
    const paiementText  = document.getElementById('paiementStatusText');

    // Delete alert
    const deleteOverlay   = document.getElementById('deleteEvtAlertOverlay');
    const deleteNameEl    = document.getElementById('deleteEvtName');
    const cancelDeleteBtn = document.getElementById('cancelEvtDeleteBtn');
    const deleteForm      = document.getElementById('deleteEvtForm');

    // Bootstrap modal instance
    let bsModal = null;
    if (eventModal) {
        bsModal = new bootstrap.Modal(eventModal);
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


    /* ─── PAIEMENT TOGGLE TEXT ───────────────────────────────── */
    if (fPaiement) {
        fPaiement.addEventListener('change', () => {
            paiementText.textContent = fPaiement.checked ? 'Paid' : 'Free';
        });
    }


    /* ─── MODAL: ADD MODE ────────────────────────────────────── */
    if (addEventBtn) {
        addEventBtn.addEventListener('click', () => {
            resetForm();
            modalTitle.textContent = 'Add Événement';
            modalSubtitle.textContent = 'Fill in the details to create a new event';
            modalIcon.className = 'fas fa-calendar-plus';
            saveEventText.textContent = 'Save Event';
            fEvtId.value = 'Auto-generated';
            fId.value = '';

            // Set form action to STORE route
            eventForm.action = STORE_URL;

            // Hide image preview
            const imgPreview = document.getElementById('currentImagePreview');
            if (imgPreview) imgPreview.style.display = 'none';
            const imgInput = document.getElementById('inputEvtImage');
            if (imgInput) imgInput.value = '';
        });
    }


    /* ─── MODAL: EDIT MODE ───────────────────────────────────── */
    if (tableBody) {
        tableBody.addEventListener('click', (e) => {
            const editBtn = e.target.closest('.edit-evt-btn');
            if (!editBtn) return;

            const row = editBtn.closest('tr');
            if (!row) return;

            const eventId = row.getAttribute('data-event-id');

            modalTitle.textContent = 'Edit Événement';
            modalSubtitle.textContent = 'Modify the event details below';
            modalIcon.className = 'fas fa-pen-to-square';
            saveEventText.textContent = 'Update Event';

            // Populate form from row data attributes
            fId.value             = eventId;
            fEvtId.value          = row.querySelector('.evt-id')?.textContent.trim() || '';
            fNom.value            = row.getAttribute('data-nom') || '';
            fType.value           = row.getAttribute('data-type') || '';
            fParticipants.value   = row.getAttribute('data-nbr-participant') || '';
            fDate.value           = row.getAttribute('data-date') || '';
            fHeure.value          = row.getAttribute('data-heure') || '';
            fLieu.value           = row.getAttribute('data-lieu') || '';
            fDesc.value           = row.getAttribute('data-description') || '';

            const isPaid = row.getAttribute('data-paiement') === 'true';
            fPaiement.checked = isPaid;
            paiementText.textContent = isPaid ? 'Paid' : 'Free';

            // Handle image preview
            const imageVal = row.getAttribute('data-image') || '';
            const imgPreview = document.getElementById('currentImagePreview');
            const imgEl = document.getElementById('currentImageImg');
            const imgInput = document.getElementById('inputEvtImage');
            if (imgInput) imgInput.value = '';
            if (imageVal && imgPreview && imgEl) {
                imgEl.src = '/uploads/evenements/' + imageVal;
                imgPreview.style.display = 'block';
            } else if (imgPreview) {
                imgPreview.style.display = 'none';
            }

            // Set form action to UPDATE route for this event
            eventForm.action = `${UPDATE_URL}/${eventId}/update`;

            // Clear validation states
            clearValidationStates();

            bsModal.show();
        });
    }


    /* ─── CLEAR VALIDATION STATES ────────────────────────────── */
    function clearValidationStates() {
        if (!eventForm) return;
        eventForm.querySelectorAll('.is-invalid, .is-valid').forEach(el => {
            el.classList.remove('is-invalid', 'is-valid');
        });
        eventForm.querySelectorAll('.invalid-feedback').forEach(el => {
            el.textContent = el.getAttribute('data-default') || el.textContent;
        });
    }


    /* ─── CLIENT-SIDE VALIDATION (mirrors server contrôle de saisie) ── */

    const VALID_TYPES = [
        'Concerts', "Expositions d'art", 'Festivals',
        'Spectacles de danse', 'Théâtre', 'Tournois', 'Formations'
    ];

    /**
     * Set a field as invalid with a custom message shown in .invalid-feedback
     */
    function setFieldError(field, message) {
        field.classList.add('is-invalid');
        field.classList.remove('is-valid');
        const feedback = field.closest('.form-floating-custom')?.querySelector('.invalid-feedback');
        if (feedback) feedback.textContent = message;
    }

    /**
     * Set a field as valid
     */
    function setFieldValid(field) {
        field.classList.remove('is-invalid');
        field.classList.add('is-valid');
    }

    /**
     * Full validation matching EvenementService::validate() rules.
     * Returns true if valid, false otherwise. Shows errors on each field.
     */
    function validateForm() {
        let isValid = true;
        const isEditMode = fId.value && fId.value.trim() !== '';

        // ── Nom ──
        const nom = fNom.value.trim();
        if (!nom) {
            setFieldError(fNom, "Le nom de l'événement est obligatoire.");
            isValid = false;
        } else if (nom.length < 3) {
            setFieldError(fNom, 'Le nom doit contenir au moins 3 caractères.');
            isValid = false;
        } else if (nom.length > 255) {
            setFieldError(fNom, 'Le nom ne doit pas dépasser 255 caractères.');
            isValid = false;
        } else if (!/^[a-zA-ZÀ-ÿ0-9\s\-'&.,]+$/u.test(nom)) {
            setFieldError(fNom, 'Le nom contient des caractères non autorisés.');
            isValid = false;
        } else {
            setFieldValid(fNom);
        }

        // ── Type événement ──
        const type = fType.value;
        if (!type) {
            setFieldError(fType, "Le type d'événement est obligatoire.");
            isValid = false;
        } else if (!VALID_TYPES.includes(type)) {
            setFieldError(fType, "Type d'événement invalide.");
            isValid = false;
        } else {
            setFieldValid(fType);
        }

        // ── Nombre de participants ──
        const nbr = fParticipants.value.trim();
        if (!nbr) {
            setFieldError(fParticipants, 'Le nombre de participants est obligatoire.');
            isValid = false;
        } else if (isNaN(nbr) || !Number.isInteger(Number(nbr))) {
            setFieldError(fParticipants, 'Le nombre de participants doit être un nombre entier.');
            isValid = false;
        } else if (parseInt(nbr) < 1) {
            setFieldError(fParticipants, 'Le nombre de participants doit être au moins 1.');
            isValid = false;
        } else if (parseInt(nbr) > 100000) {
            setFieldError(fParticipants, 'Le nombre de participants ne peut pas dépasser 100 000.');
            isValid = false;
        } else {
            setFieldValid(fParticipants);
        }

        // ── Date (skip past-date check in edit mode) ──
        const dateVal = fDate.value;
        if (!dateVal) {
            setFieldError(fDate, "La date de l'événement est obligatoire.");
            isValid = false;
        } else {
            const dateObj = new Date(dateVal + 'T00:00:00');
            if (isNaN(dateObj.getTime())) {
                setFieldError(fDate, "La date n'est pas valide (format attendu : AAAA-MM-JJ).");
                isValid = false;
            } else if (!isEditMode) {
                const today = new Date();
                today.setHours(0, 0, 0, 0);
                if (dateObj < today) {
                    setFieldError(fDate, "La date de l'événement doit être aujourd'hui ou dans le futur.");
                    isValid = false;
                } else {
                    setFieldValid(fDate);
                }
            } else {
                setFieldValid(fDate);
            }
        }

        // ── Heure ──
        const heureVal = fHeure.value;
        if (!heureVal) {
            setFieldError(fHeure, "L'heure de l'événement est obligatoire.");
            isValid = false;
        } else if (!/^\d{2}:\d{2}$/.test(heureVal)) {
            setFieldError(fHeure, "L'heure n'est pas valide (format attendu : HH:MM).");
            isValid = false;
        } else {
            setFieldValid(fHeure);
        }

        // ── Lieu ──
        const lieu = fLieu.value.trim();
        if (!lieu) {
            setFieldError(fLieu, "Le lieu de l'événement est obligatoire.");
            isValid = false;
        } else if (lieu.length < 3) {
            setFieldError(fLieu, 'Le lieu doit contenir au moins 3 caractères.');
            isValid = false;
        } else if (lieu.length > 255) {
            setFieldError(fLieu, 'Le lieu ne doit pas dépasser 255 caractères.');
            isValid = false;
        } else {
            setFieldValid(fLieu);
        }

        // ── Description ──
        const desc = fDesc.value.trim();
        if (!desc) {
            setFieldError(fDesc, 'La description est obligatoire.');
            isValid = false;
        } else if (desc.length < 10) {
            setFieldError(fDesc, 'La description doit contenir au moins 10 caractères.');
            isValid = false;
        } else if (desc.length > 2000) {
            setFieldError(fDesc, 'La description ne doit pas dépasser 2000 caractères.');
            isValid = false;
        } else {
            setFieldValid(fDesc);
        }

        return isValid;
    }

    if (eventForm) {
        eventForm.addEventListener('submit', (e) => {
            if (!validateForm()) {
                e.preventDefault(); // Block form submission — stay in modal
            }
        });
    }


    /* ─── DELETE FLOW (opens overlay, submits form) ──────────── */
    if (tableBody) {
        tableBody.addEventListener('click', (e) => {
            const deleteBtn = e.target.closest('.delete-evt-btn');
            if (!deleteBtn) return;

            const name = deleteBtn.getAttribute('data-event-name') || 'this event';
            const deleteUrl = deleteBtn.getAttribute('data-delete-url');

            deleteNameEl.textContent = name;
            deleteForm.action = deleteUrl;
            deleteOverlay.classList.add('show');
        });
    }

    if (cancelDeleteBtn) {
        cancelDeleteBtn.addEventListener('click', () => {
            deleteOverlay.classList.remove('show');
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


    /* ─── VIEW EVENT DETAIL (inline expand) ──────────────────── */
    if (tableBody) {
        tableBody.addEventListener('click', (e) => {
            const viewBtn = e.target.closest('.view-evt-btn');
            if (!viewBtn) return;

            const row = viewBtn.closest('tr');
            if (!row) return;

            // Toggle: if detail row already exists, remove it
            const nextRow = row.nextElementSibling;
            if (nextRow && nextRow.classList.contains('evt-detail-row')) {
                nextRow.remove();
                return;
            }

            // Remove any other open detail rows
            document.querySelectorAll('.evt-detail-row').forEach(r => r.remove());

            const nom          = row.getAttribute('data-nom') || '-';
            const type         = row.getAttribute('data-type') || '-';
            const participants = row.getAttribute('data-nbr-participant') || '-';
            const date         = row.getAttribute('data-date') || '-';
            const time         = row.getAttribute('data-heure') || '-';
            const lieu         = row.getAttribute('data-lieu') || '-';
            const desc         = row.getAttribute('data-description') || 'No description provided';
            const isPaid       = row.getAttribute('data-paiement') === 'true' ? 'Paid' : 'Free';
            const evtId        = row.querySelector('.evt-id')?.textContent || '-';
            const image        = row.getAttribute('data-image') || '';

            const colCount = row.children.length;
            const detailTr = document.createElement('tr');
            detailTr.classList.add('evt-detail-row');
            const imageHtml = image ? `<div class="evt-detail-item"><span class="evt-detail-label">Image</span><span class="evt-detail-value"><img src="/uploads/evenements/${escapeHtml(image)}" alt="Event" style="max-width:180px;max-height:120px;border-radius:8px;border:1px solid rgba(255,255,255,0.1);object-fit:cover;"></span></div>` : '';
            detailTr.innerHTML = `
                <td colspan="${colCount}">
                    <div class="evt-detail-content">
                        <div class="evt-detail-grid">
                            <div class="evt-detail-item">
                                <span class="evt-detail-label">Event ID</span>
                                <span class="evt-detail-value">${escapeHtml(evtId)}</span>
                            </div>
                            <div class="evt-detail-item">
                                <span class="evt-detail-label">Event Name</span>
                                <span class="evt-detail-value">${escapeHtml(nom)}</span>
                            </div>
                            <div class="evt-detail-item">
                                <span class="evt-detail-label">Type</span>
                                <span class="evt-detail-value">${escapeHtml(type)}</span>
                            </div>
                            <div class="evt-detail-item">
                                <span class="evt-detail-label">Participants</span>
                                <span class="evt-detail-value">${escapeHtml(participants)}</span>
                            </div>
                            <div class="evt-detail-item">
                                <span class="evt-detail-label">Date</span>
                                <span class="evt-detail-value">${escapeHtml(date)}</span>
                            </div>
                            <div class="evt-detail-item">
                                <span class="evt-detail-label">Time</span>
                                <span class="evt-detail-value">${escapeHtml(time)}</span>
                            </div>
                            <div class="evt-detail-item">
                                <span class="evt-detail-label">Location</span>
                                <span class="evt-detail-value">${escapeHtml(lieu)}</span>
                            </div>
                            <div class="evt-detail-item">
                                <span class="evt-detail-label">Payment</span>
                                <span class="evt-detail-value">${isPaid}</span>
                            </div>
                            <div class="evt-detail-item evt-detail-desc">
                                <span class="evt-detail-label">Description</span>
                                <span class="evt-detail-value">${escapeHtml(desc)}</span>
                            </div>
                            ${imageHtml}
                        </div>
                    </div>
                </td>
            `;
            row.after(detailTr);
        });
    }


    /* ─── SEARCH & FILTER: AJAX Dynamic Search ──────────────── */
    const adminSearchInput   = document.getElementById('adminEvtSearchInput');
    const adminTypeFilter    = document.getElementById('adminEvtTypeFilter');
    const adminPaiementFilter = document.getElementById('adminEvtPaiementFilter');
    const adminSearchBtn     = document.getElementById('adminEvtSearchBtn');
    const adminResetBtn      = document.getElementById('adminEvtResetBtn');
    const adminSortInput     = document.getElementById('adminEvtSort');
    const adminOrderInput    = document.getElementById('adminEvtOrder');
    const evtCountBadge      = document.getElementById('evtCountBadge');
    const exportEventsBtn    = document.getElementById('exportEventsBtn');
    let searchTimer;

    function doAdminEvtSearch() {
        const q        = adminSearchInput ? adminSearchInput.value.trim() : '';
        const type     = adminTypeFilter ? adminTypeFilter.value : '';
        const paiement = adminPaiementFilter ? adminPaiementFilter.value : '';
        const sort     = adminSortInput ? adminSortInput.value : 'date';
        const order    = adminOrderInput ? adminOrderInput.value : 'DESC';

        const params = new URLSearchParams({ q, type, paiement, sort, order });

        fetch('/admin/evenement/search?' + params.toString())
            .then(r => r.json())
            .then(data => {
                renderEvtRows(data.results);
                if (evtCountBadge) evtCountBadge.textContent = data.count;
                if (adminResetBtn) {
                    adminResetBtn.style.display = (q || type || paiement) ? 'inline-block' : 'none';
                }
                
                // Update URL with all current parameters
                const currentUrl = new URL(window.location);
                if (q) currentUrl.searchParams.set('q', q);
                else currentUrl.searchParams.delete('q');
                if (type) currentUrl.searchParams.set('type', type);
                else currentUrl.searchParams.delete('type');
                if (paiement) currentUrl.searchParams.set('paiement', paiement);
                else currentUrl.searchParams.delete('paiement');
                currentUrl.searchParams.set('sort', sort);
                currentUrl.searchParams.set('order', order);
                window.history.pushState({}, '', currentUrl);
            });
    }

    function renderEvtRows(events) {
        if (!tableBody) return;

        if (events.length === 0) {
            tableBody.innerHTML = '<tr><td colspan="10" class="text-center py-5"><i class="fas fa-calendar-xmark d-block mb-2" style="font-size:2rem;color:rgba(212,175,55,0.3);"></i><span style="color:rgba(255,255,255,0.4);">Aucun événement trouvé.</span></td></tr>';
            return;
        }

        let html = '';
        events.forEach(evt => {
            const paid = evt.paiement;
            const paidClass = paid ? 'true' : 'false';
            const paidBadge = paid
                ? '<span class="paiement-badge paid"><i class="fas fa-check-circle me-1"></i>Paid</span>'
                : '<span class="paiement-badge free"><i class="fas fa-gift me-1"></i>Free</span>';
            const typeSlug = (evt.typeEvenement || '').toLowerCase().replace(/ /g, '-').replace(/'/g, '');
            const imgHtml = evt.image
                ? `<img src="/uploads/evenements/${escapeHtml(evt.image)}" alt="${escapeHtml(evt.nom)}" style="width:36px;height:36px;border-radius:8px;object-fit:cover;border:1px solid rgba(255,255,255,0.1);">`
                : '<div class="evt-icon-mini gold"><i class="fas fa-calendar-star"></i></div>';
            const desc = evt.description || '';
            const descHint = desc.length > 40 ? desc.substring(0, 40) + '…' : desc;

            html += `<tr data-event-id="${evt.id}"
                data-type="${escapeHtml(evt.typeEvenement)}"
                data-paiement="${paidClass}"
                data-nom="${escapeHtml(evt.nom)}"
                data-date="${evt.date || ''}"
                data-heure="${evt.heure || ''}"
                data-lieu="${escapeHtml(evt.lieu)}"
                data-description="${escapeHtml(desc)}"
                data-nbr-participant="${evt.nbrParticipant}"
                data-image="${evt.image || ''}">
                <td><input type="checkbox" class="form-check-input evt-check"></td>
                <td class="evt-id">#EVT-${String(evt.id).padStart(3, '0')}</td>
                <td><div class="evt-name-cell">${imgHtml}<div><span class="evt-name">${escapeHtml(evt.nom)}</span><span class="evt-desc-hint">${escapeHtml(descHint)}</span></div></div></td>
                <td><span class="type-badge type-${typeSlug}">${escapeHtml(evt.typeEvenement)}</span></td>
                <td><span class="participant-count"><i class="fas fa-users me-1"></i>${evt.nbrParticipant}</span></td>
                <td><span class="evt-date"><i class="fas fa-calendar-day me-1 text-muted"></i>${escapeHtml(evt.dateFmt || '')}</span></td>
                <td><span class="evt-time"><i class="fas fa-clock me-1 text-muted"></i>${escapeHtml(evt.heure || '')}</span></td>
                <td><span class="evt-lieu"><i class="fas fa-map-marker-alt me-1 text-muted"></i>${escapeHtml(evt.lieu)}</span></td>
                <td>${paidBadge}</td>
                <td><div class="table-actions">
                    <button type="button" class="table-action-btn view-evt-btn" title="View" data-event-id="${evt.id}"><i class="fas fa-eye"></i></button>
                    <button type="button" class="table-action-btn edit-evt-btn" title="Edit" data-event-id="${evt.id}"><i class="fas fa-pen-to-square"></i></button>
                    <button type="button" class="table-action-btn delete delete-evt-btn" title="Delete" data-event-id="${evt.id}" data-event-name="${escapeHtml(evt.nom)}" data-delete-url="/admin/evenement/${evt.id}/delete"><i class="fas fa-trash-can"></i></button>
                </div></td>
            </tr>`;
        });

        tableBody.innerHTML = html;
    }

    // Debounced live search on input
    if (adminSearchInput) {
        adminSearchInput.addEventListener('input', () => {
            clearTimeout(searchTimer);
            searchTimer = setTimeout(doAdminEvtSearch, 350);
        });
    }

    // Instant search on filter changes
    if (adminTypeFilter) adminTypeFilter.addEventListener('change', doAdminEvtSearch);
    if (adminPaiementFilter) adminPaiementFilter.addEventListener('change', doAdminEvtSearch);
    if (adminSearchBtn) adminSearchBtn.addEventListener('click', doAdminEvtSearch);

    // Reset filters
    if (adminResetBtn) {
        adminResetBtn.addEventListener('click', () => {
            if (adminSearchInput) adminSearchInput.value = '';
            if (adminTypeFilter) adminTypeFilter.value = '';
            if (adminPaiementFilter) adminPaiementFilter.value = '';
            doAdminEvtSearch();
        });
    }

    // Export events CSV
    if (exportEventsBtn) {
        exportEventsBtn.addEventListener('click', () => {
            window.location.href = '/admin/evenement/export';
        });
    }

    /* ─── AJAX COLUMN SORTING ────────────────────────────────── */
    document.querySelectorAll('#eventsTable thead th[data-sort-key]').forEach(th => {
        th.style.cursor = 'pointer';
        th.addEventListener('click', () => {
            const key = th.getAttribute('data-sort-key');
            const currentSort = adminSortInput ? adminSortInput.value : 'date';
            const currentOrder = adminOrderInput ? adminOrderInput.value : 'DESC';

            let newOrder;
            if (currentSort === key) {
                newOrder = currentOrder === 'ASC' ? 'DESC' : 'ASC';
            } else {
                newOrder = (key === 'nom' || key === 'typeEvenement' || key === 'lieu') ? 'ASC' : 'DESC';
            }

            if (adminSortInput) adminSortInput.value = key;
            if (adminOrderInput) adminOrderInput.value = newOrder;

            // Update sort icons in all headers
            document.querySelectorAll('#eventsTable thead th[data-sort-key]').forEach(h => {
                const icon = h.querySelector('.sort-icon');
                const hKey = h.getAttribute('data-sort-key');
                if (icon) {
                    if (hKey === key) {
                        icon.className = 'fas fa-sort-' + (newOrder === 'ASC' ? 'up' : 'down') + ' sort-icon';
                        icon.style.cssText = 'font-size:0.7rem;color:#a855f7;';
                    } else {
                        icon.className = 'fas fa-sort sort-icon';
                        icon.style.cssText = 'font-size:0.7rem;opacity:0.4;';
                    }
                }
            });

            doAdminEvtSearch();
            
            // Update URL with sort parameters
            const currentUrl = new URL(window.location);
            currentUrl.searchParams.set('sort', key);
            currentUrl.searchParams.set('order', newOrder);
            window.history.pushState({}, '', currentUrl);
        });
    });


    /* ─── SELECT ALL CHECKBOX ────────────────────────────────── */
    if (selectAllCb) {
        selectAllCb.addEventListener('change', () => {
            const checks = tableBody.querySelectorAll('.evt-check');
            checks.forEach(cb => { cb.checked = selectAllCb.checked; });
        });
    }


    /* ─── FORM RESET ─────────────────────────────────────────── */
    function resetForm() {
        if (eventForm) eventForm.reset();
        fId.value = '';
        fEvtId.value = '';
        fPaiement.checked = false;
        paiementText.textContent = 'Free';
        clearValidationStates();
    }


    /* ─── UTILITY: Escape HTML ───────────────────────────────── */
    function escapeHtml(str) {
        const div = document.createElement('div');
        div.appendChild(document.createTextNode(str));
        return div.innerHTML;
    }


    /* ─── LIVE VALIDATION (remove invalid on input) ──────────── */
    const allInputs = eventForm ? eventForm.querySelectorAll('.modal-input') : [];
    allInputs.forEach(input => {
        input.addEventListener('input', () => {
            if (input.value.trim()) {
                input.classList.remove('is-invalid');
                input.classList.add('is-valid');
            }
        });
    });


    /* ─── AUTO-DISMISS FLASH ALERTS after 4s ─────────────────── */
    document.querySelectorAll('.alert-dismissible').forEach(alert => {
        setTimeout(() => {
            const bsAlert = bootstrap.Alert.getOrCreateInstance(alert);
            bsAlert.close();
        }, 4000);
    });


    /* ─── Bootstrap Tooltips ─────────────────────────────────── */
    const tooltipTriggers = document.querySelectorAll('[data-bs-toggle="tooltip"]');
    tooltipTriggers.forEach(el => new bootstrap.Tooltip(el));

});
