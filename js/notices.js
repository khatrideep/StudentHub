document.addEventListener('DOMContentLoaded', async () => {
    const list = document.getElementById('noticeJsonList');
    const status = document.getElementById('noticeJsonStatus');
    const search = document.getElementById('noticeSearch');
    const categoryFilter = document.getElementById('noticeCategory');
    const sortSelect = document.getElementById('noticeSort');
    const pagination = document.getElementById('noticeJsonPagination');
    if (!list || !status || !search || !categoryFilter || !sortSelect || !pagination) return;

    const pageSize = 5;
    const isValidDate = (value) => {
        if (!/^\d{4}-\d{2}-\d{2}$/.test(value)) return false;
        const parsed = new Date(`${value}T00:00:00Z`);
        return !Number.isNaN(parsed.getTime()) && parsed.toISOString().slice(0, 10) === value;
    };
    const isNotice = (notice) =>
        notice &&
        Number.isInteger(notice.id) &&
        typeof notice.title === 'string' &&
        typeof notice.category === 'string' &&
        typeof notice.message === 'string' &&
        typeof notice.published_at === 'string' &&
        isValidDate(notice.published_at);

    let notices = [];
    let currentPage = 1;

    const renderPagination = (totalPages) => {
        pagination.replaceChildren();
        pagination.hidden = totalPages <= 1;
        if (totalPages <= 1) return;

        const addButton = (label, page, { disabled = false, current = false, ariaLabel } = {}) => {
            const button = document.createElement('button');
            button.type = 'button';
            button.className = 'notice-page-button';
            button.textContent = label;
            button.disabled = disabled;
            if (current) {
                button.classList.add('is-current');
                button.setAttribute('aria-current', 'page');
            }
            if (ariaLabel) button.setAttribute('aria-label', ariaLabel);
            button.addEventListener('click', () => {
                currentPage = page;
                renderNotices();
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

    const renderNotices = () => {
        const query = search.value.trim().toLocaleLowerCase();
        const category = categoryFilter.value;
        const [sortField, sortDirection] = sortSelect.value.split('-');
        const direction = sortDirection === 'desc' ? -1 : 1;
        const matches = notices
            .filter((notice) => !category || notice.category === category)
            .filter((notice) =>
                `${notice.title} ${notice.category} ${notice.message}`
                    .toLocaleLowerCase()
                    .includes(query),
            )
            .sort((left, right) => {
                const comparison = sortField === 'title'
                    ? left.title.localeCompare(right.title)
                    : left.published_at.localeCompare(right.published_at);
                return direction * comparison || left.id - right.id;
            });

        const totalPages = Math.ceil(matches.length / pageSize);
        currentPage = Math.min(currentPage, Math.max(totalPages, 1));
        const startIndex = (currentPage - 1) * pageSize;
        const pageNotices = matches.slice(startIndex, startIndex + pageSize);
        const fragment = document.createDocumentFragment();

        pageNotices.forEach((notice) => {
            const card = document.createElement('article');
            card.className = 'notice-json-card';

            const meta = document.createElement('div');
            meta.className = 'notice-json-meta';

            const categoryTag = document.createElement('span');
            categoryTag.className = 'notice-label';
            categoryTag.textContent = notice.category;

            const date = document.createElement('time');
            date.dateTime = notice.published_at;
            const [year, month, day] = notice.published_at.split('-').map(Number);
            date.textContent = new Date(year, month - 1, day).toLocaleDateString(undefined, {
                year: 'numeric',
                month: 'short',
                day: 'numeric'
            });

            const title = document.createElement('h3');
            title.textContent = notice.title;

            const message = document.createElement('p');
            message.textContent = notice.message;

            meta.append(categoryTag, date);
            card.append(meta, title, message);
            fragment.append(card);
        });

        list.replaceChildren(fragment);
        status.textContent = matches.length
            ? `Showing ${startIndex + 1}-${startIndex + pageNotices.length} of ${matches.length} campus updates.`
            : 'No campus updates match your search and filters.';
        status.dataset.state = matches.length ? 'ready' : 'empty';
        renderPagination(totalPages);
    };

    const resetPageAndRender = () => {
        currentPage = 1;
        renderNotices();
    };

    search.addEventListener('input', resetPageAndRender);
    categoryFilter.addEventListener('change', resetPageAndRender);
    sortSelect.addEventListener('change', resetPageAndRender);

    try {
        const response = await fetch('../data/notices.json', {
            cache: 'no-store',
            headers: { Accept: 'application/json' }
        });
        if (!response.ok) {
            throw new Error(`Campus notices request failed with status ${response.status}.`);
        }

        const data = await response.json();
        if (!Array.isArray(data) || !data.every(isNotice)) {
            throw new Error('The campus notices file contains invalid notice records.');
        }
        if (new Set(data.map((notice) => notice.id)).size !== data.length) {
            throw new Error('The campus notices file contains duplicate notice IDs.');
        }

        notices = data;
        [...new Set(notices.map((notice) => notice.category))]
            .sort((left, right) => left.localeCompare(right))
            .forEach((value) => {
                const option = document.createElement('option');
                option.value = value;
                option.textContent = value;
                categoryFilter.append(option);
            });

        renderNotices();
    } catch (error) {
        console.error('Unable to load campus updates from data/notices.json.', error);
        status.textContent = 'Campus updates could not be loaded. Please refresh the page to try again.';
        status.dataset.state = 'error';
        list.replaceChildren();
        pagination.replaceChildren();
        pagination.hidden = true;
    } finally {
        status.setAttribute('aria-busy', 'false');
    }
});
