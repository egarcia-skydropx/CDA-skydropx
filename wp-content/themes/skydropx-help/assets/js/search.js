(function () {
    const wrap     = document.getElementById('sxhc-search-wrap');
    if (!wrap) return; // no hay buscador en esta página

    const input    = document.getElementById('sxhc-search-input');
    const dropdown = document.getElementById('sxhc-dropdown');
    const list     = document.getElementById('sxhc-results');
    const footer   = document.getElementById('sxhc-dropdown-footer');
    const seeAll   = document.getElementById('sxhc-see-all');
    const spinner  = document.getElementById('sxhc-spinner');
    const clearBtn = document.getElementById('sxhc-clear');
    const ajaxUrl  = window.sxhcData ? window.sxhcData.ajaxUrl : '/wp-admin/admin-ajax.php';
    const homeUrl  = window.sxhcData ? window.sxhcData.homeUrl : '/';

    let timer        = null;
    let currentIndex = -1;

    // ── Normalización (espejo del servidor) ───────────────────────────────
    function normalize(str) {
        return str
            .toLowerCase()
            .normalize('NFD')
            .replace(/[̀-ͯ]/g, '')  // elimina diacríticos unicode
            .replace(/[^a-z0-9]/g, '');
    }

    // ── Escape HTML ───────────────────────────────────────────────────────
    function escHtml(str) {
        return str
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;');
    }

    // ── Highlight de coincidencias ────────────────────────────────────────
    function highlight(text, query) {
        const normalQ    = normalize(query);
        const normalText = normalize(text);
        const idx        = normalText.indexOf(normalQ);
        if (idx === -1 || !normalQ) return escHtml(text);

        // Mapear posición normalizada → posición original
        const chars = Array.from(text);
        let normCount = 0, startOrig = -1, endOrig = -1;

        for (let ci = 0; ci < chars.length; ci++) {
            const n = normalize(chars[ci]);
            if (normCount === idx && startOrig === -1) startOrig = ci;
            normCount += n.length;
            if (normCount >= idx + normalQ.length && endOrig === -1) { endOrig = ci + 1; break; }
        }

        if (startOrig === -1 || endOrig === -1) return escHtml(text);

        return escHtml(text.slice(0, startOrig))
             + '<mark class="bg-brand-light text-brand rounded px-0.5">'
             + escHtml(text.slice(startOrig, endOrig))
             + '</mark>'
             + escHtml(text.slice(endOrig));
    }

    // ── Toggle botón limpiar ──────────────────────────────────────────────
    function toggleClear() {
        const hasText = input.value.length > 0;
        clearBtn.classList.toggle('hidden', !hasText);
        clearBtn.classList.toggle('flex',   hasText);
        if (!hasText) {
            spinner.classList.add('hidden');
            spinner.classList.remove('flex');
        }
    }

    // ── Renderizar resultados ─────────────────────────────────────────────
    function renderResults(results, query) {
        list.innerHTML = '';
        currentIndex   = -1;

        if (!results.length) {
            list.innerHTML = `<li class="px-4 py-5 text-sm text-gray-400 text-center">
                No encontramos resultados para <strong>"${escHtml(query)}"</strong>
            </li>`;
            footer.classList.add('hidden');
            dropdown.classList.remove('hidden');
            return;
        }

        results.forEach((item) => {
            const li = document.createElement('li');
            li.innerHTML = `
                <a href="${item.url}"
                   class="flex flex-col px-4 py-3 hover:bg-brand-light transition-colors group result-item">
                    <span class="text-sm font-medium text-gray-900 group-hover:text-brand leading-snug">
                        ${highlight(item.title, query)}
                    </span>
                    ${item.crumb
                        ? `<span class="text-xs text-gray-400 mt-0.5">${escHtml(item.crumb)}</span>`
                        : ''}
                </a>`;
            list.appendChild(li);
        });

        seeAll.href = homeUrl + '?s=' + encodeURIComponent(query) + '&post_type=help_article';
        footer.classList.remove('hidden');
        dropdown.classList.remove('hidden');
    }

    // ── Fetch de sugerencias ──────────────────────────────────────────────
    function fetchSuggestions(q) {
        spinner.classList.remove('hidden');
        spinner.classList.add('flex');
        clearBtn.classList.add('hidden');
        clearBtn.classList.remove('flex');

        fetch(ajaxUrl + '?action=sxhc_search&q=' + encodeURIComponent(q))
            .then(r => r.json())
            .then(res => {
                spinner.classList.add('hidden');
                spinner.classList.remove('flex');
                toggleClear();
                if (res.success) renderResults(res.data.results, q);
            })
            .catch(() => {
                spinner.classList.add('hidden');
                spinner.classList.remove('flex');
                toggleClear();
            });
    }

    // ── Eventos ───────────────────────────────────────────────────────────
    clearBtn.addEventListener('click', () => {
        input.value = '';
        input.focus();
        dropdown.classList.add('hidden');
        toggleClear();
    });

    input.addEventListener('input', () => {
        const q = input.value.trim();
        toggleClear();
        clearTimeout(timer);

        if (q.length < 2) { dropdown.classList.add('hidden'); return; }
        timer = setTimeout(() => fetchSuggestions(q), 280);
    });

    input.addEventListener('keydown', (e) => {
        const items = list.querySelectorAll('.result-item');

        if (e.key === 'ArrowDown') {
            e.preventDefault();
            currentIndex = Math.min(currentIndex + 1, items.length - 1);
            items.forEach((el, i) => el.classList.toggle('bg-brand-light', i === currentIndex));
        } else if (e.key === 'ArrowUp') {
            e.preventDefault();
            currentIndex = Math.max(currentIndex - 1, -1);
            items.forEach((el, i) => el.classList.toggle('bg-brand-light', i === currentIndex));
        } else if (e.key === 'Enter') {
            e.preventDefault();
            if (currentIndex >= 0 && items[currentIndex]) {
                items[currentIndex].click();
            } else {
                const q = input.value.trim();
                if (q) window.location.href = homeUrl + '?s=' + encodeURIComponent(q) + '&post_type=help_article';
            }
        } else if (e.key === 'Escape') {
            dropdown.classList.add('hidden');
        }
    });

    document.addEventListener('click', (e) => {
        if (!wrap.contains(e.target)) dropdown.classList.add('hidden');
    });
})();
