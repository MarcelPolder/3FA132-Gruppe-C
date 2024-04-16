jQuery(function($) {
	$('.user-edit').click(function(event) {
		event.preventDefault();
		$(this).parents('.user').find('.user-password-edit').slideUp('fast', function() {
			$(this).parents('.user').find('.user-info-edit').slideToggle('fast');
		});
	});
	$('.user-password').click(function(event) {
		event.preventDefault();
		$(this).parents('.user').find('.user-info-edit').slideUp('fast', function() {
			$(this).parents('.user').find('.user-password-edit').slideToggle('fast');
		});
	});
	$('.add-user').click(function(event) {
		event.preventDefault();
		$('#add-user').slideToggle('fast');
	});
});

function updateUser(response) {
	notify(response.msg);
	if (response.status == 200) {
		const el = $('.user[data-id="'+response.data.id+'"]');
		el.find('input[name="firstname"]').val(response.data.firstname);
		el.find('input[name="lastname"]').val(response.data.lastname);
		el.find('.user-firstname').text(response.data.firstname);
		el.find('.user-lastname').text(response.data.lastname);
		el.find('.user-info-edit').slideUp('fast');
	}
}
function updatePassword(response) {
	notify(response.msg);
	if (response.status == 200) {
		$(document).find('.user[data-id="'+response.data.id+'"] .user-password-edit').slideUp('fast');
	}
}