document.addEventListener('DOMContentLoaded', () => {
    const api = 'api.php?resource=events';
    const tableBody = document.getElementById('eventTableBody');
    const form = document.getElementById('eventForm');
    const editor = document.getElementById('eventEditor');
    const message = document.getElementById('eventMessage');
    const search = document.getElementById('eventSearch');
    const categoryFilter = document.getElementById('eventCategoryFilter');
    const saveButton = document.getElementById('saveEventButton');
    let events = [];
    let csrfToken = '';

    const showMessage = (text, isError = false) => {
        message.textContent = text;
        message.className = `alert ${isError ? 'alert-dark' : 'alert-secondary'}`;
    };

    const apiErrorMessage = (error) => ({
        authentication_required: 'Your session expired. Sign in again.',
        administrator_required: 'Administrator access is required.',
        csrf_validation_failed: 'The form expired. Reload the page and try again.',
        poster_too_large: 'Choose a poster smaller than 2 MB.',
        poster_type_not_allowed: 'Choose a valid JPG, PNG, or WebP image.',
        poster_upload_failed: 'The poster could not be uploaded.',
        database_error: 'The event could not be saved because of a database error.',
        server_error: 'The request could not be completed. Check the server logs.'
    }[error] || 'Check the event details and try again.');

    const request = async (url, options = {}) => {
        const response = await fetch(url, {
            credentials: 'same-origin',
            ...options,
            headers: {
                Accept: 'application/json',
                ...(String(options.method || 'GET').toUpperCase() !== 'GET' ? { 'X-CSRF-Token': csrfToken } : {}),
                ...options.headers
            }
        });
        const payload = await response.json();
        if (!response.ok) {
            const error = new Error(apiErrorMessage(payload.error));
            error.payload = payload;
            throw error;
        }
        return payload;
    };

    const formatDate = (value) => {
        const date = new Date(String(value).replace(' ', 'T'));
        return Number.isNaN(date.getTime()) ? 'Date unavailable' : new Intl.DateTimeFormat(undefined, {
            dateStyle: 'medium',
            timeStyle: 'short'
        }).format(date);
    };

    const eventMatches = (event) => {
        const query = search.value.trim().toLocaleLowerCase();
        return (!query || [event.title, event.category, event.location]
            .some((value) => String(value || '').toLocaleLowerCase().includes(query)))
            && (!categoryFilter.value || event.category === categoryFilter.value);
    };

    const render = () => {
        const visible = events.filter(eventMatches);
        tableBody.replaceChildren();
        if (!visible.length) {
            const row = document.createElement('tr');
            const cell = document.createElement('td');
            cell.colSpan = 6;
            cell.className = 'text-center text-secondary py-4';
            cell.textContent = 'No events match these filters.';
            row.append(cell);
            tableBody.append(row);
            return;
        }

        visible.forEach((event) => {
            const row = document.createElement('tr');
            const titleCell = document.createElement('td');
            const eventName = document.createElement('div');
            eventName.className = 'd-flex align-items-center gap-2';
            if (event.poster_path) {
                const poster = document.createElement('img');
                poster.className = 'event-poster-thumb';
                poster.src = `../${event.poster_path}`;
                poster.alt = '';
                eventName.append(poster);
            }
            const title = document.createElement('strong');
            title.textContent = event.title;
            eventName.append(title);
            titleCell.append(eventName);

            const categoryCell = document.createElement('td');
            categoryCell.textContent = event.category;
            const dateCell = document.createElement('td');
            dateCell.textContent = formatDate(event.starts_at);
            const countCell = document.createElement('td');
            countCell.textContent = event.capacity
                ? `${event.registration_count} / ${event.capacity}`
                : `${event.registration_count}`;
            const statusCell = document.createElement('td');
            const status = document.createElement('span');
            status.className = 'status-pill';
            status.textContent = event.status === 'open' ? 'Open' : 'Closed';
            statusCell.append(status);

            const actionsCell = document.createElement('td');
            actionsCell.className = 'text-end text-nowrap';
            const editButton = document.createElement('button');
            editButton.type = 'button';
            editButton.className = 'btn btn-outline-secondary btn-sm me-1';
            editButton.textContent = 'Edit';
            editButton.addEventListener('click', () => openEditor(event));
            const deleteButton = document.createElement('button');
            deleteButton.type = 'button';
            deleteButton.className = 'btn btn-outline-secondary btn-sm';
            deleteButton.textContent = 'Delete';
            deleteButton.addEventListener('click', () => deleteEvent(event));
            actionsCell.append(editButton, deleteButton);
            row.append(titleCell, categoryCell, dateCell, countCell, statusCell, actionsCell);
            tableBody.append(row);
        });
    };

    const updateCategories = () => {
        const selected = categoryFilter.value;
        categoryFilter.replaceChildren(new Option('All categories', ''));
        [...new Set(events.map((event) => event.category).filter(Boolean))]
            .sort((left, right) => left.localeCompare(right))
            .forEach((category) => categoryFilter.add(new Option(category, category)));
        categoryFilter.value = selected;
    };

    const loadEvents = async () => {
        tableBody.innerHTML = '<tr><td colspan="6" class="text-center py-4">Loading events…</td></tr>';
        try {
            const payload = await request(api);
            if (!Array.isArray(payload.data)) {
                throw new Error('The server returned an invalid event list.');
            }
            events = payload.data;
            updateCategories();
            render();
        } catch (error) {
            showMessage(error.message, true);
            tableBody.innerHTML = '<tr><td colspan="6" class="text-center py-4">Events could not be loaded.</td></tr>';
            console.error('Unable to load admin events.', error);
        }
    };

    const openEditor = (event = null) => {
        form.reset();
        document.getElementById('eventId').value = event?.event_id || '';
        document.getElementById('eventTitle').value = event?.title || '';
        document.getElementById('eventDescription').value = event?.description || '';
        document.getElementById('eventCategory').value = event?.category || '';
        document.getElementById('eventLocation').value = event?.location || '';
        document.getElementById('eventCapacity').value = event?.capacity || '';
        document.getElementById('eventStatus').value = event?.status || 'open';
        document.getElementById('eventDate').value = event
            ? String(event.starts_at).replace(' ', 'T').slice(0, 16)
            : '';
        document.getElementById('eventFormTitle').textContent = event ? 'Edit event' : 'Create event';
        editor.hidden = false;
        document.getElementById('eventTitle').focus();
        editor.scrollIntoView({ behavior: 'smooth', block: 'start' });
    };

    const deleteEvent = async (event) => {
        if (!window.confirm(`Delete "${event.title}" and its registrations? This cannot be undone.`)) {
            return;
        }
        try {
            const payload = await request(`${api}&id=${encodeURIComponent(event.event_id)}`, { method: 'DELETE' });
            showMessage(payload.message);
            await loadEvents();
        } catch (error) {
            showMessage(error.message, true);
            console.error('Unable to delete event.', error);
        }
    };

    document.getElementById('newEventButton').addEventListener('click', () => openEditor());
    document.getElementById('cancelEventButton').addEventListener('click', () => {
        editor.hidden = true;
        form.reset();
    });
    search.addEventListener('input', render);
    categoryFilter.addEventListener('change', render);
    form.addEventListener('submit', async (event) => {
        event.preventDefault();
        saveButton.disabled = true;
        const formData = new FormData(form);
        if (!formData.get('id')) {
            formData.delete('id');
        }
        try {
            const payload = await request(api, { method: 'POST', body: formData });
            showMessage(payload.message);
            editor.hidden = true;
            form.reset();
            await loadEvents();
        } catch (error) {
            showMessage(error.message, true);
            console.error('Unable to save event.', error);
        } finally {
            saveButton.disabled = false;
        }
    });

    const initialize = async () => {
        try {
            const response = await fetch('csrf.php', { credentials: 'same-origin', headers: { Accept: 'application/json' } });
            if (!response.ok) {
                throw new Error(`CSRF token request failed with status ${response.status}.`);
            }
            const payload = await response.json();
            if (typeof payload.csrfToken !== 'string' || !payload.csrfToken) {
                throw new Error('The server returned an invalid CSRF token.');
            }
            csrfToken = payload.csrfToken;
            await loadEvents();
        } catch (error) {
            showMessage('The secure event manager could not be initialized. Reload the page or sign in again.', true);
            tableBody.innerHTML = '<tr><td colspan="6" class="text-center py-4">Event management is unavailable.</td></tr>';
            console.error('Unable to initialize admin event management.', error);
        }
    };

    initialize();
});
