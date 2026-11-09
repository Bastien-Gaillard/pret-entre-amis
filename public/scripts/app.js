// Petites améliorations progressives : tout fonctionne aussi sans JavaScript.

// Confirmation avant suppression (formulaires marqués data-confirm).
document.addEventListener('submit', (event) => {
    const message = event.target.dataset?.confirm;
    if (message && !window.confirm(message)) {
        event.preventDefault();
    }
});

// Les filtres de l'historique s'appliquent dès qu'on change une valeur.
document.querySelectorAll('form[data-autosubmit]').forEach((form) => {
    form.addEventListener('change', () => form.requestSubmit());
});

// Les messages de confirmation se ferment au clic ou tout seuls après quelques secondes.
document.querySelectorAll('.toast').forEach((toast) => {
    const close = () => {
        toast.classList.add('is-leaving');
        setTimeout(() => toast.remove(), 300);
    };
    toast.querySelector('.toast-close')?.addEventListener('click', close);
    if (toast.classList.contains('toast-success')) {
        setTimeout(close, 5000);
    }
});
