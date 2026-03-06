/**
 * WhatsApp Gateway — Client-Side Validation (Popup Mode)
 *
 * Opens the gateway as a popup modal, validates Saudi phone numbers,
 * saves leads via AJAX, and redirects to WhatsApp.
 *
 * @package WhatsApp_Gateway
 * @since   1.1.0
 */

(function () {
	'use strict';

	/* ------------------------------------------------------------------ */
	/* Constants                                                           */
	/* ------------------------------------------------------------------ */

	const SAUDI_MOBILE_REGEX = /^05\d{8}$/;
	const WA_BASE_URL = 'https://wa.me/';

	/* ------------------------------------------------------------------ */
	/* Settings from WordPress                                             */
	/* ------------------------------------------------------------------ */

	const settings = window.waGatewaySettings || {};

	/* ------------------------------------------------------------------ */
	/* Helper Functions                                                    */
	/* ------------------------------------------------------------------ */

	function digitsOnly(value) {
		return value.replace(/\D/g, '');
	}

	function isValidSaudiNumber(number) {
		return SAUDI_MOBILE_REGEX.test(number);
	}

	function toInternational(number) {
		return '966' + number.substring(1);
	}

	/* ------------------------------------------------------------------ */
	/* Validation Handlers (Event Delegation)                              */
	/* ------------------------------------------------------------------ */

	function showFormError(form, phoneInput, message) {
		const errorEl = form.querySelector('.wa-gateway-error');
		const inputGroup = form.querySelector('.wa-gateway-input-group');
		if (errorEl) {
			errorEl.textContent = message;
			errorEl.classList.add('wa-gateway-error--visible');
		}
		if (inputGroup) {
			inputGroup.classList.add('wa-gateway-input-group--error');
		}
		if (phoneInput) {
			phoneInput.setAttribute('aria-invalid', 'true');
		}
	}

	function clearFormError(form, phoneInput) {
		const errorEl = form.querySelector('.wa-gateway-error');
		const inputGroup = form.querySelector('.wa-gateway-input-group');
		if (errorEl) {
			errorEl.textContent = '';
			errorEl.classList.remove('wa-gateway-error--visible');
		}
		if (inputGroup) {
			inputGroup.classList.remove('wa-gateway-input-group--error');
		}
		if (phoneInput) {
			phoneInput.removeAttribute('aria-invalid');
		}
	}

	// Handle Input Formatting & Validation on the fly
	document.addEventListener('input', function (e) {
		if (!e.target.classList.contains('wa-gateway-input')) return;

		var phoneInput = e.target;
		var form = phoneInput.closest('.wa-gateway-form');
		if (!form) return;

		var inputGroup = form.querySelector('.wa-gateway-input-group');
		if (!inputGroup) return;

		var value = digitsOnly(phoneInput.value);

		if (value.length > 10) {
			value = value.substring(0, 10);
		}

		phoneInput.value = value;

		if (value.length === 10) {
			if (isValidSaudiNumber(value)) {
				clearFormError(form, phoneInput);
				inputGroup.classList.add('wa-gateway-input-group--success');
			} else {
				inputGroup.classList.remove('wa-gateway-input-group--success');
			}
		} else {
			inputGroup.classList.remove('wa-gateway-input-group--success');
			if (value.length > 0) {
				clearFormError(form, phoneInput);
			}
		}
	});

	// Handle Input Focus logic
	document.addEventListener('focusin', function (e) {
		if (!e.target.classList.contains('wa-gateway-input')) return;

		var phoneInput = e.target;
		var form = phoneInput.closest('.wa-gateway-form');
		if (!form) return;

		if (phoneInput.value.length === 0) {
			clearFormError(form, phoneInput);
		}
	});

	// Handle Form Submission
	document.addEventListener('submit', function (e) {
		var form = e.target;
		if (!form.classList.contains('wa-gateway-form')) return;

		e.preventDefault();

		var phoneInput = form.querySelector('.wa-gateway-input');
		var submitBtn = form.querySelector('.wa-gateway-button');

		if (!phoneInput || !submitBtn) return;

		var phone = digitsOnly(phoneInput.value);

		if (!phone) {
			showFormError(form, phoneInput, 'يرجى إدخال رقم الجوال');
			phoneInput.focus();
			return;
		}

		if (phone.length !== 10) {
			showFormError(form, phoneInput, 'رقم الجوال يجب أن يكون 10 أرقام');
			phoneInput.focus();
			return;
		}

		if (!phone.startsWith('05')) {
			showFormError(form, phoneInput, 'رقم الجوال يجب أن يبدأ بـ 05');
			phoneInput.focus();
			return;
		}

		if (!isValidSaudiNumber(phone)) {
			showFormError(form, phoneInput, 'يرجى إدخال رقم جوال سعودي صحيح');
			phoneInput.focus();
			return;
		}

		clearFormError(form, phoneInput);
		submitBtn.disabled = true;
		submitBtn.classList.add('wa-gateway-button--loading');

		// Determine Gateway Type
		var typeInput = form.querySelector('.wa-gateway-type-input');
		var gatewayType = typeInput ? typeInput.value : 'whatsapp';

		var internationalNumber = toInternational(phone);
		var redirectUrl = '';

		if (gatewayType === 'call') {
			var callDestination = settings.callDestination || internationalNumber;
			redirectUrl = 'tel:' + callDestination;
		} else {
			// Ensure WA URL uses api.whatsapp.com for better strict-browser compatibility
			var destination = settings.destinationNumber || internationalNumber;
			var message = settings.defaultMessage || '';

			redirectUrl = WA_BASE_URL.replace('wa.me/', 'api.whatsapp.com/send?phone=') + encodeURIComponent(destination);
			if (message) {
				redirectUrl += '&text=' + encodeURIComponent(message);
			}
		}

		// Fire and forget fetch request
		var formData = new FormData();
		formData.append('action', 'wa_gateway_save_lead');
		formData.append('nonce', settings.nonce);
		formData.append('phone_number', phone);
		formData.append('page_url', window.location.href);
		formData.append('lead_type', gatewayType);

		// For WhatsApp, we open a new tab immediately, so fetch runs in the background.
		// For Phone calls (tel:), changing location.href immediately cancels pending AJAX requests on some browsers/mobile OS.
		// We need to wait for the fetch to resolve (or timeout) before redirecting.

		if (gatewayType === 'whatsapp') {
			// Open WhatsApp immediately to bypass strict Safari/iOS async popup blockers
			var newWindow = window.open(redirectUrl, '_blank');
			if (!newWindow) {
				// Fallback if blocked
				window.location.href = redirectUrl;
			}

			fetch(settings.ajaxUrl, {
				method: 'POST',
				body: formData,
				credentials: 'same-origin',
			}).catch(function () {
				// Ignore fetch errors
			});

			closeOrResetForm();
		} else {
			// It's a Call (tel:). Wait for AJAX to finish before redirecting (with a 2s safety timeout).
			var redirectDone = false;
			var doRedirect = function () {
				if (redirectDone) return;
				redirectDone = true;
				window.location.href = redirectUrl;
				closeOrResetForm();
			};

			// Safety timeout in case of bad connection
			var fallbackTimeout = setTimeout(doRedirect, 2000);

			fetch(settings.ajaxUrl, {
				method: 'POST',
				body: formData,
				credentials: 'same-origin',
			})
				.then(function () {
					clearTimeout(fallbackTimeout);
					doRedirect();
				})
				.catch(function () {
					clearTimeout(fallbackTimeout);
					doRedirect();
				});
		}

		function closeOrResetForm() {
			// If this form is inside the popup, close it
			if (typeof window.waGatewayClosePopup === 'function' && form.closest('.wa-gateway-overlay')) {
				window.waGatewayClosePopup();
			} else {
				// Reset UI for inline form
				setTimeout(function () {
					submitBtn.disabled = false;
					submitBtn.classList.remove('wa-gateway-button--loading');
					phoneInput.value = '';
					var inputGroup = form.querySelector('.wa-gateway-input-group');
					if (inputGroup) inputGroup.classList.remove('wa-gateway-input-group--success');
				}, 1000);
			}
		}
	});

	/* ------------------------------------------------------------------ */
	/* Popup Specific Logic                                                */
	/* ------------------------------------------------------------------ */

	function initPopupLogic() {
		const overlay = document.getElementById('wa-gateway-overlay');
		const backdrop = document.getElementById('wa-gateway-backdrop');
		const closeBtn = document.getElementById('wa-gateway-close');

		if (!overlay) return;

		const popupInput = overlay.querySelector('.wa-gateway-input');
		const popupInputGroup = overlay.querySelector('.wa-gateway-input-group');
		const popupSubmitBtn = overlay.querySelector('.wa-gateway-button');
		const popupErrorEl = overlay.querySelector('.wa-gateway-error');

		function openPopup() {
			overlay.classList.add('wa-gateway-overlay--active');
			document.body.style.overflow = 'hidden';
			document.documentElement.style.overflow = 'hidden';
			if (popupInput) popupInput.focus();
		}

		function closePopup() {
			overlay.classList.remove('wa-gateway-overlay--active');
			document.body.style.overflow = '';
			document.documentElement.style.overflow = '';
			setTimeout(function () {
				if (popupInput) popupInput.value = '';
				if (popupErrorEl) {
					popupErrorEl.textContent = '';
					popupErrorEl.classList.remove('wa-gateway-error--visible');
				}
				if (popupInputGroup) popupInputGroup.classList.remove('wa-gateway-input-group--error', 'wa-gateway-input-group--success');
				if (popupInput) popupInput.removeAttribute('aria-invalid');
				if (popupSubmitBtn) {
					popupSubmitBtn.disabled = false;
					popupSubmitBtn.classList.remove('wa-gateway-button--loading');
				}
			}, 300);
		}

		if (closeBtn) closeBtn.addEventListener('click', closePopup);
		if (backdrop) backdrop.addEventListener('click', closePopup);

		document.addEventListener('keydown', function (e) {
			if (e.key === 'Escape' && overlay.classList.contains('wa-gateway-overlay--active')) {
				closePopup();
			}
		});

		window.waGatewayOpenPopup = openPopup;
		window.waGatewayClosePopup = closePopup;

		// Helper to open popup with specific type
		function openSpecificPopup(type) {
			const typeInput = overlay.querySelector('.wa-gateway-type-input');
			const titleEl = overlay.querySelector('.wa-gateway-title');
			const btnTextEl = overlay.querySelector('.wa-gateway-button-text');

			if (type === 'call') {
				overlay.classList.add('is-call-gateway');
				if (typeInput) typeInput.value = 'call';
				if (titleEl) titleEl.textContent = 'بدء المكالمة الآن';
				if (btnTextEl) btnTextEl.textContent = 'اتصال الآن';
			} else {
				overlay.classList.remove('is-call-gateway');
				if (typeInput) typeInput.value = 'whatsapp';
				if (titleEl) titleEl.textContent = 'المتابعة إلى واتساب';
				if (btnTextEl) btnTextEl.textContent = 'المتابعة إلى واتساب';
			}

			openPopup();
		}

		// Bind Triggers Using Event Delegation to catch any dynamically added buttons
		document.addEventListener('click', function (e) {
			var checkTriggers = function (triggerString, type) {
				if (!triggerString) return false;
				var ids = triggerString.split(',').map(function (id) { return id.trim(); });
				for (var i = 0; i < ids.length; i++) {
					var id = ids[i];
					if (!id) continue;

					// Check if clicked element or its parent has the ID or Class
					var targetElement = e.target.closest('#' + id + ', .' + id);
					if (targetElement) {
						e.preventDefault();
						openSpecificPopup(type);
						return true;
					}
				}
				return false;
			};

			var waTriggers = settings.triggerButtonId || 'whatsapp-btn';
			if (checkTriggers(waTriggers, 'whatsapp')) return;

			var callTriggers = settings.callTriggerId || 'call-btn';
			if (checkTriggers(callTriggers, 'call')) return;
		});
	}

	if (document.readyState === 'loading') {
		document.addEventListener('DOMContentLoaded', initPopupLogic);
	} else {
		initPopupLogic();
	}

})();
