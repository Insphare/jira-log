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
	};

	$('#testConnection').click(function () {
		_callAjax('/check', {});
	});

	$('#getTimeTrack').click(function () {
		var callback = function(jsonResponse) {
			if (jsonResponse['issues'] && jsonResponse['issues'][0] && jsonResponse['issues'][0]['key']) {
				$('#timetrack').val(jsonResponse['issues'][0]['key']);
			}
			disableLoggedInFields();
		};
		_callAjax('/timetrack', {}, callback);
	});

	$('#callProfile').click(function () {
		var params = {'username': $('#username').val(), 'password': $('#password').val()};
		var callback = function(jsonResponse) {
			$('.user').show();
			disableLoggedInFields();
		};

		_callAjax('/me', params, callback);
	});

	$('#preview').click(function () {
		var params = {'sheet': $('#sheet').val()};
		var callback = function(jsonResponse) {
			$('#ajaxContent').show();
		};

		_callAjax('/preview', params, callback);
	});

	disableLoggedInFields();

	$('#username').keypress(function(){
		enableFields();
	});

});
