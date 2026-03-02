/**
 * Recherche, tri et filtre AJAX pour les listes admin (Galeries, Œuvres).
 * Met à jour le tbody sans rechargement de page.
 * Recherche : à chaque lettre tapée, affiche les éléments qui contiennent le texte (sinon liste vide si aucun filtre).
 */
(function () {
    var DEBOUNCE_MS = 280;

    function buildParams(container) {
        var params = {};
        container.querySelectorAll('[data-list-param]').forEach(function (el) {
            var name = el.getAttribute('data-list-param');
            var value = el.value !== null && el.value !== undefined ? String(el.value).trim() : '';
            if (!name) return;
            // Toujours envoyer 'q' pour que la recherche soit appliquée (chaîne vide = pas de filtre recherche)
            if (name === 'q') {
                params[name] = value;
                return;
            }
            if (value !== '') {
                params[name] = value;
            }
        });
        return params;
    }

    function buildUrl(baseUrl, params) {
        var url = new URL(baseUrl, window.location.origin);
        Object.keys(params).forEach(function (key) {
            url.searchParams.set(key, params[key]);
        });
        return url.toString();
    }

    function getTbodyId(container) {
        var listType = container.getAttribute('data-list-ajax');
        if (listType === 'galerie') return 'galerie-list-body';
        if (listType === 'oeuvre') return 'oeuvre-list-body';
        return null;
    }

    function loadList(container) {
        var url = container.getAttribute('data-list-url');
        var tbodyId = getTbodyId(container);
        if (!url || !tbodyId) return;

        var params = buildParams(container);
        var fullUrl = buildUrl(url, params);
        var tbody = document.getElementById(tbodyId);
        if (!tbody) return;

        var wrapper = tbody.closest('.table-responsive');
        var loader = null;
        if (wrapper) {
            loader = wrapper.querySelector('.admin-list-loader');
            if (!loader) {
                loader = document.createElement('div');
                loader.className = 'admin-list-loader position-absolute top-0 start-0 end-0 bottom-0 d-flex align-items-center justify-content-center bg-dark bg-opacity-25';
                loader.style.minHeight = '120px';
                loader.innerHTML = '<div class="spinner-border text-accent" role="status"><span class="visually-hidden">Chargement...</span></div>';
                wrapper.style.position = 'relative';
                wrapper.appendChild(loader);
            }
            loader.classList.remove('d-none');
        }

        fetch(fullUrl, {
            method: 'GET',
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'text/html'
            }
        })
        .then(function (response) {
            if (!response.ok) throw new Error('Erreur ' + response.status);
            return response.text();
        })
        .then(function (html) {
            var parser = new DOMParser();
            var doc = parser.parseFromString(html.trim(), 'text/html');
            var newTbody = doc.getElementById(tbodyId) || doc.querySelector('tbody');
            if (newTbody && tbody.parentNode) {
                if (!newTbody.id) newTbody.id = tbodyId;
                var imported = document.importNode(newTbody, true);
                tbody.parentNode.replaceChild(imported, tbody);
            }
        })
        .catch(function (err) {
            console.error('List AJAX error:', err);
            var currentTbody = document.getElementById(tbodyId);
            if (currentTbody) {
                var row = document.createElement('tr');
                row.innerHTML = '<td colspan="10" class="text-center text-danger py-3">Erreur de chargement. Réessayez.</td>';
                currentTbody.appendChild(row);
            }
        })
        .finally(function () {
            if (loader) loader.classList.add('d-none');
        });
    }

    function debounce(fn, ms) {
        var timeout;
        return function () {
            var self = this;
            var args = arguments;
            clearTimeout(timeout);
            timeout = setTimeout(function () { fn.apply(self, args); }, ms);
        };
    }

    function initList(container) {
        var listType = container.getAttribute('data-list-ajax');
        if (!listType) return;

        var run = debounce(function () { loadList(container); }, DEBOUNCE_MS);

        container.querySelectorAll('[data-list-param]').forEach(function (el) {
            if (el.getAttribute('name') === 'q') {
                el.addEventListener('input', run);
                el.addEventListener('keyup', run);
            } else {
                el.addEventListener('change', run);
            }
        });
    }

    function init() {
        document.querySelectorAll('[data-list-ajax]').forEach(initList);
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
})();
