(($) => {
	$(() => {
		$(document).on("click", ".faq-question", function () {
			$(this).toggleClass("active");
			$(this).next(".faq-answer").toggleClass("active");
		});
	});
})(jQuery);
