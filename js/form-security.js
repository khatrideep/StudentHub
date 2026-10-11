document.addEventListener('DOMContentLoaded', async () => {
    const forms = [...document.querySelectorAll('form[data-csrf]')];
    if (forms.length === 0) {
        return;
    }
    const buttons = forms.flatMap((form) => [...form.querySelectorAll('[type="submit"]')]);
    buttons.forEach((button) => {
        button.disabled = true;
    });

    try {
        const response = await fetch('../pages/csrf.php', {
            credentials: 'same-origin',
            headers: { Accept: 'application/json' }
        });
        if (!response.ok) {
            throw new Error(`CSRF token request failed with status ${response.status}.`);
        }

        const { csrfToken } = await response.json();
        if (typeof csrfToken !== 'string' || csrfToken.length === 0) {
            throw new Error('The server returned an invalid CSRF token.');
        }

        forms.forEach((form) => {
            const input = document.createElement('input');
            input.type = 'hidden';
            input.name = 'csrf_token';
            input.value = csrfToken;
            form.append(input);
        });
        buttons.forEach((button) => {
            button.disabled = false;
        });
    } catch (error) {
        console.error('Unable to secure form submission.', error);
        forms.forEach((form) => {
            const message = document.createElement('p');
            message.className = 'auth-message';
            message.setAttribute('role', 'alert');
            message.textContent = 'Secure form setup failed. Reload the page and try again.';
            form.before(message);
        });
    }
});
