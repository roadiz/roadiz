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

Lazyload.prototype.onClick = function(event) {
    var _this = this;
    var $link = $(event.currentTarget);

    var href = $link.attr('href');
    if(typeof href != "undefined" &&
        !$link.hasClass('rz-no-ajax-link') &&
        href !== "" &&
        href != "#" &&
        href.indexOf(Rozier.baseUrl) >= 0){

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

    console.log(state);
    console.log(document.location);

    _this.loadContent(state, window.location);
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
    })
    .always(function() {
        console.log("complete");
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

    $old.fadeOut(300, function () {
        $old.remove();

        _this.generalBind();

        $tempData.fadeIn(300, function () {

            Rozier.centerVerticalObjects();
            $tempData.removeClass('new-content-global');
        });
    });
};


Lazyload.prototype.generalBind = function() {
    var _this = this;

    new DocumentWidget();
    new ChildrenNodesField();
    new SaveButtons();
    new MarkdownEditor();


    // Init markdown-preview
    if($('textarea[data-uk-htmleditor]').length){

        setTimeout(function(){
            $.UIkit.htmleditor($('textarea[data-uk-htmleditor]'), {markdown:true, mode:'tab'});
            $(".uk-htmleditor-preview").css("height", 250);
            $(".CodeMirror").css("height", 250);
            $(".uk-htmleditor-content").after($(".uk-htmleditor-navbar"));
        }, 0);
    }

    // Init document uploader
    if($('#upload-dropzone-document').length){

        var dropZone = new Dropzone("#upload-dropzone-document", Dropzone.options.uploadDropzoneDocument);
        
    }
    Rozier.initNestables();
    Rozier.bindMainTrees();
    Rozier.nodeStatuses = new NodeStatuses();

    // Switch checkboxes
    $(".rz-boolean-checkbox").bootstrapSwitch();
};