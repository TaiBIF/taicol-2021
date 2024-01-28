<template>
    <div class="container flex justify-center items-center">
        <div class="middle-center">
            <h1 class="text-center mb-10 font-bold text-[2rem]">{{ $t('indexPage.title') }}</h1>
            <div class="field round-form">
                <auto-complete v-model="keywords"
                               :search-type="searchType"
                               v-on:go-search="onGoSearch()"
                />
            </div>
            <div class="py-6 px-8">
                {{ $t('indexPage.search') }}
                &nbsp;&nbsp;
                <label class="radio">
                    <input v-model="searchType" type="radio" value="taxon-names">
                    {{ $t('indexPage.model.taxonName') }}
                </label>

                <label class="radio">
                    <input v-model="searchType" type="radio" value="references">
                    {{ $t('indexPage.model.reference') }}
                </label>

                <label class="radio">
                    <input v-model="searchType" type="radio" value="persons"> {{
                        $t('indexPage.model.person')
                    }}
                </label>
            </div>
        </div>
    </div>
</template>
<script lang="ts">
import { defineComponent, ref } from '@vue/composition-api';
import AutoComplete from '../components/AutoComplete.vue';

declare type Keywords = {
    type: string;
    name: string;
};

export default defineComponent({
    name: 'IndexPage',
    props: {},
    setup(props, context) {
        const keywords = ref<Array<Keywords>>([]);
        const searchType = ref<string>('taxon-names');

        const app: any = context.root;

        const onGoSearch = () => {
            const keywordString = keywords.value.map((k) => `${k.type}: ${k.name}`).join('@');

            const routeName = searchType.value;
            const routeTypeMapping = {
                'taxon-names': 'taxon-name-list-page',
                references: 'reference-list-page',
                persons: 'person-list-page',
            };

            app.$router.push({
                name: routeTypeMapping[routeName],
                query: {
                    keywords: keywordString,
                },
            });
        };

        return {
            keywords,
            searchType,
            onGoSearch,
        };
    },
    components: {
        AutoComplete,
    },
});
</script>
<style lang="scss" scoped>
.container {
    height: calc(90vh - #{$navbar-height});
    max-width: $desktop !important;
    padding: 0 2rem;

    .middle-center {
        width: 80%;
    }
}
</style>
