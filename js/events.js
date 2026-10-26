document.addEventListener('DOMContentLoaded', async () => {
    const list = document.getElementById('sampleEventList');
    const status = document.getElementById('sampleEventStatus');
    const search = document.getElementById('eventSearch');
    const categoryFilter = document.getElementById('eventCategory');
    const sortSelect = document.getElementById('eventSort');
    const pagination = document.getElementById('eventPagination');
    if (!list || !status || !search || !categoryFilter || !sortSelect || !pagination) return;

    const pageSize = 7;
    const isEvent = (event) =>
        event &&
        typeof event.title === 'string' &&
        typeof event.date === 'string' &&
        /^\d{4}-\d{2}-\d{2}$/.test(event.date) &&
        typeof event.category === 'string' &&
        typeof event.description === 'string' &&
        typeof event.venue === 'string' &&
        Number.isInteger(event.seatsLeft) &&
        event.seatsLeft >= 0;

    let events = [];
    let currentPage = 1;

    const renderPagination = (totalPages) => {
        pagination.replaceChildren();
        pagination.hidden = totalPages <= 1;
        if (totalPages <= 1) return;

        const addButton = (label, page, { disabled = false, current = false, ariaLabel } = {}) => {
            const button = document.createElement('button');
            button.type = 'button';
            button.className = 'event-page-button';
            button.textContent = label;
            button.disabled = disabled;
            if (current) {
                button.classList.add('is-current');
                button.setAttribute('aria-current', 'page');
            }
            if (ariaLabel) button.setAttribute('aria-label', ariaLabel);
            button.addEventListener('click', () => {
                currentPage = page;
                renderEvents();
                list.scrollIntoView({ behavior: 'smooth', block: 'start' });
            });
            pagination.append(button);
        };

        addButton('Previous', currentPage - 1, {
            disabled: currentPage === 1,
            ariaLabel: 'Previous page'
        });

        for (let page = 1; page <= totalPages; page += 1) {
            addButton(String(page), page, {
                current: page === currentPage,
                ariaLabel: `Page ${page}`
            });
        }

        addButton('Next', currentPage + 1, {
            disabled: currentPage === totalPages,
            ariaLabel: 'Next page'
        });
    };

    const renderEvents = () => {
        const query = search.value.trim().toLocaleLowerCase();
        const category = categoryFilter.value;
        const [sortField, sortDirection] = sortSelect.value.split('-');
        const direction = sortDirection === 'desc' ? -1 : 1;
        const matches = events
            .filter((event) => !category || event.category === category)
            .filter((event) =>
                `${event.title} ${event.category} ${event.description} ${event.venue}`
                    .toLocaleLowerCase()
                    .includes(query),
            )
            .sort((a, b) => {
                if (sortField === 'title') {
                    return direction * a.title.localeCompare(b.title);
                }
                if (sortField === 'seats') {
                    return direction * (a.seatsLeft - b.seatsLeft);
                }
                return direction * a.date.localeCompare(b.date);
            });

        const totalPages = Math.ceil(matches.length / pageSize);
        currentPage = Math.min(currentPage, Math.max(totalPages, 1));
        const startIndex = (currentPage - 1) * pageSize;
        const pageEvents = matches.slice(startIndex, startIndex + pageSize);
        const fragment = document.createDocumentFragment();
        pageEvents.forEach((event) => {
            const card = document.createElement('article');
            card.className = 'card event-card';

            const dateTag = document.createElement('span');
            dateTag.className = 'tag';
            dateTag.textContent = new Date(`${event.date}T00:00:00`).toLocaleDateString(undefined, {
                year: 'numeric',
                month: 'short',
                day: 'numeric'
            });

            const categoryTag = document.createElement('span');
            categoryTag.className = 'tag';
            categoryTag.textContent = event.category;

            const title = document.createElement('h3');
            title.textContent = event.title;

            const description = document.createElement('p');
            description.textContent = event.description;

            const venue = document.createElement('p');
            venue.className = 'text-muted event-venue';
            venue.textContent = event.venue;

            const seats = document.createElement('p');
            seats.className = 'text-muted event-seats';
            seats.textContent = `${event.seatsLeft} places remaining`;

            card.append(dateTag, categoryTag, title, description, venue, seats);
            fragment.append(card);
        });

        list.replaceChildren(fragment);
        status.textContent = matches.length
            ? `Showing ${startIndex + 1}-${startIndex + pageEvents.length} of ${matches.length} events.`
            : 'No events match your search and filters.';
        renderPagination(totalPages);
    };

    const resetPageAndRender = () => {
        currentPage = 1;
        renderEvents();
    };

    search.addEventListener('input', resetPageAndRender);
    categoryFilter.addEventListener('change', resetPageAndRender);
    sortSelect.addEventListener('change', resetPageAndRender);

    try {
        const response = await fetch('../data/events.json', {
            cache: 'no-store',
            headers: { Accept: 'application/json' }
        });
        if (!response.ok) {
            throw new Error(`Sample events request failed with status ${response.status}.`);
        }

        const data = await response.json();
        if (!Array.isArray(data) || !data.every(isEvent)) {
            throw new Error('The sample events file contains invalid event records.');
        }

        events = data;
        const categories = [...new Set(events.map((event) => event.category))]
            .sort((a, b) => a.localeCompare(b));
        categories.forEach((value) => {
            const option = document.createElement('option');
            option.value = value;
            option.textContent = value;
            categoryFilter.append(option);
        });

        renderEvents();
    } catch (error) {
        console.error('Unable to load events from data/events.json.', error);
        status.textContent = 'Events could not be loaded. Please refresh the page to try again.';
        status.dataset.state = 'error';
    } finally {
        status.setAttribute('aria-busy', 'false');
    }
});
