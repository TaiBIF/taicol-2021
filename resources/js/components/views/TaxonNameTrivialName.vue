<template>
    <div>

    </div>
</template>
<script>
    export default {
        props: {
            taxonName: {
                type: Object,
                required: true,
            }
        },
        mounted() {
            this.axios.get('/taxon-name');
        }
    }
</script>
