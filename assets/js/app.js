'use strict';

document.querySelectorAll('.toggle-password').forEach((button) => {
    button.addEventListener('click', () => {
        const input = document.getElementById(button.dataset.target);
        if (!(input instanceof HTMLInputElement)) {
            return;
        }
        const showing = input.type === 'text';
        input.type = showing ? 'password' : 'text';
        button.textContent = showing ? 'Show' : 'Hide';
    });
});

const apiForm = document.getElementById('api-demo-form');
const apiResult = document.getElementById('api-result');
if (apiForm instanceof HTMLFormElement && apiResult instanceof HTMLElement) {
    apiForm.addEventListener('submit', async (event) => {
        event.preventDefault();
        const id = new FormData(apiForm).get('id');
        apiResult.textContent = 'Loading…';
        try {
            const response = await fetch(`/api/users.php?id=${encodeURIComponent(String(id))}`, {
                headers: { Accept: 'application/json' },
            });
            const body = await response.json();
            apiResult.textContent = `HTTP ${response.status}\n${JSON.stringify(body, null, 2)}`;
        } catch (_error) {
            apiResult.textContent = 'The API request failed.';
        }
    });
}
