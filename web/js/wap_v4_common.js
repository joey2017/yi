function imgError(tag,src) {
    $(tag).attr({'src':src,'onerror':''});
}

function MsgBox(alertdetail, alerttitle, gourl) {
	if (alerttitle == 'undefined' || alerttitle == '' || alerttitle == null) {
		$('#alerttitle').hide();
	}else{
		$('#alerttitle').text(alerttitle).show();
	}
	$('#alertdetail').text(alertdetail);
	if (arguments.length == 1 || arguments.length == 2) {
		$('#msgBox').show().fadeOut(3000);
	} else if (arguments.length == 3) {
		// $('#msgBox').show().fadeOut(3000, function() {
		// 	location.href = gourl;
		// });
		location.href = gourl;
		$('#msgBox').show().fadeOut(3000);
	}
}

function isMobile(mobile) {
	return /^1[3|4|5|8|7][0-9]\d{8}$/i.test(mobile);
}

function isEmptyObject(obj) {
	for (var i in obj) {
		return false;
	}
	return true;
}

function isArray(obj) {  
	return Object.prototype.toString.call(obj) === '[object Array]';   
}







