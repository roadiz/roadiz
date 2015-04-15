var AutoUpdate = function () {
    var _this = this;

    _this.$section = $('#auto-update');

    if(_this.$section.length) {
        _this.init();
    }
};

AutoUpdate.prototype.init = function() {
    var _this = this;

    if(typeof(nextStep) !== "undefined" &&
       typeof(nextStepDescription) !== "undefined" &&
       nextStep !== null &&
       nextStepDescription !== null){

        _this.$button = _this.$section.find("#update-done-button");
        _this.$buttonIcon = _this.$button.find("i");
        _this.$buttonText = _this.$button.find(".text");
        _this.$progressBar = $("#update-progress-bar");
        _this.$progressBarInner = _this.$progressBar.find(".uk-progress-bar");

        // disable back button
        _this.$button.on("click", function (event) {
            event.preventDefault();
            return false;
        });

        _this.loadStep(5, nextStep, nextStepDescription);
    }
};

AutoUpdate.prototype.loadStep = function(progress, url, description) {
    var _this = this;

    _this.displayProgress(progress, description);

    $.ajax({
        url: url,
        type: 'get',
        dataType: 'json',
        //data: {param1: 'value1'},
    })
    .done(function(data) {
        console.log(data);

        if(typeof(data.progress) !== "undefined") {
            if(typeof(data.complete) !== "undefined" &&
                data.complete === true){
                _this.finish(data.progress, data.nextStepDescription);
            } else {
                _this.loadStep(data.progress, data.nextStepRoute, data.nextStepDescription);
            }
        }
    })
    .fail(function(data) {
        console.log(data);

        if(typeof(data.responseJSON) !== "undefined") {
            _this.$progressBarInner.html(data.responseJSON.message);
        }

        _this.buttonFail();

        _this.$progressBar.removeClass("uk-progress-striped uk-active");
        _this.$progressBar.addClass("uk-progress-danger");
    });

};

AutoUpdate.prototype.displayProgress = function(progress, description) {
    var _this = this;

    _this.$progressBarInner.width(progress+'%');
    _this.$progressBarInner.html(description);
};

AutoUpdate.prototype.finish = function(progress, description) {
    var _this = this;

    _this.displayProgress(progress, description);

    _this.buttonOK();

    _this.$progressBar.removeClass("uk-progress-striped uk-active");
    _this.$progressBar.addClass("uk-progress-success");
};

AutoUpdate.prototype.buttonOK = function() {
    var _this = this;

    _this.$buttonIcon.removeClass('uk-icon-spin');
    _this.$buttonIcon.removeClass('uk-icon-refresh');
    _this.$buttonIcon.addClass('uk-icon-check');
    _this.$button.addClass('uk-button-primary');
    _this.$button.off('click');
    var doneText = _this.$button.attr("data-done-text");
    _this.$buttonText.html(doneText);

};

AutoUpdate.prototype.buttonFail = function() {
    var _this = this;

    _this.$buttonIcon.removeClass('uk-icon-spin');
    _this.$buttonIcon.removeClass('uk-icon-refresh');
    _this.$buttonIcon.addClass('uk-icon-warning');
    _this.$button.addClass('uk-button-danger');
    _this.$button.off('click');
    var doneText = _this.$button.attr("data-done-text");
    _this.$buttonText.html(doneText);

};
