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
            let searchType = this.getSearchType(this.$route.name);

            const keywords = this.$route.query.keywords ? this.$route.query.keywords?.split('@').map(k => {
                const [type, name] = k.split(': ');
                return {
                    type,
                    name,
                }
            }) || [] : [];

            return {
                searchType,
                keywords,
            }
        },
        methods: {
            getSearchType(value) {
                switch (value) {
                    case 'taxon-name-list':
                        return 'taxon-names';
                    case 'reference-list':
                        return 'references';
                    default:
                        return '';
                }
            },
            onGoSearch() {
                const keywordsString = this.keywords.map(k => `${k.type}: ${k.name}`).join('@');
                if (
                    this.getSearchType(this.$route.name) !== this.searchType ||
                    keywordsString !== this.$route.query.keywords
                ) {
                    this.$router.push(`/${this.searchType}?keywords=${keywordsString}`);
                    this.$emit('on-search');
                }
            },
        },
        components: {
            AutoComplete,
        },
    }
</script>
<style lang="scss" scoped>
    /deep/ .control {
        .select-input {
            padding: 0 2rem;
        }
    }

    .searchbar {
        padding: 0 1rem;
        background-color: $grey;
        position: fixed;
        top: $navbar-height;
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
