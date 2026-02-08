/**
 * ═══════════════════════════════════════════════════════════════
 * PUBLICATIONS MANAGEMENT - Interactive Features
 * – Counter animations
 * – Image upload with preview
 * – CRUD operations
 * – Search & Filter
 * ═══════════════════════════════════════════════════════════════
 */

document.addEventListener('DOMContentLoaded', function() {
    // ───────────────────────────────────────────────────────────
    // COUNTER ANIMATIONS
    // ───────────────────────────────────────────────────────────
    function animateCounter(element, target) {
        let current = 0;
        const increment = target / 100;
        const timer = setInterval(() => {
            current += increment;
            if (current >= target) {
                element.textContent = target.toLocaleString();
                clearInterval(timer);
            } else {
                element.textContent = Math.ceil(current).toLocaleString();
            }
        }, 10);
    }

    // Animate stat counters on page load
    const statValues = document.querySelectorAll('.pub-stat-value');
    statValues.forEach(stat => {
        const target = parseInt(stat.textContent.replace(/,/g, ''));
        animateCounter(stat, target);
    });

    // ───────────────────────────────────────────────────────────
    // IMAGE UPLOAD WITH PREVIEW
    // ───────────────────────────────────────────────────────────
    const imageInput = document.getElementById('publicationImage');
    const imagePreviewContainer = document.getElementById('imagePreviewContainer');
    const imagePreview = document.getElementById('imagePreview');
    const removeImageBtn = document.getElementById('removeImageBtn');
    const uploadPlaceholder = document.getElementById('uploadPlaceholder');

    let currentImageFile = null;

    // File input change event
    if (imageInput) {
        imageInput.addEventListener('change', function(e) {
            const file = e.target.files[0];
            if (file) {
                handleImageUpload(file);
            }
        });

        // Drag and drop support
        const uploadArea = imageInput.parentElement;
        
        uploadArea.addEventListener('dragover', function(e) {
            e.preventDefault();
            uploadArea.style.borderColor = 'rgba(212, 175, 55, 0.8)';
            uploadArea.style.backgroundColor = 'rgba(212, 175, 55, 0.1)';
        });

        uploadArea.addEventListener('dragleave', function(e) {
            e.preventDefault();
            uploadArea.style.borderColor = '';
            uploadArea.style.backgroundColor = '';
        });

        uploadArea.addEventListener('drop', function(e) {
            e.preventDefault();
            uploadArea.style.borderColor = '';
            uploadArea.style.backgroundColor = '';
            
            const file = e.dataTransfer.files[0];
            if (file && file.type.startsWith('image/')) {
                imageInput.files = e.dataTransfer.files;
                handleImageUpload(file);
            } else {
                showToast('error', 'Please select a valid image file');
            }
        });
    }

    // Handle image upload and preview
    function handleImageUpload(file) {
        // Validate file type
        if (!file.type.startsWith('image/')) {
            showToast('error', 'Please select a valid image file (PNG, JPG, GIF)');
            return;
        }

        // Validate file size (10MB limit)
        const maxSize = 10 * 1024 * 1024; // 10MB in bytes
        if (file.size > maxSize) {
            showToast('error', 'File size must be less than 10MB');
            return;
        }

        // Read and display image preview
        const reader = new FileReader();
        reader.onload = function(e) {
            imagePreview.src = e.target.result;
            imagePreviewContainer.style.display = 'flex';
            uploadPlaceholder.style.display = 'none';
            currentImageFile = file;
        };
        reader.readAsDataURL(file);
    }

    // Remove image button
    if (removeImageBtn) {
        removeImageBtn.addEventListener('click', function(e) {
            e.stopPropagation();
            clearImagePreview();
        });
    }

    // Clear image preview
    function clearImagePreview() {
        if (imageInput) imageInput.value = '';
        if (imagePreview) imagePreview.src = '';
        if (imagePreviewContainer) imagePreviewContainer.style.display = 'none';
        if (uploadPlaceholder) uploadPlaceholder.style.display = 'flex';
        currentImageFile = null;
    }

    // ───────────────────────────────────────────────────────────
    // MODAL MANAGEMENT
    // ───────────────────────────────────────────────────────────
    const publicationModal = new bootstrap.Modal(document.getElementById('publicationModal'));
    const publicationForm = document.getElementById('publicationForm');
    const modalTitle = document.getElementById('modalTitle');
    const modalSubtitle = document.getElementById('modalSubtitle');
    const publicationIdInput = document.getElementById('publicationId');
    const titleInput = document.getElementById('publicationTitre');
    const descriptionInput = document.getElementById('publicationDescription');
    const dateInput = document.getElementById('publicationDate');

    let isEditMode = false;
    let editingPublicationId = null;

    // Add new publication button
    const addPublicationBtn = document.getElementById('addPublicationBtn');
    if (addPublicationBtn) {
        addPublicationBtn.addEventListener('click', function() {
            openModalForAdd();
        });
    }

    // Open modal for adding new publication
    function openModalForAdd() {
        isEditMode = false;
        editingPublicationId = null;
        
        if (modalTitle) modalTitle.textContent = 'Ajouter une Publication';
        if (modalSubtitle) modalSubtitle.textContent = 'Créer une nouvelle publication';
        
        publicationForm.reset();
        if (publicationIdInput) publicationIdInput.value = '';
        clearImagePreview();
        
        publicationModal.show();
    }

    // Open modal for editing publication
    window.editPublication = function(id) {
        isEditMode = true;
        editingPublicationId = id;
        
        const row = document.querySelector(`tr[data-id="${id}"]`);
        if (!row) {
            showToast('error', 'Publication introuvable');
            return;
        }
        
        // Extract data from row
        const title = row.querySelector('.pub-title')?.textContent || '';
        const description = row.dataset.description || '';
        const date = row.dataset.date || '';
        const imageUrl = row.querySelector('.pub-thumbnail')?.src || '';
        
        // Populate form
        if (modalTitle) modalTitle.textContent = 'Modifier la Publication';
        if (modalSubtitle) modalSubtitle.textContent = 'Mettre à jour les informations';
        if (publicationIdInput) publicationIdInput.value = id;
        if (titleInput) titleInput.value = title;
        if (descriptionInput) descriptionInput.value = description;
        if (dateInput) dateInput.value = date;
        
        // Show existing image if available
        if (imageUrl && imageUrl !== '') {
            imagePreview.src = imageUrl;
            imagePreviewContainer.style.display = 'flex';
            uploadPlaceholder.style.display = 'none';
        } else {
            clearImagePreview();
        }
        
        publicationModal.show();
    };

    // View publication details
    window.viewPublication = function(id) {
        const row = document.querySelector(`tr[data-id="${id}"]`);
        if (!row) {
            showToast('error', 'Publication introuvable');
            return;
        }
        
        const title = row.querySelector('.pub-title')?.textContent || '';
        const description = row.dataset.description || '';
        const date = row.dataset.date || '';
        
        // For now, just show in modal (you can create a separate view modal)
        editPublication(id);
    };

    // Form submission
    if (publicationForm) {
        publicationForm.addEventListener('submit', function(e) {
            e.preventDefault();
            
            const formData = {
                id: publicationIdInput?.value || null,
                titre: titleInput?.value.trim() || '',
                description: descriptionInput?.value.trim() || '',
                date_act: dateInput?.value || '',
                image: currentImageFile
            };
            
            // Validate
            if (!formData.titre) {
                showToast('warning', 'Veuillez saisir un titre');
                return;
            }
            
            if (!formData.description) {
                showToast('warning', 'Veuillez saisir une description');
                return;
            }
            
            if (!formData.date_act) {
                showToast('warning', 'Veuillez sélectionner une date');
                return;
            }
            
            if (isEditMode) {
                updatePublication(editingPublicationId, formData);
            } else {
                addPublication(formData);
            }
        });
    }

    // Add publication
    function addPublication(data) {
        // In real application, send AJAX request to server
        console.log('Adding publication:', data);
        
        // Generate new ID
        const tableBody = document.getElementById('publicationsTableBody');
        const rows = tableBody.querySelectorAll('tr');
        const newId = rows.length > 0 
            ? Math.max(...Array.from(rows).map(r => parseInt(r.dataset.id))) + 1 
            : 1;
        
        // Create image URL (in real app, this would be returned from server)
        let imageUrl = '';
        if (data.image) {
            // Use FileReader result as preview (in real app, upload to server first)
            imageUrl = imagePreview.src;
        }
        
        // Create new row
        const newRow = createPublicationRow({
            id: newId,
            titre: data.titre,
            description: data.description,
            date_act: data.date_act,
            image: imageUrl
        });
        
        tableBody.insertBefore(newRow, tableBody.firstChild);
        newRow.classList.add('animate-in');
        
        // Update counters
        updateCounters('add');
        
        publicationModal.hide();
        publicationForm.reset();
        clearImagePreview();
        showToast('success', 'Publication ajoutée avec succès');
    }

    // Update publication
    function updatePublication(id, data) {
        console.log('Updating publication:', id, data);
        
        const row = document.querySelector(`tr[data-id="${id}"]`);
        if (!row) {
            showToast('error', 'Publication introuvable');
            return;
        }
        
        // Update row data
        row.dataset.description = data.description;
        row.dataset.date = data.date_act;
        
        // Update title
        const titleCell = row.querySelector('.pub-title');
        if (titleCell) titleCell.textContent = data.titre;
        
        // Update description in cell
        const descCell = row.querySelector('.pub-description');
        if (descCell) descCell.textContent = data.description;
        
        // Update date
        const dateCell = row.querySelector('.pub-date');
        if (dateCell) {
            const dateParts = data.date_act.split('-');
            const formattedDate = `${dateParts[2]}/${dateParts[1]}/${dateParts[0]}`;
            dateCell.innerHTML = `<i class="fas fa-calendar-alt me-1"></i>${formattedDate}`;
        }
        
        // Update image if changed
        if (currentImageFile) {
            const imgElement = row.querySelector('.pub-thumbnail');
            if (imgElement) {
                imgElement.src = imagePreview.src;
            }
        }
        
        row.classList.add('table-warning');
        setTimeout(() => row.classList.remove('table-warning'), 1000);
        
        publicationModal.hide();
        publicationForm.reset();
        clearImagePreview();
        showToast('success', 'Publication mise à jour avec succès');
    }

    // Create publication row
    function createPublicationRow(pub) {
        const row = document.createElement('tr');
        row.dataset.id = pub.id;
        row.dataset.description = pub.description;
        row.dataset.date = pub.date_act;
        
        const dateParts = pub.date_act.split('-');
        const formattedDate = `${dateParts[2]}/${dateParts[1]}/${dateParts[0]}`;
        
        const imageHtml = pub.image 
            ? `<div class="pub-image-container">
                 <img src="${pub.image}" alt="${pub.titre}" class="pub-thumbnail">
               </div>`
            : `<div class="pub-image-container">
                 <div class="d-flex align-items-center justify-content-center h-100 text-muted">
                   <i class="fas fa-image"></i>
                 </div>
               </div>`;
        
        row.innerHTML = `
            <td>
                <div class="form-check">
                    <input class="form-check-input" type="checkbox" value="${pub.id}">
                </div>
            </td>
            <td>
                <span class="pub-id">#${pub.id}</span>
            </td>
            <td>${imageHtml}</td>
            <td>
                <div class="pub-title-cell">
                    <i class="fas fa-star text-warning"></i>
                    <span class="pub-title">${pub.titre}</span>
                </div>
            </td>
            <td>
                <span class="pub-description">${pub.description}</span>
            </td>
            <td>
                <span class="pub-date">
                    <i class="fas fa-calendar-alt me-1"></i>${formattedDate}
                </span>
            </td>
            <td>
                <div class="admin-actions">
                    <button class="btn-action btn-action-view" onclick="viewPublication(${pub.id})" title="Voir">
                        <i class="fas fa-eye"></i>
                    </button>
                    <button class="btn-action btn-action-edit" onclick="editPublication(${pub.id})" title="Modifier">
                        <i class="fas fa-edit"></i>
                    </button>
                    <button class="btn-action btn-action-delete" onclick="confirmDelete(${pub.id})" title="Supprimer">
                        <i class="fas fa-trash-alt"></i>
                    </button>
                </div>
            </td>
        `;
        
        return row;
    }

    // ───────────────────────────────────────────────────────────
    // DELETE FUNCTIONALITY
    // ───────────────────────────────────────────────────────────
    const deleteAlertOverlay = document.getElementById('deleteAlertOverlay');
    const confirmDeleteBtn = document.getElementById('confirmDeleteBtn');
    const cancelDeleteBtn = document.getElementById('cancelDeleteBtn');
    let pendingDeleteId = null;

    // Show delete confirmation
    window.confirmDelete = function(id) {
        pendingDeleteId = id;
        const row = document.querySelector(`tr[data-id="${id}"]`);
        const title = row?.querySelector('.pub-title')?.textContent || `#${id}`;
        
        const messageElement = document.querySelector('.delete-alert-message');
        if (messageElement) {
            messageElement.innerHTML = `Êtes-vous sûr de vouloir supprimer la publication <strong>"${title}"</strong> ?<br>Cette action est irréversible.`;
        }
        
        deleteAlertOverlay.classList.add('show');
    };

    // Cancel delete
    if (cancelDeleteBtn) {
        cancelDeleteBtn.addEventListener('click', function() {
            deleteAlertOverlay.classList.remove('show');
            pendingDeleteId = null;
        });
    }

    // Close on overlay click
    if (deleteAlertOverlay) {
        deleteAlertOverlay.addEventListener('click', function(e) {
            if (e.target === deleteAlertOverlay) {
                deleteAlertOverlay.classList.remove('show');
                pendingDeleteId = null;
            }
        });
    }

    // Confirm delete
    if (confirmDeleteBtn) {
        confirmDeleteBtn.addEventListener('click', function() {
            if (pendingDeleteId) {
                deletePublication(pendingDeleteId);
            }
            deleteAlertOverlay.classList.remove('show');
            pendingDeleteId = null;
        });
    }

    // Delete publication
    function deletePublication(id) {
        console.log('Deleting publication:', id);
        
        const row = document.querySelector(`tr[data-id="${id}"]`);
        if (!row) {
            showToast('error', 'Publication introuvable');
            return;
        }
        
        row.classList.add('publication-row-removing');
        
        setTimeout(() => {
            row.remove();
            updateCounters('delete');
            showToast('success', 'Publication supprimée avec succès');
        }, 300);
    }

    // ───────────────────────────────────────────────────────────
    // SEARCH & FILTER
    // ───────────────────────────────────────────────────────────
    const searchInput = document.getElementById('publicationSearch');
    if (searchInput) {
        searchInput.addEventListener('input', function(e) {
            const query = e.target.value.toLowerCase().trim();
            filterPublications(query);
        });
    }

    function filterPublications(query) {
        const tableBody = document.getElementById('publicationsTableBody');
        const rows = tableBody.querySelectorAll('tr');
        
        let visibleCount = 0;
        
        rows.forEach(row => {
            const title = row.querySelector('.pub-title')?.textContent.toLowerCase() || '';
            const description = row.dataset.description?.toLowerCase() || '';
            
            if (title.includes(query) || description.includes(query)) {
                row.style.display = '';
                visibleCount++;
            } else {
                row.style.display = 'none';
            }
        });
        
        // Update count badge
        const countBadge = document.querySelector('.pub-count-badge');
        if (countBadge) {
            countBadge.textContent = visibleCount;
        }
    }

    // ───────────────────────────────────────────────────────────
    // BULK ACTIONS
    // ───────────────────────────────────────────────────────────
    const selectAllCheckbox = document.getElementById('selectAllPublications');
    if (selectAllCheckbox) {
        selectAllCheckbox.addEventListener('change', function(e) {
            const checkboxes = document.querySelectorAll('#publicationsTableBody .form-check-input:not(#selectAllPublications)');
            checkboxes.forEach(checkbox => {
                if (checkbox.closest('tr').style.display !== 'none') {
                    checkbox.checked = e.target.checked;
                }
            });
        });
    }

    // Delete selected button
    const deleteSelectedBtn = document.getElementById('deleteSelectedBtn');
    if (deleteSelectedBtn) {
        deleteSelectedBtn.addEventListener('click', function() {
            const selectedCheckboxes = document.querySelectorAll('#publicationsTableBody .form-check-input:checked');
            
            if (selectedCheckboxes.length === 0) {
                showToast('warning', 'Aucune publication sélectionnée');
                return;
            }
            
            const count = selectedCheckboxes.length;
            const messageElement = document.querySelector('.delete-alert-message');
            if (messageElement) {
                messageElement.innerHTML = `Êtes-vous sûr de vouloir supprimer <strong>${count}</strong> publication(s) ?<br>Cette action est irréversible.`;
            }
            
            deleteAlertOverlay.classList.add('show');
            
            // Override confirm button for bulk delete
            confirmDeleteBtn.onclick = function() {
                selectedCheckboxes.forEach(checkbox => {
                    const row = checkbox.closest('tr');
                    row.classList.add('publication-row-removing');
                    setTimeout(() => row.remove(), 300);
                });
                
                updateCounters('delete', count);
                deleteAlertOverlay.classList.remove('show');
                showToast('success', `${count} publication(s) supprimée(s)`);
                
                if (selectAllCheckbox) selectAllCheckbox.checked = false;
            };
        });
    }

    // ───────────────────────────────────────────────────────────
    // COUNTER UPDATES
    // ───────────────────────────────────────────────────────────
    function updateCounters(action, count = 1) {
        const totalStat = document.querySelector('.pub-stat-card:nth-child(1) .pub-stat-value');
        const monthStat = document.querySelector('.pub-stat-card:nth-child(2) .pub-stat-value');
        const countBadge = document.querySelector('.pub-count-badge');
        
        if (totalStat) {
            let current = parseInt(totalStat.textContent.replace(/,/g, ''));
            current = action === 'add' ? current + count : current - count;
            totalStat.textContent = current.toLocaleString();
        }
        
        if (monthStat && action === 'add') {
            let current = parseInt(monthStat.textContent.replace(/,/g, ''));
            current += count;
            monthStat.textContent = current.toLocaleString();
        }
        
        if (countBadge) {
            const tableBody = document.getElementById('publicationsTableBody');
            const visibleRows = tableBody.querySelectorAll('tr:not([style*="display: none"])');
            countBadge.textContent = visibleRows.length;
        }
    }

    // ───────────────────────────────────────────────────────────
    // TOAST NOTIFICATIONS
    // ───────────────────────────────────────────────────────────
    function showToast(type, message) {
        let toastElement;
        
        switch(type) {
            case 'success':
                toastElement = document.getElementById('successToast');
                break;
            case 'error':
                toastElement = document.getElementById('errorToast');
                break;
            case 'warning':
                toastElement = document.getElementById('warningToast');
                break;
            default:
                return;
        }
        
        if (toastElement) {
            const toastBody = toastElement.querySelector('.toast-body');
            if (toastBody) toastBody.textContent = message;
            
            const toast = new bootstrap.Toast(toastElement, {
                animation: true,
                autohide: true,
                delay: 3000
            });
            toast.show();
        }
    }

    // ───────────────────────────────────────────────────────────
    // INITIALIZATION
    // ───────────────────────────────────────────────────────────
    console.log('Publications page initialized');
    
    // Set initial count badge
    const tableBody = document.getElementById('publicationsTableBody');
    if (tableBody) {
        const rows = tableBody.querySelectorAll('tr');
        const countBadge = document.querySelector('.pub-count-badge');
        if (countBadge) {
            countBadge.textContent = rows.length;
        }
    }
});
