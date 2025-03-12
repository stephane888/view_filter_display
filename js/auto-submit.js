(function(Drupal) {
	Drupal.behaviors.view_filter_display = {
		attach: function(context, settings) {
			const forms = context.querySelectorAll ? context.querySelectorAll('.vfd__exposed_fields_filter_auto_submit') : [];
			if (forms)
				forms.forEach((form) => {
					const selects = form.querySelectorAll('select');
					if(selects)
					selects.forEach((select) => {
						select.addEventListener('change', () => {
							select.closest("form").submit();
						})

					})

				})
		},
	};
})(window.Drupal);