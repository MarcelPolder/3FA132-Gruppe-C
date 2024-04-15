jQuery(function($) {
	// darkmode
	if($('.theme-toggle').length) {
		$(document).on('click', '.theme-toggle-switch, .theme-toggle .material-symbols-rounded.active', function(e) {
			e.preventDefault();
			let parent = $(this).closest('.theme-toggle');
			parent.children('.material-symbols-rounded').toggleClass('active');
			parent.children('.theme-toggle-cbx').prop('checked', !parent.children('.theme-toggle-cbx').prop('checked'));
			setColorScheme(parent.children('.theme-toggle-cbx').get(0).checked);
		});
	}
	if(window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches) {
		setColorScheme(true);
		if($('.theme-toggle-cbx').lenght && !$('.theme-toggle-cbx').prop('checked')) {
			$('.theme-toggle-cbx').prop('checked', true);
		}
	}
	window.matchMedia('(prefers-color-scheme: dark)').addEventListener('change', event => {
		setColorScheme(event.matches);
		if($('.theme-toggle-cbx').lenght && event.matches!=$('.theme-toggle-cbx').prop('checked')) {
			$('.theme-toggle-cbx').prop('checked', event.matches);
		}
	});
});
function setColorScheme(isDarkmode = false, days = 365) {
	$('body').toggleClass('darkmode', isDarkmode);
	setCookie("darkmode", isDarkmode, days, "Lax");
	$('img[data-color-scheme-switch-src]').each(function() {
		var oldSrc = $(this).attr('src');
		$(this).attr('src', $(this).attr('data-color-scheme-switch-src'));
		$(this).attr('data-color-scheme-switch-src', oldSrc);
	});
}