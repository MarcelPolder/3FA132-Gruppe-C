jQuery(($) => {
	// Callback
	if(typeof jQueryReady === 'function') jQueryReady($);

	// Functions
	$.fn.isInViewport = () => {
		var elementTop = $(this).offset().top;
		var elementBottom = elementTop + $(this).outerHeight();
		var viewportTop = $(window).scrollTop();
		var viewportBottom = viewportTop + $(window).height();
		return elementBottom > viewportTop && elementTop < viewportBottom;
	};

	// Ajax
	$.ajaxSetup({ cache: false });

	// Events
	$(document).on('submit', 'form[method="AJAX"]', function(e) {
		e.preventDefault();
		var el = $(this);
		var confirm = el.attr('confirm');
		if (typeof confirm === 'undefined' || window.confirm(confirm)) {
			var action = el.attr('action');
			if (action == '') action = location.pathname.substring(1);
			var callback = el.attr('callback');
			var data = el.serializeArray();
			if (el.find('input[type="file"]').length) {
				data = new FormData(el.get(0));
			}
			var middleware = el.attr('middleware');
			if (typeof middleware!=='undefined' && typeof window[middleware]==='function') {
				var middlewareResponse = window[middleware]();
				if (el.find('input[type="file"]').length) {
					for (const [key, value] of Object.entries(middlewareResponse)) {
						data.append(key, value);
					}
				} else {
					data = {...data, ...middlewareResponse};
				}
			}
			if (data!='' && el[0].checkValidity()) {
				ajax('/ajax/' + action, 'POST', data, (response) => {
					if (typeof callback!=='undefined' && typeof window[callback]==='function') {
						response['element'] = el;
						window[callback](response);
					}
				});
			}
		}
		return false;
	});
	$('.ajaxAutoload').each((index, el) => {
		var el = $(el);
		var action = el.attr('action') ?? el.attr('href') ?? '';
		if (action == '') action = location.pathname.substring(1);
		var data = JSON.parse((typeof el.attr('data')!=='undefined' && el.attr('data')!='' ? el.attr('data') : "{}"));
		var callback = el.attr('callback');
		var callbackParam = el.attr('callbackParam');
		var middleware = el.attr('middleware');
		if (typeof middleware!=='undefined' && typeof window[middleware]==='function') {
			var middlewareResponse = window[middleware]();
			data = {...data, ...middlewareResponse};
		}
		ajax('/ajax/' + action, 'POST', data, (response) => {
			if (typeof callback!=='undefined' && typeof window[callback]==='function') {
				response['element'] = el;
				if (typeof callbackParam!=='undefined' && callbackParam!='') {
					window[callback](response, callbackParam);
				} else {
					window[callback](response);
				}
			}
		});
	});
	$(document).on('click', '.ajaxClick', function(e) {
		e.preventDefault();
		var el = $(this);
		var confirm = el.attr('confirm');
		if (typeof confirm === 'undefined' || window.confirm(confirm)) {
			var action = el.attr('action') ?? el.attr('href') ?? '';
			if (action=='') action = location.pathname.substring(1);
			var data = JSON.parse((typeof el.attr('data')!=='undefined' && el.attr('data')!='' ? el.attr('data') : "{}"));
			var callback = el.attr('callback');
			var callbackParam = el.attr('callbackParam');
			var middleware = el.attr('middleware');
			if (typeof middleware!=='undefined' && typeof window[middleware]==='function') {
				var middlewareResponse = window[middleware]();
				data = {...data, ...middlewareResponse};
			}
			ajax('/ajax/' + action, 'POST', data, (response) => {
				if (typeof callback!=='undefined' && typeof window[callback]==='function') {
					response['element'] = el;
					if (typeof callbackParam!=='undefined' && callbackParam!='') {
						window[callback](response, callbackParam);
					} else {
						window[callback](response);
					}
				}
			});
		}
		return false;
	});
	$(document).on('change', '.ajaxChange', function(e) {
		e.preventDefault();
		var el = $(this);
		var confirm = el.attr('confirm');
		if (typeof confirm==='undefined' || window.confirm(confirm)) {
			var action = el.attr('action') ?? el.attr('href') ?? '';
			if (action == '') action = location.pathname.substring(1);
			var data = JSON.parse(el.attr('data') ?? "{}");
			if(el.val() && el.attr('name')) data[el.attr('name')] = el.val();
			var callback = el.attr('callback');
			var callbackParam = el.attr('callbackParam');
			var middleware = el.attr('middleware');
			if (typeof middleware!=='undefined' && typeof window[middleware]==='function') {
				var middlewareResponse = window[middleware]();
				data = {...data, ...middlewareResponse};
			}
			ajax('ajax/' + action, 'POST', data, (response) => {
				if (typeof callback!=='undefined' && typeof window[callback]==='function') {
					response['element'] = el;
					if (typeof callbackParam!=='undefined' && callbackParam != '') {
						window[callback](response, callbackParam);
					} else {
						window[callback](response);
					}
				}
			});
		}
		return false;
	});
	$(document).on('click', '[confirm]:not(.ajaxClick):not(.ajaxChange)', function(e) {
		e.stopPropagation();
		var el = $(this);
		var confirm = el.attr('confirm');
		if (typeof confirm==='undefined' || window.confirm(confirm)) {
			var popup = el.attr('popupOnConfirm');
			if (typeof popup !== 'undefined') {
				e.preventDefault();
				openPopup($(this));
			}
			return true;
		}
		return false;
	});

	// Navigation Mobile
	$('#nav-mobile').on('click', (e) => {
		e.preventDefault();
		$('nav').slideToggle('fast');
		return false;
	});

	// Scroll to Top
	$(window).scroll(() => {
		if ($(document).scrollTop() >= 300) {
			$('#scrollTop').fadeIn('fast');
		} else {
			$('#scrollTop').fadeOut('fast');
		}
	});
	$('#scrollTop').on('click', (e) => {
		e.preventDefault();
		$('html,body').animate({ scrollTop: 0 }, 'normal');
		return false;
	});

	// Popup
	$(document).on('click', '.popupBoxClose', function(e) {
		e.preventDefault();
		$(this).parent().parent('.popup').fadeOut('fast');
	});
	$(document).on('click', '.popupToggle', function(e) {
		e.preventDefault();
		openPopup($(this));
	});

	// Table sorting
	$('table[data-data]').each((idx, table) => {
		var data = $(table).data('data');
		$(table).removeAttr('data-data').data('data', data);
	});
	$(document).on('click', 'table thead th[data-sort]', function() {
		var table = $(this).closest('table');
		var data = table.data('data');
		var column = $(this);
		var columnValue = column.data('sort');
		var columnSortDesc = (column.hasClass('sorted') && !column.hasClass('sorted-desc'));

		const dataIndexed = data.map((item, idx) => ({ index: idx, value: item }));
		dataIndexed.sort((a, b) => {
			if(!columnSortDesc) {
				return (a.value[columnValue]+"").localeCompare((b.value[columnValue]+""), undefined, {
					numeric: !isNaN(a.value[columnValue]),
				});
			} else {
				return (b.value[columnValue]+"").localeCompare((a.value[columnValue]+""), undefined, {
					numeric: !isNaN(b.value[columnValue]),
				});
			}
		});

		var tableBody = table.find('tbody');
		const currentData = tableBody.children('tr');
		
		tableBody.html('');
		dataIndexed.forEach(element => {
			tableBody.append(currentData[element.index]);
		});

		const dataSorted = dataIndexed.map((item) => item.value);

		table.data('data', dataSorted);
		table.find('thead th').removeClass('sorted sorted-desc');
		column.addClass('sorted');
		if(columnSortDesc) column.addClass('sorted-desc');
	});

	// Flatpickr
	if(typeof flatpickr!=='undefined') {
		flatpickr.localize(flatpickr.l10ns.de);
		flatpickr('.flpckr', {
			dateFormat: 'd.m.Y',
			weekNumbers: true,
			onReady: (dateObj, dateStr, instance) => {
				var $cal = $(instance.calendarContainer);
				if($cal.find('.flpckr-clear').length < 1) {
					$cal.append('<div class="flpckr-clear"></div>');
					$cal.find('.flpckr-clear').on('click', () => {
						instance.clear();
						instance.close();
					});
				}
			},
		});
		flatpickr('.flpckr-time', {
			dateFormat: 'd.m.Y H:i',
			enableTime: true,
			time_24hr: true,
			weekNumbers: true,
			onReady: (dateObj, dateStr, instance) => {
				var $cal = $(instance.calendarContainer);
				if($cal.find('.flpckr-clear').length < 1) {
					$cal.append('<div class="flpckr-clear"></div>');
					$cal.find('.flpckr-clear').on('click', () => {
						instance.clear();
						instance.close();
					});
				}
			},
		});
		flatpickr('.flpckr-only-time', {
			dateFormat: 'H:i',
			noCalendar: true,
			enableTime: true,
			time_24hr: true,
			onReady: (dateObj, dateStr, instance) => {
				var $cal = $(instance.calendarContainer);
				if($cal.find('.flpckr-clear').length < 1) {
					$cal.append('<div class="flpckr-clear"></div>');
					$cal.find('.flpckr-clear').on('click', () => {
						instance.clear();
						instance.close();
					});
				}
			},
		});
		flatpickr('.flpckr-range', {
			mode: 'range',
			dateFormat: 'd.m.Y',
			weekNumbers: true,
			onReady: (dateObj, dateStr, instance) => {
				var $cal = $(instance.calendarContainer);
				if($cal.find('.flpckr-clear').length < 1) {
					$cal.append('<div class="flpckr-clear"></div>');
					$cal.find('.flpckr-clear').on('click', () => {
						instance.clear();
						instance.close();
					});
				}
			},
		});
		// var fp = $('#flpckr-startseite')[0]._flatpickr;
		// fp.config.onChange = [(dateObj, dateStr) => {
		// 	if(dateObj.length==2) { // when finished changing } else if(dateStr.length==0) { }
		// }];
	}

	// form field error tooltips
	$(document).on('click', '.fieldErrorTooltip', function(e) {
		e.preventDefault();
		$(this).fadeOut('fast');
	});

	$(document).on('click', '.nav-toggle', function(event) {
		event.preventDefault();
		$('nav').toggleClass('wide');
		$('.nav-item').toggleClass('item-large');
		$('main').toggleClass('shrunk');
	})
});

// mhLightbox
$.mhLightbox({ padding: '0px' });

// Callbacks
// function logout(response) {
// 	if(response) redirect('/');
// }

// Functions
function ajax(action, type = 'POST', data = {}, callback) {
	type = type=='' || type==null ? 'POST' : type;
	data = data=='' || data==null ? {} : data;
	data = type!='POST' ? '' : data;
	if (action !== '') {
		$.ajax({
			url: action,
			type: type,
			data: data,
			processData: (data instanceof FormData ? false : true),
			contentType: (data instanceof FormData ? false : "application/x-www-form-urlencoded; charset=UTF-8"),
			beforeSend: () => {
				if ($('#progressBar').length) {
					$('#progressBar').show();
				}
			},
			xhr: () => {
				var xhr = $.ajaxSettings.xhr();
				xhr.onprogress = (e) => {
					if (e.isTrusted && e.lengthComputable) {
						var percentComplete = Math.floor(
							(e.loaded / e.total) * 100
						);
						$('#progressBar').css('width', percentComplete + '%');
					}
				};
				xhr.upload.onprogress = (e) => {
					if (e.isTrusted && e.lengthComputable) {
						var percentComplete = Math.floor(
							(e.loaded / e.total) * 100
						);
						$('#progressBar').css('width', percentComplete + '%');
					}
				};
				return xhr;
			},
			success: (response) => {
				if (typeof callback === 'function') callback(response);
				return;
			},
			error: () => {
				if (typeof callback === 'function') callback(false);
				return;
			},
			complete: () => {
				if ($('#progressBar').length) {
					setTimeout(() => {
						$('#progressBar').fadeOut('fast', () => {
							$(this).css('width', '0%');
						});
					}, 50);
				}
			},
		});
	}
}
function openPopup(element) {
	if(typeof element!=='undefined' && element.length) {
		var autofocus = element.data('autofocus');
		if (typeof autofocus === 'undefined') autofocus = '';
		var animateId = element.data('popup') ?? element.data('id') ?? element.attr('href');
		var animate = element.data('animate');
		if (typeof animate === 'undefined') animate = false;

		if (animate) {
			var animations = element.data();
			var animationObject = {};
			$.each(animations, (index, value) => {
				if (index.includes('animate') && index.length > 7) {
					var animation = index.replace('animate', '').toLowerCase();
					animationObject[animation] = value;
					element.data(index, $(animateId).css(animation));
				}
			});
			$(animateId).animate(animationObject, 'fast');
		} else {
			$(animateId).fadeToggle('fast');
		}

		if (autofocus != '') $(autofocus).focus();
	}
}
function redirect(path) {
	if (typeof path !== 'undefined' && path != '') {
		location.href = path;
	} else {
		location.reload();
	}
}
class notificationQueue {
	constructor() {
		this.data = [];
	}
	add(record) {
		this.data.push(record);
	}
	remove() {
		this.data.shift();
	}
	first() {
		return this.data[0];
	}
	last() {
		return this.data[this.data.length - 1];
	}
	size() {
		return this.data.length;
	}
	notificationShow(duration) {
		if(duration<1000) duration = 2000;
		var top = $('#notify').css('top');
		$('#notify').text(nQ.first()).show()
			.animate({ top: '24px', }, 500, () => {
				nQ.remove();
			})
			.delay(duration - 1000)
			.animate({ top: top, }, 500, () => {
				$(this).hide().text('');
				if(nQ.size()==0) {
					notifyActive = false;
					clearInterval(notifyInterval);
				}
			});
	}
}
var nQ = new notificationQueue();
var notifyActive = false;
var notifyInterval = null;
function notify(msg, callback = null, duration = 4000) {
	if(typeof msg!=='undefined' && msg!='') {
		nQ.add(msg);
		if(!notifyActive) {
			notifyActive = true;
			nQ.notificationShow(duration);
			notifyInterval = setInterval(function() {
				nQ.notificationShow(duration);
			}, duration + 25);
			if(typeof callback === 'function') setTimeout(callback, duration + 25);
		}
	}
}
function isMobile() {
	var check = false;
	((a) => {
		if (
			/(android|bb\d+|meego).+mobile|avantgo|bada\/|blackberry|blazer|compal|elaine|fennec|hiptop|iemobile|ip(hone|od)|iris|kindle|lge |maemo|midp|mmp|mobile.+firefox|netfront|opera m(ob|in)i|palm( os)?|phone|p(ixi|re)\/|plucker|pocket|psp|series(4|6)0|symbian|treo|up\.(browser|link)|vodafone|wap|windows ce|xda|xiino/i.test(
				a
			) ||
			/1207|6310|6590|3gso|4thp|50[1-6]i|770s|802s|a wa|abac|ac(er|oo|s\-)|ai(ko|rn)|al(av|ca|co)|amoi|an(ex|ny|yw)|aptu|ar(ch|go)|as(te|us)|attw|au(di|\-m|r |s )|avan|be(ck|ll|nq)|bi(lb|rd)|bl(ac|az)|br(e|v)w|bumb|bw\-(n|u)|c55\/|capi|ccwa|cdm\-|cell|chtm|cldc|cmd\-|co(mp|nd)|craw|da(it|ll|ng)|dbte|dc\-s|devi|dica|dmob|do(c|p)o|ds(12|\-d)|el(49|ai)|em(l2|ul)|er(ic|k0)|esl8|ez([4-7]0|os|wa|ze)|fetc|fly(\-|_)|g1 u|g560|gene|gf\-5|g\-mo|go(\.w|od)|gr(ad|un)|haie|hcit|hd\-(m|p|t)|hei\-|hi(pt|ta)|hp( i|ip)|hs\-c|ht(c(\-| |_|a|g|p|s|t)|tp)|hu(aw|tc)|i\-(20|go|ma)|i230|iac( |\-|\/)|ibro|idea|ig01|ikom|im1k|inno|ipaq|iris|ja(t|v)a|jbro|jemu|jigs|kddi|keji|kgt( |\/)|klon|kpt |kwc\-|kyo(c|k)|le(no|xi)|lg( g|\/(k|l|u)|50|54|\-[a-w])|libw|lynx|m1\-w|m3ga|m50\/|ma(te|ui|xo)|mc(01|21|ca)|m\-cr|me(rc|ri)|mi(o8|oa|ts)|mmef|mo(01|02|bi|de|do|t(\-| |o|v)|zz)|mt(50|p1|v )|mwbp|mywa|n10[0-2]|n20[2-3]|n30(0|2)|n50(0|2|5)|n7(0(0|1)|10)|ne((c|m)\-|on|tf|wf|wg|wt)|nok(6|i)|nzph|o2im|op(ti|wv)|oran|owg1|p800|pan(a|d|t)|pdxg|pg(13|\-([1-8]|c))|phil|pire|pl(ay|uc)|pn\-2|po(ck|rt|se)|prox|psio|pt\-g|qa\-a|qc(07|12|21|32|60|\-[2-7]|i\-)|qtek|r380|r600|raks|rim9|ro(ve|zo)|s55\/|sa(ge|ma|mm|ms|ny|va)|sc(01|h\-|oo|p\-)|sdk\/|se(c(\-|0|1)|47|mc|nd|ri)|sgh\-|shar|sie(\-|m)|sk\-0|sl(45|id)|sm(al|ar|b3|it|t5)|so(ft|ny)|sp(01|h\-|v\-|v )|sy(01|mb)|t2(18|50)|t6(00|10|18)|ta(gt|lk)|tcl\-|tdg\-|tel(i|m)|tim\-|t\-mo|to(pl|sh)|ts(70|m\-|m3|m5)|tx\-9|up(\.b|g1|si)|utst|v400|v750|veri|vi(rg|te)|vk(40|5[0-3]|\-v)|vm40|voda|vulc|vx(52|53|60|61|70|80|81|83|85|98)|w3c(\-| )|webc|whit|wi(g |nc|nw)|wmlb|wonu|x700|yas\-|your|zeto|zte\-/i.test(
				a.substr(0, 4)
			)
		)
			check = true;
	})(navigator.userAgent || navigator.vendor || window.opera);
	return check;
}
function copyToClipboard(text, notifyMsg) {
	if (typeof notifyMsg === 'undefined' || notifyMsg == '') notifyMsg = 'kopiert';
	if(navigator.clipboard) {
		navigator.clipboard.writeText(text).then(() => notify(notifyMsg));
	} else {
		var input = $('<input>').val(text).appendTo('body').select();
		document.execCommand('copy');
		input.remove();
		notify(notifyMsg);
	}
}
function sendMail(subject, body) {
	var mail = encodeURI('mailto:?subject=' + subject + '&body=' + body) + '%0D%0A%0D%0A';
	location.href = mail;
}
function sendWhatsapp(subject, body) {
	if (isMobile()) {
		location.href = encodeURI(
			'whatsapp://send?text=' + subject + ': ' + body
		);
	} else {
		window.open(
			encodeURI(
				'https://api.whatsapp.com/send?text=' + subject + ': ' + body
			),
			'_blank'
		);
	}
}
function getCookie(cookieName) {
	let name = cookieName + "=";
	let decodedCookie = decodeURIComponent(document.cookie);
	let ca = decodedCookie.split(';');
	for(let i = 0; i <ca.length; i++) {
		let c = ca[i];
		while (c.charAt(0) == ' ') {
			c = c.substring(1);
		}
		if (c.indexOf(name) == 0) {
			return c.substring(name.length, c.length);
		}
	}
	return "";
}
function setCookie(cookieName, cookieValue, expDays = 1, samesite = "Lax") {
	expDays = expDays=='' || expDays==null ? 1 : expDays;
	samesite = samesite=='' || samesite==null ? "Lax" : samesite;
	const d = new Date();
	d.setTime(d.getTime() + (expDays*24*60*60*1000));
	let expires = "expires="+ d.toUTCString();
	document.cookie = cookieName + "=" + cookieValue + ";" + expires + ";path=/;SameSite="+samesite+(document.location.protocol=='https:' ? ";secure" : "");
}