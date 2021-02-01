<template>
    <div>
        <div class="searchbar">
            <div class="columns">
                <div class="column is-5-desktop round-form">
                    <auto-complete :isSmall="true" :results="results" v-model="keyword"/>
                </div>
                <div class="column search-options">
                    搜尋選項
                    &nbsp;&nbsp;
                    <label class="checkbox">
                        <input type="checkbox"> 全部
                    </label>

                    <label class="checkbox">
                        <input type="checkbox"> 學名
                    </label>

                    <label class="checkbox">
                        <input type="checkbox"> 文獻
                    </label>

                    <label class="checkbox">
                        <input type="checkbox"> 命名者
                    </label>

                    <label class="checkbox">
                        <input type="checkbox"> 俗名
                    </label>
                    <div class="clear has-text-grey-light">清除全部</div>
                </div>
            </div>
        </div>
        <div class="container">
            <div class="breadcrumb">
                <breadcrumb />
            </div>
            <table class="table is-fullwidth">
                <thead>
                    <tr>
                        <th>階層</th>
                        <th>接受學名<i class="fas fa-info-circle"></i></th>
                        <th>地位<i class="fas fa-info-circle"></i></th>
                        <th>發表文獻</th>
                        <th>發表年份</th>
                        <th>學名</th>
                        <th>俗名</th>
                        <th>界<i class="fas fa-info-circle"></i></th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="a in [1,1,1,1,1,1,1,1,1,1,1,1,1,1,1,1,1,1,1,1,1,1,1,1,1,1,1,1,1,1]">
                        <td>1854</td>
                        <td>Genus</td>
                        <td>*</td>
                        <td>List Spec. lepid. Insects Colln Br. Mus. 2: 547</td>
                        <td>Sesapa</td>
                        <td>Walker, 1854</td>
                        <td>瑟美苔蛾屬</td>
                        <td>Animalia 動物界</td>
                    </tr>
                </tbody>

            </table>
        </div>
        <br/>
    </div>
</template>
<script>
    import AutoComplete from './../components/AutoComplete';
    import Breadcrumb from '../components/Breadcrumb';

    export default {
        components: {
            AutoComplete,
            Breadcrumb,
        },
        data() {
            return {
                keyword: '',
                results: [
                    'asdf',
                    'asdfasdfasdf'
                ]
            }
        }
    }
</script>
<style lang="scss" scoped>
    .searchbar {
        padding: 0 1rem;
        background-color: $grey;
        color: white;
        padding-bottom: .5rem;
        position: fixed;
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

    .container {
        padding-top: 5rem;
        .breadcrumb {
            position: sticky;
            top: calc(#{$navbar-height} + 4.5rem);
            z-index: 60;
            background: white;
            margin-bottom: 0;
        }

        .table {
            position: relative;
            z-index: 10;
            th {
                background-color: white;
                z-index: 60;
                position: sticky;
                top: calc(#{$navbar-height} + 4.5rem + 3rem);
                color: $grey;
            }
        }
    }
</style>
