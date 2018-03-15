<template>
    <div class="toolbar" ref="toolbar">
        <template v-if="!cropped">
            <button class="toolbar__button uk-button" data-uk-tooltip :title="translations.move" v-if="setDragMode" @click="setDragMode('move')">
                <i class="uk-icon-arrows"></i>
            </button>
            <button class="toolbar__button uk-button" data-uk-tooltip :title="translations.crop" v-if="setDragMode" @click="setDragMode('crop')">
                <i class="uk-icon-crop"></i>
            </button>
            <button class="toolbar_button uk-button" data-uk-tooltip :title="translations.zoomIn" v-if="zoomIn" @click="zoomIn">
                <i class="uk-icon-search-plus"></i>
            </button>
            <button class="toolbar_button uk-button" data-uk-tooltip :title="translations.zoomOut" v-if="zoomOut" @click="zoomOut">
                <i class="uk-icon-search-minus"></i>
            </button>
            <button class="toolbar_button uk-button" data-uk-tooltip :title="translations.rotateLeft" v-if="rotateLeft" @click="rotateLeft">
                <i class="uk-icon-rotate-left"></i>
            </button>
            <button class="toolbar_button uk-button" data-uk-tooltip :title="translations.rotateRight" v-if="rotateRight" @click="rotateRight">
                <i class="uk-icon-rotate-right"></i>
            </button>
            <button class="toolbar_button uk-button" data-uk-tooltip :title="translations.flipHorizontal" v-if="flipHorizontal" @click="flipHorizontal">
                <i class="uk-icon-arrows-h"></i>
            </button>
            <button class="toolbar_button uk-button" data-uk-tooltip :title="translations.flipVertical" v-if="flipVertical" @click="flipVertical">
                <i class="uk-icon-arrows-v"></i>
            </button>

            <div class="toolbar__select_wraper" data-uk-tooltip :title="translations.aspectRatio">
                <select class="toolbar__select uk-button" v-model="ratio" @change="aspectRatio">
                    <optgroup :label="translations.other">
                        <option value="free">{{ translations.free }}</option>
                        <option value="1:1">1:1</option>
                        <option value="4:3">4:3</option>
                    </optgroup>
                    <optgroup :label="translations.landscape">
                        <option value="16:9">16:9</option>
                        <option value="21:9">21:9</option>
                    </optgroup>
                    <optgroup :label="translations.portrait">
                        <option value="9:16">9:16</option>
                        <option value="9:21">9:21</option>
                    </optgroup>
                </select>
                <i class="uk-icon-arrow-down"></i>
            </div>

            <button class="toolbar_button uk-button uk-button-primary" data-uk-tooltip :title="translations.applyChange" v-if="cropping && !cropped" @click="crop">
                <i class="uk-icon-check"></i>
            </button>
        </template>

        <button class="toolbar_button uk-button uk-button-secondary" data-uk-tooltip :title="translations.undo" v-if="cropped" @click="undo">
            <i class="uk-icon-undo"></i>
        </button>
        <button class="toolbar_button uk-button uk-button-primary" data-uk-tooltip :title="translations.saveAndOverwrite" v-if="cropped" @click="overwrite">
            <i class="uk-icon-floppy-o"></i> {{ translations.saveAndOverwrite }}
        </button>
    </div>
</template>
<style lang="scss" scoped>
    .toolbar {
        margin: 20px 0;

        .toolbar__select_wraper {
            position: relative;
            display: inline-block;

            .toolbar__select {
                padding-right: 30px;
            }

            i {
                position: absolute;
                right: 7px;
                top: 11px;
                pointer-events: none;
            }
        }
    }
</style>
<script>
    export default {
        props: {
            translations: {
                type: Object,
                required: true
            },
            aspectRatio: {
                type: Function
            },
            cropping: {
                type: Boolean,
                required: true
            },
            cropped: {
                type: Boolean,
                required: true
            },
            overwrite: {
                type: Function,
                required: true
            },
            undo: {
                type: Function,
                required: true
            },
            setDragMode: {
                type: Function
            },
            zoomOut: {
                type: Function
            },
            zoomIn: {
                type: Function
            },
            rotateLeft: {
                type: Function
            },
            rotateRight: {
                type: Function
            },
            flipHorizontal: {
                type: Function
            },
            flipVertical: {
                type: Function
            },
            crop: {
                type: Function
            },
            move: {
                type: Function
            },
            clear: {
                type: Function
            }
        },
        data () {
            return {
                ratio: 'free'
            }
        },
        mounted () {
            window.addEventListener('keydown', this.keydown, false)
        },
        beforeDestroy () {
            window.removeEventListener('keydown', this.keydown, false)
        },
        methods: {
            keydown (e) {
                const key = e.keyCode

                switch (key) {
                    // Undo crop (Key: Ctrl + Z)
                case 90:
                    if (this.undo) {
                        e.preventDefault()
                        this.undo()
                    }
                    break
                }

                if (this.cropped) {
                    return
                }

                switch (key) {
                // Crop the image (Key: Enter)
                case 13:
                    if (this.crop) {
                        this.crop()
                    }
                    break

                // Clear crop area (Key: Esc)
                case 27:
                    if (this.clear) {
                        this.clear()
                    }
                    break

                // Move to the left (Key: ←)
                case 37:
                    if (this.move) {
                        e.preventDefault()
                        this.move(-1, 0)
                    }
                    break

                // Move to the top (Key: ↑)
                case 38:
                    if (this.move) {
                        e.preventDefault()
                        this.move(0, -1)
                    }
                    break

                // Move to the right (Key: →)
                case 39:
                    if (this.move) {
                        e.preventDefault()
                        this.move(1, 0)
                    }
                    break

                // Move to the bottom (Key: ↓)
                case 40:
                    if (this.move) {
                        e.preventDefault()
                        this.move(0, 1)
                    }
                    break

                // Enter crop mode (Key: C)
                case 67:
                    if (this.setDragMode) {
                        this.setDragMode('crop')
                    }
                    break

                // Enter move mode (Key: M)
                case 77:
                    if (this.setDragMode) {
                        this.setDragMode('move')
                    }
                    break

                // Zoom in (Key: I)
                case 73:
                    if (this.zoomIn) {
                        this.zoomIn()
                    }
                    break

                // Zoom out (Key: O)
                case 79:
                    if (this.zoomOut) {
                        this.zoomOut()
                    }
                    break

                // Rotate left (Key: L)
                case 76:
                    if (this.rotateLeft) {
                        this.rotateLeft()
                    }
                    break

                // Rotate right (Key: R)
                case 82:
                    if (this.rotateRight) {
                        this.rotateRight()
                    }
                    break

                // Flip horizontal (Key: H)
                case 72:
                    if (this.flipHorizontal) {
                        this.flipHorizontal()
                    }
                    break

                // Flip vertical (Key: V)
                case 86:
                    if (this.flipVertical) {
                        this.flipVertical()
                    }
                    break
                }
            }
        }
    }
</script>
