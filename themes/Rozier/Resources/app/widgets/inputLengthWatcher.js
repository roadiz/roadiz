
InputLengthWatcher = function () {
    var _this = this;

    _this.$maxLengthed = $('input[data-max-length]');
    _this.$minLengthed = $('input[data-min-length]');

    if (_this.$maxLengthed.length) {
        var proxyMax = $.proxy(_this.onMaxKeyUp, _this);
        _this.$maxLengthed.off('keyup', proxyMax);
        _this.$maxLengthed.on('keyup', proxyMax);
    }
    if (_this.$minLengthed.length) {
        var proxyMin = $.proxy(_this.onMinKeyUp, _this);
        _this.$minLengthed.off('keyup', proxyMin);
        _this.$minLengthed.on('keyup', proxyMin);
    }
};

InputLengthWatcher.prototype.onMaxKeyUp = function(event) {
    var _this = this;

    var input = $(event.currentTarget);
    var maxLength = Math.round(event.currentTarget.getAttribute('data-max-length'));
    var currentLength = event.currentTarget.value.length;

    if (currentLength > maxLength) {
        input.addClass('uk-form-danger');
    } else {
        input.removeClass('uk-form-danger');
    }
};

InputLengthWatcher.prototype.onMinKeyUp = function(event) {
    var _this = this;

    var input = $(event.currentTarget);
    var maxLength = Math.round(event.currentTarget.getAttribute('data-min-length'));
    var currentLength = event.currentTarget.value.length;

    if (currentLength <= maxLength) {
        input.addClass('uk-form-danger');
    } else {
        input.removeClass('uk-form-danger');
    }
};
