(($) => {
	$(() => {
		const $doc = $(document);

		$doc.on("click", ".faq-ai-question", function () {
			const $item = $(this).closest(".faq-ai-item");
			const $accordion = $item.closest(".faq-ai-accordion");

			$item.toggleClass("active");

			if ($accordion.length) {
				$item.siblings(".faq-ai-item").removeClass("active");
			}
		});

		$doc.on("click", ".faq-ai-tab", function () {
			const $tab = $(this);
			const tabId = $tab.data("tab");
			const $container = $tab.closest(".faq-ai-container");

			$container.find(".faq-ai-tab").removeClass("active");
			$tab.addClass("active");

			$container.find(".faq-ai-tab-content").removeClass("active");
			$container.find(`#${tabId}`).addClass("active");
		});

		$(".faq-ai-tabs").each(function () {
			const $firstTab = $(this).find(".faq-ai-tab").first();
			if ($firstTab.length) {
				$firstTab.trigger("click");
			}
		});

		$(".faq-ai-accordion").each(function () {
			const $firstItem = $(this).find(".faq-ai-item").first();
			if ($firstItem.length) {
				$firstItem.addClass("active");
			}
		});
	});
})(jQuery);
