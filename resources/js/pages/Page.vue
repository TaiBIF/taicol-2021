<template>
    <div class="w-full h-full">
        <loading-section v-if="isLoading"/>

        <slot v-else-if="status === STATUS_SUCCESS"/>
        <div v-else-if="status === STATUS_NOTFOUND">
            <not-found-view/>
        </div>
        <div v-else-if="status === STATUS_ERROR">發生錯誤</div>
    </div>
</template>
<script lang="ts">
import {
    defineComponent, onMounted, PropType, ref,
} from '@vue/composition-api';
import LoadingSection from '../components/LoadingSection.vue';
import NotFoundView from '../components/views/NotFoundView.vue';

const STATUS_INITIAL = 0;
const STATUS_SUCCESS = 200;
const STATUS_NOTFOUND = 404;
const STATUS_ERROR = 500;

export default defineComponent({
    props: {
        preload: {
            type: Function as PropType<() => void>,
            required: true,
        },
    },
    setup(props) {
        const status = ref<number>(STATUS_INITIAL);
        const isLoading = ref<boolean>(true);

        onMounted(async () => {
            await props.preload();
            status.value = STATUS_SUCCESS;
            isLoading.value = false;
        });

        return {
            isLoading,
            status,
            STATUS_NOTFOUND,
            STATUS_ERROR,
            STATUS_SUCCESS,
        };
    },
    components: { NotFoundView, LoadingSection },
});
</script>
