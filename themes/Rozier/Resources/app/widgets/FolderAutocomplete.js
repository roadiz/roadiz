import $ from 'jquery'

export default class FolderAutocomplete {
    constructor () {
        const _this = this

        $('.rz-folder-autocomplete')
            // don't navigate away from the field on tab when selecting an item
            .bind('keydown', function (event) {
                if (event.keyCode === $.ui.keyCode.TAB &&
                    $(this).autocomplete('instance').menu.active) {
                    event.preventDefault()
                }
            })
            .autocomplete({
                source: function (request, response) {
                    $.getJSON(window.Rozier.routes.foldersAjaxSearch, {
                        '_action': 'folderAutocomplete',
                        '_token': window.Rozier.ajaxToken,
                        'search': _this.extractLast(request.term)
                    }, response)
                },
                search: function () {
                    // custom minLength
                    let term = _this.extractLast(this.value)
                    if (term.length < 2) {
                        return false
                    }
                },
                focus: function () {
                    // prevent value inserted on focus
                    return false
                },
                select: function (event, ui) {
                    let terms = _this.split(this.value)
                    // remove the current input
                    terms.pop()
                    // add the selected item
                    terms.push(ui.item.value)
                    // add placeholder to get the comma-and-space at the end
                    terms.push('')
                    this.value = terms.join(', ')
                    return false
                }
            })
    }

    unbind () {}

    split (val) {
        return val.split(/,\s*/)
    }

    extractLast (term) {
        return this.split(term).pop()
    }
}
