(($) => {
	$(() => {
		const config = window.faqAiGeneratorSettings || {};
		const $button = $("#faq-ai-sync-models");
		const $select = $("#model");
		const $status = $("#faq-ai-sync-status");

		if (!$button.length) {
			return;
		}

		$button.on("click", (e) => {
			e.preventDefault();

			const originalLabel = config.i18n?.syncButton || $button.text();

			$button.prop("disabled", true).text(config.i18n?.syncing || "Syncing...");
			$status.removeClass("notice-success notice-error").text("");

			$.ajax({
				url: config.ajaxurl,
				type: "POST",
				data: {
					action: "faq_ai_sync_models",
					nonce: config.nonce,
				},
				success: (response) => {
					if (!response || !response.success) {
						const errorMessage =
							response?.data?.message ||
							config.i18n?.syncError ||
							"Unable to sync models from OpenAI.";
						$status.addClass("notice-error").text(errorMessage);
						return;
					}

					const current = $select.val();
					$select.empty();

					$.each(response.data.models || {}, (id, name) => {
						$select.append($("<option></option>").attr("value", id).text(name));
					});

					if (current && $select.find(`option[value="${current}"]`).length) {
						$select.val(current);
					}

					$status
						.addClass("notice-success")
						.text(
							response.data.message ||
								config.i18n?.syncSuccess ||
								"Models synced successfully.",
						);
				},
				error: () => {
					$status
						.addClass("notice-error")
						.text(
							config.i18n?.syncError || "Unable to sync models from OpenAI.",
						);
				},
				complete: () => {
					$button.prop("disabled", false).text(originalLabel);
				},
			});
		});
	});
})(jQuery);
