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
 * @file nodeTypeFieldsPosition.js
 * @author Adrien Scholaert <adrien@rezo-zero.com>
 * @author Ambroise Maupate <ambroise@rezo-zero.com>
 */

import $ from 'jquery'

/**
 * Node type fields position
 */
export default class NodeTypeFieldsPosition {
    /**
     * Constructor
     */
    constructor () {
        this.$list = $('.node-type-fields > .uk-sortable')
        this.currentRequest = null

        // Bind methods
        this.onSortableChange = this.onSortableChange.bind(this)

        this.init()
    }

    /**
     * Init
     */
    init () {
        if (this.$list.length && this.$list.children().length > 1) {
            this.$list.on('change.uk.sortable', this.onSortableChange)
        }
    }

    unbind () {
        if (this.$list.length && this.$list.children().length > 1) {
            this.$list.off('change.uk.sortable', this.onSortableChange)
        }
    }

    /**
     * @param event
     * @param list
     * @param element
     */
    onSortableChange (event, list, element) {
        if (this.currentRequest && this.currentRequest.readyState !== 4) {
            this.currentRequest.abort()
        }

        let $element = $(element)
        let nodeTypeFieldId = parseInt($element.data('field-id'))
        let $sibling = $element.prev()
        let newPosition = 0.0

        if ($sibling.length === 0) {
            $sibling = $element.next()
            newPosition = parseInt($sibling.data('position')) - 0.5
        } else {
            newPosition = parseInt($sibling.data('position')) + 0.5
        }

        let postData = {
            '_token': window.Rozier.ajaxToken,
            '_action': 'updatePosition',
            'nodeTypeFieldId': nodeTypeFieldId,
            'newPosition': newPosition
        }

        this.currentRequest = $.ajax({
            url: window.Rozier.routes.nodeTypesFieldAjaxEdit.replace('%nodeTypeFieldId%', nodeTypeFieldId),
            type: 'POST',
            dataType: 'json',
            data: postData
        })
            .done(data => {
                $element.attr('data-position', newPosition)
                window.UIkit.notify({
                    message: data.responseText,
                    status: data.status,
                    timeout: 3000,
                    pos: 'top-center'
                })
            })
            .fail(data => {
                data = JSON.parse(data.responseText)
                window.UIkit.notify({
                    message: data.error_message,
                    status: 'danger',
                    timeout: 3000,
                    pos: 'top-center'
                })
            })
    }
}
