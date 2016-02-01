$(function () {
	var delayInt = 3000;
	var toggleInt = 2000;
	setLight();
	function setLight() {
		$("#img1").delay(delayInt).fadeToggle(toggleInt, function () {
			$("#img2").delay(delayInt).fadeToggle(toggleInt, function () {
				$("#img3").delay(delayInt).fadeToggle(toggleInt, function () {
					$("#img4").delay(delayInt).fadeToggle(toggleInt, function () {
						$("#img5").delay(delayInt).fadeToggle(toggleInt, function () {
							$("#img6").delay(delayInt).fadeToggle(toggleInt, function () {
								$("#img1").delay(delayInt).fadeToggle(toggleInt, function () {
									$("#img2").delay(delayInt).fadeToggle(toggleInt, function () {
										$("#head img").fadeIn(0);
										setLight();
									});
								});
							});
						});
					});
				});
			});
		});
	}
	$("#event").click(function (event) {
		$("#eventScroll").slideToggle(1000);
		$("#leaflet").slideToggle(1000);
	});
	$("#leaflet").click(function (event) {
		$(this).slideToggle(1000);
		$("#eventScroll").slideToggle(1000);
	});
});
