/*
 * quick-reply.js
 * https://github.com/savetheinternet/Tinyboard/blob/master/js/quick-reply.js
 *
 * Released under the MIT license
 * Copyright (c) 2013 Michael Save <savetheinternet@tinyboard.org>
 * Copyright (c) 2013-2014 Marcin Łabanowski <marcin@6irc.net>
 *
 * Usage:
 *   $config['additional_javascript'][] = 'js/jquery.min.js';
 *   $config['additional_javascript'][] = 'js/jquery-ui.custom.min.js'; // Optional; if you want the form to be draggable.
 *   $config['additional_javascript'][] = 'js/file-selector.js'; // for settings to show up.
 *   $config['additional_javascript'][] = 'js/quick-reply.js';
 *
 */

(function () {
	// Set default settings if not present
	function setDefaultSettings() {
		const defaults = {
			quickReplyFloatingLink: 'true',
			quickReplyHideAtTop: 'false',
			quickReplyShowEmbed: 'true',
			quickReplyShowRemote: 'true',
			quickReplyShowVoice: 'true' // <-- Added default for voice
		};
		for (const key in defaults) {
			if (typeof localStorage[key] === 'undefined') {
				localStorage[key] = defaults[key];
			}
		}
	}

	// Helper to get quick-reply option
	function getQuickReplySetting(key, fallback) {
		if (typeof localStorage[key] !== 'undefined') {
			return localStorage[key] === 'true';
		}
		return fallback;
	}

	// Add options to the Options panel if present
	function addOptionsPanel() {
		if (window.Options && Options.get_tab('general')) {
			setTimeout(function () {
				const $fileSelectorLabel = $("#file-drag-drop").closest('label');
				const quickReplyFieldset = $(
					"<fieldset id='quick-reply-fs'><legend>" + _("Quick Reply") + "</legend>" +
					"<label class='quick-reply-setting' id='quickReplyFloatingLink'><input type='checkbox' /> " + _('Show floating Quick Reply button') + "</label>" +
					"<label class='quick-reply-setting' id='quickReplyHideAtTop'><input type='checkbox' /> " + _('Hide Quick Reply at top of page') + "</label>" +
					"<label class='quick-reply-setting' id='quickReplyShowEmbed'><input type='checkbox' /> " + _('Show embed field in Quick Reply') + "</label>" +
					"<label class='quick-reply-setting' id='quickReplyShowRemote'><input type='checkbox' /> " + _('Show remote upload in Quick Reply') + "</label>" +
					"<label class='quick-reply-setting' id='quickReplyShowVoice'><input type='checkbox' /> " + _('Show voice recording in Quick Reply') + "</label>" + // <-- Added voice option
					"</fieldset>"
				);
				if ($fileSelectorLabel.length) {
					$fileSelectorLabel.after(quickReplyFieldset);
				} else {
					Options.get_tab('general').content.append(quickReplyFieldset[0]);
				}
				// Sync UI with localStorage
				$('#quickReplyFloatingLink>input').prop('checked', localStorage.quickReplyFloatingLink === 'true');
				$('#quickReplyHideAtTop>input').prop('checked', localStorage.quickReplyHideAtTop === 'true');
				$('#quickReplyShowEmbed>input').prop('checked', localStorage.quickReplyShowEmbed === 'true');
				$('#quickReplyShowRemote>input').prop('checked', localStorage.quickReplyShowRemote === 'true');
				$('#quickReplyShowVoice>input').prop('checked', localStorage.quickReplyShowVoice === 'true'); // <-- Sync voice

				$('.quick-reply-setting').on('change', function () {
					const setting = $(this).attr('id');
					localStorage[setting] = $(this).children('input').is(':checked');
				});
			}, 0);
		}
	}

	// Inject CSS for quick-reply
	function do_css() {
		$('#quick-reply-css').remove();

		const dummy_reply = $('<div class="post reply"></div>').appendTo($('body'));
		const reply_background = dummy_reply.css('backgroundColor');
		const reply_border_style = dummy_reply.css('borderStyle');
		const reply_border_color = dummy_reply.css('borderColor');
		const reply_border_width = dummy_reply.css('borderWidth');
		dummy_reply.remove();

		const css = `
		#quick-reply {
			position: fixed;
			right: 5%;
			top: 5%;
			float: right;
			display: block;
			padding: 0;
			width: 300px;
			z-index: 100;
		}
		#quick-reply table {
			border-collapse: collapse;
			background: ${reply_background};
			border-style: ${reply_border_style};
			border-width: ${reply_border_width};
			border-color: ${reply_border_color};
			margin: 0;
			width: 100%;
		}
		#quick-reply tr td:nth-child(2) {
			white-space: nowrap;
			text-align: right;
			padding-right: 4px;
		}
		#quick-reply tr td:nth-child(2) input[type="submit"] {
			width: 100%;
		}
		#quick-reply th, #quick-reply td {
			margin: 0;
			padding: 0;
		}
		#quick-reply th {
			text-align: center;
			padding: 2px 0;
			border: 1px solid #222;
		}
		#quick-reply th .handle {
			float: left;
			width: 100%;
			display: inline-block;
		}
		#quick-reply th .close-btn {
			float: right;
			padding: 0 5px;
		}
		#quick-reply input[type="text"], #quick-reply select {
			width: 100%;
			padding: 2px;
			font-size: 10pt;
			box-sizing: border-box;
		}
		#quick-reply textarea {
			width: 100%;
			min-width: 100%;
			box-sizing: border-box;
			font-size: 10pt;
			resize: both;
		}
		#quick-reply input, #quick-reply select, #quick-reply textarea {
			margin: 0 0 1px 0;
		}
		#quick-reply input[type="file"] {
			padding: 5px 2px;
		}
		#quick-reply .nonsense {
			display: none;
		}
		#quick-reply td.submit {
			width: 1%;
		}
		#quick-reply td.recaptcha {
			text-align: center;
			padding: 0 0 1px 0;
		}
		#quick-reply td.recaptcha span {
			display: inline-block;
			width: 100%;
			background: white;
			border: 1px solid #ccc;
			cursor: pointer;
		}
		#quick-reply td.recaptcha-response {
			padding: 0 0 1px 0;
		}
		@media screen and (max-width: 400px) {
			#quick-reply {
				display: none !important;
			}
		}
		`;
		$('<style type="text/css" id="quick-reply-css"></style>').text(css).appendTo($('head'));
	}

	// Show the quick-reply form
	function show_quick_reply() {
		if ($('div.banner').length === 0) return;
		if ($('#quick-reply').length !== 0) return;

		do_css();

		const $origPostForm = $('form[name="post"]:first');
		const $postForm = $origPostForm.clone();
		const $dummyStuff = $('<div class="nonsense"></div>').appendTo($postForm);

		$postForm.find('table tr').each(function () {
			const $tr = $(this);
			const $th = $tr.children('th:first');
			const $td = $tr.children('td:first');

			if ($th.length && $td.length) {
				$td.attr('colspan', 2);

				$td.find('input[type="text"]').each(function () {
					$(this)
						.removeAttr('size')
						.attr('placeholder', $th.clone().children().remove().end().text());
				});

				$th.contents().filter(function () {
					return this.nodeType === 3;
				}).remove();
				$th.contents().appendTo($dummyStuff);
				$th.remove();

				if ($td.find('input[name="password"]').length) {
					$tr.hide();
				}

				if ($td.find('input[type="submit"]').length) {
					$td.removeAttr('colspan');
					$('<td class="submit"></td>').append($td.find('input[type="submit"]')).insertAfter($td);
				}

				// reCAPTCHA
				if ($td.find('#recaptcha_widget_div').length) {
					const $captchaimg = $td.find('#recaptcha_image img');
					$captchaimg
						.removeAttr('id')
						.removeAttr('style')
						.addClass('recaptcha_image')
						.on('click', function () {
							$('#recaptcha_reload').click();
						});

					$('#recaptcha_response_field').on('focus', function () {
						if ($captchaimg.attr('src') !== $('#recaptcha_image img').attr('src')) {
							$captchaimg.attr('src', $('#recaptcha_image img').attr('src'));
							$postForm.find('input[name="recaptcha_challenge_field"]').val($('#recaptcha_challenge_field').val());
							$postForm.find('input[name="recaptcha_response_field"]').val('').focus();
						}
					});

					$postForm.on('submit', function () {
						setTimeout(function () {
							$('#recaptcha_reload').click();
						}, 200);
					});

					const $newRow = $('<tr><td class="recaptcha-response" colspan="2"></td></tr>');
					$newRow.children().first().append(
						$td.find('input').removeAttr('style')
					);
					$newRow.find('#recaptcha_response_field')
						.removeAttr('id')
						.addClass('recaptcha_response_field')
						.attr('placeholder', $('#recaptcha_response_field').attr('placeholder'));

					$('#recaptcha_response_field').addClass('recaptcha_response_field');

					$td.replaceWith($('<td class="recaptcha" colspan="2"></td>').append($('<span></span>').append($captchaimg)));
					$newRow.insertAfter(this);
				}

				// Upload section
				if ($td.find('input[type="file"]').length) {
					// Handle remote upload field
					if ($td.find('input[name="file_url"]').length) {
						const $file_url = $td.find('input[name="file_url"]');
						if (getQuickReplySetting('quickReplyShowRemote', false)) {
							const $newRow = $('<tr class="quick-reply-remote"><td colspan="2"></td></tr>');
							$file_url.clone().attr('placeholder', _('Upload URL')).appendTo($newRow.find('td'));
							$newRow.insertBefore($tr);
						}
						$file_url.parent().remove();
						$td.find('label').remove();
						$td.contents().filter(function () {
							return this.nodeType === 3;
						}).remove();
						$td.find('input[name="file_url"]').removeAttr('id');
					}
					if ($tr.find('input[name="spoiler"]').length) {
						$td.removeAttr('colspan');
					}
				}

				// Embed field
				if ($tr.is('#upload_embed')) {
					if (!getQuickReplySetting('quickReplyShowEmbed', false)) {
						$tr.remove();
					}
				}

				// Voice recording (now controlled by option)
				if ($tr.is('#upload_voice')) {
					if (!getQuickReplySetting('quickReplyShowVoice', false) || typeof window.MediaRecorder === 'undefined') {
						$tr.remove();
					} else {
						setTimeout(function () {
							initQuickReplyVoiceRecorder($postForm);
						}, 0);
					}
				}

				// Remove oekaki if existent
				if ($tr.is('#oekaki')) {
					$tr.remove();
				}

				// Remove upload selection
				if ($td.is('#upload_selection')) {
					$tr.remove();
				}

				$td.find('small').hide();
			}
		});

		$postForm.find('textarea[name="body"]').removeAttr('id').removeAttr('cols').attr('placeholder', _('Comment'));
		$postForm.find('textarea:not([name="body"]),input[type="hidden"]:not(.captcha_cookie)').removeAttr('id').appendTo($dummyStuff);
		$postForm.find('br').remove();
		$postForm.find('table').prepend('<tr><th colspan="2">' +
			'<span class="handle">' +
			'<a class="close-btn" href="javascript:void(0)">×</a>' +
			_('Quick Reply') +
			'</span>' +
			'</th></tr>');

		$postForm.attr('id', 'quick-reply');
		$postForm.appendTo($('body')).hide();
		$postForm.find('#upload_embed, #upload_voice, #upload_url').css('display', '');

		// Synchronize body text with original post form
		const $origBody = $origPostForm.find('textarea[name="body"]');
		const $qrBody = $postForm.find('textarea[name="body"]');
		$origBody.on('change input propertychange', function () {
			$qrBody.val($(this).val());
		});
		$qrBody.on('change input propertychange', function () {
			$origBody.val($(this).val());
		});
		$qrBody.on('focus', function () {
			$origBody.removeAttr('id');
			$(this).attr('id', 'body');
		});
		$origBody.on('focus', function () {
			$qrBody.removeAttr('id');
			$(this).attr('id', 'body');
		});

		// Synchronize other inputs
		$origPostForm.find('input[type="text"],select').on('change input propertychange', function () {
			$postForm.find('[name="' + $(this).attr('name') + '"]').val($(this).val());
		});
		$postForm.find('input[type="text"],select').on('change input propertychange', function () {
			$origPostForm.find('[name="' + $(this).attr('name') + '"]').val($(this).val());
		});

		// Make draggable if available
		if (typeof $postForm.draggable !== 'undefined') {
			if (localStorage.quickReplyPosition) {
				const offset = JSON.parse(localStorage.quickReplyPosition);
				offset.top = Math.max(0, offset.top);
				offset.right = Math.min($(window).width() - $postForm.width(), offset.right);
				offset.top = Math.min($(window).height() - $postForm.height(), offset.top);
				$postForm.css('right', offset.right).css('top', offset.top);
			}
			$postForm.draggable({
				handle: 'th .handle',
				containment: 'window',
				distance: 10,
				scroll: false,
				stop: function () {
					const offset = {
						top: $(this).offset().top - $(window).scrollTop(),
						right: $(window).width() - $(this).offset().left - $(this).width()
					};
					localStorage.quickReplyPosition = JSON.stringify(offset);
					$postForm.css('right', offset.right).css('top', offset.top).css('left', 'auto');
				}
			});
			$postForm.find('th .handle').css('cursor', 'move');
		}

		$postForm.find('th .close-btn').on('click', function () {
			$origPostForm.find('textarea[name="body"]').attr('id', 'body');
			$postForm.remove();
			floating_link();
		});

		// Fix bug when table gets too big for form
		$postForm.show();
		$postForm.width($postForm.find('table').width());
		$postForm.hide();

		$(window).trigger('quick-reply');

		$(window).ready(function () {
			if (getQuickReplySetting('quickReplyHideAtTop', false)) {
				$(window).on('scroll', function () {
					if ($(this).width() <= 400) return;
					if ($(this).scrollTop() < $origPostForm.offset().top + $origPostForm.height() - 100)
						$postForm.fadeOut(100);
					else
						$postForm.fadeIn(100);
				}).trigger('scroll');
			} else {
				$postForm.show();
			}

			$(window).on('stylesheet', function () {
				do_css();
				const $stylesheet = $('link#stylesheet');
				if ($stylesheet.attr('href')) {
					$stylesheet[0].onload = do_css;
				}
			});
		});
	}

	// Floating quick-reply button
	function floating_link() {
		if (!getQuickReplySetting('quickReplyFloatingLink', true)) return;
		$('<a href="javascript:void(0)" class="quick-reply-btn">' + _('Quick Reply') + '</a>')
			.on('click', function () {
				show_quick_reply();
				$(this).remove();
			}).appendTo($('body'));

		$(window).on('quick-reply', function () {
			$('.quick-reply-btn').remove();
		});
	}

	// --- Voice recording logic for Quick Reply ---
	function initQuickReplyVoiceRecorder($form) {
		const $recordBtn = $form.find('#record_btn');
		const $finishBtn = $form.find('#finish_btn');
		const $abortBtn = $form.find('#abort_btn');
		const $status = $form.find('#recording_status');
		const $audio = $form.find('#audio_preview');
		const $voiceData = $form.find('#voice_data');

		let mediaRecorder = null;
		let audioChunks = [];

		$recordBtn.off('click').on('click', function () {
			if (!navigator.mediaDevices || !navigator.mediaDevices.getUserMedia) {
				$status.text(_('Voice recording not supported.'));
				return;
			}
			navigator.mediaDevices.getUserMedia({ audio: true }).then(function (stream) {
				mediaRecorder = new MediaRecorder(stream);
				audioChunks = [];
				mediaRecorder.ondataavailable = function (e) {
					audioChunks.push(e.data);
				};
				mediaRecorder.onstop = function () {
					const blob = new Blob(audioChunks, { type: 'audio/webm' });
					const reader = new FileReader();
					reader.onloadend = function () {
						$voiceData.val(reader.result);
					};
					reader.readAsDataURL(blob);
					$audio[0].src = URL.createObjectURL(blob);
					$audio.show();
				};
				mediaRecorder.start();
				$status.text(_('Recording...'));
				$recordBtn.prop('disabled', true);
				$finishBtn.prop('disabled', false);
				$abortBtn.prop('disabled', false);
			}).catch(function () {
				$status.text(_('Could not access microphone.'));
			});
		});

		$finishBtn.off('click').on('click', function () {
			if (mediaRecorder && mediaRecorder.state === 'recording') {
				mediaRecorder.stop();
				$status.text(_('Recording finished.'));
				$recordBtn.prop('disabled', false);
				$finishBtn.prop('disabled', true);
				$abortBtn.prop('disabled', true);
			}
		});

		$abortBtn.off('click').on('click', function () {
			if (mediaRecorder && mediaRecorder.state === 'recording') {
				mediaRecorder.stop();
				audioChunks = [];
				$voiceData.val('');
				$audio.hide();
				$status.text(_('Recording aborted.'));
				$recordBtn.prop('disabled', false);
				$finishBtn.prop('disabled', true);
				$abortBtn.prop('disabled', true);
			}
		});
	}

	// Initialize on document ready
	$(function () {
		setDefaultSettings();
		addOptionsPanel();
	});

	// Show floating button and handle scroll visibility
	if (getQuickReplySetting('quickReplyFloatingLink', true)) {
		$(window).ready(function () {
			if ($('div.banner').length === 0) return;
			$('<style type="text/css">a.quick-reply-btn {position: fixed;right: 0;bottom: 0;display: block;padding: 5px 13px;text-decoration: none;}</style>').appendTo($('head'));
			floating_link();

			if (getQuickReplySetting('quickReplyHideAtTop', true)) {
				$('.quick-reply-btn').hide();
				$(window).on('scroll', function () {
					if ($(this).width() <= 400) return;
					const $form = $('form[name="post"]:first');
					if ($(this).scrollTop() < $form.offset().top + $form.height() - 100)
						$('.quick-reply-btn').fadeOut(100);
					else
						$('.quick-reply-btn').fadeIn(100);
				}).trigger('scroll');
			}
		});
	}

	// Show quick-reply on cite event
	$(window).on('cite', function (e, id, with_link) {
		if ($(this).width() <= 400) return;
		show_quick_reply();
		if (with_link) {
			$(document).ready(function () {
				if ($('#' + id).length) {
					highlightReply(id);
					$(document).scrollTop($('#' + id).offset().top);
				}
				setTimeout(function () {
					const $body = $('#quick-reply textarea[name="body"]');
					const tmp = $body.val();
					$body.val('').focus().val(tmp);
				}, 1);
			});
		}
	});

	// Expose for debugging if needed
	window.show_quick_reply = show_quick_reply;
})();