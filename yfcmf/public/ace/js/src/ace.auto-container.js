/**
 <b>Auto Container</b> 自动Container宽度
*/
(function($ , undefined) {

 $(window).on('resize.auto_container', function() {
	var enable = $(window).width() > 1140;
	try {
		ace.settings.main_container_fixed(enable, false, false);
	} catch(e) {
		if(enable) $('.main-container,.navbar-container').addClass('container');
		else $('.main-container,.navbar-container').removeClass('container');
		$(document).trigger('settings.ace', ['main_container_fixed' , enable]);
	}
 }).triggerHandler('resize.auto_container');

})(window.jQuery);