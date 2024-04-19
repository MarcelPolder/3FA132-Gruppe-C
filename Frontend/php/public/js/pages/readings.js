jQuery(function($) {
	var renderCount = 0;
	loadReadings(renderCount);

	$(window).scroll(function() {
		if ($(window).scrollTop() + $(window).height() >= $(document).height() - 44) {
			renderCount++;
			loadReadings(renderCount);
		}
	});


	$(document).on('click', '.add-reading', function(event) {
		event.preventDefault();
		$(document).find('#add-reading').slideToggle('fast');
	});
	$(document).on('click', '.edit-reading-toggle', function(event) {
		event.preventDefault();
		$(this).parents('.reading').find('.edit-reading').slideToggle();
	});

	function loadReadings(idx) {
		ajax("/ajax/readings/get", "POST", {index: idx}, function(response) {
			if (response.status == 200) {
				if (idx == 0) $('#readings .grid').html(response.data.html);
				else $('#readings .grid').append(response.data.html);
			}
		});
	}
});

function updateReading(response) {
	notify(response.msg);
	if (response.status == 200) {
		
	}
}

function deleteReading(response) {
	notify(response.msg);
	if (response.status == 200) {
		$(document).find('.reading[data-id="'+response.data.id+'"]').remove();
	}
}