function toggleAnswer(question) {
	const answer = question.querySelector('.faq-answer');
	const isOpen = question.getAttribute('aria-expanded') === 'true';

	question.setAttribute('aria-expanded', String(!isOpen));
	answer.hidden = isOpen;
}

document.querySelectorAll('.faq-question').forEach((question) => {
	question.addEventListener('click', () => toggleAnswer(question));
	question.addEventListener('keydown', (event) => {
		if (event.key === 'Enter' || event.key === ' ') {
			event.preventDefault();
			toggleAnswer(question);
		}
	});
});
