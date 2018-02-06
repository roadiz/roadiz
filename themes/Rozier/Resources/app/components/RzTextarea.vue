<template>
    <textarea :name="name" :id="id" :cols="cols" :rows="rows">{{ initialValue }}</textarea>
</template>
<script>
    export default {
        props: {
            name: {
                required: true,
                type: String
            },
            id: {
                required: true,
                type: String
            },
            initialValue: {
                type: String
            },
            cols: {
                type: Number
            },
            rows: {
                type: Number
            }
        }
    }
</script>
