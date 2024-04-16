jQuery(function($) {
	var renderCount = 0;
	loadCustomers(renderCount);

	$(window).scroll(function() {
		if ($(window).scrollTop() + $(window).height() >= $(document).height() - 44) {
			renderCount++;
			loadCustomers(renderCount);
		}
	});


	$(document).on('click', '.add-customer', function(event) {
		event.preventDefault();
		$(document).find('#add-customer').slideToggle('fast');
	});
	$(document).on('click', '.edit-customer-toggle', function(event) {
		event.preventDefault();
		$(this).parents('.customer').next('.edit-customer').slideToggle();
	});

	function loadCustomers(idx) {
		ajax("/ajax/customers/get", "POST", {index: idx}, function(response) {
			if (response.status == 200) {
				if (idx == 0) $('table tbody').html(response.data.html);
				else $('table tbody').append(response.data.html);
			}
		});
	}
});

function updateCustomer(response) {
	notify(response.msg);
	if (response.status == 200) {
		$(document).find('tr.customer[data-id="'+response.data.id+'"]').find('.customer-firstname').text(response.data.firstname)
		$(document).find('tr.customer[data-id="'+response.data.id+'"]').find('.customer-lastname').text(response.data.lastname);
		$(document).find('tr.edit-customer[data-id="'+response.data.id+'"]').find('input[name="firstname"]').val(response.data.firstname)
		$(document).find('tr.edit-customer[data-id="'+response.data.id+'"]').find('input[name="lastname"]').val(response.data.lastname);
		$(document).find('tr.edit-customer[data-id="'+response.data.id+'"]').slideUp('fast');
	}
}

function deleteCustomer(response) {
	notify(response.msg);
	if (response.status == 200) {
		$(document).find('.customer[data-id="'+response.data.id+'"], .edit-customer[data-id="'+response.data.id+'"]').remove();
	}
}