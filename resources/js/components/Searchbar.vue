<template>
    <div class="searchbar">
        <div class="columns">
            <div class="column is-6-desktop round-form">
                <auto-complete v-model="keywords"
                               :isSmall="true"
                               :search-type="searchType"
                               v-on:go-search="onGoSearch"/>
            </div>
            <div class="column search-options">
                {{ $t('indexPage.search') }}
                &nbsp;&nbsp;
                <label class="radio">
                    <input v-model="searchType" type="radio" value="taxon-names">
                    {{ `${$t('common.taxonNameCommonName')}` }}
                </label>

                <label class="radio">
                    <input v-model="searchType" type="radio" value="references"> {{ $t('common.reference') }}
                </label>

                <label class="radio">
                    <input v-model="searchType" type="radio" value="persons"> {{ $t('common.person') }}
                </label>
            </div>
        </div>
    </div>
</template>
<script>
import AutoComplete from './AutoComplete.vue';

export default {
    data() {
        const searchType = this.getSearchType(this.$route.name);

        const keywords = this.$route.query.keywords ? this.$route.query.keywords?.split('@').map((k) => {
            const [type, name] = k.split(': ');
            return {
                type,
                name,
            };
        }) || [] : [];

        return {
            searchType,
            keywords,
        };
    },
    methods: {
        getSearchType(value) {
            switch (value) {
                case 'taxon-name-list-page':
                case 'taxon-name-page':
                    return 'taxon-names';
                case 'reference-list-page':
                case 'reference-page':
                    return 'references';
                case 'person-list-page':
                case 'person-page':
                    return 'persons';
                default:
                    return '';
            }
        },
        onGoSearch() {
            const keywordsString = this.keywords.map((k) => `${k.type}: ${k.name}`).join('@');
            if (
                this.getSearchType(this.$route.name) !== this.searchType
                || keywordsString !== this.$route.query.keywords
            ) {
                const map = {
                    'taxon-names': 'taxon-name-list-page',
                    references: 'reference-list-page',
                    persons: 'person-list-page',
                };

                this.$router.push({ name: map[this.searchType], query: { keywords: keywordsString } });

                this.$emit('on-search');
            }
        },
    },
    components: {
        AutoComplete,
    },
};
</script>
<style lang="scss" scoped>
.searchbar {
    padding: 0 1rem;
    background-color: $grey;
    position: sticky;
    width: 100%;
    z-index: 50;
    left: 0;

    .columns {
        margin-bottom: 0;
        margin-top: 0;
    }

    .search-options {
        color: white;

        &:after {
            content: "";
            display: inline-block;
            height: 100%;
            vertical-align: bottom;
        }
    }
}
</style>
