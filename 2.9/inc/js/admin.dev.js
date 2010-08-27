/**
 * Handle: avhamazonmetabox Version: 2.0 Deps: jquery Enqueue: true
 */


jQuery(document).ready(function($) {

	// Tabs
	$('#printOptions a').click(function(){
		var t = $(this).attr('href');
		$(this).parent().addClass('avhamazon-tabs-selected').siblings('li').removeClass('avhamazon-tabs-selected');
		$(this).parent().parent().siblings('.avhamazon-tabs-panel').hide();
		$(t).show();
	});
});