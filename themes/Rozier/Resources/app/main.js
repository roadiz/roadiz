import './scss/styles.scss'
import './less/vendor.less'
import './less/style.less'

// Include bower dependencies
import '../../bower_components/CanvasLoader/js/heartcode-canvasloader'
import '../../bower_components/jquery.actual/jquery.actual'
// import '../../bower_components/jquery-tag-editor/jquery.tag-editor'
import './vendor/jquery.tag-editor'
import './vendor/jquery.collection'
import '../../bower_components/bootstrap-switch/dist/js/bootstrap-switch'
import '../../bower_components/mousetrap/mousetrap'
import '../../bower_components/caret/jquery.caret.js'
import '../../bower_components/jquery-minicolors/jquery.minicolors.js'

import UIkit from '../../node_modules/uikit/dist/js/uikit'
import '../../node_modules/uikit/dist/js/components/nestable'
import '../../node_modules/uikit/dist/js/components/sortable.js'
import '../../node_modules/uikit/dist/js/components/datepicker.js'
import '../../node_modules/uikit/dist/js/components/pagination.js'
import '../../node_modules/uikit/dist/js/components/notify.js'
import '../../node_modules/uikit/dist/js/components/tooltip.js'

import CodeMirror from 'codemirror'
import 'codemirror/mode/markdown/markdown.js'
import 'codemirror/mode/javascript/javascript.js'
import 'codemirror/mode/css/css.js'
import 'codemirror/addon/mode/overlay.js'
import 'codemirror/mode/xml/xml.js'
import 'codemirror/mode/yaml/yaml.js'
import 'codemirror/mode/gfm/gfm.js'
import 'codemirror/addon/display/rulers.js'

import 'jquery-ui'
import 'jquery-ui/ui/widgets/autocomplete'

import $ from 'jquery'
import Rozier from './Rozier'

window.CodeMirror = CodeMirror
window.UIkit = UIkit
/*
 * ============================================================================
 * Rozier entry point
 * ============================================================================
 */

window.Rozier = new Rozier()

/*
 * ============================================================================
 * Plug into jQuery standard events
 * ============================================================================
 */
$(document).ready(() => {
    window.Rozier.onDocumentReady()
})
