/* ═══════════════════════════════════════════════════════════════
   COMMENTAIRES MANAGEMENT - JAVASCRIPT
   – CRUD operations for comments
   – Conditional raison_signalement field
   – Search & filter functionality
   – Modal management
═══════════════════════════════════════════════════════════════ */

document.addEventListener('DOMContentLoaded', function() {
    
    // ───────────────────────────────────────────────────────────
    // COUNTER ANIMATION
    // ───────────────────────────────────────────────────────────
    function animateCounter(element) {
        const target = parseInt(element.textContent.replace(/,/g, ''));
        const duration = 2000;
        const increment = target / (duration / 16);
        let current = 0;
        
        const timer = setInterval(() => {
            current += increment;
            if (current >= target) {
                element.textContent = target.toLocaleString();
                clearInterval(timer);
            } else {
                element.textContent = Math.floor(current).toLocaleString();
            }
        }, 16);
    }
    
    // Animate all stat card values
    document.querySelectorAll('.com-stat-value').forEach(element => {
        animateCounter(element);
    });
    
    // ───────────────────────────────────────────────────────────
    // MODAL MANAGEMENT
    // ───────────────────────────────────────────────────────────
    const commentaireModal = document.getElementById('commentaireModal');
    const commentaireForm = document.getElementById('commentaireForm');
    const modalTitle = commentaireModal.querySelector('.modal-title');
    const modalSubtitle = commentaireModal.querySelector('.modal-subtitle');
    const saveBtn = document.getElementById('saveCommentaireBtn');
    const addBtn = document.getElementById('addCommentaireBtn');
    
    const estSignaleCheckbox = document.getElementById('commentaireEstSignale');
    const raisonSignalementGroup = document.getElementById('raisonSignalementGroup');
    const raisonSignalementTextarea = document.getElementById('commentaireRaisonSignalement');
    
    let editingCommentaireRow = null;
    
    // Toggle raison_signalement field based on est_signale checkbox
    estSignaleCheckbox.addEventListener('change', function() {
        if (this.checked) {
            raisonSignalementGroup.style.display = 'block';
            raisonSignalementTextarea.required = true;
        } else {
            raisonSignalementGroup.style.display = 'none';
            raisonSignalementTextarea.required = false;
            raisonSignalementTextarea.value = '';
        }
    });
    
    // Open modal for new commentaire
    addBtn.addEventListener('click', function() {
        editingCommentaireRow = null;
        modalTitle.textContent = 'Add Commentaire';
        modalSubtitle.textContent = 'Create a new comment';
        commentaireForm.reset();
        
        // Generate new ID
        const rows = document.querySelectorAll('.com-table tbody tr');
        const maxId = Math.max(...Array.from(rows).map(row => parseInt(row.querySelector('.com-id').textContent)));
        document.getElementById('commentaireId').value = maxId + 1;
        
        // Hide raison_signalement by default
        raisonSignalementGroup.style.display = 'none';
        raisonSignalementTextarea.required = false;
        
        const modal = new bootstrap.Modal(commentaireModal);
        modal.show();
    });
    
    // Open modal for editing commentaire
    document.querySelectorAll('.btn-edit-commentaire').forEach(btn => {
        btn.addEventListener('click', function() {
            const row = this.closest('tr');
            editingCommentaireRow = row;
            
            const id = row.querySelector('.com-id').textContent;
            modalTitle.textContent = 'Edit Commentaire';
            modalSubtitle.textContent = `Editing comment #${id}`;
            
            // Fill form with row data
            document.getElementById('commentaireIdHidden').value = id;
            document.getElementById('commentaireId').value = id;
            
            // User
            const userName = row.querySelector('.com-user-name').textContent.trim();
            const userId = row.querySelector('.com-user-id').textContent.replace(/[^\d]/g, '');
            document.getElementById('commentaireUserId').value = userId;
            
            // Publication
            const pubId = row.querySelector('.com-pub-id').textContent.replace(/[^\d]/g, '');
            document.getElementById('commentairePublicationId').value = pubId;
            
            // Comment text
            const fullText = row.dataset.fulltext || '';
            document.getElementById('commentaireText').value = fullText;
            
            // Status
            const status = row.querySelector('.com-status-badge').classList.contains('visible') ? 'visible' : 'hidden';
            document.getElementById('commentaireStatus').value = status;
            
            // Likes
            const likes = row.querySelector('.com-likes span').textContent;
            document.getElementById('commentaireLikes').value = likes;
            
            // Parent ID
            const parentSpan = row.querySelector('.com-parent');
            if (parentSpan.classList.contains('reply')) {
                const parentId = parentSpan.textContent.replace(/[^\d]/g, '');
                document.getElementById('commentaireParentId').value = parentId;
            } else {
                document.getElementById('commentaireParentId').value = '';
            }
            
            // Est Signalé
            const isReported = row.querySelector('.com-reported-badge').classList.contains('true');
            estSignaleCheckbox.checked = isReported;
            
            // Raison Signalement
            if (isReported) {
                const raison = row.dataset.raison || '';
                raisonSignalementTextarea.value = raison;
                raisonSignalementGroup.style.display = 'block';
                raisonSignalementTextarea.required = true;
            } else {
                raisonSignalementGroup.style.display = 'none';
                raisonSignalementTextarea.required = false;
                raisonSignalementTextarea.value = '';
            }
            
            const modal = new bootstrap.Modal(commentaireModal);
            modal.show();
        });
    });
    
    // Save commentaire
    saveBtn.addEventListener('click', function() {
        if (!commentaireForm.checkValidity()) {
            commentaireForm.reportValidity();
            return;
        }
        
        const formData = {
            id: document.getElementById('commentaireId').value,
            userId: document.getElementById('commentaireUserId').value,
            userText: document.getElementById('commentaireUserId').selectedOptions[0].text,
            publicationId: document.getElementById('commentairePublicationId').value,
            publicationText: document.getElementById('commentairePublicationId').selectedOptions[0].text,
            text: document.getElementById('commentaireText').value,
            status: document.getElementById('commentaireStatus').value,
            likes: document.getElementById('commentaireLikes').value,
            parentId: document.getElementById('commentaireParentId').value,
            estSignale: estSignaleCheckbox.checked,
            raisonSignalement: estSignaleCheckbox.checked ? raisonSignalementTextarea.value : ''
        };
        
        if (editingCommentaireRow) {
            updateCommentaireRow(editingCommentaireRow, formData);
            showToast('success', 'Update successful', `Comment #${formData.id} has been updated successfully.`);
        } else {
            addCommentaireRow(formData);
            showToast('success', 'Creation successful', `New comment has been created successfully.`);
        }
        
        bootstrap.Modal.getInstance(commentaireModal).hide();
    });
    
    // ───────────────────────────────────────────────────────────
    // TABLE OPERATIONS
    // ───────────────────────────────────────────────────────────
    function addCommentaireRow(data) {
        const tbody = document.querySelector('.com-table tbody');
        
        const row = document.createElement('tr');
        row.dataset.fulltext = data.text;
        row.dataset.raison = data.raisonSignalement;
        
        const parentHtml = data.parentId ? 
            `<span class="com-parent reply"><i class="fas fa-reply me-1"></i>Reply to #${data.parentId}</span>` :
            `<span class="com-parent">—</span>`;
        
        const reportedClass = data.estSignale ? 'true' : 'false';
        const reportedIcon = data.estSignale ? 'fa-flag' : 'fa-check-circle';
        const reportedText = data.estSignale ? 'Yes' : 'No';
        
        row.innerHTML = `
            <td><input type="checkbox" class="form-check-input com-checkbox"></td>
            <td><span class="com-id">${data.id}</span></td>
            <td>
                <div class="com-user-cell">
                    <div class="com-user-avatar">
                        <i class="fas fa-user"></i>
                    </div>
                    <div>
                        <span class="com-user-name">${escapeHtml(data.userText.split('(')[0].trim())}</span>
                        <small class="com-user-id">User #${data.userId}</small>
                    </div>
                </div>
            </td>
            <td>
                <div class="com-pub-cell">
                    <i class="fas fa-image text-muted me-2"></i>
                    <span class="com-pub-title">${escapeHtml(data.publicationText.split('(')[0].trim())}</span>
                    <small class="com-pub-id">(Pub #${data.publicationId})</small>
                </div>
            </td>
            <td><span class="com-status-badge ${data.status}">${data.status.charAt(0).toUpperCase() + data.status.slice(1)}</span></td>
            <td>
                <div class="com-likes">
                    <i class="fas fa-heart me-1"></i>
                    <span>${data.likes}</span>
                </div>
            </td>
            <td>${parentHtml}</td>
            <td><span class="com-reported-badge ${reportedClass}"><i class="fas ${reportedIcon}"></i> ${reportedText}</span></td>
            <td>
                <button class="table-action-btn btn-edit-commentaire" title="Edit"><i class="fas fa-edit"></i></button>
                <button class="table-action-btn delete btn-delete-commentaire" title="Delete"><i class="fas fa-trash-alt"></i></button>
            </td>
        `;
        
        tbody.insertBefore(row, tbody.firstChild);
        
        // Attach event listeners to new buttons
        attachRowEventListeners(row);
        
        // Update count badge
        updateCommentaireCount();
    }
    
    function updateCommentaireRow(row, data) {
        row.dataset.fulltext = data.text;
        row.dataset.raison = data.raisonSignalement;
        
        // Update user
        row.querySelector('.com-user-name').textContent = data.userText.split('(')[0].trim();
        row.querySelector('.com-user-id').textContent = `User #${data.userId}`;
        
        // Update publication
        row.querySelector('.com-pub-title').textContent = data.publicationText.split('(')[0].trim();
        row.querySelector('.com-pub-id').textContent = `(Pub #${data.publicationId})`;
        
        // Update status
        const statusBadge = row.querySelector('.com-status-badge');
        statusBadge.className = `com-status-badge ${data.status}`;
        statusBadge.textContent = data.status.charAt(0).toUpperCase() + data.status.slice(1);
        
        // Update likes
        row.querySelector('.com-likes span').textContent = data.likes;
        
        // Update parent
        const parentCell = row.querySelector('td:nth-child(7)');
        if (data.parentId) {
            parentCell.innerHTML = `<span class="com-parent reply"><i class="fas fa-reply me-1"></i>Reply to #${data.parentId}</span>`;
        } else {
            parentCell.innerHTML = `<span class="com-parent">—</span>`;
        }
        
        // Update reported
        const reportedBadge = row.querySelector('.com-reported-badge');
        const reportedClass = data.estSignale ? 'true' : 'false';
        const reportedIcon = data.estSignale ? 'fa-flag' : 'fa-check-circle';
        const reportedText = data.estSignale ? 'Yes' : 'No';
        reportedBadge.className = `com-reported-badge ${reportedClass}`;
        reportedBadge.innerHTML = `<i class="fas ${reportedIcon}"></i> ${reportedText}`;
    }
    
    function attachRowEventListeners(row) {
        // Edit button
        row.querySelector('.btn-edit-commentaire').addEventListener('click', function() {
            const row = this.closest('tr');
            editingCommentaireRow = row;
            
            const id = row.querySelector('.com-id').textContent;
            modalTitle.textContent = 'Edit Commentaire';
            modalSubtitle.textContent = `Editing comment #${id}`;
            
            document.getElementById('commentaireIdHidden').value = id;
            document.getElementById('commentaireId').value = id;
            
            const userName = row.querySelector('.com-user-name').textContent.trim();
            const userId = row.querySelector('.com-user-id').textContent.replace(/[^\d]/g, '');
            document.getElementById('commentaireUserId').value = userId;
            
            const pubId = row.querySelector('.com-pub-id').textContent.replace(/[^\d]/g, '');
            document.getElementById('commentairePublicationId').value = pubId;
            
            const fullText = row.dataset.fulltext || '';
            document.getElementById('commentaireText').value = fullText;
            
            const status = row.querySelector('.com-status-badge').classList.contains('visible') ? 'visible' : 'hidden';
            document.getElementById('commentaireStatus').value = status;
            
            const likes = row.querySelector('.com-likes span').textContent;
            document.getElementById('commentaireLikes').value = likes;
            
            const parentSpan = row.querySelector('.com-parent');
            if (parentSpan.classList.contains('reply')) {
                const parentId = parentSpan.textContent.replace(/[^\d]/g, '');
                document.getElementById('commentaireParentId').value = parentId;
            } else {
                document.getElementById('commentaireParentId').value = '';
            }
            
            const isReported = row.querySelector('.com-reported-badge').classList.contains('true');
            estSignaleCheckbox.checked = isReported;
            
            if (isReported) {
                const raison = row.dataset.raison || '';
                raisonSignalementTextarea.value = raison;
                raisonSignalementGroup.style.display = 'block';
                raisonSignalementTextarea.required = true;
            } else {
                raisonSignalementGroup.style.display = 'none';
                raisonSignalementTextarea.required = false;
                raisonSignalementTextarea.value = '';
            }
            
            const modal = new bootstrap.Modal(commentaireModal);
            modal.show();
        });
        
        // Delete button
        row.querySelector('.btn-delete-commentaire').addEventListener('click', function() {
            const row = this.closest('tr');
            const id = row.querySelector('.com-id').textContent;
            showDeleteAlert(id, row);
        });
    }
    
    // ───────────────────────────────────────────────────────────
    // DELETE OPERATION
    // ───────────────────────────────────────────────────────────
    const deleteAlertOverlay = document.getElementById('deleteAlertOverlay');
    const deleteAlertMessage = document.getElementById('deleteAlertMessage');
    const confirmDeleteBtn = document.getElementById('confirmDeleteBtn');
    const cancelDeleteBtn = document.getElementById('cancelDeleteBtn');
    
    let commentaireToDelete = null;
    
    function showDeleteAlert(id, row) {
        deleteAlertMessage.innerHTML = `Are you sure you want to delete comment <strong>#${id}</strong>? This action cannot be undone.`;
        commentaireToDelete = row;
        deleteAlertOverlay.classList.add('show');
    }
    
    confirmDeleteBtn.addEventListener('click', function() {
        if (commentaireToDelete) {
            const id = commentaireToDelete.querySelector('.com-id').textContent;
            
            commentaireToDelete.classList.add('commentaire-row-removing');
            
            setTimeout(() => {
                commentaireToDelete.remove();
                updateCommentaireCount();
                showToast('success', 'Deletion successful', `Comment #${id} has been deleted.`);
            }, 300);
            
            commentaireToDelete = null;
        }
        deleteAlertOverlay.classList.remove('show');
    });
    
    cancelDeleteBtn.addEventListener('click', function() {
        commentaireToDelete = null;
        deleteAlertOverlay.classList.remove('show');
    });
    
    // Attach delete listeners to existing buttons
    document.querySelectorAll('.btn-delete-commentaire').forEach(btn => {
        btn.addEventListener('click', function() {
            const row = this.closest('tr');
            const id = row.querySelector('.com-id').textContent;
            showDeleteAlert(id, row);
        });
    });
    
    // Close overlay on background click
    deleteAlertOverlay.addEventListener('click', function(e) {
        if (e.target === deleteAlertOverlay) {
            commentaireToDelete = null;
            deleteAlertOverlay.classList.remove('show');
        }
    });
    
    // ───────────────────────────────────────────────────────────
    // BULK OPERATIONS
    // ───────────────────────────────────────────────────────────
    const selectAllCheckbox = document.getElementById('selectAllCommentaires');
    
    if (selectAllCheckbox) {
        selectAllCheckbox.addEventListener('change', function() {
            const checkboxes = document.querySelectorAll('.com-checkbox');
            checkboxes.forEach(cb => {
                cb.checked = this.checked;
            });
        });
    }
    
    document.addEventListener('change', function(e) {
        if (e.target.classList.contains('com-checkbox')) {
            const allCheckboxes = document.querySelectorAll('.com-checkbox');
            const checkedCheckboxes = document.querySelectorAll('.com-checkbox:checked');
            if (selectAllCheckbox) {
                selectAllCheckbox.checked = allCheckboxes.length === checkedCheckboxes.length;
            }
        }
    });
    
    // ───────────────────────────────────────────────────────────
    // SEARCH & FILTER
    // ───────────────────────────────────────────────────────────
    const searchInput = document.getElementById('searchCommentaire');
    const statusFilter = document.getElementById('filterCommentaireStatus');
    const signaleFilter = document.getElementById('filterCommentaireSignale');
    
    searchInput.addEventListener('input', filterTable);
    statusFilter.addEventListener('change', filterTable);
    signaleFilter.addEventListener('change', filterTable);
    
    function filterTable() {
        const searchTerm = searchInput.value.toLowerCase();
        const statusValue = statusFilter.value;
        const signaleValue = signaleFilter.value;
        const rows = document.querySelectorAll('.com-table tbody tr');
        
        rows.forEach(row => {
            const text = row.dataset.fulltext.toLowerCase();
            const userName = row.querySelector('.com-user-name').textContent.toLowerCase();
            const pubTitle = row.querySelector('.com-pub-title').textContent.toLowerCase();
            
            const statusBadge = row.querySelector('.com-status-badge');
            const status = statusBadge.classList.contains('visible') ? 'visible' : 'hidden';
            
            const reportedBadge = row.querySelector('.com-reported-badge');
            const isReported = reportedBadge.classList.contains('true') ? 'true' : 'false';
            
            const matchesSearch = text.includes(searchTerm) || userName.includes(searchTerm) || pubTitle.includes(searchTerm);
            const matchesStatus = statusValue === '' || status === statusValue;
            const matchesSignale = signaleValue === '' || isReported === signaleValue;
            
            if (matchesSearch && matchesStatus && matchesSignale) {
                row.style.display = '';
            } else {
                row.style.display = 'none';
            }
        });
        
        updateVisibleCount();
    }
    
    function updateVisibleCount() {
        const visibleRows = document.querySelectorAll('.com-table tbody tr:not([style*="display: none"])');
        const countBadge = document.querySelector('.com-count-badge');
        if (countBadge) {
            countBadge.textContent = visibleRows.length;
        }
    }
    
    function updateCommentaireCount() {
        const rows = document.querySelectorAll('.com-table tbody tr');
        const countBadge = document.querySelector('.com-count-badge');
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
                    icon.className = 'fas fa-sort';
                }
            });
            
            // Update current header
            this.dataset.order = newOrder;
            const icon = this.querySelector('i');
            if (icon) {
                icon.className = `fas fa-sort-${newOrder === 'asc' ? 'up' : 'down'}`;
            }
            
            sortTable(column, newOrder);
        });
    });
    
    function sortTable(column, order) {
        const tbody = document.querySelector('.com-table tbody');
        const rows = Array.from(tbody.querySelectorAll('tr'));
        
        rows.sort((a, b) => {
            let aValue, bValue;
            
            switch(column) {
                case 'id':
                    aValue = parseInt(a.querySelector('.com-id').textContent);
                    bValue = parseInt(b.querySelector('.com-id').textContent);
                    break;
                case 'status':
                    aValue = a.querySelector('.com-status-badge').textContent.toLowerCase();
                    bValue = b.querySelector('.com-status-badge').textContent.toLowerCase();
                    break;
                case 'likes':
                    aValue = parseInt(a.querySelector('.com-likes span').textContent);
                    bValue = parseInt(b.querySelector('.com-likes span').textContent);
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
        } else if (type === 'error') {
            toastElement = document.getElementById('errorToast');
        } else if (type === 'warning') {
            toastElement = document.getElementById('warningToast');
        }
        
        if (toastElement) {
            const titleEl = toastElement.querySelector('.toast-title');
            const bodyEl = toastElement.querySelector('.toast-body');
            
            if (titleEl) titleEl.textContent = title;
            if (bodyEl) bodyEl.textContent = message;
            
            const toast = new bootstrap.Toast(toastElement, {
                delay: 3000
            });
            toast.show();
        }
    }
    
    // ───────────────────────────────────────────────────────────
    // UTILITY FUNCTIONS
    // ───────────────────────────────────────────────────────────
    function escapeHtml(str) {
        const div = document.createElement('div');
        div.appendChild(document.createTextNode(str));
        return div.innerHTML;
    }
    
    // ───────────────────────────────────────────────────────────
    // INITIALIZATION
    // ───────────────────────────────────────────────────────────
    updateCommentaireCount();
    
    console.log('✨ Commentaires management initialized');
});
