const summaryEndpoint = new URL('../pages/stats.php', document.currentScript.src);

document.addEventListener('DOMContentLoaded', async () => {
    const stats = document.querySelectorAll('[data-home-stat]');
    if (stats.length === 0) {
        return;
    }

    try {
        const response = await fetch(summaryEndpoint, { headers: { Accept: 'application/json' } });
        if (!response.ok) {
            throw new Error(`Home summary request failed with status ${response.status}.`);
        }

        const summary = await response.json();
        const values = {
            events: String(summary.upcoming_events ?? 0),
            notices: String(summary.active_notices ?? 0),
            nextEvent: summary.next_event
                ? new Intl.DateTimeFormat(undefined, { month: 'short', day: 'numeric' }).format(new Date(summary.next_event.replace(' ', 'T')))
                : '—'
        };

        stats.forEach((element) => {
            element.textContent = values[element.dataset.homeStat] ?? '—';
        });
    } catch (error) {
        stats.forEach((element) => {
            element.textContent = 'Unavailable';
        });
        console.error('Unable to load the Student Hub home summary.', error);
    }
});
