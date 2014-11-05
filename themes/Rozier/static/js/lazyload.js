/**
 * Lazyload
 */
var Lazyload = function() {
    var _this = this;

    _this.$linksSelector = "a:not('[target=_blank]')";

    var onClickProxy = $.proxy(_this.onClick, _this);
    var onStateChangeProxy = $.proxy(_this.onPopState, _this);

    $('body').on('click', _this.$linksSelector, onClickProxy);

    $(window).on('popstate', function (event) {
        _this.onPopState(event);
    });
};

Lazyload.prototype.$linksSelector = null;
Lazyload.prototype.$textAreaHTMLeditor = null;
Lazyload.prototype.$HTMLeditor = null;
Lazyload.prototype.$HTMLeditorContent = null;
Lazyload.prototype.$HTMLeditorNav = null;
Lazyload.prototype.HTMLeditorNavToRemove = null;

Lazyload.prototype.onClick = function(event) {
    var _this = this;
    var $link = $(event.currentTarget);

    var href = $link.attr('href');
    if(typeof href !== "undefined" &&
        !$link.hasClass('rz-no-ajax-link') &&
        href !== "" &&
        href != "#" &&
        href.indexOf(Rozier.baseUrl) >= 0){

        console.log(href);

        history.pushState({}, null, $link.attr('href'));
        _this.onPopState(null);
        return false;
    }
};

Lazyload.prototype.onPopState = function(event) {
    var _this = this;

    var state;

    if(null !== event){
        state = event.originalEvent.state;
    }

    if(null !== state &&
        typeof state != "undefined"){

    } else {
        state = window.history.state;
    }

    //console.log(state);
    //console.log(document.location);

    if (null !== state) {
        _this.loadContent(state, window.location);
    }

};


Lazyload.prototype.loadContent = function(state, location) {
    var _this = this;

    $.ajax({
        url: location.href,
        type: 'get',
        dataType: 'html'
    })
    .done(function(data) {
        _this.applyContent(data);
    })
    .fail(function() {
        console.log("error");
        $.UIkit.notify({
            message : Rozier.messages.forbiddenPage,
            status  : 'danger',
            timeout : 3000,
            pos     : 'top-center'
        });
    });
};

Lazyload.prototype.applyContent = function(data) {
    var _this = this;

    var $container = $('#main-content-scrollable');
    var $old = $container.find('.content-global');

    var $tempData = $(data);

    $tempData.addClass('new-content-global');
    $container.append($tempData);
    $tempData = $container.find('.new-content-global');

    $old.fadeOut(100, function () {
        $old.remove();

        _this.generalBind();
        Rozier.centerVerticalObjects('ajax');
        $tempData.fadeIn(200, function () {

            $tempData.removeClass('new-content-global');
        });
    });
};


Lazyload.prototype.generalBind = function() {
    var _this = this;

    new DocumentWidget();
    new DocumentUploader();
    new ChildrenNodesField();
    new StackNodeTree();
    new SaveButtons();
    new TagAutocomplete();
    new NodeTypeFieldsPosition();
    new CustomFormFieldsPosition();


    // Init markdown-preview
    _this.$textAreaHTMLeditor = $('textarea[data-uk-htmleditor]');

    if(_this.$textAreaHTMLeditor.length){

        setTimeout(function(){
            for(var i = 0; i < _this.$textAreaHTMLeditor.length; i++) {

                $.UIkit.htmleditor($(_this.$textAreaHTMLeditor[i]), {markdown:true, mode:'tab'});
                _this.$HTMLeditor = $('.uk-htmleditor');
                _this.$HTMLeditorNav = $('.uk-htmleditor-navbar');
                _this.HTMLeditorNavInner = '<div class="uk-htmleditor-navbar bottom">'+_this.$HTMLeditorNav[0].innerHTML+'</div>';

                $(_this.$HTMLeditor[i]).append(_this.HTMLeditorNavInner);

            }

            $(".uk-htmleditor-preview").css("height", 250);
            $(".CodeMirror").css("height", 250);

            setTimeout(function(){
                _this.$HTMLeditorNavToRemove = $('.uk-htmleditor-navbar:not(.bottom)');
                _this.$HTMLeditorNavToRemove.remove();
                new MarkdownEditor();
            }, 0);

        }, 0);

    }

    // Init colorpicker
    if($('.colorpicker-input').length){
        $('.colorpicker-input').minicolors();
    }

    // Animate actions menu
    if($('.actions-menu').length){
        TweenLite.to('.actions-menu', 0.5, {right:0, delay:0.4, ease:Expo.easeOut});
    }

    Rozier.initNestables();
    Rozier.bindMainTrees();
    Rozier.nodeStatuses = new NodeStatuses();

    // Switch checkboxes
    $(".rz-boolean-checkbox").bootstrapSwitch();

    Rozier.getMessages();
};
