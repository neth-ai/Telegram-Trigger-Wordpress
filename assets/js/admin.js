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

	function normalizeChatIds(value) {
		return value
			.replace(/[\u2212\u2013\u2014\uFF0D]/g, '-')
			.split(/[,;\r\n]+/)
			.map(function (part) {
				var chatId = part.trim();
				var digits = chatId.replace(/^-+/, '');

				return /^[1-9][0-9]{0,18}$/.test(digits) ? '-' + digits : chatId;
			})
			.filter(function (chatId) {
				return chatId !== '';
			})
			.join(', ');
	}

	function bindChatIdNormalization() {
		document.querySelectorAll('[data-myp-chat-ids]').forEach(function (input) {
			input.addEventListener('blur', function () {
				input.value = normalizeChatIds(input.value);
			});

			if (input.form) {
				input.form.addEventListener('submit', function () {
					input.value = normalizeChatIds(input.value);
				});
			}
		});
	}

	function renderFormatPreview(card) {
		var template = card.querySelector('[data-myp-format-template]');
		var icon = card.querySelector('[data-myp-format-icon]');
		var preview = card.querySelector('[data-myp-format-preview]');
		var showRole = card.querySelector('[data-myp-show-role]');
		var optional = ['categories', 'link', 'detail', 'role', 'item', 'version', 'content_title', 'details'];
		var values;

		if (!template || !preview) {
			return;
		}

		try {
			values = JSON.parse(card.getAttribute('data-preview-values') || '{}');
		} catch (error) {
			values = {};
		}

		values.icon = icon ? icon.value : '';

		if (showRole && !showRole.checked) {
			values.role = '';
		}

		preview.textContent = template.value
			.replace(/\r\n?/g, '\n')
			.split('\n')
			.filter(function (line) {
				return !optional.some(function (key) {
					return line.indexOf('{' + key + '}') !== -1 && (!values[key] || String(values[key]).trim() === '');
				});
			})
			.join('\n')
			.replace(/\{([a-z_]+)\}/g, function (placeholder, key) {
				return Object.prototype.hasOwnProperty.call(values, key) ? String(values[key]) : placeholder;
			})
			.trim()
			.replace(/\n{3,}/g, '\n\n');
	}

	function bindMessageFormats() {
		document.querySelectorAll('[data-myp-format-card]').forEach(function (card) {
			var template = card.querySelector('[data-myp-format-template]');
			var icon = card.querySelector('[data-myp-format-icon]');
			var showRole = card.querySelector('[data-myp-show-role]');

			card.querySelectorAll('[data-myp-insert-placeholder]').forEach(function (button) {
				button.addEventListener('click', function () {
					var placeholder = button.getAttribute('data-myp-insert-placeholder') || '';
					var start;
					var end;

					if (!template) {
						return;
					}

					start = template.selectionStart;
					end = template.selectionEnd;
					template.setRangeText(placeholder, start, end, 'end');
					template.focus();
					renderFormatPreview(card);
				});
			});

			[template, icon, showRole].forEach(function (field) {
				if (field) {
					field.addEventListener('input', function () {
						renderFormatPreview(card);
					});
					field.addEventListener('change', function () {
						renderFormatPreview(card);
					});
				}
			});

			renderFormatPreview(card);
		});
	}

	function initializeAdmin() {
		bindMasterToggles();
		bindPasswordToggles();
		bindChatIdNormalization();
		bindMessageFormats();
	}

	if (document.readyState === 'loading') {
		document.addEventListener('DOMContentLoaded', initializeAdmin);
	} else {
		initializeAdmin();
	}
})();
