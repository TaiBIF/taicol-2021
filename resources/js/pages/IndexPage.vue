<template>
    <div class="container">
        <div class="middle-center">
            <h1 class="title">物種學名管理工具</h1>
            <div class="field round-form">
                <auto-complete v-model="keywords"
                               :search-type="searchType"
                               v-on:go-search="onGoSearch()"
                />
            </div>
            <div class="search-options">
                搜尋選項
                &nbsp;&nbsp;
                <label class="radio">
                    <input v-model="searchType" type="radio" value="taxon-names"> 學名、俗名
                </label>

                <label class="radio">
                    <input v-model="searchType" type="radio" value="references"> 文獻
                </label>
            </div>
        </div>
    </div>
</template>
<script>
    import AutoComplete from './../components/AutoComplete';

    export default {
        data() {
            return {
                keywords: [],
                searchType: 'taxon-names',
            }
        },
        methods: {
            onGoSearch() {
                const keywordString = this.keywords.map(k => {
                    return `${k.type}: ${k.name}`;
                }).join('@');

                this.$router.push({
                    path: `/${this.searchType}`,
                    query: {
                        'keywords': keywordString,
                    }
                });
            },
        },
        components: {
            AutoComplete,
        },
    }
</script>
<style lang="scss" scoped>
    .title {
        text-align: center;
        color: $black;
        margin-bottom: 3.5rem;
        font-weight: 700;
    }

    .container {
        display: flex;
        justify-content: center;
        align-items: center;
        height: 70vh;
        max-width: $desktop;
        padding: 0 2rem;

        .middle-center {
            width: 80%;

            .search-options {
                padding: 1.5rem 2rem;
            }
        }
    }
</style>
