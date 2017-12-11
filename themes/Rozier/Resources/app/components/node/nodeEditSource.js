import $ from 'jquery'
import {
    toType
} from '../../plugins'

/**
 * NODE EDIT SOURCE
 */
export default function NodeEditSource () {
    var _this = this

    // Selectors
    _this.$content = $('.content-node-edit-source').eq(0)
    _this.$form = $('#edit-node-source-form')
    _this.$formRow = null
    _this.$dropdown = null
    _this.$input = null

    // Methods
    if (_this.$content.length) {
        _this.$formRow = _this.$content.find('.uk-form-row')
        _this.wrapInTabs()
        _this.init()
        _this.initEvents()
    }
};

NodeEditSource.prototype.wrapInTabs = function () {
    var _this = this
    var fieldGroups = {}
    var $fields = _this.$content.find('.uk-form-row[data-field-group]')
    var fieldsLength = $fields.length
    var fieldsGroupsLength = 0

    if (fieldsLength > 1) {
        for (var i = 0; i < fieldsLength; i++) {
            var groupName = $fields[i].getAttribute('data-field-group')

            if (typeof fieldGroups[groupName] === 'undefined') {
                fieldGroups[groupName] = []
                fieldsGroupsLength++
            }
            fieldGroups[groupName].push($fields[i])
        }

        if (fieldsGroupsLength > 1) {
            _this.$form.append('<div id="node-source-form-switcher-nav-cont"><ul id="node-source-form-switcher-nav" class="uk-subnav uk-subnav-pill" data-uk-switcher="{connect:\'#node-source-form-switcher\', swiping:false}"></ul></div><ul id="node-source-form-switcher" class="uk-switcher"></ul>')
            var $formSwitcher = _this.$form.find('.uk-switcher')
            var $formSwitcherNav = _this.$form.find('#node-source-form-switcher-nav')

            /*
             * Sort tab name and put default in first
             */
            var keysSorted = Object.keys(fieldGroups).sort(function (a, b) {
                if (a === 'default') { return -1 }
                if (b === 'default') { return 1 }
                return +(a.toLowerCase() > b.toLowerCase()) || +(a.toLowerCase() === b.toLowerCase()) - 1
            })

            for (var keyIndex in keysSorted) {
                var groupName2 = keysSorted[keyIndex]
                var groupName2Safe = groupName2.toLowerCase().replace(/ /g, '-').replace(/[^\w-]+/g, '')

                var groupId = 'group-' + groupName2Safe
                $formSwitcher.append('<li class="field-group" id="' + groupId + '"></li>')

                if (groupName2 === 'default') {
                    $formSwitcherNav.append('<li class="switcher-nav-item"><a href="#"><i class="uk-icon-star"></i></a></li>')
                } else {
                    $formSwitcherNav.append('<li class="switcher-nav-item"><a href="#">' + groupName2 + '</a></li>')
                }
                var $group = $formSwitcher.find('#' + groupId)

                for (var index = 0; index < fieldGroups[groupName2].length; index++) {
                    $group.append($(fieldGroups[groupName2][index]))
                }
            }

            $formSwitcherNav.on('show.uk.switcher', function (event, area) {
                window.Rozier.$window.trigger('resize')
            })
        }
    }
}
/**
 * Init
 * @return {[type]} [description]
 */
NodeEditSource.prototype.init = function () {
    var _this = this

    // Inputs - add form help
    _this.$input = _this.$content.find('input, select')
    _this.$devNames = _this.$content.find('[data-dev-name]')

    /* for (var i = _this.$input.length - 1; i >= 0; i--) {
        if(_this.$input[i].getAttribute('data-desc') &&
            null !== _this.$input[i].getAttribute('data-desc') &&
            _this.$input[i].getAttribute('data-desc') !== ''){
            $(_this.$input[i]).after('<div class="form-help uk-alert uk-alert-large">'+_this.$input[i].getAttribute('data-desc')+'</div>');
        }
    } */

    for (var j = _this.$devNames.length - 1; j >= 0; j--) {
        var input = _this.$devNames[j]
        var $input = $(input)
        if (input.getAttribute('data-dev-name') !== '') {
            var $label = $input.parents('.uk-form-row').find('label')
            var $barLabel = $input.find('.uk-navbar-brand.label')

            if ($label.length) {
                $label.append('<span class="field-dev-name">' + input.getAttribute('data-dev-name') + '</span>')
            } else if ($barLabel.length) {
                $barLabel.append('<span class="field-dev-name">' + input.getAttribute('data-dev-name') + '</span>')
            }
        }
    }

    // Check if children node widget needs his dropdowns to be flipped up
    for (var k = _this.$formRow.length - 1; k >= 0; k--) {
        if (_this.$formRow[k].className.indexOf('children-nodes-widget') >= 0) {
            _this.childrenNodeWidgetFlip(k)
            break
        }
    }
}

NodeEditSource.prototype.initEvents = function () {
    var _this = this

    window.Rozier.$window.off('keydown', $.proxy(_this.onInputKeyDown, _this))
    window.Rozier.$window.on('keydown', $.proxy(_this.onInputKeyDown, _this))
    window.Rozier.$window.off('keyup', $.proxy(_this.onInputKeyUp, _this))
    window.Rozier.$window.on('keyup', $.proxy(_this.onInputKeyUp, _this))

    _this.$input.off('focus', $.proxy(_this.inputFocus, _this))
    _this.$input.on('focus', $.proxy(_this.inputFocus, _this))
    _this.$input.off('focusout', $.proxy(_this.inputFocusOut, _this))
    _this.$input.on('focusout', $.proxy(_this.inputFocusOut, _this))

    _this.$form.off('submit')
    _this.$form.on('submit', $.proxy(_this.onFormSubmit, _this))
}

NodeEditSource.prototype.onFormSubmit = function (event) {
    var _this = this

    window.Rozier.lazyload.canvasLoader.show()

    if (_this.currentTimeout) {
        clearTimeout(_this.currentTimeout)
    }

    _this.currentTimeout = setTimeout(function () {
        /*
         * Trigger event on window to notify open
         * widgets to close.
         */
        var pageChangeEvent = new CustomEvent('pagechange')
        window.dispatchEvent(pageChangeEvent)

        var formData = new FormData(_this.$form.get(0))
        $.ajax({
            url: window.location.href,
            type: 'post',
            data: formData,
            processData: false,
            cache: false,
            contentType: false
        })
        .done(function (data) {
            _this.cleanErrors()

            /*
             * Update preview or view url
             */
            if (data.public_url) {
                let $publicUrlLinks = $('a.public-url-link')
                if ($publicUrlLinks.length) {
                    $publicUrlLinks.attr('href', data.public_url)
                }
            }
        })
        .fail(function (data) {
            _this.displayErrors(data.responseJSON.errors)
            // console.log(data.responseJSON);
            window.UIkit.notify({
                message: data.responseJSON.message,
                status: 'danger',
                timeout: 2000,
                pos: 'top-center'
            })
        })
        .always(function () {
            window.Rozier.lazyload.canvasLoader.hide()
            window.Rozier.getMessages()
            window.Rozier.refreshAllNodeTrees()
        })
    }, 300)

    return false
}

NodeEditSource.prototype.cleanErrors = function () {
    var $previousErrors = $('.form-errored')
    $previousErrors.each(function (index) {
        $previousErrors.eq(index).removeClass('form-errored')
        $previousErrors.eq(index).find('.error-message').remove()
    })
}

/**
 *
 * @param errors
 * @param keepExisting Keep existing errors.
 */
NodeEditSource.prototype.displayErrors = function (errors, keepExisting) {
    var _this = this

    /*
     * First clean fields
     */
    if (!keepExisting || keepExisting === false) {
        _this.cleanErrors()
    }

    for (var key in errors) {
        var classKey = null
        var errorMessage = null
        if (toType(errors[key]) === 'object') {
            _this.displayErrors(errors[key], true)
        } else {
            classKey = key.replace('_', '-')
            errorMessage = errors[key][0]
            let $field = $('.form-col-' + classKey)
            if ($field.length) {
                $field.addClass('form-errored')
                $field.append('<p class="error-message uk-alert uk-alert-danger"><i class="uk-icon uk-icon-warning"></i> ' + errorMessage + '</p>')
            }
        }
    }
}

NodeEditSource.prototype.onInputKeyDown = function (event) {
    // ALT key
    if (event.keyCode === 18) {
        window.Rozier.$body.toggleClass('dev-name-visible')
    }
}
NodeEditSource.prototype.onInputKeyUp = function (event) {
    // ALT key
    if (event.keyCode === 18) {
        window.Rozier.$body.toggleClass('dev-name-visible')
    }
}

/**
 * Flip children node widget
 * @param  {[type]} index [description]
 * @return {[type]}       [description]
 */
NodeEditSource.prototype.childrenNodeWidgetFlip = function (index) {
    var _this = this

    if (index >= (_this.$formRow.length - 2)) {
        _this.$dropdown = $(_this.$formRow[index]).find('.uk-dropdown-small')
        _this.$dropdown.addClass('uk-dropdown-up')
    }
}

/**
 * Input focus
 * @return {[type]} [description]
 */
NodeEditSource.prototype.inputFocus = function (e) {
    $(e.currentTarget).parent().addClass('form-col-focus')
}

/**
 * Input focus out
 * @return {[type]} [description]
 */
NodeEditSource.prototype.inputFocusOut = function (e) {
    $(e.currentTarget).parent().removeClass('form-col-focus')
}

/**
 * Window resize callback
 * @return {[type]} [description]
 */
NodeEditSource.prototype.resize = function () {}
