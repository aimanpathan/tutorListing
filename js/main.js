/* --------------------------------------
		CUSTOM FUNCTION WRITE HERE
-------------------------------------- */
jQuery(document).ready(function() {
	"use strict";
	/* -------------------------------------
			COLLAPSE MENU SMALL DEVICES
	-------------------------------------- */
	function collapseMenu(){
		$('.tg-navigation ul li.tg-hasdropdown').prepend('<span class="tg-dropdowarrow"><i class="fa fa-angle-down"></i></span>');
		$('.tg-navigation ul li.tg-hasdropdown span').on('click', function() {
			$(this).next().next().slideToggle(300);
		});
	}
	collapseMenu();
	/* --------------------------------------
			NEWS TICKERS
	-------------------------------------- */
	jQuery("#tg-newsticker").simplyScroll({
		auto : true,
		pauseOnTouch : true,
	});
	/* --------------------------------------
			COUNTER
	-------------------------------------- */
	try {
		jQuery('.tg-counter').appear(function () {
			jQuery('.tg-counter > h2 > span').countTo()
		});
	} catch (err) {}
	/* --------------------------------------
			TUITION CENTER SLIDER
	-------------------------------------- */
	jQuery("#tg-tuitioncentersslider").owlCarousel({
		autoPlay : false,
		slideSpeed : 300,
		navigation : true,
		pagination : false,
		navigationText: [
			"<span class='tg-btnroundprev'><i class='fa fa-angle-left'></i></span>",
			"<span class='tg-btnroundnext'><i class='fa fa-angle-right'></i></span>"
		],
		itemsCustom : [
			[0, 1],
			[481, 2],
			[991, 2],
			[1200, 4],
		],
	});
	/* --------------------------------------
			BOOTSTRAP TOOLTIP
	-------------------------------------- */
	jQuery('.tg-tooltip').tooltip();
	/* -------------------------------------
			HOME SLIDER VERSION THREE
	-------------------------------------- */
	function reviewSlider(){
		var sync1 = jQuery("#tg-studentreviewsslider");
		var sync2 = jQuery("#tg-reviewerdpslider");
		sync1.owlCarousel({
			singleItem : true,
			slideSpeed : 1000,
			pagination:false,
			afterAction : syncPosition,
			responsiveRefreshRate : 200,
		});
		sync2.owlCarousel({
			items					: 6,
			itemsDesktop			: [1199,6],
			itemsTablet				: [768,4],
			itemsMobile				: [479,2],
			pagination:false,
			responsiveRefreshRate : 100,
			afterInit : function(el){
				el.find(".owl-item").eq(0).addClass("tg-active");
			}
		});
		function syncPosition(el){
			var current = this.currentItem;
			jQuery("#tg-reviewerdpslider")
			.find(".owl-item")
			.removeClass("tg-active")
			.eq(current)
			.addClass("tg-active");
			if(jQuery("#tg-reviewerdpslider").data("owlCarousel") !== undefined){
				center(current);
			}
		}
		jQuery("#tg-reviewerdpslider").on("click", ".owl-item", function(e){
			e.preventDefault();
			var number = jQuery(this).data("owlItem");
			sync1.trigger("owl.goTo",number);
		});
		function center(number){
			var sync2visible = sync2.data("owlCarousel").owl.visibleItems;
			var num = number;
			var found = false;
			for(var i in sync2visible){
				if(num === sync2visible[i]){
					var found = true;
				}
			}
			if(found===false){
				if(num>sync2visible[sync2visible.length-1]){
				sync2.trigger("owl.goTo", num - sync2visible.length+2);
				}else{
					if(num - 1 === -1){
						num = 0;
					}
					sync2.trigger("owl.goTo", num);
				}
			} else if(num === sync2visible[sync2visible.length-1]){
				sync2.trigger("owl.goTo", sync2visible[1]);
			} else if(num === sync2visible[0]){
				sync2.trigger("owl.goTo", num-1);
			}
		}
	}
	reviewSlider();
	/* -------------------------------------
			BRANDS SLIDER
	-------------------------------------- */
	jQuery("#tg-brandsslider").owlCarousel({
		slideSpeed : 300,
		singleItem: true,
		pagination: true,
	});
	/* -------------------------------------
			CHECKBOX SCROLL
	-------------------------------------- */
	jQuery(".tg-filterscrollbar").mCustomScrollbar({
		axis:"y",
	});
	/* -------------------------------------
			FEE RANGE SLIDER
	-------------------------------------- */
	function feeRangeslider(){
		jQuery("#tg-feerangeslider").slider({
			range: true,
			min: 0,
			max: 500,
			values: [ 75, 300 ],
			slide: function( event, ui ) {
				jQuery( "#tg-feerangeamount" ).val( "$" + ui.values[ 0 ] + " - $" + ui.values[ 1 ] );
			}
		});
		jQuery( "#tg-feerangeamount" ).val( "$" + jQuery( "#tg-feerangeslider" ).slider( "values", 0 ) + " - $" + jQuery( "#tg-feerangeslider" ).slider( "values", 1 ) );
	}
	feeRangeslider();
	/* -------------------------------------
			AGE RANGE SLIDER
	-------------------------------------- */
	function ageRangeslider(){
		jQuery("#tg-agerangeslider").slider({
			range: true,
			min: 0,
			max: 100,
			values: [ 25, 50 ],
			slide: function( event, ui ) {
				jQuery( "#tg-agerangeamount" ).val( "$" + ui.values[ 0 ] + " - " + ui.values[ 1 ] );
			}
		});
		jQuery( "#tg-agerangeamount" ).val( jQuery( "#tg-agerangeslider" ).slider( "values", 0 ) + " - " + jQuery( "#tg-agerangeslider" ).slider( "values", 1 ) );
	}
	ageRangeslider();
	/* -------------------------------------
			Google Map
	-------------------------------------- */
	jQuery("#tg-location-map, #tg-addressmap").gmap3({
		marker: {
			address: "1600 Elizabeth St, Melbourne, Victoria, Australia",
			options: {
				title: "Robert Frost Elementary School",
				icon: "images/marker.png",
			}
		},
		map: {
			options: {
				zoom: 16,
				scrollwheel: false,
				disableDoubleClickZoom: true,
			}
		}
	});
	/* -------------------------------------
			PROGRESS BAR
	-------------------------------------- */
	try {
		jQuery('#tg-userskill').appear(function () {
			jQuery('.tg-skillholder').each(function () {
				jQuery(this).find('.tg-skillbar').animate({
					width: jQuery(this).attr('data-percent')
				}, 2500);
			});
		});
	} catch (err) {}
	/* --------------------------------------
			COUNTER
	-------------------------------------- */
	function expireyCounter(){
		var note = jQuery('#note'),
			ts = new Date(2017, 0, 1),
			newYear = true;
		if((new Date()) > ts){
			ts = (new Date()).getTime() + 10*24*60*60*1000;
			newYear = false;
		}
		jQuery('#tg-countdown').countdown({
			timestamp	: ts,
			callback	: function(days, hours, minutes, seconds){
				var message = "";
				message += days + " day" + ( days==1 ? '':'s' ) + ", ";
				message += hours + " hour" + ( hours==1 ? '':'s' ) + ", ";
				message += minutes + " minute" + ( minutes==1 ? '':'s' ) + " and ";
				message += seconds + " second" + ( seconds==1 ? '':'s' ) + " <br />";
				if(newYear){
					message += "left until the new year!";
				}
				else {
					message += "left to 10 days from now!";
				}
				note.html(message);
			}
		});
	}
	expireyCounter();
	/* -------------------------------------
			REMOVE LINK
	-------------------------------------- */
	jQuery(".tg-url").addClear({
		symbolClass: "fa fa-close"
	});
	/* -------------------------------------
			DATE PICKER
	-------------------------------------- */
	jQuery(".tg-datepicker").datepicker();
	/* -------------------------------------
			TIME PICKER
	-------------------------------------- */
	jQuery('#tg-timepicker1, #tg-timepicker2').timepicker({
		minuteStep: 15,
		defaultTime: false
	});
	/* -------------------------------------
			MULTI SELECT INPUT
	-------------------------------------- */
	jQuery(function() {
		jQuery('.chosen-select').chosen();
		jQuery('.chosen-select-deselect').chosen({ allow_single_deselect: true });
	});
	/* -------------------------------------
			DASHBOARD NAVIGATION
	-------------------------------------- */
	jQuery(".tg-btndashboard").on('click', function() {
		jQuery("#tg-dashboardnav").slideToggle("slow");
		jQuery(".tg-btndashboard").toggleClass("tg-active");
	});
	/* -------------------------------------
			ACADEMICS SETTING
	-------------------------------------- */
	jQuery('.tg-settingcontentarea').each(function(){
		if(jQuery(this).attr('id') == 'tg-academics') {
			jQuery(this).addClass('tg-active');
			jQuery(this).slideDown(400);
		}
	});
	jQuery('.tg-dashboarddepartment a').on( "click", function(e) {
		e.preventDefault();
		var id = jQuery(this).data('related'); 
		jQuery('.tg-dashboarddepartment').removeClass('tg-active');
		jQuery(this).parent().addClass('tg-active');
		jQuery(".tg-settingcontentarea").each(function(){
			jQuery(this).slideUp(400);
			if(jQuery(this).attr('id') == id) {
				jQuery(this).slideDown(400);
			}
		});
	});
});