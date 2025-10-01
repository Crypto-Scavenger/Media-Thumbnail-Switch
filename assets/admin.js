/**
 * Admin scripts for Media Thumbnail Switch
 */

(function($) {
	'use strict';

	$(document).ready(function() {
		// Save settings via AJAX
		$('#mts-settings-form').on('submit', function(e) {
			e.preventDefault();
			
			var $form = $(this);
			var $button = $('#mts-save-button');
			var $message = $('#mts-message');
			var formData = $form.serialize();
			formData += '&action=mts_save_settings';
			formData += '&nonce=' + mtsData.nonce;
			
			// Disable button
			$button.prop('disabled', true).text(mtsData.strings.saving);
			$message.hide();
			
			$.ajax({
				url: mtsData.ajaxurl,
				type: 'POST',
				data: formData,
				success: function(response) {
					if (response.success) {
						$message
							.removeClass('notice-error')
							.addClass('notice notice-success')
							.html('<p>' + response.data.message + '</p>')
							.show();
					} else {
						$message
							.removeClass('notice-success')
							.addClass('notice notice-error')
							.html('<p>' + (response.data || mtsData.strings.error) + '</p>')
							.show();
					}
				},
				error: function() {
					$message
						.removeClass('notice-success')
						.addClass('notice notice-error')
						.html('<p>' + mtsData.strings.error + '</p>')
						.show();
				},
				complete: function() {
					$button.prop('disabled', false).text($button.data('original-text') || 'Save Settings');
					
					// Scroll to message
					$('html, body').animate({
						scrollTop: $message.offset().top - 50
					}, 300);
				}
			});
		});
		
		// Store original button text
		var $saveButton = $('#mts-save-button');
		$saveButton.data('original-text', $saveButton.text());
		
		// Regenerate thumbnails
		$('#mts-regenerate-button').on('click', function() {
			if (!confirm(mtsData.strings.confirm)) {
				return;
			}
			
			var $button = $(this);
			var $progress = $('#mts-regenerate-progress');
			var $progressBar = $('#mts-progress-bar');
			var $progressText = $('#mts-progress-text');
			var $message = $('#mts-message');
			
			// Reset and show progress
			$button.prop('disabled', true).text(mtsData.strings.regenerating);
			$progress.show();
			$progressBar.val(0);
			$progressText.text('0%');
			$message.hide();
			
			// Start regeneration
			regenerateBatch(0);
			
			function regenerateBatch(batch) {
				$.ajax({
					url: mtsData.ajaxurl,
					type: 'POST',
					data: {
						action: 'mts_regenerate_thumbnails',
						nonce: mtsData.nonce,
						batch: batch
					},
					success: function(response) {
						if (response.success) {
							if (response.data.done) {
								// Complete
								$progressBar.val(100);
								$progressText.text('100%');
								$message
									.removeClass('notice-error')
									.addClass('notice notice-success')
									.html('<p>' + response.data.message + '</p>')
									.show();
								$button.prop('disabled', false).text($button.data('original-text') || 'Regenerate All Thumbnails');
								
								// Scroll to message
								setTimeout(function() {
									$('html, body').animate({
										scrollTop: $message.offset().top - 50
									}, 300);
								}, 500);
							} else {
								// Continue
								$progressBar.val(response.data.percentage);
								$progressText.text(response.data.percentage + '% (' + response.data.processed + '/' + response.data.total + ')');
								regenerateBatch(response.data.batch);
							}
						} else {
							$message
								.removeClass('notice-success')
								.addClass('notice notice-error')
								.html('<p>' + (response.data || mtsData.strings.error) + '</p>')
								.show();
							$button.prop('disabled', false).text($button.data('original-text') || 'Regenerate All Thumbnails');
						}
					},
					error: function() {
						$message
							.removeClass('notice-success')
							.addClass('notice notice-error')
							.html('<p>' + mtsData.strings.error + '</p>')
							.show();
						$button.prop('disabled', false).text($button.data('original-text') || 'Regenerate All Thumbnails');
					}
				});
			}
		});
		
		// Store original regenerate button text
		var $regenButton = $('#mts-regenerate-button');
		$regenButton.data('original-text', $regenButton.text());
	});
})(jQuery);
