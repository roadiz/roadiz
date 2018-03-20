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

export default class TagAutocomplete {
    constructor () {
        this.$input = $('.rz-tag-autocomplete').eq(0)
        this.initialUrl = this.$input.attr('data-get-url')
        this.placeholder = this.$input.attr('placeholder')
        this.initialTags = []

        this.init()
    }

    init () {
        if (typeof this.initialUrl !== 'undefined' &&
            this.initialUrl !== '') {
            $.getJSON(this.initialUrl, {
                '_action': 'getNodeTags',
                '_token': window.Rozier.ajaxToken
            }, data => {
                this.initialTags = data
                this.initAutocomplete()
            })
        } else {
            this.initAutocomplete()
        }
    }

    unbind () {

    }

    split (val) {
        return val.split(/,\s*/)
    }

    extractLast (term) {
        return this.split(term).pop()
    }

    initAutocomplete () {
        this.$input.tagEditor({
            autocomplete: {
                delay: 0.3, // show suggestions immediately
                position: {
                    collision: 'flip' // automatic menu position up/down
                },
                source: (request, response) => {
                    $.getJSON(window.Rozier.routes.tagAjaxSearch, {
                        '_action': 'tagAutocomplete',
                        '_token': window.Rozier.ajaxToken,
                        'search': this.extractLast(request.term)
                    }, response)
                }
            },
            placeholder: this.placeholder,
            initialTags: this.initialTags,
            animateDelete: 0
        })
    }
}
