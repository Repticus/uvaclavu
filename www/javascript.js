$(function () {
	$("#event").click(function (event) {
		$("#eventScroll").slideToggle(1000);
		$("#leaflet").slideToggle(1000);
	});
	$("#leaflet").click(function (event) {
		$(this).slideToggle(1000);
		$("#eventScroll").slideToggle(1000);
	});
});
