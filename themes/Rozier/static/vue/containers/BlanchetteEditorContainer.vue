<template>
    <div>
        <h3>Blanchette Editor</h3>
        <vue-cropper
            ref="cropper"
            :guides="guides"
            :view-mode="viewMode"
            :drag-mode="dragMode"
            :auto-crop-area="autoCropArea"
            :min-container-width="minContainerWidth"
            :min-container-height="minContainerHeight"
            :background="background"
            :rotatable="rotatable"
            :src="url"
            :alt="alt"
            :imgStyle="imgStyle"
            :cropmove="cropImage">
        </vue-cropper>
    </div>
</template>
<script>
    import VueCropper from 'vue-cropperjs'

    export default {
        props: {
            url: {
                required: true,
                type: String
            }
        },
        data () {
            return {
                imgStyle: {
                    width: '400px',
                    height: '300px'
                },
                guides: true,
                background: true,
                rotatable: true,
                viewMode: 2,
                dragMode: 'crop',
                autoCropArea: 0.5,
                minContainerWidth: 250,
                minContainerHeight: 180,
                alt: 'Source Image'
            }
        },
        methods: {
            setImage (e) {
                const file = e.target.files[0];

                if (!file.type.includes('image/')) {
                    alert('Please select an image file')
                    return
                }

                if (typeof FileReader === 'function') {
                    const reader = new FileReader()

                    reader.onload = (event) => {
                        this.url = event.target.result
                        // rebuild cropperjs with the updated source
                        this.$refs.cropper.replace(event.target.result)
                    };

                    reader.readAsDataURL(file)
                } else {
                    alert('Sorry, FileReader API not supported')
                }
            },
            cropImage () {
                // get image data for post processing, e.g. upload or setting image src
                this.cropImg = this.$refs.cropper.getCroppedCanvas().toDataURL()
            },
            rotate () {
                // guess what this does :)
                this.$refs.cropper.rotate(90)
            }
        },
        components: {
            VueCropper
        }
    }
</script>
