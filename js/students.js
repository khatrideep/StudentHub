document.addEventListener('DOMContentLoaded', async () => {
    const studentsContainer = document.getElementById('studentsContainer');
    const searchInput = document.getElementById('studentSearch');
    const programFilter = document.getElementById('programFilter');
    const yearFilter = document.getElementById('yearFilter');
    const sortSelect = document.getElementById('studentSort');
    const status = document.getElementById('directoryStatus');
    const pagination = document.getElementById('studentPagination');

    if (!studentsContainer || !searchInput || !programFilter || !yearFilter || !sortSelect || !status || !pagination) {
        return;
    }

    const pageSize = 9;
    let students = [];
    let currentPage = 1;
    let usingCachedData = false;

    const text = (value) => typeof value === 'string' ? value.trim() : '';

    const addFilterOptions = (select, values, label) => {
        [...new Set(values)].sort((left, right) => left.localeCompare(right)).forEach((value) => {
            const option = document.createElement('option');
            option.value = value;
            option.textContent = value;
            select.append(option);
        });
        select.setAttribute('aria-label', label);
    };

    const createDetail = (list, label, value, email = false) => {
        const term = document.createElement('dt');
        term.textContent = label;
        const description = document.createElement('dd');
        if (email && /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(value)) {
            const link = document.createElement('a');
            link.href = `mailto:${value}`;
            link.textContent = value;
            description.append(link);
        } else {
            description.textContent = value || 'Not provided';
        }
        list.append(term, description);
    };

    const filteredStudents = () => {
        const query = searchInput.value.trim().toLocaleLowerCase();
        const program = programFilter.value;
        const year = yearFilter.value;

        const results = students.filter((student) => {
            const matchesQuery = !query || [
                student.name,
                student.program,
                student.year,
                student.email,
                student.city
            ].some((value) => text(value).toLocaleLowerCase().includes(query));
            return matchesQuery
                && (!program || student.program === program)
                && (!year || student.year === year);
        });
        const sortKey = sortSelect.value;
        const studentSortKey = sortKey === 'course' ? 'program' : sortKey;
        return results.sort((left, right) => text(left[studentSortKey]).localeCompare(text(right[studentSortKey])) || text(left.name).localeCompare(text(right.name)));
    };

    const render = () => {
        const results = filteredStudents();
        const pageCount = Math.ceil(results.length / pageSize);
        currentPage = Math.min(currentPage, Math.max(1, pageCount));
        const pageResults = results.slice((currentPage - 1) * pageSize, currentPage * pageSize);

        studentsContainer.replaceChildren();
        pageResults.forEach((student) => {
            const card = document.createElement('article');
            card.className = 'card student-card';

            const heading = document.createElement('h2');
            heading.textContent = text(student.name);
            const details = document.createElement('dl');
            createDetail(details, 'Course', text(student.program));
            createDetail(details, 'Year', text(student.year));
            createDetail(details, 'City', text(student.city));
            createDetail(details, 'Email', text(student.email), true);
            card.append(heading, details);
            studentsContainer.append(card);
        });

        status.dataset.state = results.length ? 'ready' : 'empty';
        status.textContent = results.length
            ? `${results.length} student${results.length === 1 ? '' : 's'} found${usingCachedData ? ' · showing saved data while offline' : ''}`
            : 'No students match your search. Try changing the search or filters.';

        pagination.replaceChildren();
        pagination.hidden = pageCount <= 1;
        if (pageCount > 1) {
            const previous = document.createElement('button');
            previous.type = 'button';
            previous.className = 'btn btn-outline';
            previous.textContent = 'Previous';
            previous.disabled = currentPage === 1;
            previous.addEventListener('click', () => {
                currentPage -= 1;
                render();
            });

            const current = document.createElement('span');
            current.textContent = `Page ${currentPage} of ${pageCount}`;
            current.setAttribute('aria-current', 'page');

            const next = document.createElement('button');
            next.type = 'button';
            next.className = 'btn btn-outline';
            next.textContent = 'Next';
            next.disabled = currentPage === pageCount;
            next.addEventListener('click', () => {
                currentPage += 1;
                render();
            });

            pagination.append(previous, current, next);
        }
    };

    const onFilterChange = () => {
        currentPage = 1;
        render();
    };
    searchInput.addEventListener('input', onFilterChange);
    programFilter.addEventListener('change', onFilterChange);
    yearFilter.addEventListener('change', onFilterChange);
    try {
        const savedSort = localStorage.getItem('studenthub-directory-sort');
        if (['name', 'course', 'year', 'city'].includes(savedSort)) {
            sortSelect.value = savedSort;
        }
    } catch (error) {
        console.warn('Unable to restore the student directory sort preference.', error);
    }
    sortSelect.addEventListener('change', () => {
        try {
            localStorage.setItem('studenthub-directory-sort', sortSelect.value);
        } catch (error) {
            console.warn('Unable to save the student directory sort preference.', error);
        }
        onFilterChange();
    });

    try {
        const response = await fetch('../data/students.json', {
            headers: { Accept: 'application/json' }
        });
        if (!response.ok) {
            throw new Error(`Student directory request failed with status ${response.status}.`);
        }

        const data = await response.json();
        if (!Array.isArray(data) || data.some((student) => !student || typeof student !== 'object')) {
            throw new Error('The student directory data has an invalid format.');
        }
        students = data;
        try {
            localStorage.setItem('studenthub-directory-cache-v1', JSON.stringify(students));
        } catch (error) {
            console.warn('Unable to cache the student directory data.', error);
        }
        addFilterOptions(programFilter, students.map((student) => text(student.program)).filter(Boolean), 'Filter by course');
        addFilterOptions(yearFilter, students.map((student) => text(student.year)).filter(Boolean), 'Filter by academic year');
        studentsContainer.setAttribute('aria-busy', 'false');
        render();
    } catch (error) {
        try {
            const cached = JSON.parse(localStorage.getItem('studenthub-directory-cache-v1') || 'null');
            if (Array.isArray(cached) && cached.every((student) => student && typeof student === 'object')) {
                students = cached;
                usingCachedData = true;
                addFilterOptions(programFilter, students.map((student) => text(student.program)).filter(Boolean), 'Filter by course');
                addFilterOptions(yearFilter, students.map((student) => text(student.year)).filter(Boolean), 'Filter by academic year');
                studentsContainer.setAttribute('aria-busy', 'false');
                render();
                console.warn('Showing cached Student Hub directory after a failed live request.', error);
                return;
            }
        } catch (cacheError) {
            console.warn('The saved student directory cache could not be read.', cacheError);
        }
        studentsContainer.setAttribute('aria-busy', 'false');
        status.dataset.state = 'error';
        status.textContent = 'Student information could not be loaded. Please refresh the page to try again.';
        console.error('Unable to load the Student Hub student directory.', error);
    }
});
