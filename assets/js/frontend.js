(function () {
	'use strict';

	document.addEventListener('click', function (event) {
		var toggle = event.target.closest('.tarw-read-more');
		if (toggle) {
			var container = toggle.closest('.tarw-review-text');
			var shortText = container.querySelector('.tarw-review-short');
			var fullText = container.querySelector('.tarw-review-full');
			var expanded = toggle.getAttribute('aria-expanded') === 'true';

			toggle.setAttribute('aria-expanded', expanded ? 'false' : 'true');
			shortText.hidden = !expanded;
			fullText.hidden = expanded;
			toggle.textContent = expanded ? toggle.dataset.more : toggle.dataset.less;
			return;
		}

		var control = event.target.closest('.tarw-carousel-prev, .tarw-carousel-next');
		if (!control) {
			return;
		}

		var widget = control.closest('.tarw-layout-carousel');
		var track = widget.querySelector('.tarw-reviews');
		var card = track.querySelector('.tarw-review');
		if (!card) {
			return;
		}

		var direction = control.classList.contains('tarw-carousel-prev') ? -1 : 1;
		var gap = parseFloat(window.getComputedStyle(track).gap) || 0;
		track.scrollBy({ left: direction * (card.getBoundingClientRect().width + gap), behavior: 'smooth' });
	});
}());
