;(function ($) {
	'use strict'

	$(document).ready(function () {
		$('.faq-question').click(function () {
			$(this).toggleClass('active')
			$(this).next('.faq-answer').toggleClass('active')
		})
	})
})(jQuery)
