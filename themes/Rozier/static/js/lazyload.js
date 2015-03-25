/**
 * Lazyload
 */
var Lazyload = function() {
    var _this = this;

    _this.$linksSelector = null;
    _this.$textAreaHTMLeditor = null;
    _this.$HTMLeditor = null;
    _this.htmlEditor = [];
    _this.$HTMLeditorContent = null;
    _this.$HTMLeditorNav = null;
    _this.HTMLeditorNavToRemove = null;
    _this.documentsList = null;
    _this.mainColor = null;
    _this.$canvasLoaderContainer = null;

    var onStateChangeProxy = $.proxy(_this.onPopState, _this);

    _this.parseLinks();

    $(window).off('popstate', onStateChangeProxy);
    $(window).on('popstate', onStateChangeProxy);

    _this.$canvasLoaderContainer = $('#canvasloader-container');
    _this.mainColor = isset(Rozier.mainColor) ? Rozier.mainColor : '#ffffff';
    _this.initLoader();

    /*
     * Start history with first hard loaded page
     */
    history.pushState({}, null, window.location.href);
};

/**
 * Init loader
 * @return {[type]} [description]
 */
Lazyload.prototype.initLoader = function(){
    var _this = this;

    _this.canvasLoader = new CanvasLoader('canvasloader-container');
    _this.canvasLoader.setColor(_this.mainColor);
    _this.canvasLoader.setShape('square');
    _this.canvasLoader.setDensity(90);
    _this.canvasLoader.setRange(0.8);
    _this.canvasLoader.setSpeed(4);
    _this.canvasLoader.setFPS(30);
};

Lazyload.prototype.parseLinks = function() {
    var _this = this;

    _this.$linksSelector = $("a:not('[target=_blank]')").not('.rz-no-ajax-link');
};

/**
 * Bind links to load pages
 * @param  {[type]} event [description]
 * @return {[type]}       [description]
 */
Lazyload.prototype.onClick = function(event) {
    var _this = this;

    var $link = $(event.currentTarget),
        href = $link.attr('href');

    if(typeof href !== "undefined" &&
        !$link.hasClass('rz-no-ajax-link') &&
        href !== "" &&
        href != "#" &&
        (href.indexOf(Rozier.baseUrl) >= 0 || href.charAt(0) == '?')) {

        event.preventDefault();

        history.pushState({}, null, $link.attr('href'));
        _this.onPopState(null);
        return false;
    }
};


/**
 * On pop state
 * @param  {[type]} event [description]
 * @return {[type]}       [description]
 */
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
        _this.canvasLoader.show();
        _this.loadContent(state, window.location);
    }
};


/**
 * Load content (ajax)
 * @param  {[type]} state    [description]
 * @param  {[type]} location [description]
 * @return {[type]}          [description]
 */
Lazyload.prototype.loadContent = function(state, location) {
    var _this = this;

    $.ajax({
        url: location.href,
        type: 'get',
        dataType: 'html',
        data: state.headerData
    })
    .done(function(data) {
        _this.applyContent(data);
        _this.canvasLoader.hide();
    })
    .fail(function(data) {
        console.log("error");
        console.log(data);

        if (typeof data.responseText !== "undefined") {
            try {
                var exception = JSON.parse(data.responseText);
                UIkit.notify({
                    message : exception.message,
                    status  : 'danger',
                    timeout : 3000,
                    pos     : 'top-center'
                });
            } catch (e) {
                // No valid JsonResponse, need to refresh page
                window.location.href = location.href;
            }
        } else {
            UIkit.notify({
                message : Rozier.messages.forbiddenPage,
                status  : 'danger',
                timeout : 3000,
                pos     : 'top-center'
            });
        }

        _this.canvasLoader.hide();
    });
};


/**
 * Apply content to main content
 * @param  {[type]} data [description]
 * @return {[type]}      [description]
 */
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
        if(isMobile.any() === null) Rozier.centerVerticalObjects('ajax');
        $tempData.fadeIn(200, function () {

            $tempData.removeClass('new-content-global');
        });
    });
};


/**
 * General bind on page load
 * @return {[type]} [description]
 */
Lazyload.prototype.generalBind = function() {
    var _this = this;

    _this.parseLinks();

    var onClickProxy = $.proxy(_this.onClick, _this);
    _this.$linksSelector.off('click', onClickProxy);
    _this.$linksSelector.on('click', onClickProxy);

    new DocumentsBulk();
    new NodesBulk();
    new DocumentWidget();
    new NodeWidget();
    new CustomFormWidget();
    new DocumentUploader(Rozier.messages.dropzone);
    new ChildrenNodesField();
    new GeotagField();
    new StackNodeTree();
    if(isMobile.any() === null) new SaveButtons();
    new TagAutocomplete();
    new FolderAutocomplete();
    new NodeTypeFieldsPosition();
    new CustomFormFieldsPosition();

    _this.documentsList = new DocumentsList();
    _this.settingsSaveButtons = new SettingsSaveButtons();
    _this.nodeTypeFieldEdit = new NodeTypeFieldEdit();
    _this.nodeEditSource = new NodeEditSource();
    _this.nodeTree = new NodeTree();
    _this.customFormFieldEdit = new CustomFormFieldEdit();

    // Init markdown-preview
    _this.initMarkdownEditors();

    // Init colorpicker
    if($('.colorpicker-input').length){
        $('.colorpicker-input').minicolors();
    }

    // Animate actions menu
    if($('.actions-menu').length && isMobile.any() === null){
        TweenLite.to('.actions-menu', 0.5, {right:0, delay:0.4, ease:Expo.easeOut});
    }

    Rozier.initNestables();
    Rozier.bindMainTrees();
    Rozier.nodeStatuses = new NodeStatuses();

    // Switch checkboxes
    $(".rz-boolean-checkbox").bootstrapSwitch({
        size: 'small'
    });

    Rozier.getMessages();
};

Lazyload.prototype.initMarkdownEditors = function() {
    var _this = this;

    // Init markdown-preview
    _this.$textAreaHTMLeditor = $('textarea[data-uk-htmleditor], textarea[data-uk-rz-htmleditor]').not('[data-uk-check-display]');

    if(_this.$textAreaHTMLeditor.length){

        setTimeout(function(){
            for(var i = 0; i < _this.$textAreaHTMLeditor.length; i++) {

                _this.htmlEditor[i] = UIkit.htmleditor(
                    $(_this.$textAreaHTMLeditor[i]),
                    {
                        markdown:true,
                        mode:'tab',
                        labels : Rozier.messages.htmleditor
                    }
                );
                _this.$HTMLeditor = $('.uk-htmleditor');
                _this.$HTMLeditorNav = $('.uk-htmleditor-navbar');
                _this.HTMLeditorNavInner = '<div class="uk-htmleditor-navbar bottom">'+_this.$HTMLeditorNav[0].innerHTML+'</div>';

                $(_this.$HTMLeditor[i]).append(_this.HTMLeditorNavInner);

                _this.htmlEditor[i].redraw();

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
};

/**
 * Resize
 * @return {[type]} [description]
 */
Lazyload.prototype.resize = function(){
    var _this = this;

    _this.$canvasLoaderContainer[0].style.left = Rozier.mainContentScrollableOffsetLeft + (Rozier.mainContentScrollableWidth/2) + 'px';
};
