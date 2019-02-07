<template>
    <transition name="fade">
        <div class="document-preview-widget" v-if="isVisible && document">
            <div class="document-preview-widget__wrapper" @click.prevent="onClick">
                <div class="document-preview-widget__information">
                    <div class="info">
                        {{ document.filename }}
                    </div>
                    <div class="info" v-if="document.name !== document.filename">
                        {{ document.name }}
                    </div>
                    <div class="info" v-if="document.embedPlatform">
                        {{ document.embedPlatform }}
                    </div>
                    <div class="close" @click.prevent="closePreview">
                        <i class="uk-icon-times"></i>
                    </div>
                </div>
                <div class="document-preview-widget__container"
                     :class="containerClass">
                    <div v-html="document.preview_html"></div>
                </div>
            </div>
        </div>
    </transition>
</template>
<script>
    import $ from 'jquery'
    import { mapState, mapActions } from 'vuex'

    export default {
        computed: {
            ...mapState({
                isVisible: state => state.documentPreview.isVisible,
                document: state => state.documentPreview.document
            }),
            containerClass () {
                return {
                    isPdf: this.document.isPdf,
                    hasNoPreview: !this.document.isImage,
                    isVideo: this.document.isVideo,
                    isSvg: this.document.isSvg
                }
            }
        },
        methods: {
            ...mapActions([
                'documentPreviewClose'
            ]),
            closePreview () {
                this.documentPreviewClose()
            },
            onClick (e) {
                if ($(e.srcElement).hasClass('document-preview-widget__wrapper')) {
                    this.closePreview()
                }
            }
        }
    }
</script>
<style lang="scss">
    .document-preview-widget {
        position: fixed;
        width: 100%;
        height: 100%;
        z-index: 99999;
        top: 0;
        left: 0;

        &__wrapper {
            position: absolute;
            width: 100%;
            height: 100%;
            background: rgba(#000, 0.5);
        }

        &__information {
            position: absolute;
            top: 10px;
            right: 10px;
            height: 31px;
            line-height: 30px;
            font-size: 12px;
            text-align: left;
            padding: 0 15px;
            border-radius: 16px;
            color: #7d7d7d;
            background: #000;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.31);

            .info,
            .close {
                display: inline-block;

                &:before {
                    display: inline-block;
                    content: ' ';
                    height: 20px;
                    width: 1px;
                    background: #000;
                    margin: 0 10px;
                    position: relative;
                    border-left: 1px solid #2d2d2d;
                    top: 5px;
                }

                &:first-of-type:before {
                    display: none;
                }
            }

            .close {
                font-size: 15px;
                cursor: pointer;

                i {
                    transition: all 0.3s ease-out;
                }

                &:hover i {
                    color: lighten(#7d7d7d, 20);
                }
            }
        }

        &__container {
            position: absolute;
            top: 50%;
            left: 50%;
            max-width: 80vw;
            max-height: 80vh;
            width: auto;
            height: auto;
            display: inline-block;
            transform: translateX(-50%) translateY(-50%);
            text-align: center;
            box-shadow: 0 0 320px rgba(0, 0, 0, 0.7);
            background: #dadada;

            & > div {
                height: 100%;
            }

            &.hasNoPreview.isVideo {
                position: relative;
                width: auto;
                height: auto;

                .document-preview-widget__preview {
                    position: relative;
                }
            }

            &.isSvg {
                position: relative;
                width: auto;
                height: auto;

                .document-preview-widget__preview {
                    position: relative;
                    width: 500px;
                    height: 500px;
                    pointer-events: none;
                }
            }

            &.isSvg object,
            &.isPdf object {
                width: 100%;
                height: 100%;
            }

            &.isPdf {
                width: 80vw;
                height: 80vh;
                background: #000;
            }
        }

        object,
        iframe,
        video,
        img {
            width: auto;
            height: auto;
            max-width: 80vw;
            max-height: 80vh;
        }

        iframe {
            min-width: 640px;
            min-height: 360px;
        }

        &__message {
            position: absolute;
            left: 50%;
            top: 50%;
            transform: translateY(-50%) translateX(-50%);
            background: lighten(#000, 10);
            padding-left: 20px;
            padding-right: 20px;
            height: 43px;
            border-radius: 25px;
            color: darken(#fff, 30);
        }
    }
</style>
