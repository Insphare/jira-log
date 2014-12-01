/**
 *
 */
$(document).ready(function () {

	var disableLoggedInFields = function() {
		if ($('.user').is(":visible")) {
			$('#password, #callProfile').attr('disabled','disabled');

			var timeTrack = $('#timetrack');
			if (timeTrack.val()) {
				timeTrack.attr('disabled','disabled');
			}

			$('#rvs').show();
		}
	};

	var enableFields = function() {
		$("#password, #timetrack, #callProfile").removeAttr('disabled');
	};

	$.ajaxSetup(
		{
			headers: {"X-Is-Ajax-Call": "yes"},
			beforeSend: function (xhr) {
				xhr.setRequestHeader('X-Is-Ajax-Call', 'yes');
			}
		}
	);

	_book = function (param) {
		var callback = function() {
			$('#ajaxContent').html('');
			$('#sheet').html('');
		};
		_callAjax('/book', {'tasks': param}, callback);
	};

	_defaultHandling = function (jsonResponse) {
		var success = $('.successBox'),
			error = $('.errorBox')
			;
		success.hide();
		error.hide();

		if (jsonResponse.error) {
			error.show().html(jsonResponse.error);
		}
		else if (jsonResponse.errorMessages && jsonResponse.errorMessages[0]) {
			error.show().html(jsonResponse.errorMessages[0]);
		}
		else if (jsonResponse.success) {
			success.show().html(jsonResponse.success);
		}

		if (jsonResponse.container && jsonResponse.html) {
			if ($(jsonResponse.container).length) {
				$(jsonResponse.container).html(jsonResponse.html);
			}
		}
	};

	_callAjax = function (url, params, callback) {
		$.ajax({
			url: url,
			data: params,
			dataType: 'JSON',
			type: 'POST',
			success: function (jsonResponse) {
				_defaultHandling(jsonResponse);

				if (typeof callback == 'function' && !jsonResponse.error) {
					callback(jsonResponse);
				}
			}
		});

		//$.getScript( "/js/onready.js" )
		//.done(function( script, textStatus ) {
		//	console.log( textStatus );
		//})
		//.fail(function( jqxhr, settings, exception ) {
		//	console.log( "Triggered ajaxError handler." );
		//});
	};

	_calculate = function() {
		var hour = 0;

		$('.numberMinute').each(function(x,object) {
			hour += (parseInt($(object).val())/60);
		});

		$('.numberHour').each(function(x,object) {
			hour += (parseInt($(object).val()));
		});

		var split = hour.toString().split('.');

		$('.hourSum').text(split[0] + 'h');
		var minute = 0;
		if (split && split[1]) {
			var splited = split[1];
			if (splited.length == 1) {
				splited *= 10;
			}
			minute = parseFloat(parseFloat(splited)/100*60)
		}

		$('.MinSum').text(minute + 'm');
	};

	$('#testConnection').off('click.test').on('click.test', function () {
		_callAjax('/check', {});
	});

	$('#getTimeTrack').off('click.timeTrack').on('click.timeTrack', function () {
		var callback = function(jsonResponse) {
			if (jsonResponse['issues'] && jsonResponse['issues'][0] && jsonResponse['issues'][0]['key']) {
				$('#timetrack').val(jsonResponse['issues'][0]['key']);
			}
			disableLoggedInFields();
		};
		_callAjax('/timetrack', {}, callback);
	});

	$('#callProfile').off('click.profile').on('click.profile', function () {
		var params = {'username': $('#username').val(), 'password': $('#password').val()};
		var callback = function(jsonResponse) {
			$('.user').show();
			disableLoggedInFields();
		};

		_callAjax('/me', params, callback);
	});

	$('#preview').off('click.preview').on('click.preview', function () {
		var params = {'sheet': $('#sheet').val()};
		var callback = function(jsonResponse) {
			$('#ajaxContent').show();
			_calculate();
			$('.numberHour, .numberMinute').change(function() {
				_calculate();
			});
			$('input.book').off('click.book').on('click.book', function(){
				var answer = confirm('Wirklich jetzt buchen?');
				if (answer) {
					var items = {};
					var i = 0;
					$('.bookItem').each(function(index, objectIdentifier) {
						var trObject = $(objectIdentifier),
							taskIssue = trObject.attr('data-issue'),
							hours = $('.numberHour', trObject).val() + 'h ',
							minutes = $('.numberMinute', trObject).val() + 'm ',
							comment = $('.comment', trObject).val()
						;
						items[i] = {'issue': taskIssue, 'duration': hours + minutes, 'comment': comment};
						i++;
					});

					_book(items);
				}
			});
		};

		_callAjax('/preview', params, callback);
	});

	disableLoggedInFields();

	$('#username').off('keypress.fields').on('keypress.fields', function(){
		enableFields();
	});
});
