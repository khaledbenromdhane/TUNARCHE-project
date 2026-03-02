/**
 * Soumission dynamique des formulaires admin (Oeuvre, Galerie)
 * Affiche les erreurs de validation sans recharger la page
 */
(function () {
    const FRAME_IDS = ['oeuvre-form-frame', 'galerie-form-frame'];

    function initAjaxForms() {
        document.querySelectorAll('[data-ajax-form]').forEach(function (form) {
            if (form.dataset.ajaxInitialized) return;
            form.dataset.ajaxInitialized = 'true';

            form.addEventListener('submit', function (e) {
                e.preventDefault();
                submitFormAjax(form);
            });
        });
    }

    function submitFormAjax(form) {
        const frame = form.closest('[id="oeuvre-form-frame"], [id="galerie-form-frame"]');
        const frameId = frame ? frame.id : null;
        const submitBtn = form.querySelector('button[type="submit"]');
        const originalBtnText = submitBtn ? submitBtn.innerHTML : '';

        if (submitBtn) {
            submitBtn.disabled = true;
            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i> Envoi...';
        }

        const formData = new FormData(form);
        const url = form.action || window.location.href;
        const method = (form.method || 'post').toUpperCase();

        fetch(url, {
            method: method,
            body: formData,
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json, text/html'
            }
        })
        .then(function (response) {
            const contentType = response.headers.get('Content-Type') || '';
            if (contentType.includes('application/json')) {
                return response.json().then(function (data) {
                    if (data.success && data.redirect) {
                        window.location.href = data.redirect;
                        return;
                    }
                    throw new Error(data.message || 'Erreur inconnue');
                });
            }
            return response.text().then(function (html) {
                if (frameId && html) {
                    const parser = new DOMParser();
                    const doc = parser.parseFromString(html, 'text/html');
                    const newFrame = doc.getElementById(frameId);
                    if (newFrame && frame) {
                        frame.outerHTML = newFrame.outerHTML;
                        initAjaxForms();
                        var firstError = document.querySelector('.invalid-feedback, .form-error');
                        if (firstError) {
                            firstError.closest('.mb-3, .form-group')?.scrollIntoView({ behavior: 'smooth', block: 'center' });
                        }
                    }
                }
            });
        })
        .catch(function (err) {
            console.error('Erreur soumission:', err);
            if (submitBtn) {
                submitBtn.disabled = false;
                submitBtn.innerHTML = originalBtnText;
            }
        })
        .finally(function () {
            if (submitBtn && !submitBtn.disabled) {
                submitBtn.innerHTML = originalBtnText;
            } else if (submitBtn && submitBtn.disabled) {
                submitBtn.disabled = false;
                submitBtn.innerHTML = originalBtnText;
            }
        });
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initAjaxForms);
    } else {
        initAjaxForms();
    }
})();
