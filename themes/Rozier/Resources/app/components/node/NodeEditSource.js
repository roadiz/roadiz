import $ from 'jquery'
import {
    toType
} from '../../utils/plugins'

/**
 * Node edit source
 */
export default class NodeEditSource {
    constructor () {
        // Selectors
        this.$content = $('.content-node-edit-source').eq(0)
        this.$form = $('#edit-node-source-form')
        this.$formRow = null
        this.$dropdown = null
        this.$input = null

        // Binded methods
        this.onInputKeyDown = this.onInputKeyDown.bind(this)
        this.onInputKeyUp = this.onInputKeyUp.bind(this)
        this.inputFocus = this.inputFocus.bind(this)
        this.inputFocusOut = this.inputFocusOut.bind(this)
        this.onFormSubmit = this.onFormSubmit.bind(this)

        // Methods
        if (this.$content.length) {
            this.$formRow = this.$content.find('.uk-form-row')
            this.wrapInTabs()
            this.init()
            this.initEvents()
        }
    }

    wrapInTabs () {
        let fieldGroups = {}
        let $fields = this.$content.find('.uk-form-row[data-field-group]')
        let fieldsLength = $fields.length
        let fieldsGroupsLength = 0

        if (fieldsLength > 1) {
            for (let i = 0; i < fieldsLength; i++) {
                let groupName = $fields[i].getAttribute('data-field-group')

                if (typeof fieldGroups[groupName] === 'undefined') {
                    fieldGroups[groupName] = []
                    fieldsGroupsLength++
                }
                fieldGroups[groupName].push($fields[i])
            }

            if (fieldsGroupsLength > 1) {
                this.$form.append('<div id="node-source-form-switcher-nav-cont"><ul id="node-source-form-switcher-nav" class="uk-subnav uk-subnav-pill" data-uk-switcher="{connect:\'#node-source-form-switcher\', swiping:false}"></ul></div><ul id="node-source-form-switcher" class="uk-switcher"></ul>')
                let $formSwitcher = this.$form.find('.uk-switcher')
                let $formSwitcherNav = this.$form.find('#node-source-form-switcher-nav')

                /*
                 * Sort tab name and put default in first
                 */
                let keysSorted = Object.keys(fieldGroups).sort((a, b) => {
                    if (a === 'default') { return -1 }
                    if (b === 'default') { return 1 }
                    return +(a.toLowerCase() > b.toLowerCase()) || +(a.toLowerCase() === b.toLowerCase()) - 1
                })

                for (let keyIndex in keysSorted) {
                    let groupName2 = keysSorted[keyIndex]
                    let groupName2Safe = groupName2.toLowerCase().replace(/ /g, '-').replace(/[^\w-]+/g, '')

                    let groupId = 'group-' + groupName2Safe
                    $formSwitcher.append('<li class="field-group" id="' + groupId + '"></li>')

                    if (groupName2 === 'default') {
                        $formSwitcherNav.append('<li class="switcher-nav-item"><a href="#"><i class="uk-icon-star"></i></a></li>')
                    } else {
                        $formSwitcherNav.append('<li class="switcher-nav-item"><a href="#">' + groupName2 + '</a></li>')
                    }
                    let $group = $formSwitcher.find('#' + groupId)

                    for (let index = 0; index < fieldGroups[groupName2].length; index++) {
                        $group.append($(fieldGroups[groupName2][index]))
                    }
                }

                $formSwitcherNav.on('show.uk.switcher', () => {
                    window.setTimeout(() => {
                        window.Rozier.$window.trigger('resize')
                        window.Rozier.lazyload.refreshCodemirrorEditor()
                    }, 100)
                })
            }
        }
    }

    /**
     * Init
     */
    init () {
        // Inputs - add form help
        this.$input = this.$content.find('input, select')
        this.$devNames = this.$content.find('[data-dev-name]')

        for (let j = this.$devNames.length - 1; j >= 0; j--) {
            let input = this.$devNames[j]
            let $input = $(input)
            if (input.getAttribute('data-dev-name') !== '') {
                let $label = $input.parents('.uk-form-row').find('label')
                let $barLabel = $input.find('.uk-navbar-brand.label')

                if ($label.length) {
                    $label.append('<span class="field-dev-name">' + input.getAttribute('data-dev-name') + '</span>')
                } else if ($barLabel.length) {
                    $barLabel.append('<span class="field-dev-name">' + input.getAttribute('data-dev-name') + '</span>')
                }
            }
        }

        // Check if children node widget needs his dropdowns to be flipped up
        for (let k = this.$formRow.length - 1; k >= 0; k--) {
            if (this.$formRow[k].className.indexOf('children-nodes-widget') >= 0) {
                this.childrenNodeWidgetFlip(k)
                break
            }
        }
    }

    initEvents () {
        window.Rozier.$window.on('keydown', this.onInputKeyDown)
        window.Rozier.$window.on('keyup', this.onInputKeyUp)
        this.$input.on('focus', this.inputFocus)
        this.$input.on('focusout', this.inputFocusOut)
        this.$form.on('submit', this.onFormSubmit)
    }

    unbind () {
        if (this.$content.length) {
            window.Rozier.$window.off('keydown', this.onInputKeyDown)
            window.Rozier.$window.off('keyup', this.onInputKeyUp)
            this.$input.off('focus', this.inputFocus)
            this.$input.off('focusout', this.inputFocusOut)
            this.$form.off('submit', this.onFormSubmit)
        }
    }

    onFormSubmit () {
        window.Rozier.lazyload.canvasLoader.show()

        if (this.currentTimeout) {
            clearTimeout(this.currentTimeout)
        }

        this.currentTimeout = setTimeout(() => {
            /*
             * Trigger event on window to notify open
             * widgets to close.
             */
            let pageChangeEvent = new CustomEvent('pagechange')
            window.dispatchEvent(pageChangeEvent)

            let formData = new FormData(this.$form.get(0))

            $.ajax({
                url: window.location.href,
                type: 'post',
                data: formData,
                processData: false,
                cache: false,
                contentType: false
            })
                .done(data => {
                    this.cleanErrors()

                    // Update preview or view url
                    if (data.public_url) {
                        let $publicUrlLinks = $('a.public-url-link')
                        if ($publicUrlLinks.length) {
                            $publicUrlLinks.attr('href', data.public_url)
                        }
                    }
                })
                .fail(data => {
                    if (data.responseJSON) {
                        this.displayErrors(data.responseJSON.errors)
                        window.UIkit.notify({
                            message: data.responseJSON.message,
                            status: 'danger',
                            timeout: 2000,
                            pos: 'top-center'
                        })
                    }
                })
                .always(() => {
                    window.Rozier.lazyload.canvasLoader.hide()
                    window.Rozier.getMessages()
                    window.Rozier.refreshAllNodeTrees()
                })
        }, 300)

        return false
    }

    cleanErrors () {
        const $previousErrors = $('.form-errored')
        $previousErrors.each((index) => {
            $previousErrors.eq(index).removeClass('form-errored')
            $previousErrors.eq(index).find('.error-message').remove()
        })
    }

    /**
     *
     * @param {Array} errors
     * @param {Boolean} keepExisting Keep existing errors.
     */
    displayErrors (errors, keepExisting) {
        // First clean fields
        if (!keepExisting || keepExisting === false) {
            this.cleanErrors()
        }

        for (let key in errors) {
            let classKey = null
            let errorMessage = null
            if (toType(errors[key]) === 'object') {
                this.displayErrors(errors[key], true)
            } else {
                classKey = key.replace('_', '-')
                if (errors[key] instanceof Array) {
                    errorMessage = errors[key][0]
                } else {
                    errorMessage = errors[key]
                }
                let $field = $('.form-col-' + classKey)
                if ($field.length) {
                    $field.addClass('form-errored')
                    $field.append('<p class="error-message uk-alert uk-alert-danger"><i class="uk-icon uk-icon-warning"></i> ' + errorMessage + '</p>')
                }
            }
        }
    }

    /**
     * On keyboard key down
     * @param {Event} event
     */
    onInputKeyDown (event) {
        // ALT key
        if (event.keyCode === 18) {
            window.Rozier.$body.toggleClass('dev-name-visible')
        }
    }

    /**
     * On keyboard key up
     * @param {Event} event
     */
    onInputKeyUp (event) {
        // ALT key
        if (event.keyCode === 18) {
            window.Rozier.$body.toggleClass('dev-name-visible')
        }
    }

    /**
     * Flip children node widget
     * @param  {Number} index
     */
    childrenNodeWidgetFlip (index) {
        if (index >= (this.$formRow.length - 2)) {
            this.$dropdown = $(this.$formRow[index]).find('.uk-dropdown-small')
            this.$dropdown.addClass('uk-dropdown-up')
        }
    }

    /**
     * Input focus
     * @param {Event} e
     */
    inputFocus (e) {
        $(e.currentTarget).parent().addClass('form-col-focus')
    }

    /**
     * Input focus out
     * @param {Event} e
     */
    inputFocusOut (e) {
        $(e.currentTarget).parent().removeClass('form-col-focus')
    }

    /**
     * Window resize callback
     */
    resize () {}
}
