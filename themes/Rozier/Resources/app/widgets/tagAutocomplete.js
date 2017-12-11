/*
 * Copyright (c) 2017. Ambroise Maupate and Julien Blanchet
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is furnished
 * to do so, subject to the following conditions:
 * The above copyright notice and this permission notice shall be included in all
 * copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS
 * OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL
 * THE AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS
 * IN THE SOFTWARE.
 *
 * Except as contained in this notice, the name of the ROADIZ shall not
 * be used in advertising or otherwise to promote the sale, use or other dealings
 * in this Software without prior written authorization from Ambroise Maupate and Julien Blanchet.
 *
 * @file tagAutocomplete.js
 * @author Adrien Scholaert <adrien@rezo-zero.com>
 */

import $ from 'jquery'

export default function TagAutocomplete () {
    var _this = this

    _this.$input = $('.rz-tag-autocomplete').eq(0)
    _this.initialUrl = _this.$input.attr('data-get-url')
    _this.placeholder = _this.$input.attr('placeholder')
    _this.initialTags = []

    function split (val) {
        return val.split(/,\s*/)
    }
    function extractLast (term) {
        return split(term).pop()
    }

    function initAutocomplete () {
        _this.$input.tagEditor({
            autocomplete: {
                delay: 0.3, // show suggestions immediately
                position: { collision: 'flip' }, // automatic menu position up/down
                source: function (request, response) {
                    $.getJSON(window.Rozier.routes.tagAjaxSearch, {
                        '_action': 'tagAutocomplete',
                        '_token': window.Rozier.ajaxToken,
                        'search': extractLast(request.term)
                    }, response)
                }
            },
            placeholder: _this.placeholder,
            initialTags: _this.initialTags,
            animateDelete: 0
        })
    }

    if (typeof _this.initialUrl !== 'undefined' &&
        _this.initialUrl !== '') {
        $.getJSON(
            _this.initialUrl,
            {
                '_action': 'getNodeTags',
                '_token': window.Rozier.ajaxToken
            }, function (data) {
                _this.initialTags = data
                initAutocomplete()
            }
        )
    } else {
        initAutocomplete()
    }
}
