/**
 * Bonsai Page Transitions.
 *
 * The animation itself runs purely in CSS (page-transition.css). This
 * handles two things: cleaning up after the entrance animation, and
 * playing the exit animation on internal link clicks before letting the
 * browser do its normal, real navigation — there's no AJAX content-swap
 * here, so it works with every plugin/shortcode/analytics setup as-is.
 *
 * Skipped entirely whenever the overlay markup isn't present on the page
 * (transition style set to "None", or "Skip Homepage" is on and this is
 * the front page — see bonsai-page-transitions.php).
 */
(function ($) {
	$(function () {
		var $overlay = $('#page-transition');
		if (!$overlay.length) {
			return;
		}

		var reduceMotion = window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches;
		if (reduceMotion) {
			$overlay.remove();
			return;
		}

		var overlayEl = $overlay.get(0);
		var navigating = false;

		function clearEntrance() {
			$overlay.css('pointer-events', 'none');
			$('body').removeClass('has-page-transition');
		}

		// Once the entrance animation finishes, drop pointer-events off so
		// the (now invisible) overlay can never block a click.
		$overlay.on('animationend webkitAnimationEnd', function (e) {
			var evt = e.originalEvent || e;
			if (evt.target !== overlayEl && !$.contains(overlayEl, evt.target)) {
				return;
			}
			if (!$overlay.hasClass('is-leaving')) {
				clearEntrance();
			}
		});

		// Safety net in case the entrance animationend event never fires.
		setTimeout(function () {
			if (!$overlay.hasClass('is-leaving')) {
				clearEntrance();
			}
		}, 1500);

		$(document).on('click', 'a[href]', function (e) {
			if (navigating) {
				return;
			}

			var link = this;
			var href = link.getAttribute('href');

			if (
				!href ||
				href.charAt(0) === '#' ||
				href.indexOf('mailto:') === 0 ||
				href.indexOf('tel:') === 0 ||
				href.indexOf('javascript:') === 0 ||
				href.indexOf('/wp-admin/') !== -1 ||
				href.indexOf('wp-login.php') !== -1 ||
				link.target ||
				link.hasAttribute('download') ||
				link.classList.contains('no-transition') ||
				link.hostname !== window.location.hostname ||
				e.metaKey || e.ctrlKey || e.shiftKey || e.altKey || e.button !== 0
			) {
				return;
			}

			// Same-page anchor link — leave it to any existing smooth-scroll
			// handler, don't play a transition for it.
			if (link.pathname === window.location.pathname && link.hash) {
				return;
			}

			e.preventDefault();
			navigating = true;

			var finished = false;
			var go = function () {
				if (finished) {
					return;
				}
				finished = true;
				window.location.href = href;
			};

			$overlay.css('pointer-events', 'auto').one('animationend webkitAnimationEnd', go);
			$overlay.addClass('is-leaving');

			// Safety net in case animationend never fires.
			setTimeout(go, 900);
		});

		// If the page is restored from bfcache (back/forward button), clear
		// any stale "leaving" state so the overlay doesn't sit there covering
		// the page forever.
		$(window).on('pageshow', function (e) {
			var evt = e.originalEvent || e;
			if (evt.persisted) {
				navigating = false;
				$overlay.removeClass('is-leaving').css('pointer-events', 'none');
			}
		});
	});
})(jQuery);
