/* =============================================================
   Navbar principal — interacciones
   ============================================================= */
(function () {
    'use strict';

    // ─── Dropdowns desktop ──────────────────────────────────────
    var dropdowns = document.querySelectorAll('[data-sxhc-dropdown]');
    var openDropdown = null;

    function closeDropdown(item) {
        if (!item) return;
        item.classList.remove('is-open');
        var trigger = item.querySelector('.sxhc-nav-trigger');
        if (trigger) trigger.setAttribute('aria-expanded', 'false');
    }

    function openOnly(item) {
        if (openDropdown && openDropdown !== item) closeDropdown(openDropdown);
        item.classList.add('is-open');
        var trigger = item.querySelector('.sxhc-nav-trigger');
        if (trigger) trigger.setAttribute('aria-expanded', 'true');
        openDropdown = item;
    }

    dropdowns.forEach(function (item) {
        var trigger = item.querySelector('.sxhc-nav-trigger');
        if (!trigger) return;

        // Click toggle
        trigger.addEventListener('click', function (e) {
            e.stopPropagation();
            if (item.classList.contains('is-open')) {
                closeDropdown(item);
                openDropdown = null;
            } else {
                openOnly(item);
            }
        });

        // Hover open (solo desktop con hover real)
        if (window.matchMedia('(hover: hover)').matches) {
            item.addEventListener('mouseenter', function () { openOnly(item); });
            item.addEventListener('mouseleave', function () {
                closeDropdown(item);
                if (openDropdown === item) openDropdown = null;
            });
        }
    });

    // Cerrar al hacer click fuera
    document.addEventListener('click', function (e) {
        if (!openDropdown) return;
        if (!openDropdown.contains(e.target)) {
            closeDropdown(openDropdown);
            openDropdown = null;
        }
    });

    // Cerrar con Escape
    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape') {
            if (openDropdown) {
                var trigger = openDropdown.querySelector('.sxhc-nav-trigger');
                closeDropdown(openDropdown);
                openDropdown = null;
                if (trigger) trigger.focus();
            }
            if (drawer && drawer.classList.contains('is-open')) {
                closeDrawer();
            }
        }
    });

    // ─── Drawer mobile ─────────────────────────────────────────
    var drawer = document.getElementById('sxhc-mobile-drawer');
    var toggle = document.getElementById('sxhc-menu-toggle');

    function openDrawer() {
        if (!drawer) return;
        drawer.classList.add('is-open');
        drawer.setAttribute('aria-hidden', 'false');
        document.body.classList.add('sxhc-no-scroll');
        if (toggle) toggle.setAttribute('aria-expanded', 'true');
    }

    function closeDrawer() {
        if (!drawer) return;
        drawer.classList.remove('is-open');
        drawer.setAttribute('aria-hidden', 'true');
        document.body.classList.remove('sxhc-no-scroll');
        if (toggle) toggle.setAttribute('aria-expanded', 'false');
    }

    if (toggle && drawer) {
        toggle.addEventListener('click', openDrawer);

        var closers = drawer.querySelectorAll('[data-sxhc-drawer-close]');
        closers.forEach(function (el) {
            el.addEventListener('click', closeDrawer);
        });
    }

    // ─── Acordeones del drawer ─────────────────────────────────
    var accordions = document.querySelectorAll('[data-sxhc-accordion]');
    accordions.forEach(function (acc) {
        var trigger = acc.querySelector('.sxhc-drawer-accordion__trigger');
        if (!trigger) return;
        trigger.addEventListener('click', function () {
            var willOpen = !acc.classList.contains('is-open');
            acc.classList.toggle('is-open');
            trigger.setAttribute('aria-expanded', willOpen ? 'true' : 'false');
        });
    });

})();
