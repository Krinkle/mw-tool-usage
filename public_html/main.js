$(function () {
	$(document.body).scrollspy({
	  target: '.usage-sidebar'
	});

	var $sideBar = $('.usage-sidebar');
	$sideBar.affix({
		offset: {
			top: function () {
				var offsetTop      = $sideBar.offset().top;
				var sideBarMargin  = parseInt($sideBar.children(0).css('margin-top'), 10);

				return (this.top = offsetTop - sideBarMargin);
			},
			bottom: function () {
				return (this.bottom = $('.usage-footer').outerHeight(true));
			}
		}
	});
});
