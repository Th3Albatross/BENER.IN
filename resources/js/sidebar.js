/**
 * BENER.IN: mobile navigation panel.
 *
 * On screens at or below the mobile breakpoint the sidebar collapses into a
 * sticky bar with a dropdown panel. The panel overlays the page (it never
 * pushes the content down) and closes on: toggle, scrim tap, Escape, an
 * in-panel navigation, or a resize back to desktop.
 *
 * On desktop this module does nothing: the toggle is hidden and the panel is
 * always visible, so the attribute is simply never set.
 */
(() => {
    const MOBILE_QUERY = '(max-width: 700px)';

    const sidebar = document.querySelector('[data-sidebar]');
    const toggle = document.querySelector('[data-sidebar-toggle]');
    const scrim = document.querySelector('[data-sidebar-scrim]');
    const panel = document.querySelector('[data-sidebar-panel]');

    if (!sidebar || !toggle || !panel) return;

    const media = window.matchMedia?.(MOBILE_QUERY);
    const isMobile = () => (media ? media.matches : window.innerWidth <= 700);

    const isOpen = () => document.body.hasAttribute('data-sidebar-open');

    const setOpen = (open) => {
        // Never leave the page locked on a desktop viewport.
        const next = open && isMobile();

        document.body.toggleAttribute('data-sidebar-open', next);
        toggle.setAttribute('aria-expanded', next ? 'true' : 'false');
        toggle.setAttribute('aria-label', next ? 'Tutup menu navigasi' : 'Buka menu navigasi');
    };

    toggle.addEventListener('click', () => setOpen(!isOpen()));

    scrim?.addEventListener('click', () => setOpen(false));

    // Following a link should not leave the panel hanging open.
    panel.addEventListener('click', (event) => {
        if (event.target.closest('a')) setOpen(false);
    });

    document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape' && isOpen()) {
            setOpen(false);
            toggle.focus();
        }
    });

    // Reset when crossing the breakpoint so desktop never inherits an open panel.
    const handleBreakpoint = () => setOpen(false);

    if (media?.addEventListener) {
        media.addEventListener('change', handleBreakpoint);
    } else {
        window.addEventListener('resize', handleBreakpoint);
    }
})();
