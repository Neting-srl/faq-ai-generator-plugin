(($) => {
	$(() => {
		const $list = $(".faq-ai-generator-list");
		const $generateBtn = $("#faq-ai-generate");
		const $addBtn = $("#faq-ai-add");
		const $confirmDialog = $("#faq-ai-confirm-dialog");
		const $overwriteBtn = $("#faq-ai-overwrite");
		const $appendBtn = $("#faq-ai-append");
		const $cancelBtn = $("#faq-ai-cancel");
		const $message = $(".faq-ai-message");
		const $hint = $("#faq-ai-generation-hint");
		let shouldAppend = false;

		const config = window.faqAiGenerator || {};
		const ajaxUrl =
			config.ajaxurl || (typeof ajaxurl !== "undefined" ? ajaxurl : "");
		const nonce = config.nonce || $('input[name="faq_ai_nonce"]').val();
		const postId = $("#post_ID").val();
		const i18n = config.i18n || {};

		const escapeHtml = (text) =>
			$("<div>")
				.text(text == null ? "" : String(text))
				.html();

		const showConfirmDialog = () => {
			$confirmDialog.css("display", "flex");
		};

		const hideConfirmDialog = () => {
			$confirmDialog.hide();
		};

		const showMessage = (type, text) => {
			const cssClass = type === "success" ? "notice-success" : "notice-error";
			$message.html(
				`<div class="notice ${cssClass}"><p>${escapeHtml(text)}</p></div>`,
			);
		};

		const updateIndexes = () => {
			$list.find(".faq-item").each(function (index) {
				$(this).attr("data-index", index);
			});
		};

		const addFaqItem = (question, answer, index) => {
			const safeQuestion = escapeHtml(question);
			const safeAnswer = escapeHtml(answer);
			const questionPlaceholder = escapeHtml(
				i18n.questionPlaceholder || "Question",
			);
			const answerPlaceholder = escapeHtml(i18n.answerPlaceholder || "Answer");
			const removeLabel = escapeHtml(i18n.remove || "Remove");
			const $item = $(`
				<div class="faq-item" data-index="${index}">
					<div class="faq-question">
						<input type="text" name="faq_ai_questions[]" value="${safeQuestion}" placeholder="${questionPlaceholder}">
					</div>
					<div class="faq-answer">
						<textarea name="faq_ai_answers[]" placeholder="${answerPlaceholder}">${safeAnswer}</textarea>
					</div>
					<button type="button" class="button faq-remove">${removeLabel}</button>
				</div>
			`);
			$list.append($item);
		};

		const generateNewFaqs = () => {
			const originalHtml = $generateBtn.html();
			const generatingLabel = i18n.generating || "Generating...";

			$generateBtn
				.prop("disabled", true)
				.html(
					`<span class="dashicons dashicons-update spinning"></span> ${escapeHtml(generatingLabel)}`,
				);

			$.ajax({
				url: ajaxUrl,
				type: "POST",
				data: {
					action: "faq_ai_generate",
					post_id: postId,
					nonce: nonce,
					append: shouldAppend ? "true" : "false",
					generation_hint: $hint.length ? $hint.val() : "",
				},
				success: (response) => {
					if (!response || !response.success) {
						const errorMessage =
							response?.data?.message ||
							i18n.generateError ||
							"An error occurred while generating FAQs.";
						showMessage("error", errorMessage);
						return;
					}

					if (!shouldAppend) {
						$list.empty();
					}

					const startIndex = $list.find(".faq-item").length;
					(response.data.faqs || []).forEach((faq, index) => {
						addFaqItem(faq.question, faq.answer, startIndex + index);
					});
					updateIndexes();
					showMessage(
						"success",
						i18n.generateSuccess || "FAQs generated successfully!",
					);
				},
				error: () => {
					showMessage(
						"error",
						i18n.generateError || "An error occurred while generating FAQs.",
					);
				},
				complete: () => {
					$generateBtn.prop("disabled", false).html(originalHtml);
				},
			});
		};

		$generateBtn.on("click", (e) => {
			e.preventDefault();
			showConfirmDialog();
		});

		$overwriteBtn.on("click", () => {
			shouldAppend = false;
			hideConfirmDialog();
			generateNewFaqs();
		});

		$appendBtn.on("click", () => {
			shouldAppend = true;
			hideConfirmDialog();
			generateNewFaqs();
		});

		$cancelBtn.on("click", () => {
			hideConfirmDialog();
		});

		$addBtn.on("click", () => {
			addFaqItem("", "", $list.find(".faq-item").length);
		});

		$list.on("click", ".faq-remove", function () {
			$(this).closest(".faq-item").remove();
			updateIndexes();
		});
	});
})(jQuery);
