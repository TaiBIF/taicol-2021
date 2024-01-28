<template>
    <page :preload="onPreload">
        <div class="flex h-full max-w-screen-xl my-0 mx-auto">
            <div class="shrink-0 w-[60%]">
                <reference-view :show-import="true" v-bind="reference"></reference-view>
            </div>
            <div v-if="reference.type !== 4" class="grow flex flex-col gap-4">
                <div>
                    <p class="text-xl font-bold is-5 is-inline-block">
                        {{ $t('reference.nameInReference') }}
                    </p>
                    <router-link
                        :to="{name: 'reference-usages', params: {id: this.$route.params.id}}"
                        class="button is-text float-right">
                        {{ $t('reference.viewDetails') }}
                    </router-link>
                </div>
                <div class="box has-background-light h-full overflow-y-scroll">
                    <template v-for="(usages, index) in usageGroups">
                        <template v-for="(usage, index) in usages">
                            <div :class="{
                                             'is-title': usage.isTitle,
                                             'is-indent': usage.isIndent,
                                         }" class="usage-row"
                                 tabindex="-1"
                            >
                                <router-link :to="{name: 'taxon-name-page', params: {id: usage.taxonName.id}}">
                                    <div class="usage-content">
                                        <span
                                            v-if="(usage.status === '' || usage.status === 'accepted') &&
                                                usage.taxonName.rank.order < speciesRank.order &&
                                                !usage.isIndent"
                                            class="font-bold">
                                            {{ usage.taxonName.rank.display['en-us'] }}
                                        </span>
                                        <usage-preview
                                            ref="nameRemark"
                                            :indications="getIndications(usage.properties.indications)"
                                            :is-simple="true"
                                            :per-usages="usage.perUsages"
                                            :status="usage.status"
                                            :taxon-name="usage.taxonName"
                                            :type-name="usage.typeName"
                                            :type-specimens="usage.typeSpecimens"
                                            class="inline-block"
                                        />
                                    </div>
                                </router-link>
                            </div>
                        </template>
                        <hr v-if="parseInt(index) !== usageGroupsCount" class="my-4"/>
                    </template>
                </div>
            </div>
        </div>
    </page>
</template>
<script>
import { mapGetters } from 'vuex';
import Breadcrumb from '../components/Breadcrumb.vue';
import ReferenceView from '../components/views/ReferenceView.vue';
import { subTitle, title } from '../utils/preview/reference';
import AuthorName from '../components/AuthorName.vue';
import UsagePreview from '../components/UsagePreview.vue';
import indications from '../components/selects/indications';
import Page from './Page.vue';

export default {
    data() {
        return {
            usageGroups: [],
            reference: {
                id: 0,
                type: 0,
                authors: [],
                publishYear: '',
                title: '',
                subtitle: '',
                language: null,
                note: '',
                properties: {},
            },
        };
    },
    methods: {
        async onPreload() {
            try {
                const { data: { data } } = await this.axios.get(`/references/${this.$route.params.id}`);

                this.axios.get(`/references/${this.$route.params.id}/usages`)
                    .then(({ data: { data } }) => {
                        this.usageGroups = data;
                    });

                this.reference = data;
                this.reference.id = parseInt(this.$route.params.id, 10);
                this.reference.language = data.language ? { id: data.language } : null;
                this.reference.title = title(data);
                this.reference.subtitle = subTitle(data);
                return 200;
            } catch ({ status }) {
                return status;
            }
        },
        getIndications(indicationArray) {
            return indicationArray?.map(
                (abbreviation) => indications.find((i) => i.abbreviation === abbreviation),
            ).filter(Boolean);
        },
    },
    computed: {
        ...mapGetters({
            speciesRank: 'rank/getSpeciesRank',
        }),
        usageGroupsCount() {
            return Object.values(this.usageGroups);
        },
    },
    components: {
        Page,
        UsagePreview,
        AuthorName,
        ReferenceView,
        Breadcrumb,
    },
};
</script>
<style lang="scss" scoped>
a {
    color: $black;
}

.container {
    padding-top: 1rem;

    .body {
        padding-top: 1rem;
    }
}

hr {
    background-color: lightgray;
    height: 1px;
}

.usage-row {
    padding: .25rem 1rem;
    margin-bottom: .2rem;
    cursor: pointer;
    min-height: 1rem;
    display: flex;

    &:focus, &.selected {
        outline: none;
        background: $light-grey;
    }

    &.is-title {
        font-weight: bold;
    }

    &.is-indent {
        margin-left: 2rem;
    }

    .utitle {
        color: $orange;
    }

    .handle {
        margin-right: .5rem;
    }

    .usage-content {
        flex-grow: 1;
    }
}
</style>
