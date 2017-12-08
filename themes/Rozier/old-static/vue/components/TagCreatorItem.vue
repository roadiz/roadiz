<template>
    <div class="new-tag-container" @click="tagCreatorItemClick">
        <div class="button">
            <i class="uk-icon-plus"></i>
        </div>
        <div class="new-tag">
            <p class="parent-name"></p>
            <span class="name">{{ tagName }}</span>
        </div>
    </div>
</template>
<script>
    export default {
        props: {
            tagName: {
                required: true,
                type: String
            },
            createTag: {
                required: true,
                type: Function
            }
        },
        methods: {
            tagCreatorItemClick () {
                this.createTag({
                    tagName: this.tagName
                })
            }
        }
    }
</script>
<style lang="scss" scoped>
    $bgColor: darken(#3d3d3d, 10);
    $textColor: lighten(#3d3d3d, 10);
    $btnSize: 40px;

    .new-tag-container,
    .infos {
        flex: 1;
    }

    .infos {
        font-size: 12px;
    }

    .new-tag-container:hover {
        cursor: pointer;

        .button {
            background-color: darken($bgColor, 5);
            color: lighten($textColor, 10);
        }

        .new-tag {
            background-color: darken(#4c4c4c, 5);
        }
    }

    .button {
        width: $btnSize;
        height: $btnSize;
        line-height: $btnSize;
        text-align: center;
        border: 0;
        border-radius: 0 3px 3px 0;
        background-color: darken($bgColor, 10);
        color: $textColor;
        transition: all 0.25s ease-out;
        float: right;

        i {
            position: relative;
            top: 2px;
        }
    }

    .new-tag {
        float: right;
        height: $btnSize;
        border-radius: 0;
        font-size: 12px;
        line-height: 40px;
        font-weight: 600;
        overflow: hidden;
        margin: 0;
        padding: 7px 0 6px 15px;
        background: #4c4c4c;
        width: 182px;
        box-sizing: border-box;
        border-left: 4px solid #000;
        transition: all 0.25s ease-out;

        .parent-name {
            height: 12px;
            font-size: 8px;
            text-transform: uppercase;
            display: block;
            line-height: 11px;
            color: rgba(0,0,0,.6);
            letter-spacing: .07em;
            font-weight: 700;
            text-overflow: ellipsis;
            white-space: nowrap;
            overflow: hidden;
            margin: 0;
            padding-right: 10px;
        }

        .name {
            color: #888;
            line-height: 16px;
            display: block;
            padding-right: 10px;
            text-overflow: ellipsis;
            white-space: nowrap;
            overflow: hidden;
        }
    }
</style>
