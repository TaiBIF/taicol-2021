<template>
    <div class="flex flex-col h-full">
        <searchbar/>
        <div class="overflow-y-auto grow">
            <router-view :key="$route.path + JSON.stringify($route.query)"
                         class="w-[90%] py-4 my-0 mx-auto"></router-view>
        </div>
    </div>
</template>
<script>
import Searchbar from '../components/Searchbar.vue';

export default {
    mounted() {
        console.log('mounted search layer');
    },
    components: { Searchbar },
};
</script>
