import Mousetrap from 'mousetrap'
import 'mousetrap/plugins/global-bind/mousetrap-global-bind'
import {
    KEYBOARD_EVENT_ESCAPE,
    KEYBOARD_EVENT_SAVE
} from '../store/mutationTypes'

/**
 * Keyboard Event Service Listener.
 *
 * @type {Object} store
 */
export default class KeyboardEventService {
    constructor (store) {
        this.store = store
        this.init()
    }

    init () {
        this.bindEscape()
        this.bindSave()
    }

    bindEscape () {
        Mousetrap.bindGlobal('esc', () => this.store.commit(KEYBOARD_EVENT_ESCAPE))
    }

    bindSave () {
        Mousetrap.bindGlobal(['ctrl+s', 'command+s'], () => this.store.commit(KEYBOARD_EVENT_SAVE))
    }
}
