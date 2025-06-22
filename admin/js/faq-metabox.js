jQuery(document).ready(function ($) {
	const $metabox = $('.faq-ai-generator-metabox')
	const $list = $('.faq-ai-generator-list')
	const $generateBtn = $('#faq-ai-generate')
	const $addBtn = $('#faq-ai-add')
	const $confirmDialog = $('#faq-ai-confirm-dialog')
	const $overwriteBtn = $('#faq-ai-overwrite')
	const $appendBtn = $('#faq-ai-append')
	const $cancelBtn = $('#faq-ai-cancel')
	const nonce = $('input[name="faq_ai_nonce"]').val()
	const postId = $('#post_ID').val()
	var shouldAppend = false

	// Function to show the confirmation dialog
	function showConfirmDialog() {
		$confirmDialog.show()
	}

	// Function to hide the confirmation dialog
	function hideConfirmDialog() {
		$confirmDialog.hide()
	}

	// Handle the click on the Generate FAQ button
	$generateBtn.on('click', function (e) {
		e.preventDefault()
		showConfirmDialog()
	})

	// Handle the click on the Overwrite button
	$overwriteBtn.on('click', function () {
		shouldAppend = false
		hideConfirmDialog()
		generateNewFaqs()
	})

	// Handle the click on the Add button
	$appendBtn.on('click', function () {
		shouldAppend = true
		hideConfirmDialog()
		generateNewFaqs()
	})

	// Handle the click on the Cancel button
	$cancelBtn.on('click', function () {
		hideConfirmDialog()
	})

	// Handle the click on the Add a new FAQ button
	$addBtn.on('click', function () {
		const index = $('.faq-item').length
		addFaqItem('', '', index)
	})

	// Remove FAQ
	$list.on('click', '.faq-remove', function () {
		$(this).closest('.faq-item').remove()
		updateIndexes()
	})

	// Function to update the indexes
	function updateIndexes() {
		$('.faq-item').each(function (index) {
			$(this).attr('data-index', index)
		})
	}

	// Function to generate new FAQs
	function generateNewFaqs() {
		const $self = $generateBtn
		$self.prop('disabled', true).text('Generazione in corso...')

		$.ajax({
			url: ajaxurl,
			type: 'POST',
			data: {
				action: 'faq_ai_generate',
				post_id: postId,
				nonce: nonce,
				append: shouldAppend ? 'true' : 'false',
			},
			success: function (response) {
				if (response.success) {
					if (!shouldAppend) {
						$list.empty()
					}
					response.data.faqs.forEach(function (faq, index) {
						addFaqItem(faq.question, faq.answer, index)
					})
				} else {
					alert(response.data.message)
				}
			},
			error: function () {
				alert('Errore durante la generazione delle FAQ')
			},
			complete: function () {
				$self.prop('disabled', false).text('Genera FAQ')
			},
		})
	}

	// Function to add a FAQ item
	function addFaqItem(question, answer, index) {
		const $item = $(
			'<div class="faq-item" data-index="' +
				index +
				'">' +
				'<div class="faq-question">' +
				'<input type="text" name="faq_ai_questions[]" value="' +
				question +
				'" placeholder="Domanda">' +
				'</div>' +
				'<div class="faq-answer">' +
				'<textarea name="faq_ai_answers[]" placeholder="Risposta">' +
				answer +
				'</textarea>' +
				'</div>' +
				'<button type="button" class="button faq-remove">Rimuovi</button>' +
				'</div>'
		)

		$list.append($item)
	}
})
