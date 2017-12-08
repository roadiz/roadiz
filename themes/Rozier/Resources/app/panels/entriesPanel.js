var EntriesPanel = function () {
    var _this = this;

    _this.$adminMenuNav = $('#admin-menu-nav');

    _this.replaceSubNavs();
};

EntriesPanel.prototype.replaceSubNavs = function() {
    var _this = this;

    _this.$adminMenuNav.find('.uk-nav-sub').each(function (index, element) {

        var subMenu = $(element);

        subMenu.attr('style','display:block;');
        var top = subMenu.offset().top;
        var height = subMenu.height();
        subMenu.removeAttr('style');

        if((top + height + 20) > $(window).height()){
            subMenu.parent().addClass('reversed-nav');
        }
    });
};