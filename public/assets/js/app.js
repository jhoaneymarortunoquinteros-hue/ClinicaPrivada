/* JS general: menú móvil, confirmaciones, alertas y utilidades. */
document.addEventListener('DOMContentLoaded', function () {
    // --- Menú lateral en móvil ---
    var sidebar = document.getElementById('sidebar');
    var overlay = document.getElementById('sidebar-overlay');
    var toggles = document.querySelectorAll('[data-toggle-sidebar]');

    function closeSidebar() {
        if (!sidebar) return;
        sidebar.classList.remove('open');
        if (overlay) overlay.classList.add('hidden');
    }
    function openSidebar() {
        if (!sidebar) return;
        sidebar.classList.add('open');
        if (overlay) overlay.classList.remove('hidden');
    }

    toggles.forEach(function (btn) {
        btn.addEventListener('click', function (e) {
            e.preventDefault();
            if (sidebar && sidebar.classList.contains('open')) {
                closeSidebar();
            } else {
                openSidebar();
            }
        });
    });
    if (overlay) overlay.addEventListener('click', closeSidebar);

    // --- Confirmaciones de acciones destructivas ---
    document.querySelectorAll('form[data-confirm]').forEach(function (form) {
        form.addEventListener('submit', function (e) {
            if (!window.confirm(form.getAttribute('data-confirm'))) {
                e.preventDefault();
            }
        });
    });

    // --- Toasts: se ocultan solos y se pueden cerrar ---
    function dismissToast(el) {
        if (!el || el.dataset.closing === '1') return;
        el.dataset.closing = '1';
        el.classList.add('is-leaving');
        setTimeout(function () { el.remove(); }, 320);
    }

    document.querySelectorAll('[data-auto-hide]').forEach(function (el) {
        setTimeout(function () {
            dismissToast(el);
        }, parseInt(el.getAttribute('data-auto-hide'), 10) || 6000);
    });

    document.addEventListener('click', function (e) {
        var btn = e.target.closest ? e.target.closest('[data-toast-close]') : null;
        if (btn) dismissToast(btn.closest('.toast'));
    });

    // --- Selector visual de rol en el login (CU01) ---
    var roleCards = document.querySelectorAll('[data-role]');
    var roleHint = document.getElementById('role-hint');
    roleCards.forEach(function (card) {
        card.addEventListener('click', function () {
            roleCards.forEach(function (c) { c.classList.remove('is-active'); });
            card.classList.add('is-active');
            if (roleHint) {
                roleHint.textContent = card.getAttribute('data-hint') || '';
                roleHint.classList.toggle('hidden', !roleHint.textContent);
            }
        });
    });

    // --- Cerrar sesión en pestañas antiguas (menú desplegable simple) ---
    var userMenu = document.getElementById('user-menu-button');
    var userPanel = document.getElementById('user-menu');
    if (userMenu && userPanel) {
        userMenu.addEventListener('click', function (e) {
            e.stopPropagation();
            userPanel.classList.toggle('hidden');
        });
        document.addEventListener('click', function () {
            userPanel.classList.add('hidden');
        });
    }
});
