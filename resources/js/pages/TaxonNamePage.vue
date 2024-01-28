<template>
    <page :preload="onPreload">
        <tab-content :current="currentTab" :tabs="tabs" v-on:change-tab="onChangeTab">
            <template v-slot:title>
                <taxon-name-full-label :taxon-name="taxonName" :with-color="true" class="leading-2"/>
                {{ commonName ? commonName.name : '' }}
            </template>
            <template v-slot:content>
                <detail-view v-if=" currentTab === `info` || currentTab === ''" v-bind="taxonName"/>
                <accepted-view v-if="currentTab === `accepted`" :type="taxonName.nomenclature.group"/>
                <synonym-view v-if="currentTab === `synonym`" :taxon-name="taxonName"/>
                <homonym-view v-if="currentTab === `homonym`" :taxon-name="taxonName"/>
                <trivial-name v-if="currentTab === `names`" :taxon-name="taxonName"/>
                <reference-view v-if="currentTab === `in-reference`"/>
                <sub-taxon-names-view v-if="currentTab === `sub-taxon-name`" :taxon-name="taxonName"/>
                <common-names-view v-if="currentTab === `common-name`"
                                   :taxon-name-id="taxonName.id"></common-names-view>
            </template>
        </tab-content>
    </page>
</template>
<script>
import DetailView from '../components/views/taxonName/DetailView.vue';
import SynonymView from '../components/views/taxonName/SynonymView.vue';
import HomonymView from '../components/views/taxonName/HomonymView.vue';
import TrivialName from '../components/views/taxonName/TrivialName.vue';
import ReferenceView from '../components/views/taxonName/ReferenceView.vue';
import AcceptedView from '../components/views/taxonName/AcceptedView.vue';
import SubTaxonNamesView from '../components/views/taxonName/SubTaxonNamesView.vue';
import TaxonNameFullLabel from '../components/views/TaxonNameFullLabel.vue';
import TabContent from '../components/layout/TabContent.vue';
import Page from './Page.vue';
import CommonNamesView from '../components/views/taxonName/CommonNamesView.vue';

export default {
    data() {
        return {
            currentTab: this.$route.hash,
            tabs: [
                {
                    key: 'info',
                    title: this.$t('taxonName.taxonNameInfo'),
                    default: true,
                    display: true,
                },
                {
                    key: 'accepted',
                    title: this.$t('taxonName.accepted'),
                    display: false,
                },
                {
                    key: 'in-reference',
                    title: this.$t('taxonName.inReference'),
                    display: false,
                },
                {
                    key: 'homonym',
                    title: this.$t('taxonName.homonym'),
                    display: false,
                },
                {
                    key: 'synonym',
                    title: this.$t('taxonName.synonym'),
                    display: false,
                },
                {
                    key: 'sub-taxon-name',
                    title: this.$t('taxonName.subTaxonName'),
                    display: false,
                },
                {
                    key: 'common-name',
                    title: this.$t('taxonName.commonName'),
                    display: false,
                },
            ],
            commonName: null,
            taxonName: {
                usage: {},
                authors: [],
                exAuthors: [],
            },
        };
    },
    mounted() {
        this.currentTab = this.$route.hash.replace('#', '');
    },
    methods: {
        async onPreload() {
            try {
                const {
                    data: {
                        taxonName,
                        commonName,
                        tabs,
                    },
                } = await this.axios.get(`/taxon-names/${this.$route.params.id}`);

                this.taxonName = taxonName;
                this.commonName = commonName;

                tabs.forEach(({ key, display }) => {
                    this.onToggle(key, display);
                });

                return 200;
            } catch ({ status }) {
                return status;
            }
        },
        onChangeTab(key) {
            this.currentTab = key;
            this.$router.replace({ hash: key });
        },
        onToggle(key, status) {
            const tab = this.tabs.find((t) => t.key === key);
            if (tab) {
                tab.display = status;
            }
        },
    },
    components: {
        CommonNamesView,
        Page,
        TabContent,
        TaxonNameFullLabel,
        SubTaxonNamesView,
        AcceptedView,
        DetailView,
        SynonymView,
        HomonymView,
        TrivialName,
        ReferenceView,
    },
};
</script>
