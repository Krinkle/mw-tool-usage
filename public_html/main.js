$(function () {
	var $window = $(window);
	var $body   = $(document.body);

	$body.scrollspy({
	  target: '.usage-sidebar'
	});

	setTimeout(function () {
		var $sideBar = $('.usage-sidebar');

		$sideBar.affix({
			offset: {
				top: function () {
					var offsetTop      = $sideBar.offset().top;
					var sideBarMargin  = parseInt($sideBar.children(0).css('margin-top'), 10);
					var navOuterHeight = $('.usage-nav').height();

					return (this.top = offsetTop - navOuterHeight - sideBarMargin);
				},
				bottom: function () {
					return (this.bottom = $('.usage-footer').outerHeight(true));
				}
			}
		});
	}, 100);
});
