<template>
    <div class="w-full h-full overflow-y-scroll">
        <div class="container py-2 ">
            <router-link :to="{name:'reference-page', params: {id: this.$route.params.id}}"
                         class="button is-text is-pulled-right">
                {{ $t('namespace.listSimple') }}
            </router-link>
            <div class="py-5 columns">
                <div class="column is-1 flex justify-center items-center">
                    <label class="title is-5">文獻</label>
                </div>
                <div class="column is-11">
                    <span class="has-text-weight-bold is-5">{{ reference.title }}</span>
                    <br/>
                    <span class="has-text-weight-normal is-5">{{ reference.subtitle }}</span>
                </div>
            </div>

            <div class="body py-3">
                <div class="usage-container">
                    <usages-view :group="usageGroups"/>
                </div>
            </div>
        </div>
    </div>
</template>

<script>
import { mapGetters } from 'vuex';
import FavoriteButton from '../components/FavoriteButton.vue';
import UsagePreview from '../components/UsagePreview.vue';
import UsagePropertyTags from '../components/views/UsagePropertyTags.vue';
import { subTitle, title } from '../utils/preview/reference';
import TaxonNameFullLabel from '../components/views/TaxonNameFullLabel.vue';
import UsagesView from '../components/views/UsagesView.vue';

export default {
    components: {
        UsagesView,
        TaxonNameFullLabel,
        UsagePropertyTags,
        UsagePreview,
        FavoriteButton,
    },
    data() {
        return {
            usageGroups: [],
            reference: {},
            referenceUrl: `/references/${this.$route.params.id}`,
        };
    },
    mounted() {
        this.axios.get(`/references/${this.$route.params.id}`)
            .then(({ data: { data } }) => {
                this.reference = data;
                this.reference.id = parseInt(this.$route.params.id);
                this.reference.language = data.language ? { id: data.language } : null;
                this.reference.title = title(data);
                this.reference.subtitle = subTitle(data);

                this.formStatus = this.$c.PAGE_IS_SUCCESS;
                this.$store.commit('breadcrumb/SET_ITEMS', [{
                    url: this.referenceUrl,
                    name: `文獻: ${this.reference.title}`,
                    to: {
                        name: 'reference-page',
                        params: {
                            id: this.reference.id,
                        },
                    },
                }, {
                    url: '#',
                    name: '詳細異名表',
                }]);
            });

        this.axios.get(`/references/${this.$route.params.id}/usages`)
            .then(({ data: { data } }) => {
                this.usageGroups = Object.values(data);
            });
    },
    computed: {
        ...mapGetters({
            speciesRank: 'rank/getSpeciesRank',
        }),
    },
};
</script>

<style lang="scss" scoped>
.trapezoid {
    border-color: transparent transparent #f4f4f5 transparent;
    border-width: 0 2rem 2rem 0;
}

.group {
    .usage-row {
        border-bottom: 1px solid lightgrey;

        .usage-content {
            &.is-indent {
                margin-left: 2rem;
            }
        }
    }
}
</style>
