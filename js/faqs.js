document.addEventListener('DOMContentLoaded', async () => {
    const list = document.getElementById('faqList');
    const search = document.getElementById('faqSearch');
    const status = document.getElementById('faqStatus');
    if (!list || !search || !status) return;

    let faqs = [];
    let showingCachedData = false;

    const render = () => {
        const query = search.value.trim().toLocaleLowerCase();
        const matches = faqs.filter((faq) => `${faq.question} ${faq.answer}`.toLocaleLowerCase().includes(query));
        list.replaceChildren();
        matches.forEach((faq, index) => {
            const card = document.createElement('article');
            card.className = 'card faq-question';
            const question = document.createElement('button');
            question.className = 'faq-trigger';
            question.type = 'button';
            question.setAttribute('aria-expanded', 'false');
            question.textContent = faq.question;
            const answer = document.createElement('p');
            answer.className = 'text-muted faq-answer';
            answer.id = `faq-answer-${index}`;
            answer.textContent = faq.answer;
            answer.hidden = true;
            question.setAttribute('aria-controls', answer.id);
            question.addEventListener('click', () => {
                const isExpanded = question.getAttribute('aria-expanded') === 'true';
                question.setAttribute('aria-expanded', String(!isExpanded));
                answer.hidden = isExpanded;
            });
            card.append(question, answer);
            list.append(card);
        });
        status.textContent = matches.length
            ? `${matches.length} question${matches.length === 1 ? '' : 's'} found${showingCachedData ? ' · showing saved data while offline' : ''}`
            : 'No questions match your search.';
    };

    search.addEventListener('input', render);
    try {
        const response = await fetch('../data/faqs.json', { headers: { Accept: 'application/json' } });
        if (!response.ok) throw new Error(`FAQ request failed with status ${response.status}.`);
        const data = await response.json();
        if (!Array.isArray(data) || !data.every((faq) => faq && typeof faq.question === 'string' && typeof faq.answer === 'string')) {
            throw new Error('The FAQ file does not contain valid question and answer records.');
        }
        faqs = data;
        try {
            localStorage.setItem('studenthub-faq-cache-v1', JSON.stringify(faqs));
        } catch (error) {
            console.warn('Unable to cache the FAQ data.', error);
        }
    } catch (error) {
        try {
            const cached = JSON.parse(localStorage.getItem('studenthub-faq-cache-v1') || 'null');
            if (Array.isArray(cached) && cached.every((faq) => faq && typeof faq.question === 'string' && typeof faq.answer === 'string')) {
                faqs = cached;
                showingCachedData = true;
                console.warn('Showing cached FAQs after a failed live request.', error);
            }
        } catch (cacheError) {
            console.warn('The saved FAQ cache could not be read.', cacheError);
        }
        if (!faqs.length) {
            list.setAttribute('aria-busy', 'false');
            status.setAttribute('data-state', 'error');
            status.textContent = 'Questions could not be loaded. Please refresh the page to try again.';
            return;
        }
    }
    list.setAttribute('aria-busy', 'false');
    render();
});
