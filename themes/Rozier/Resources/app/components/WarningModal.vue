<template>
    <modal name="warning-modal"
           transition="pop-out"
           :width="modalWidth"
           :height="350"
           @before-close="beforeClose">
        <div class="box">
            <div class="box-part" id="bp-left">
                <div class="modal" id="partition-register">
                    <div class="modal-title">{{ title }}</div>
                    <div class="modal-content">
                        <p>{{ content }}</p>

                        <div class="btns">
                            <a :href="linkUrl" class="btn large-btn" v-if="linkUrl && linkLabel">{{ linkLabel }}</a>
                            <button class="large-btn" @click="close">Close</button>
                        </div>
                    </div>
                </div>
            </div>
            <div class="box-part" id="bp-right">
                <template v-if="imageUrl">
                    <img src="data:image/gif;base64,R0lGODlhAQABAAD/ACwAAAAAAQABAAACADs="
                         v-dynamic-img="imageUrl" />
                </template>
            </div>
        </div>
    </modal>
</template>
<script>
    // Api
    import * as SplashScreenApi from '../api/SplashScreenApi'

    // Directives
    import DynamicImg from '../directives/DynamicImg'

    const MODAL_WIDTH = 656

    export default {
        data () {
            return {
                modalWidth: MODAL_WIDTH,
                manualClose: false,
                imageUrl: null
            }
        },
        props: {
            text: '',
            open: false,
            title: '',
            content: '',
            linkLabel: '',
            linkUrl: ''
        },
        directives: {
            DynamicImg
        },
        created () {
            this.modalWidth = window.innerWidth < MODAL_WIDTH
                ? MODAL_WIDTH / 2
                : MODAL_WIDTH
        },
        methods: {
            close () {
                this.manualClose = true
                this.$modal.hide('warning-modal')
            },
            beforeClose (event) {
                if (!this.manualClose) {
                    event.stop()
                }
            }
        },
        watch: {
            open (value) {
                if (value) {
                    SplashScreenApi.getImage()
                        .then((url) => {
                            this.imageUrl = url
                        })

                    this.$modal.show('warning-modal')
                } else {
                    this.$modal.hide('warning-modal')
                }
            }
        }
    }
</script>
<style lang="scss">
    $background_color: #404142;

    .box {
        background: white;
        overflow: hidden;
        width: 656px;
        height: 350px;
        border-radius: 2px;
        box-sizing: border-box;
        box-shadow: 0 0 40px black;
        color: #8b8c8d;
        display: flex;

        .box-part {
            display: inline-block;
            position: relative;
            vertical-align: top;
            box-sizing: border-box;
            height: 100%;
            width: 50%;

            &#bp-right {
                border-left: 1px solid #eee;
                background: #eee;

                img {
                    width: 100%;
                    height: 100%;
                    object-fit: cover;
                }
            }
        }

        .box-messages {
            position: absolute;
            left: 0;
            bottom: 0;
            width: 100%;
        }

        .modal {
            width: 100%;
            height: 100%;
            display: flex;
            flex-flow: column;
            justify-content: flex-start;

            .modal-title {
                box-sizing: border-box;
                padding: 30px;
                width: 100%;
                text-align: center;
                letter-spacing: 1px;
                font-size: 20px;
                font-weight: 300;
            }

            .modal-content {
                padding: 0 20px 20px 20px;
                box-sizing: border-box;
                display: flex;
                flex-flow: column;
                justify-content: space-between;
                height: 100%;

                p {
                    margin-top: 0;
                    font-size: 12px;
                }
            }
        }

        input[type=password],
        input[type=text] {
            display: block;
            box-sizing: border-box;
            margin-bottom: 4px;
            width: 100%;
            font-size: 12px;
            line-height: 2;
            border: 0;
            border-bottom: 1px solid #DDDEDF;
            padding: 4px 8px;
            font-family: inherit;
            transition: 0.5s all;
            outline: none;
        }

        .btn,
        button {
            background: white;
            border-radius: 4px;
            box-sizing: border-box;
            padding: 10px;
            letter-spacing: 1px;
            font-family: "Open Sans", sans-serif;
            font-weight: 400;
            min-width: 140px;
            margin-top: 8px;
            color: #8b8c8d;
            cursor: pointer;
            border: 1px solid #DDDEDF;
            text-transform: uppercase;
            transition: 0.1s all;
            font-size: 10px;
            outline: none;

            display: block;
            text-align: center;

            &:hover {
                text-decoration: none;
                border-color: mix(#DDDEDF, black, 90%);
                color: mix(#8b8c8d, black, 80%);
            }
        }

        .large-btn {
            width: 100%;
            background: white;
            span {
                font-weight: 600;
            }
        }
    }

    .pop-out-enter-active,
    .pop-out-leave-active {
        transition: all 0.5s;
    }

    .pop-out-enter,
    .pop-out-leave-active {
        opacity: 0;
        transform: translateY(24px);
    }
</style>
