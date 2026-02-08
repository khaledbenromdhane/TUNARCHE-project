/* ═══════════════════════════════════════════════════════════════
   ÉVALUATIONS MANAGEMENT - JAVASCRIPT
   – Star rating interaction
   – Counter animation with decimals
   – CRUD operations
   – Search & filter by rating
   – Modal management
═══════════════════════════════════════════════════════════════ */

document.addEventListener('DOMContentLoaded', function() {
    
    // ───────────────────────────────────────────────────────────
    // COUNTER ANIMATION
    // ───────────────────────────────────────────────────────────
    function animateCounter(element) {
        const target = parseFloat(element.textContent);
        const duration = 2000;
        const increment = target / (duration / 16);
        const isDecimal = element.dataset.decimal === 'true';
        let current = 0;
        
        const timer = setInterval(() => {
            current += increment;
            if (current >= target) {
                element.textContent = isDecimal ? target.toFixed(1) : Math.floor(target);
                clearInterval(timer);
            } else {
                element.textContent = isDecimal ? current.toFixed(1) : Math.floor(current);
            }
        }, 16);
    }
    
    // Animate all stat card values
    document.querySelectorAll('.eval-stat-value').forEach(element => {
        animateCounter(element);
    });
    
    // ───────────────────────────────────────────────────────────
    // STAR RATING INTERACTION
    // ───────────────────────────────────────────────────────────
    const starRatingInput = document.querySelector('.star-rating-input');
    const ratingDisplay = document.querySelector('.rating-value-display');
    const starLabels = document.querySelectorAll('.star-label');
    const starRadios = document.querySelectorAll('.star-rating-input input[type="radio"]');
    
    // Update rating display when star is selected
    starRadios.forEach(radio => {
        radio.addEventListener('change', function() {
            const value = this.value;
            ratingDisplay.textContent = `${value}/5`;
        });
    });
    
    // Visual feedback on hover
    starLabels.forEach((label, index) => {
        label.addEventListener('mouseenter', function() {
            const starValue = parseInt(this.getAttribute('for').replace('star', ''));
            highlightStars(starValue);
        });
    });
    
    // Reset to selected value on mouse leave
    starRatingInput.addEventListener('mouseleave', function() {
        const checkedRadio = document.querySelector('.star-rating-input input[type="radio"]:checked');
        if (checkedRadio) {
            const selectedValue = parseInt(checkedRadio.value);
            highlightStars(selectedValue);
        } else {
            resetStars();
        }
    });
    
    function highlightStars(count) {
        starLabels.forEach((label, index) => {
            const starValue = parseInt(label.getAttribute('for').replace('star', ''));
            const starIcon = label.querySelector('i');
            
            if (starValue <= count) {
                starIcon.style.color = '#d4af37';
                starIcon.style.textShadow = '0 0 10px rgba(212, 175, 55, 0.5)';
            } else {
                starIcon.style.color = 'rgba(255, 255, 255, 0.2)';
                starIcon.style.textShadow = 'none';
            }
        });
    }
    
    function resetStars() {
        starLabels.forEach(label => {
            const starIcon = label.querySelector('i');
            starIcon.style.color = 'rgba(255, 255, 255, 0.2)';
            starIcon.style.textShadow = 'none';
        });
        ratingDisplay.textContent = '0/5';
    }
    
    // ───────────────────────────────────────────────────────────
    // MODAL MANAGEMENT
    // ───────────────────────────────────────────────────────────
    const evaluationModal = document.getElementById('evaluationModal');
    const evaluationForm = document.getElementById('evaluationForm');
    const modalTitle = evaluationModal.querySelector('.modal-title');
    const modalSubtitle = evaluationModal.querySelector('.modal-subtitle');
    const saveBtn = document.getElementById('saveEvaluationBtn');
    
    let editingEvaluationId = null;
    
    // Open modal for new evaluation
    document.getElementById('addEvaluationBtn').addEventListener('click', function() {
        editingEvaluationId = null;
        modalTitle.textContent = 'Nouvelle Évaluation';
        modalSubtitle.textContent = 'Ajouter une évaluation';
        evaluationForm.reset();
        resetStars();
        
        // Generate new ID
        const rows = document.querySelectorAll('.eval-table tbody tr');
        const maxId = Math.max(...Array.from(rows).map(row => parseInt(row.querySelector('.eval-id').textContent)));
        document.getElementById('evaluationId').value = maxId + 1;
        
        const modal = new bootstrap.Modal(evaluationModal);
        modal.show();
    });
    
    // Open modal for editing evaluation
    document.querySelectorAll('.btn-edit-evaluation').forEach(btn => {
        btn.addEventListener('click', function() {
            const row = this.closest('tr');
            editingEvaluationId = row.querySelector('.eval-id').textContent;
            
            modalTitle.textContent = 'Modifier Évaluation';
            modalSubtitle.textContent = `Édition de l'évaluation #${editingEvaluationId}`;
            
            // Fill form with row data
            document.getElementById('evaluationId').value = editingEvaluationId;
            document.getElementById('evaluationTitre').value = row.querySelector('.eval-titre').textContent.trim();
            document.getElementById('evaluationDate').value = row.querySelector('.eval-date').textContent.trim().split(' ')[0];
            document.getElementById('evaluationCommentaire').value = row.dataset.fullComment || '';
            
            // Set rating
            const ratingValue = row.querySelector('.rating-text').textContent.trim();
            const starRating = parseInt(parseFloat(ratingValue));
            const radioToCheck = document.getElementById(`star${starRating}`);
            if (radioToCheck) {
                radioToCheck.checked = true;
                ratingDisplay.textContent = `${starRating}/5`;
                highlightStars(starRating);
            }
            
            const modal = new bootstrap.Modal(evaluationModal);
            modal.show();
        });
    });
    
    // Save evaluation
    saveBtn.addEventListener('click', function() {
        if (!evaluationForm.checkValidity()) {
            evaluationForm.reportValidity();
            return;
        }
        
        const formData = {
            id: document.getElementById('evaluationId').value,
            titre: document.getElementById('evaluationTitre').value,
            date: document.getElementById('evaluationDate').value,
            commentaire: document.getElementById('evaluationCommentaire').value,
            rating: document.querySelector('.star-rating-input input[type="radio"]:checked')?.value || 0
        };
        
        if (editingEvaluationId) {
            updateEvaluationRow(formData);
            showToast('success', 'Mise à jour réussie', `L'évaluation #${formData.id} a été modifiée avec succès.`);
        } else {
            addEvaluationRow(formData);
            showToast('success', 'Ajout réussi', `L'évaluation "${formData.titre}" a été ajoutée avec succès.`);
        }
        
        bootstrap.Modal.getInstance(evaluationModal).hide();
    });
    
    // ───────────────────────────────────────────────────────────
    // TABLE OPERATIONS
    // ───────────────────────────────────────────────────────────
    function addEvaluationRow(data) {
        const tbody = document.querySelector('.eval-table tbody');
        const avatarClass = getAvatarClass(data.rating);
        const starsHtml = generateStarsHtml(data.rating);
        
        const row = document.createElement('tr');
        row.innerHTML = `
            <td>
                <input class="form-check-input eval-checkbox" type="checkbox">
            </td>
            <td><span class="eval-id">${data.id}</span></td>
            <td>
                <div class="eval-titre-cell">
                    <div class="eval-icon-avatar ${avatarClass}">
                        <i class="fas fa-comment-alt"></i>
                    </div>
                    <span class="eval-titre">${data.titre}</span>
                </div>
            </td>
            <td>
                <span class="eval-commentaire">${data.commentaire}</span>
            </td>
            <td>
                <div class="eval-date">
                    <i class="fas fa-calendar"></i>
                    <span>${data.date}</span>
                </div>
            </td>
            <td>
                <div class="eval-rating">
                    ${starsHtml}
                    <span class="rating-text">${data.rating}.0</span>
                </div>
            </td>
            <td>
                <button class="btn btn-sm btn-outline-light me-2 btn-edit-evaluation" title="Modifier">
                    <i class="fas fa-edit"></i>
                </button>
                <button class="btn btn-sm btn-outline-danger btn-delete-evaluation" title="Supprimer">
                    <i class="fas fa-trash-alt"></i>
                </button>
            </td>
        `;
        
        row.dataset.fullComment = data.commentaire;
        tbody.insertBefore(row, tbody.firstChild);
        
        // Attach event listeners to new buttons
        attachRowEventListeners(row);
        
        // Update count badge
        updateEvaluationCount();
    }
    
    function updateEvaluationRow(data) {
        const rows = document.querySelectorAll('.eval-table tbody tr');
        const row = Array.from(rows).find(r => r.querySelector('.eval-id').textContent === editingEvaluationId);
        
        if (row) {
            const avatarClass = getAvatarClass(data.rating);
            const starsHtml = generateStarsHtml(data.rating);
            
            row.querySelector('.eval-titre').textContent = data.titre;
            row.querySelector('.eval-commentaire').textContent = data.commentaire;
            row.querySelector('.eval-date span').textContent = data.date;
            row.querySelector('.eval-rating').innerHTML = `
                ${starsHtml}
                <span class="rating-text">${data.rating}.0</span>
            `;
            row.querySelector('.eval-icon-avatar').className = `eval-icon-avatar ${avatarClass}`;
            row.dataset.fullComment = data.commentaire;
        }
    }
    
    function getAvatarClass(rating) {
        const r = parseInt(rating);
        if (r === 5) return 'excellent';
        if (r === 4) return 'good';
        if (r === 3) return 'average';
        return 'poor';
    }
    
    function generateStarsHtml(rating) {
        const r = parseInt(rating);
        let html = '';
        for (let i = 1; i <= 5; i++) {
            if (i <= r) {
                html += '<i class="fas fa-star filled"></i>';
            } else {
                html += '<i class="fas fa-star"></i>';
            }
        }
        return html;
    }
    
    function attachRowEventListeners(row) {
        // Edit button
        row.querySelector('.btn-edit-evaluation').addEventListener('click', function() {
            const row = this.closest('tr');
            editingEvaluationId = row.querySelector('.eval-id').textContent;
            
            modalTitle.textContent = 'Modifier Évaluation';
            modalSubtitle.textContent = `Édition de l'évaluation #${editingEvaluationId}`;
            
            document.getElementById('evaluationId').value = editingEvaluationId;
            document.getElementById('evaluationTitre').value = row.querySelector('.eval-titre').textContent.trim();
            document.getElementById('evaluationDate').value = row.querySelector('.eval-date span').textContent.trim();
            document.getElementById('evaluationCommentaire').value = row.dataset.fullComment || '';
            
            const ratingValue = row.querySelector('.rating-text').textContent.trim();
            const starRating = parseInt(parseFloat(ratingValue));
            const radioToCheck = document.getElementById(`star${starRating}`);
            if (radioToCheck) {
                radioToCheck.checked = true;
                ratingDisplay.textContent = `${starRating}/5`;
                highlightStars(starRating);
            }
            
            const modal = new bootstrap.Modal(evaluationModal);
            modal.show();
        });
        
        // Delete button
        row.querySelector('.btn-delete-evaluation').addEventListener('click', function() {
            const row = this.closest('tr');
            const id = row.querySelector('.eval-id').textContent;
            const titre = row.querySelector('.eval-titre').textContent.trim();
            showDeleteAlert(id, titre, row);
        });
    }
    
    // ───────────────────────────────────────────────────────────
    // DELETE OPERATION
    // ───────────────────────────────────────────────────────────
    const deleteAlertOverlay = document.getElementById('deleteAlertOverlay');
    const deleteAlertMessage = document.getElementById('deleteAlertMessage');
    const confirmDeleteBtn = document.getElementById('confirmDeleteBtn');
    const cancelDeleteBtn = document.getElementById('cancelDeleteBtn');
    
    let evaluationToDelete = null;
    
    function showDeleteAlert(id, titre, row) {
        deleteAlertMessage.innerHTML = `Êtes-vous sûr de vouloir supprimer l'évaluation <strong>"${titre}"</strong> ?`;
        evaluationToDelete = row;
        deleteAlertOverlay.classList.add('show');
    }
    
    confirmDeleteBtn.addEventListener('click', function() {
        if (evaluationToDelete) {
            const titre = evaluationToDelete.querySelector('.eval-titre').textContent.trim();
            const id = evaluationToDelete.querySelector('.eval-id').textContent;
            
            evaluationToDelete.classList.add('evaluation-row-removing');
            
            setTimeout(() => {
                evaluationToDelete.remove();
                updateEvaluationCount();
                showToast('success', 'Suppression réussie', `L'évaluation "${titre}" a été supprimée.`);
            }, 300);
            
            evaluationToDelete = null;
        }
        deleteAlertOverlay.classList.remove('show');
    });
    
    cancelDeleteBtn.addEventListener('click', function() {
        evaluationToDelete = null;
        deleteAlertOverlay.classList.remove('show');
    });
    
    // Attach delete listeners to existing buttons
    document.querySelectorAll('.btn-delete-evaluation').forEach(btn => {
        btn.addEventListener('click', function() {
            const row = this.closest('tr');
            const id = row.querySelector('.eval-id').textContent;
            const titre = row.querySelector('.eval-titre').textContent.trim();
            showDeleteAlert(id, titre, row);
        });
    });
    
    // ───────────────────────────────────────────────────────────
    // BULK OPERATIONS
    // ───────────────────────────────────────────────────────────
    const selectAllCheckbox = document.getElementById('selectAllEvaluations');
    const bulkDeleteBtn = document.getElementById('bulkDeleteEvaluationsBtn');
    
    selectAllCheckbox.addEventListener('change', function() {
        const checkboxes = document.querySelectorAll('.eval-checkbox');
        checkboxes.forEach(cb => {
            cb.checked = this.checked;
        });
        updateBulkDeleteButton();
    });
    
    document.addEventListener('change', function(e) {
        if (e.target.classList.contains('eval-checkbox')) {
            updateBulkDeleteButton();
            
            const allCheckboxes = document.querySelectorAll('.eval-checkbox');
            const checkedCheckboxes = document.querySelectorAll('.eval-checkbox:checked');
            selectAllCheckbox.checked = allCheckboxes.length === checkedCheckboxes.length;
        }
    });
    
    function updateBulkDeleteButton() {
        const checkedCount = document.querySelectorAll('.eval-checkbox:checked').length;
        bulkDeleteBtn.disabled = checkedCount === 0;
    }
    
    bulkDeleteBtn.addEventListener('click', function() {
        const checkedBoxes = document.querySelectorAll('.eval-checkbox:checked');
        const count = checkedBoxes.length;
        
        if (count === 0) return;
        
        deleteAlertMessage.innerHTML = `Êtes-vous sûr de vouloir supprimer <strong>${count}</strong> évaluation(s) sélectionnée(s) ?`;
        evaluationToDelete = checkedBoxes;
        deleteAlertOverlay.classList.add('show');
    });
    
    // Update confirm delete to handle bulk
    const originalConfirmHandler = confirmDeleteBtn.onclick;
    confirmDeleteBtn.onclick = function() {
        if (evaluationToDelete && evaluationToDelete.length > 1) {
            evaluationToDelete.forEach(checkbox => {
                const row = checkbox.closest('tr');
                row.classList.add('evaluation-row-removing');
                setTimeout(() => {
                    row.remove();
                    updateEvaluationCount();
                }, 300);
            });
            
            showToast('success', 'Suppression réussie', `${evaluationToDelete.length} évaluation(s) supprimée(s).`);
            evaluationToDelete = null;
            selectAllCheckbox.checked = false;
            updateBulkDeleteButton();
            deleteAlertOverlay.classList.remove('show');
        } else {
            originalConfirmHandler ? originalConfirmHandler() : confirmDeleteBtn.click();
        }
    };
    
    // ───────────────────────────────────────────────────────────
    // SEARCH & FILTER
    // ───────────────────────────────────────────────────────────
    const searchInput = document.getElementById('searchEvaluation');
    const ratingFilter = document.getElementById('filterEvaluationRating');
    
    searchInput.addEventListener('input', filterTable);
    ratingFilter.addEventListener('change', filterTable);
    
    function filterTable() {
        const searchTerm = searchInput.value.toLowerCase();
        const ratingValue = ratingFilter.value;
        const rows = document.querySelectorAll('.eval-table tbody tr');
        
        rows.forEach(row => {
            const titre = row.querySelector('.eval-titre').textContent.toLowerCase();
            const commentaire = row.querySelector('.eval-commentaire').textContent.toLowerCase();
            const rating = row.querySelector('.rating-text').textContent.trim();
            
            const matchesSearch = titre.includes(searchTerm) || commentaire.includes(searchTerm);
            const matchesRating = ratingValue === '' || rating.startsWith(ratingValue);
            
            if (matchesSearch && matchesRating) {
                row.style.display = '';
            } else {
                row.style.display = 'none';
            }
        });
        
        updateVisibleCount();
    }
    
    function updateVisibleCount() {
        const visibleRows = document.querySelectorAll('.eval-table tbody tr:not([style*="display: none"])');
        const countBadge = document.querySelector('.eval-count-badge');
        if (countBadge) {
            countBadge.textContent = visibleRows.length;
        }
    }
    
    function updateEvaluationCount() {
        const rows = document.querySelectorAll('.eval-table tbody tr');
        const countBadge = document.querySelector('.eval-count-badge');
        if (countBadge) {
            countBadge.textContent = rows.length;
        }
    }
    
    // ───────────────────────────────────────────────────────────
    // SORTING
    // ───────────────────────────────────────────────────────────
    document.querySelectorAll('.sortable').forEach(header => {
        header.addEventListener('click', function() {
            const column = this.dataset.column;
            const currentOrder = this.dataset.order || 'asc';
            const newOrder = currentOrder === 'asc' ? 'desc' : 'asc';
            
            // Update all headers
            document.querySelectorAll('.sortable').forEach(h => {
                h.dataset.order = 'asc';
                const icon = h.querySelector('i');
                if (icon) {
                    icon.className = 'fas fa-sort ms-1';
                }
            });
            
            // Update current header
            this.dataset.order = newOrder;
            const icon = this.querySelector('i');
            if (icon) {
                icon.className = `fas fa-sort-${newOrder === 'asc' ? 'up' : 'down'} ms-1`;
            }
            
            sortTable(column, newOrder);
        });
    });
    
    function sortTable(column, order) {
        const tbody = document.querySelector('.eval-table tbody');
        const rows = Array.from(tbody.querySelectorAll('tr'));
        
        rows.sort((a, b) => {
            let aValue, bValue;
            
            switch(column) {
                case 'id':
                    aValue = parseInt(a.querySelector('.eval-id').textContent);
                    bValue = parseInt(b.querySelector('.eval-id').textContent);
                    break;
                case 'titre':
                    aValue = a.querySelector('.eval-titre').textContent.toLowerCase();
                    bValue = b.querySelector('.eval-titre').textContent.toLowerCase();
                    break;
                case 'date':
                    aValue = new Date(a.querySelector('.eval-date span').textContent.trim());
                    bValue = new Date(b.querySelector('.eval-date span').textContent.trim());
                    break;
                case 'note':
                    aValue = parseFloat(a.querySelector('.rating-text').textContent.trim());
                    bValue = parseFloat(b.querySelector('.rating-text').textContent.trim());
                    break;
                default:
                    return 0;
            }
            
            if (aValue < bValue) return order === 'asc' ? -1 : 1;
            if (aValue > bValue) return order === 'asc' ? 1 : -1;
            return 0;
        });
        
        rows.forEach(row => tbody.appendChild(row));
    }
    
    // ───────────────────────────────────────────────────────────
    // TOAST NOTIFICATIONS
    // ───────────────────────────────────────────────────────────
    function showToast(type, title, message) {
        let toastElement;
        
        if (type === 'success') {
            toastElement = document.getElementById('successToast');
            toastElement.querySelector('.toast-title').textContent = title;
            toastElement.querySelector('.toast-body').textContent = message;
        } else if (type === 'error') {
            toastElement = document.getElementById('errorToast');
            toastElement.querySelector('.toast-title').textContent = title;
            toastElement.querySelector('.toast-body').textContent = message;
        } else if (type === 'warning') {
            toastElement = document.getElementById('warningToast');
            toastElement.querySelector('.toast-title').textContent = title;
            toastElement.querySelector('.toast-body').textContent = message;
        }
        
        const toast = new bootstrap.Toast(toastElement, {
            delay: 3000
        });
        toast.show();
    }
    
    // ───────────────────────────────────────────────────────────
    // INITIALIZATION
    // ───────────────────────────────────────────────────────────
    updateEvaluationCount();
    updateBulkDeleteButton();
    
    console.log('✨ Évaluations management initialized');
});
