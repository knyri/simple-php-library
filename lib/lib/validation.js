// JavaScript Document
function ttError(form, element) {
	document.getElementById(form.name+"."+element.name+".row").style.backgroundColor = '#FFCCCC';
}
function showErrorTip(element) {
	var div = document.getElementById(element.name+'Error');
	if (div.innerHTML != '&nbsp;' && div.innerHTML != ' ' && div.innerHTML != '')
		TagToTip(div.id, FIX, [element,0,0], ABOVE, true, BGCOLOR, '#FFCCCC');
}
function showErrorTip2(form, element) {
	var div = document.getElementById(form.name+element.name+'Error');
	if (div.innerHTML != '&nbsp;' && div.innerHTML != ' ' && div.innerHTML != '')
		TagToTip(div.id, FIX, [element,0,0], ABOVE, true, BGCOLOR, '#FFCCCC');
}
function showError3(message, form, element) {
	var errEle = document.getElementById(element.name+'Error');
	document.getElementById(form.name+"."+element.name+".row").style.backgroundColor = '#FFCCCC';
	if (errEle != null) {
		errEle.innerHTML = message;
	}
}
function showClear3(form, element) {
	var errEle = document.getElementById(element.name+'Error');
	document.getElementById(form.name+"."+element.name+".row").style.backgroundColor = '#00CCCC';
	if (errEle != null) {
		errEle.innerHTML = 'Filled Correctly.';
	}
}
function clearError(form, element) {
	document.getElementById(form.name+"."+element.name+".row").style.backgroundColor = '';
	var errEle = document.getElementById(element.name+'Error');
	if (errEle != null) {
		errEle.innerHTML = '&nbsp;';
	}
}
function showError2(message, element) {
	var errEle = document.getElementById(element.name+'Error');
	if (errEle != null) {
		errEle.innerHTML = message;
	}
}
function showError(message, element, form) {
	var errEle = document.getElementById(form.name+element.name+'Error');
	if (errEle != null) {
		errEle.innerHTML = message;
	}
}
function countLetters(element, limit, display) {
	var count = limit - element.value.length;
	if (count < 0) {
		element.value = element.value.substr(0,limit);
		count = 0;
	}
	document.getElementById('letters'+element.name).innerHTML = '('+count + ' characters remaining)';
}
/* Nothing but isValid* functions
 * past this point.
 */
 var validUnamePassRegExp = /^[A-Za-z0-9_]{5,15}$/;
 var validEmailFormatRegExp = /^\w(\.?\w)*@\w(\.?[-\w])*\.[a-z]{2,4}$/i;
 var validZipRegExp = /^\d{5}(-\d{4})?$/i;
 function autoValidEmail(form, ele) {
	if (isValidEmail(ele.value)) {
		showClear3(form, ele);
	} else {
		showError3('Please enter a valid email.', form, ele);
	}
 }
 function autoValidZip(form, ele) {
	if (isValidZip(ele.value)) {
		showClear3(form, ele);
	} else {
		showError3('Please enter a valid zip.', form, ele);
	}
 }
 function autoValidUsername(form, ele) {
	if (isValidUsername(ele.value)) {
		showClear3(form, ele);
	} else {
		if (ele.value.length < 5 || ele.value.length > 15) {
			showError3('Username must be 5 to 15 alphanumeric characters.', form, ele);
		} else {
			showError3('Username can only contain a-z, A-Z, 0-9, and _', form, ele);
		}
	}
 }
 function autoValidPassword(form, ele) {
	if (isValidPassword(ele.value)) {
		showClear3(form, ele);
	} else {
		if (ele.value.length < 5 || ele.value.length > 15) {
			showError3('Username must be 5 to 15 alphanumeric characters.', form, ele);
		} else {
			showError3('Username can only contain a-z, A-Z, 0-9, and _', form, ele);
		}
	}
 }
 function autoCheckPasswords(form, p1, p2) {
	if (p1.value == p2.value) {
		showClear3(form, p2);
	} else {
		showError3('Passwords do not match.', form, p2);
	}
 }
 function autoValidName(form, ele) {
	if (ele.value.length > 0) {
		showClear3(form, ele);
	} else {
		showError3('Please enter your name.', form, ele);
	}
 }
function isValidEmail(email) {
	return validEmailFormatRegExp.test(email);
}
function isValidUsername(name) {
	return validUnamePassRegExp.test(name);
}
function isValidPassword(pass) {
	return validUnamePassRegExp.test(pass);
}
function isValidZip(zip) {
	return validZipRegExp.test(zip);
}
function isValidDate(form, name) {
	//jan, mar, may, july, aug, oct, dec
	var month = form.elements[name+'Month'].value;
	var day = form.elements[name+'Day'].value;
	var year = form.elements[name+'Year'].value;
//	alert(month+'-'+day+'-'+year);
	if (month == 0) return false;
	if (day == 0) return false;
	if (year == 0) return false;
	if (month == 2 && day > 28) {
		if (day == 29) {
			if (!(year%4 == 0 && year%100 != 0 || year%400 == 0)) {return false;}
		} else {
			return false;
		}
	}
	if ((month == 4 || month == 6 || month == 8 || month == 11) && day == 31) return false;
	return true;
}