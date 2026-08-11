(function () {
	'use strict';

	function toggleSourceFields() {
		var select = document.getElementById('tarw-source-type');
		if (!select) {
			return;
		}

		document.querySelectorAll('.tarw-source-business-profile').forEach(function (element) {
			element.hidden = select.value !== 'business_profile';
		});
		document.querySelectorAll('.tarw-source-places').forEach(function (element) {
			element.hidden = select.value !== 'places';
		});
	}

	document.addEventListener('DOMContentLoaded', function () {
		if (window.jQuery && window.jQuery.fn.wpColorPicker) {
			window.jQuery('.tarw-color-field').wpColorPicker();
		}

		var select = document.getElementById('tarw-source-type');
		if (select) {
			select.addEventListener('change', toggleSourceFields);
			toggleSourceFields();
		}

		document.querySelectorAll('.tarw-shortcode').forEach(function (field) {
			field.addEventListener('click', function () {
				field.select();
			});
		});
	});
}());
