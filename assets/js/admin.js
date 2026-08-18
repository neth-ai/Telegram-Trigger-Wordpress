(function () {
	'use strict';

	function updateMasterState(master) {
		var card = master.closest('.myp-card');

		if (!card) {
			return;
		}

		card.classList.toggle('myp-card--disabled', !master.checked);
	}

	function bindMasterToggles() {
		document.querySelectorAll('.myp-master input[type="checkbox"]').forEach(function (master) {
			updateMasterState(master);

			master.addEventListener('change', function () {
				updateMasterState(master);
			});
		});
	}

	function bindPasswordToggles() {
		document.querySelectorAll('[data-myp-password-toggle]').forEach(function (button) {
			button.addEventListener('click', function () {
				var input = document.getElementById(button.getAttribute('data-myp-password-toggle'));
				var icon = button.querySelector('.dashicons');

				if (!input) {
					return;
				}

				var showing = input.type === 'text';
				input.type = showing ? 'password' : 'text';
				button.setAttribute('aria-label', showing ? button.getAttribute('data-label-show') : button.getAttribute('data-label-hide'));

				if (icon) {
					icon.classList.toggle('dashicons-visibility', showing);
					icon.classList.toggle('dashicons-hidden', !showing);
				}
			});
		});
	}

	function initializeAdmin() {
		bindMasterToggles();
		bindPasswordToggles();
	}

	if (document.readyState === 'loading') {
		document.addEventListener('DOMContentLoaded', initializeAdmin);
	} else {
		initializeAdmin();
	}
})();
