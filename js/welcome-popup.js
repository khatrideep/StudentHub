document.addEventListener('DOMContentLoaded', () => {
    const popup = document.getElementById('welcomePopup');
    if (!(popup instanceof HTMLDialogElement)) return;

    const continueButton = popup.querySelector('[data-close-welcome]');
    continueButton?.addEventListener('click', () => popup.close());

    popup.addEventListener('click', (event) => {
        if (event.target === popup) {
            popup.close();
        }
    });

    popup.showModal();
});
