(function () {
	'use strict';

	function bindMasterToggles() {
		document.querySelectorAll('.myp-master input[type="checkbox"]').forEach(function (master) {
			master.addEventListener('change', function () {
				var card = master.closest('.myp-card');

				if (!card) {
					return;
				}

				card.classList.toggle('myp-card--disabled', !master.checked);
			});
		});
	}

	if (document.readyState === 'loading') {
		document.addEventListener('DOMContentLoaded', bindMasterToggles);
	} else {
		bindMasterToggles();
	}
})();
