<template>
    <div>
        <table class="table is-fullwidth is-hoverable has-text-left in-tab-content">
            <thead>
            <tr>
                <th>俗名</th>
                <th>
                    <sort-button :id="'language'" :direction="direction" :on-click="onSortBy" :sortby="sortby">
                        語言
                    </sort-button>
                </th>
                <th>
                    地區
                </th>
                <th>出處</th>
                <th>
                    <sort-button :id="'publish_year'" :on-click="onSortBy" :sortby="sortby" :direction="direction">
                        {{ $t('forms.reference.publishYear') }}
                    </sort-button>
                </th>
            </tr>
            </thead>
            <tbody>
            <tr v-for="commonName in commonNames">
                <td>{{ commonName.name }}</td>
                <td>
                    {{ $t(`forms.reference.languages.${commonName.language}`) }}
                </td>
                <td>{{ commonName.area }}</td>
                <td>
                    <router-link :to="`/references/${commonName.reference.id}`" class="my-link">
                        {{ commonName.reference.subtitle }}
                    </router-link>
                </td>
                <td>{{ commonName.reference.publishYear }}</td>
            </tr>
            <tr>
                <td colspan="8">
                    <p v-if="lastPage < currentPage" class="text-gray-300 text-center">資料最底，共計 {{ total }} 筆</p>
                    <span class="caption"></span>
                    <loading v-if="isLoading"></loading>
                </td>
            </tr>
            </tbody>
        </table>
    </div>
</template>
<script>
import SortButton from '../../SortButton';
import Loading from '../../Loading';

export default {
    props: {
        taxonNameId: {
            type: Number,
            required: true,
        },
    },
    data() {
        return {
            direction: '',
            sortby: '',
            currentPage: 0,
            lastPage: 0,
            perPage: 20,
            total: 0,
            commonNames: [],
            isLoading: false,
        }
    },
    mounted() {
        const app = this;
        app.intersectionObserver = new IntersectionObserver(function (entries) {
            if (entries[0].intersectionRatio > 0) {
                app.fetchData();
            }
        }, {rootMargin: '0px 0px 500px 0px'});
        app.intersectionObserver.observe(document.querySelector('.caption'));
    },
    methods: {
        async fetchData() {
            if ((this.lastPage !== 0 && this.lastPage < this.currentPage) || this.isLoading) {
                return;
            }

            this.isLoading = true;

            try {
                const page = this.currentPage + 1;
                const {
                    data: {data, lastPage, total}
                } = await this.axios.get(`/taxon-names/${this.taxonNameId}/common-names`, {
                    params: {
                        page,
                        direction: this.direction,
                        sortby: this.sortby,
                        perPage: this.perPage,
                    }
                });

                this.total = total;
                this.lastPage = lastPage;
                this.currentPage = page;
                this.commonNames = this.commonNames.concat(data);

                if (this.commonNames.length > 0) {
                    this.$emit('toggle', 'common-name', true);
                }
            } catch (e) {
                this.$emit('toggle', 'common-name', false);
            }

            this.isLoading = false;
        },
        onSortBy(column, direction) {
            this.sortby = column;
            this.direction = direction;
            this.commonNames = [];
            this.currentPage = 1;
            this.fetchData();
        },
    },
    components: {SortButton, Loading},
}
</script>
