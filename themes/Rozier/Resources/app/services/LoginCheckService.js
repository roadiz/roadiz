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
 * @file LoginCheckService.js
 * @author Adrien Scholaert <adrien@rezo-zero.com>
 */
import request from 'axios'
import {
    LOGIN_CHECK_DISCONNECTED
} from '../types/mutationTypes'

/**
 * Login Check Event Service.
 *
 * @type {Object} store
 */
export default class LoginCheckService {
    constructor (store) {
        this.store = store
        this.intervalDuration = 10000
        this.check()
    }

    check () {
        if (this.interval) {
            window.clearInterval(this.interval)
        }

        this.interval = window.setInterval(() => {
            request({
                method: 'GET',
                url: window.RozierRoot.routes.ping,
                headers: {'X-Requested-With': 'XMLHttpRequest'}
            })
                .then((response) => {
                    if (response && response.status === 200) {
                        this.check()
                    }
                })
                .catch(() => {
                    this.store.commit(LOGIN_CHECK_DISCONNECTED)
                })
        }, this.intervalDuration)
    }
}
