/**
 * Lazyload
 */
var Lazyload = function() {
    var _this = this;

    _this.$linksSelector = null;
    _this.$textareasMarkdown = null;
    _this.documentsList = null;
    _this.mainColor = null;
    _this.$canvasLoaderContainer = null;
    _this.currentRequest = null;

    var onStateChangeProxy = $.proxy(_this.onPopState, _this);

    _this.parseLinks();

    // this hack resolves safari triggering popstate
    // at initial load.
    window.addEventListener('load', function() {
        setTimeout(function() {
            $(window).off('popstate', onStateChangeProxy);
            $(window).on('popstate', onStateChangeProxy);
        }, 0);
    });

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
        (href.indexOf(Rozier.baseUrl) >= 0 || href.charAt(0) == '/' || href.charAt(0) == '?')) {
        event.preventDefault();

        if (_this.clickTimeout) {
            clearTimeout(_this.clickTimeout);
        }
        _this.clickTimeout = window.setTimeout(function () {
            history.pushState({}, null, $link.attr('href'));
            _this.onPopState(null);
        }, 50);

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
    var state = null;

    if(null !== event){
        state = event.originalEvent.state;
    }

    if(typeof state === "undefined" || null === state){
        state = window.history.state;
    }

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

    /*
     * Delay loading if user is click like devil
     */
    if (_this.currentTimeout) {
        clearTimeout(_this.currentTimeout);
    }

    _this.currentTimeout = window.setTimeout(function () {
        _this.currentRequest = $.ajax({
            url: location.href,
            type: 'get',
            dataType: 'html',
            cache: false,
            data: state.headerData
        })
            .done(function(data) {
                _this.applyContent(data);
                _this.canvasLoader.hide();
            })
            .fail(function(data) {
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
    }, 100);
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
        $tempData.fadeIn(200, function () {

            $tempData.removeClass('new-content-global');
        });
    });
};

Lazyload.prototype.bindAjaxLink = function() {
    var _this = this;
    _this.parseLinks();

    var onClickProxy = $.proxy(_this.onClick, _this);
    _this.$linksSelector.off('click', onClickProxy);
    _this.$linksSelector.on('click', onClickProxy);
};


/**
 * General bind on page load
 * @return {[type]} [description]
 */
Lazyload.prototype.generalBind = function() {
    var _this = this;

    _this.bindAjaxLink();

    new DocumentsBulk();
    new AutoUpdate();
    new NodesBulk();
    new TagsBulk();
    new DocumentWidget();
    new NodeWidget();
    new CustomFormWidget();
    new InputLengthWatcher();
    new DocumentUploader(Rozier.messages.dropzone);
    _this.childrenNodesFields = new ChildrenNodesField();
    new GeotagField();
    new MultiGeotagField();
    _this.stackNodeTrees = new StackNodeTree();
    if(isMobile.any() === null) new SaveButtons();
    new TagAutocomplete();
    new FolderAutocomplete();
    new NodeTypeFieldsPosition();
    new CustomFormFieldsPosition();
    _this.nodeTreeContextActions = new NodeTreeContextActions();

    //_this.documentsList = new DocumentsList();
    _this.settingsSaveButtons = new SettingsSaveButtons();
    _this.nodeTypeFieldEdit = new NodeTypeFieldEdit();
    _this.nodeEditSource = new NodeEditSource();
    _this.nodeTree = new NodeTree();
    _this.customFormFieldEdit = new CustomFormFieldEdit();

    /*
     * Codemirror
     */
    _this.initMarkdownEditors();
    _this.initJsonEditors();
    _this.initCssEditors();
    _this.initYamlEditors();

    _this.initFilterBars();

    // Init colorpicker
    if($('.colorpicker-input').length){
        $('.colorpicker-input').minicolors();
    }

    // Animate actions menu
    if($('.actions-menu').length && isMobile.any() === null) {
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

    if(typeof Rozier.importRoutes != "undefined" &&
        Rozier.importRoutes !== null){
        Rozier.import = new Import(Rozier.importRoutes);
        Rozier.importRoutes = null;
    }
};

Lazyload.prototype.initMarkdownEditors = function() {
    var _this = this;

    // Init markdown-preview
    _this.$textareasMarkdown = $('textarea[data-rz-markdowneditor]');
    var editorCount = _this.$textareasMarkdown.length;

    if(editorCount){
        setTimeout(function(){
            for(var i = 0; i < editorCount; i++) {
                new MarkdownEditor(_this.$textareasMarkdown.eq(i), i);
            }
        }, 100);
    }
};

Lazyload.prototype.initJsonEditors = function() {
    var _this = this;

    // Init markdown-preview
    _this.$textareasJson = $('textarea[data-rz-jsoneditor]');
    var editorCount = _this.$textareasJson.length;

    if(editorCount){
        setTimeout(function(){
            for(var i = 0; i < editorCount; i++) {
                new JsonEditor(_this.$textareasJson.eq(i), i);
            }
        }, 100);
    }
};

Lazyload.prototype.initCssEditors = function() {
    var _this = this;

    // Init markdown-preview
    _this.$textareasCss = $('textarea[data-rz-csseditor]');
    var editorCount = _this.$textareasCss.length;

    if(editorCount){
        setTimeout(function(){
            for(var i = 0; i < editorCount; i++) {
                new CssEditor(_this.$textareasCss.eq(i), i);
            }
        }, 100);
    }
};

Lazyload.prototype.initYamlEditors = function() {
    var _this = this;

    // Init markdown-preview
    _this.$textareasYaml = $('textarea[data-rz-yamleditor]');
    var editorCount = _this.$textareasYaml.length;

    if(editorCount){
        setTimeout(function(){
            for(var i = 0; i < editorCount; i++) {
                new YamlEditor(_this.$textareasYaml.eq(i), i);
            }
        }, 100);
    }
};

Lazyload.prototype.initFilterBars = function() {
    var _this = this;

    var $selectItemPerPage = $('select.item-per-page');

    if($selectItemPerPage.length){
        $selectItemPerPage.off('change');
        $selectItemPerPage.on('change', function (event) {
            var $form = $(event.currentTarget).parents('form').submit();
        });
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
