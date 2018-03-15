<template>
    <transition name="fade">
        <div class="widget-explorer-infos" v-if="length && itemCount">
            {{ length }} / {{ itemCount }}
        </div>
    </transition>
</template>
<script>
    export default {
        props: {
            length: {
                required: true,
                type: Number
            },
            itemCount: {
                required: true,
                type: Number,
                default: 0
            }
        }
    }
</script>
