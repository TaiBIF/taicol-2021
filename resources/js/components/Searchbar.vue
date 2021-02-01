<template>
    <div v-if="$route.meta.searchbar" class="searchbar">
        <div class="columns">
            <div class="column is-3-desktop round-form">
                <auto-complete v-model="keyword"
                               :isSmall="true"
                               :search-type="searchType"
                               v-on:go-search="onSearch"/>
            </div>
            <div class="column search-options">
                {{ $t('search.options') }}
                &nbsp;&nbsp;
                <label class="radio">
                    <input v-model="searchType" type="radio" value="taxon-names">
                    {{ `${$t('models.taxonName')}、${$t('models.commonName')}` }}
                </label>

                <label class="radio">
                    <input v-model="searchType" type="radio" value="references"> {{ $t('models.reference') }}
                </label>
            </div>
        </div>
    </div>
</template>
<script>
    import AutoComplete from './../components/AutoComplete';

    export default {
        data() {
            let searchType = '';
            switch (this.$route.name) {
                case 'taxon-name-list':
                    searchType = 'taxon-names';
                    break;
                case 'reference-list':
                    searchType = 'references';
                    break;
                default:
                    searchType = '';
            }

            return {
                searchType,
                keyword: this.$route.query.keyword,
            }
        },
        computed: {
            route() {
                return this.$route.query;
            },
        },
        watch: {
            route() {
                let searchType = '';
                switch (this.$route.name) {
                    case 'taxon-name-list':
                        searchType = 'taxon-names';
                        break;
                    case 'reference-list':
                        searchType = 'references';
                        break;
                    default:
                        searchType = '';
                }
                this.searchType = searchType;
                this.keyword = this.$route.query.keyword;
            },
        },
        methods: {
            onSearch() {
                this.$router.push(`/${this.searchType}?keyword=${this.keyword}`);
            },
        },
        components: {
            AutoComplete,
        },
    }
</script>
<style lang="scss" scoped>
    .searchbar {
        padding: 0 1rem;
        background-color: $grey;
        color: white;
        padding-bottom: .5rem;
        position: sticky;
        top: $navbar-height;
        width: 100%;
        z-index: 50;

        .columns {
            margin-bottom: 0;
            margin-top: 0;
        }

        .search-options {
            &:after {
                content: "";
                display: inline-block;
                height: 100%;
                vertical-align: bottom;
            }
        }
    }
</style>
