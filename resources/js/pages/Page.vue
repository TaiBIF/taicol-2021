<template>
    <div class="container">
        <loading-section v-if="isLoading"/>

        <slot v-else-if="status === STATUS_SUCCESS"/>
        <div v-else-if="status === STATUS_NOTFOUND">
            <not-found-view/>
        </div>
        <div v-else-if="status === STATUS_ERROR">發生錯誤</div>
    </div>
</template>
<script>
    import LoadingSection from '../components/LoadingSection';
    import NotFoundView from '../components/views/NotFoundView';

    const STATUS_INITIAL = 0;
    const STATUS_SUCCESS = 200;
    const STATUS_NOTFOUND = 404;
    const STATUS_ERROR = 500;

    export default {
        props: {
            preload: {
                type: Function,
                required: true,
            },
        },
        async mounted() {
            console.log('- start preload data');
            this.status = await this.preload();
            this.isLoading = false;
            console.log('- end of preload data');
        },
        data() {
            return {
                isLoading: true,
                status: STATUS_INITIAL,
                STATUS_NOTFOUND,
                STATUS_ERROR,
                STATUS_SUCCESS,
            }
        },
        components: { NotFoundView, LoadingSection },
    }
</script>
<style lang="scss" scoped>
    .container {
        padding: 2rem 0;
        display: flex;
        flex-flow: column;
        height: 100%;
    }
</style>
