(function ($) {
	'use strict';

	var L10N = window.wpaeSettingsL10n || {};

	function t(key, fallback) {
		return typeof L10N[key] === 'string' ? L10N[key] : fallback;
	}

	/* ------------------------------------------------------------------ *
	 * Unsaved-changes guard
	 *
	 * The screen saves with an ordinary submit button at the end of the panel,
	 * the way every other action on it works. This exists only so that leaving
	 * the page with edits on it warns first.
	 * ------------------------------------------------------------------ */

	function serialize($form) {
		// Skip the nonce and the regenerate flag; neither is a user edit.
		return $form
			.find('input, select, textarea')
			.filter(function () {
				return this.name && !/^_wpnonce|^_wp_http_referer$|^regenerate_cron_job_key$/.test(this.name);
			})
			.map(function () {
				if (this.type === 'checkbox' || this.type === 'radio') {
					return this.name + '=' + (this.checked ? '1' : '0');
				}
				return this.name + '=' + this.value;
			})
			.get()
			.join('&');
	}

	function initUnsavedGuard($form) {
		var $save = $form.find('[data-wpae-save]');

		if (!$save.length) {
			return;
		}

		var clean = serialize($form);
		var submitting = false;

		$form.on('submit', function () {
			submitting = true;
			$save.prop('disabled', true).text(t('saving', 'Saving…'));
		});

		$(window).on('beforeunload', function () {
			if (!submitting && serialize($form) !== clean) {
				return t('leaveWarning', 'You have unsaved changes that will be lost.');
			}
		});
	}

	/* ------------------------------------------------------------------ *
	 * License status, resolved after paint
	 * ------------------------------------------------------------------ */

	/** Not a reload: the box renders during grace too, where the site is licensed and `blocked` is correctly false. */
	function renderGateNotice(notice) {
		if (!notice) {
			return;
		}

		var $existing = $('.wpae-license-gate-settings');

		if (!notice.shown) {
			$existing.remove();
			return;
		}

		var cls = 'notice ' +
			(notice.blocked ? 'notice-error wpae-license-gate-blocked' : 'notice-warning') +
			' wpae-license-gate-settings';

		if ($existing.length) {
			$existing.attr('class', cls).find('p').first().html(notice.html);
			return;
		}

		// Where core's own notice relocation puts them, so an inserted banner
		// lands with the ones rendered server-side.
		var $anchor = $('.wp-header-end').first();
		var $box = $('<div/>', { 'class': cls }).append($('<p/>').html(notice.html));

		if ($anchor.length) {
			$box.insertAfter($anchor.nextAll('.notice').last().length ? $anchor.nextAll('.notice').last() : $anchor);
		} else {
			$('.wrap').first().prepend($box);
		}
	}

	function renderLicense($status, data) {
		// Nothing on the page can repair the withheld tab list, so reload. Keyed on
		// the blocked marker, not the box: the box also renders during grace, where
		// the site is licensed, and keying on it would reload for ever.
		if (data.blocked === false && $('.wpae-license-gate-blocked').length) {
			window.location.reload();
			return;
		}

		renderGateNotice(data.notice);

		var $chip = $status.find('.wpae-set-chip');
		var $row  = $status.closest('.wpae-set-row__field');
		var field = $row.find('.wpae-set-input--key')[0];

		// The server guessed from the last known status; keep the resolved answer so
		// refreshActivate() can fall back to it when the field is emptied again.
		$status.attr('data-license-state', data.state || '');

		if (field) {
			refreshActivate(field);
		}
		var $help = $status
			.closest('.wpae-set-row__field')
			.find('[data-license-help="' + $status.data('license-context') + '"]');

		$chip
			.attr('class', 'wpae-set-chip wpae-set-chip--' + (data.state || 'unknown'))
			.text(data.label || '');

		if ($help.length) {
			if (data.help) {
				$help.html(data.help).toggleClass('is-neutral', data.state === 'unknown' || data.state === 'warning');
			} else {
				$help.empty().removeClass('is-neutral');
			}
		}
	}

	/**
	 * Activate is offered when there is something to activate: a key typed into the
	 * field, or a stored key that is not currently valid.
	 */
	function refreshActivate(field) {
		var $field  = $(field);
		var $row    = $field.closest('.wpae-set-row__field');
		var $status = $row.find('.wpae-set-status');
		var typed   = $field.val().trim() !== '';
		var hasKey  = String($status.data('has-key')) === '1';
		var state   = $status.attr('data-license-state');

		var settled = typeof state === 'string' && state !== '';
		var $btn    = $row.find('[data-wpae-requires="' + field.id + '"]');

		if ($btn.data('initialDisabled') === undefined) {
			$btn.data('initialDisabled', $btn.prop('disabled'));
		}

		// Before the check resolves, trust the server-rendered state rather than assuming
		// "not active", or clearing the field re-enables the button mid-check.
		var enable = typed || (hasKey && (settled ? state !== 'active' : !$btn.data('initialDisabled')));

		if (!enable && $btn.is(':focus')) {
			// Disabling the focused element would drop focus to <body>, and the next Tab
			// lands on Remove, the destructive one.
			field.focus();
		}

		$btn.prop('disabled', !enable);
	}

	function failedLicenseState(xhr) {
		var refusal = xhr && xhr.status === 403 && xhr.responseJSON && xhr.responseJSON.message;

		if (refusal || $('.wpae-license-gate-settings').length) {
			return {
				state: 'error',
				label: t('notActiveHere', 'Not active on this site'),
				help: refusal ? xhr.responseJSON.message : t('statusUncheckedHelp', '')
			};
		}

		return {
			state: 'unknown',
			label: t('statusUnavailable', 'Status unavailable'),
			help: t('statusUnavailableHelp', '')
		};
	}

	function checkLicenses() {
		$('.wpae-set-status').each(function () {
			var $status = $(this);

			if (String($status.data('has-key')) !== '1') {
				return;
			}

			$.post(window.ajaxurl, {
				action: 'wpae_settings_check_license',
				context: $status.data('license-context'),
				security: L10N.nonce
			})
				.done(function (response) {
					if (response && response.success && response.data) {
						renderLicense($status, response.data);
					} else {
						renderLicense($status, failedLicenseState(null));
					}
				})
				.fail(function (xhr) {
					renderLicense($status, failedLicenseState(xhr));
				});
		});
	}

	/* ------------------------------------------------------------------ *
	 * Copy to clipboard
	 * ------------------------------------------------------------------ */

	function copyValue(value) {
		if (navigator.clipboard && window.isSecureContext) {
			return navigator.clipboard.writeText(value);
		}

		// Plain-HTTP local installs don't get the async clipboard API.
		return new Promise(function (resolve, reject) {
			var previouslyFocused = document.activeElement;
			var scratch = document.createElement('textarea');
			scratch.value = value;
			scratch.setAttribute('readonly', '');
			scratch.style.position = 'fixed';
			scratch.style.opacity = '0';
			document.body.appendChild(scratch);
			scratch.select();
			var ok = false;
			try {
				ok = document.execCommand('copy');
			} catch (e) {
				ok = false;
			}
			document.body.removeChild(scratch);

			// Removing the scratch node drops focus to <body>; the next Tab would restart
			// at "Skip to main content". Put it back where the user left it.
			if (previouslyFocused && previouslyFocused.isConnected && typeof previouslyFocused.focus === 'function') {
				previouslyFocused.focus();
			}

			ok ? resolve() : reject();
		});
	}

	$(document).on('click', '.wpae-set-copy', function () {
		var $btn = $(this);
		var field = document.getElementById($btn.data('target'));
		if (!field) {
			return;
		}

		var $label   = $btn.find('[data-copy-label]');
		var $target  = $label.length ? $label : $btn;
		var original = $btn.data('label') || $target.text();
		$btn.data('label', original);

		// Generated URLs render as <code>, editable fields as inputs.
		var text = ('value' in field && typeof field.value === 'string') ? field.value : field.textContent.trim();

		copyValue(text)
			.then(function () {
				$btn.addClass('is-done');
				$target.text(t('copied', 'Copied'));
			})
			.catch(function () {
				$btn.removeClass('is-done');
				$target.text(t('copyFailed', 'Press Ctrl+C'));
				if (typeof field.select === 'function') {
					field.select();
				} else if (window.getSelection) {
					var range = document.createRange();
					range.selectNodeContents(field);
					window.getSelection().removeAllRanges();
					window.getSelection().addRange(range);
				}
			})
			.finally(function () {
				window.clearTimeout($btn.data('revertTimer'));
				$btn.data('revertTimer', window.setTimeout(function () {
					$btn.removeClass('is-done');
					$target.text(original);
				}, 2000));
			});
	});

	/* ------------------------------------------------------------------ *
	 * Confirmation for destructive actions
	 * ------------------------------------------------------------------ */

	function confirmAction(message, confirmLabel) {
		return new Promise(function (resolve) {
			if (typeof window.HTMLDialogElement === 'undefined') {
				resolve(window.confirm(message));
				return;
			}

			var dialog = document.createElement('dialog');
			dialog.className = 'wpae-set-dialog';
			var uid = 'wpae-dlg-' + Math.floor(performance.now() * 1000).toString(36);
			dialog.innerHTML =
				'<form method="dialog">' +
				'<div class="wpae-set-dialog__body">' +
				'<h2 class="wpae-set-dialog__title" id="' + uid + '-t"></h2>' +
				'<p class="wpae-set-dialog__text" id="' + uid + '-d"></p>' +
				'</div>' +
				'<div class="wpae-set-dialog__actions">' +
				'<button class="button" value="cancel" type="submit"></button>' +
				'<button class="button wpae-set-danger" value="confirm" type="submit"></button>' +
				'</div>' +
				'</form>';

			dialog.setAttribute('aria-labelledby', uid + '-t');
			dialog.setAttribute('aria-describedby', uid + '-d');
			dialog.querySelector('.wpae-set-dialog__title').textContent = confirmLabel || t('confirmTitle', 'Are you sure?');
			dialog.querySelector('.wpae-set-dialog__text').textContent = message;
			dialog.querySelector('[value="cancel"]').textContent = t('cancel', 'Cancel');
			dialog.querySelector('[value="confirm"]').textContent = confirmLabel || t('confirm', 'Confirm');

			// Must live inside .wpae-set or the design tokens don't resolve. showModal()
			// promotes it to the top layer, so no ancestor can clip it.
			(document.querySelector('.wpae-set') || document.body).appendChild(dialog);
			dialog.addEventListener('close', function () {
				var confirmed = dialog.returnValue === 'confirm';
				dialog.remove();
				resolve(confirmed);
			});
			dialog.showModal();
		});
	}

	$(document).on('click', '[data-wpae-confirm]', function (event) {
		var el = this;

		if ($(el).data('wpaeConfirmed')) {
			return;
		}

		event.preventDefault();

		confirmAction($(el).data('wpae-confirm'), $(el).data('wpae-confirm-label')).then(function (ok) {
			if (!ok) {
				return;
			}
			$(el).data('wpaeConfirmed', true);
			if (el.tagName === 'A') {
				window.location.href = el.href;
			} else {
				el.click();
			}
		});
	});

	/* ------------------------------------------------------------------ *
	 * Cron key regeneration
	 * ------------------------------------------------------------------ */

	$(document).on('click', '[data-wpae-regenerate]', function () {
		var $form = $(this).closest('form');

		confirmAction(
			t(
				'regenerateWarning',
				'Generate a new secret key? Every cron job you have already set up will stop working until you update it with the new URLs.'
			),
			t('regenerateLabel', 'Generate new key')
		).then(function (ok) {
			if (!ok) {
				return;
			}
			if (!$form[0].checkValidity()) {
				// submit() would skip validation, post an invalid form, and silently not
				// regenerate, after the user confirmed a destructive dialog.
				$form[0].reportValidity();
				return;
			}
			$form.find('[data-wpae-regenerate-flag]').val('1');
			$form[0].requestSubmit();
		});
	});

	/* ------------------------------------------------------------------ *
	 * Zapier key generation
	 * ------------------------------------------------------------------ */

	// The key is written by the ajax call itself, which is why the tab has no form
	// and no save button: the field only ever displays what was already stored.
	$(document).on('click', '[data-wpae-generate-zapier-key]', function () {
		var $button = $(this);
		var $field = $button.closest('.wpae-set-inputgroup').find('[data-wpae-zapier-key]');

		confirmAction(
			t('zapierRegenerateWarning', 'Generate a new API key? Every Zap that uses the current key will stop working until you update it.'),
			t('regenerateLabel', 'Generate new key')
		).then(function (ok) {
			if (!ok) {
				return;
			}

			$button.prop('disabled', true);

			$.post(window.ajaxurl, {
				action: 'generate_zapier_api_key',
				security: L10N.nonce
			}, null, 'json')
				.done(function (response) {
					if (response && response.api_key) {
						$field.val(response.api_key);
					}
				})
				.always(function () {
					$button.prop('disabled', false);
				});
		});
	});

	/* ------------------------------------------------------------------ *
	 * Inline numeric validation
	 * ------------------------------------------------------------------ */

	$(document).on('blur', '.wpae-set-input--number', function () {
		$(this).toggleClass('wpae-set-input--invalid', !this.checkValidity());
	});

	$(document).on('input', '.wpae-set-input--key', function () {
		refreshActivate(this);
	});

	$(function () {
		// A validation error renders at the top of the page while the offending field can
		// be far below it.
		var $errors = $('[data-wpae-errors]');
		if ($errors.length) {
			$errors.trigger('focus');
		}

		$('[data-wpae-save]').closest('form').each(function () {
			initUnsavedGuard($(this));
		});
		checkLicenses();
	});
})(jQuery);
