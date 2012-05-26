// JavaScript Document
function ImagePreload() {
	if (typeof(arguments) != 'undefined') {
		for (i=0; i<arguments.length; i++ ) {
			if (typeof(arguments[i]) == "object") {
				for (k=0; k<arguments[i].length; k++) {
					var oImage = new Image;
					oImage.src = arguments[i][k];
				}
			}
 
			if (typeof(arguments[i]) == "string") {
				var oImage = new Image;
				oImage.src = arguments[i];
			}
		}
	}
}
ImagePreload(
 '/images/star_full_h1.gif',
 '/images/star_full_h1_hover.gif',
 '/images/star_full_h2.gif',
 '/images/star_full_h2_hover.gif',
 '/images/star_empty_h1.gif',
 '/images/star_empty_h1_hover.gif',
 '/images/star_empty_h2.gif',
 '/images/star_empty_h2_hover.gif');
function setHover(img, hover) {
	var num = img.id.substr(0,1)%2 + 1;
	var set = img.alt.length == 1;
	img.src = '/images/star_'+(set?'full':'empty')+'_h'+num+(hover==1?'_hover.gif':'.gif');
}
function selectStar(img) {
	var divid = img.parentNode.id;
	var inputid = divid+'v';
	var cValue = parseInt(document.ratings.elements[inputid].value);
	var nValue = parseInt(img.id)+1;
	var start = 0;
	var end = 0;
//	alert(divid+ ' ' + document.ratings.elements['inputid'].type);
//	alert(img.id + ' ' + document.ratings.elements['inputid'].value);
	if (nValue == cValue) {
		return;
	} else if (cValue > nValue) {
		start = nValue;
		end = cValue;
	} else {
		start = cValue;
		end = nValue;
	}
	var imgs = document.getElementById(divid).getElementsByTagName('img');
	var num = 0;
	var set = false;
	var img = null;
//	alert('S'+start + ' E' + end + ' ' + imgs.length);
	for (; start < end; start++) {
		img = imgs[start];
		num = img.id%2 + 1;
		set = img.alt.length == 1;
		if (set) {
			img.alt = '';
			img.src = '/images/star_empty_h'+num+'.gif';
		} else {
			img.alt = 'Y';
			img.src = '/images/star_full_h'+num+'.gif';
		}
	}
	document.ratings.elements[divid+'v'].value = nValue;
}
