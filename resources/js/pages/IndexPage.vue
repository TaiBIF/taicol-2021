<template>
    <div class="container">
        <div class="middle-center">
            <h1 class="title">物種學名管理工具</h1>
            <div class="field round-form">
                <auto-complete v-model="keyword"
                               :search-type="searchType"
                               v-on:go-search="onSearch"
                />
            </div>
            <div class="search-options">
                搜尋選項
                &nbsp;&nbsp;
                <label class="radio">
                    <input type="radio" v-model="searchType" value="taxon-names"> 學名、俗名
                </label>

                <label class="radio">
                    <input type="radio" v-model="searchType" value="references"> 文獻
                </label>
            </div>
        </div>
    </div>
</template>
<script>
    import AutoComplete from './../components/AutoComplete';
    export default {
        components: {
            AutoComplete,
        },
        data() {
            return {
                keyword: '',
                searchType: 'taxon-names',
            }
        },
        methods: {
            onSearch() {
                this.$router.push(`/${this.searchType}?keyword=${this.keyword}`);
            }
        }
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
            width: 100%;
            max-width: 500px;

            .search-options {
                padding: 1.5rem 2rem;
            }
        }
    }
</style>
