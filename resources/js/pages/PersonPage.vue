<template>
    <page :preload="preload">
        <tab-content :current="currentTab" :tabs="tabs" v-on:change-tab="onChangeTab">
            <template v-slot:title>
                <p class="leading-2 inline">{{ person.fullName }}</p>
            </template>
            <template v-slot:content>
                <info v-show="currentTab === 'info' || currentTab === ''" :person="person"></info>
                <references v-show="currentTab === 'reference'" v-on:hide="onHideTab"></references>
                <taxon-names v-show="currentTab === 'taxon-name'" v-on:hide="onHideTab"></taxon-names>
                <type-specimens v-show="currentTab === 'type-specimen'" v-on:hide="onHideTab"></type-specimens>
            </template>
        </tab-content>
    </page>
</template>
<script>
import AuthorName from '../components/AuthorName.vue';
import Page from './Page.vue';
import TabContent from '../components/layout/TabContent.vue';
import Info from '../components/views/person/Info.vue';
import References from '../components/views/person/References.vue';
import TaxonNames from '../components/views/person/TaxonNames.vue';
import TypeSpecimens from '../components/views/person/TypeSpecimens.vue';

export default {
    data() {
        return {
            person: null,
            currentTab: null,
            tabs: [
                {
                    key: 'info',
                    title: this.$t('person.tabs.info'),
                    default: true,
                    display: true,
                },
                {
                    key: 'taxon-name',
                    title: this.$t('person.tabs.taxonName'),
                    display: true,
                },
                {
                    key: 'reference',
                    title: this.$t('person.tabs.reference'),
                    display: true,
                },
                {
                    key: 'type-specimen',
                    title: this.$t('person.tabs.typeSpecimens'),
                    display: true,
                },
            ],
        };
    },
    mounted() {
        this.currentTab = this.$route.hash.replace('#', '');
    },
    methods: {
        async preload() {
            try {
                const { data } = await this.axios.get(`/persons/${this.$route.params.id}`);
                this.person = data;
                return 200;
            } catch (e) {
                return e.status;
            }
        },
        onChangeTab(key) {
            this.currentTab = key;
            this.$router.replace({ hash: key });
        },
        onHideTab(key) {
            const tab = this.tabs.find((t) => t.key === key);
            if (tab) {
                tab.display = false;
            }
        },
    },
    components: {
        TypeSpecimens,
        TaxonNames,
        References,
        Info,
        TabContent,
        Page,
        AuthorName,
    },
};
</script>
