<template>
    <transition name="fade">
        <li class="widget-explorer-infos" v-if="length && itemCount">
            {{ length }} / {{ itemCount }}
        </li>
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
                type: Number
            }
        }
    }
</script>
