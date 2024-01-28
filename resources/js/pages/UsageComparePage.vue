<template>
    <div class="container h-full flex flex-col">
        <div class="py-2 flex">
            <div class="flex-1"></div>
            <div class="items-end">
                <button :class="{'bg-gray-200': isSimple}" class="button" v-on:click="() => isSimple = !isSimple">
                    <i class="far fa-eye mr-2"></i> {{ $t('namespace.listSimple') }}
                </button>
                <button class="button" v-on:click="onChangeReference">
                    <i class="fas fa-exchange-alt mr-2"></i> {{ $t('common.swapCompare') }}
                </button>
            </div>
        </div>
        <div class="flex grow overflow-hidden pb-4">
            <div class="w-1/2 overflow-y-auto">
                <div class="sticky top-0 z-20 bg-white p-6">
                    <div class="flex">
                        <div class="text-center px-4 py-2 font-bold no-wrap">
                            {{ $t('common.chooseReference') }}
                        </div>
                        <div class="p-2">
                            <static-reference-select v-model="reference1"
                                                     :references="references"/>
                        </div>
                    </div>
                    <div v-if="reference1" class="flex">
                        <div class="text-center py-4 px-6 font-bold">
                            <label>{{ $t('common.reference') }}</label>
                        </div>
                        <div class="flex-1">
                            <span class="has-text-weight-bold is-5">{{ reference1.title }}</span>
                            <br/>
                            <span class="has-text-weight-normal is-5">{{ reference1.subtitle }}</span>
                        </div>
                    </div>
                    <div v-else class="text-center text-gray-300 text-lg">
                        {{ $t('common.chooseReference') }}
                    </div>
                </div>
                <div class="px-5">
                    <div class="usage-container">
                        <usages-view :group="r1usages" :is-simple="isSimple"></usages-view>
                    </div>
                </div>
            </div>
            <div class="w-1/2 overflow-y-auto border-l-2">
                <div class="sticky top-0 z-10 bg-white p-6">
                    <div class="flex">
                        <div class="text-center px-4 py-2 font-bold no-wrap">
                            {{ $t('common.chooseReference') }}
                        </div>
                        <div class="p-2">
                            <static-reference-select v-model="reference2"
                                                     :references="references"/>
                        </div>
                    </div>
                    <div v-if="reference2" class="flex">
                        <div class="text-center py-4 px-6 font-bold">
                            <label>{{ $t('common.reference') }}</label>
                        </div>
                        <div class="flex-1">
                            <span class="has-text-weight-bold is-5">{{ reference2.title }}</span>
                            <br/>
                            <span class="has-text-weight-normal is-5">{{ reference2.subtitle }}</span>
                        </div>
                    </div>
                    <div v-else class="text-center text-gray-300 text-lg">
                        {{ $t('common.chooseReference') }}
                    </div>
                </div>
                <div v-if="reference2" class="px-5">
                    <div class="usage-container">
                        <usages-view :group="r2usages" :is-simple="isSimple"></usages-view>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script>
import FavoriteButton from '../components/FavoriteButton.vue';
import UsagesView from '../components/views/UsagesView.vue';
import StaticReferenceSelect from '../components/StaticReferenceSelect.vue';
import Breadcrumb from '../components/Breadcrumb.vue';
import TaxonNameFullLabel from '../components/views/TaxonNameFullLabel.vue';

export default {
    components: {
        TaxonNameFullLabel, Breadcrumb, StaticReferenceSelect, UsagesView, FavoriteButton,
    },
    data() {
        return {
            usageGroups: [],
            references: [],
            reference1: null,
            r1usages: [],
            reference2: null,
            r2usages: [],
            referenceUrl: `/references/${this.$route.params.id}`,
            isSimple: false,
            taxonName: null,
        };
    },
    mounted() {
        this.axios.get(`/taxon-names/${this.$route.params.id}/references`)
            .then(({ data: { data } }) => {
                this.references = Array.from(new Set(data.map((d) => d.referenceId)))
                    .map((id) => ({
                        ...data.find((d) => d.referenceId === id)?.reference,
                    }));

                this.reference1 = this.references[0] || null;
                this.reference2 = this.references[1] || null;
            });
    },
    methods: {
        onChangeReference() {
            const temp = this.reference1;
            this.reference1 = this.reference2;
            this.reference2 = temp;
        },
        showProperties(p) {
            return (
                p.isFossil
                || p.isTerrestrial
                || p.isFreshwater
                || p.isBrackish
                || p.isMarine
                || p.commonNames?.length
                || p.note
            );
        },
    },
    watch: {
        reference1(r) {
            if (r === null) {
                this.r1usages = {};
                return;
            }

            this.axios.get(`/references/${r.id}/usages`)
                .then(({ data: { data } }) => {
                    this.r1usages = data;
                });
        },
        reference2(r) {
            if (r === null) {
                this.r2usages = {};
                return;
            }

            this.axios.get(`/references/${r.id}/usages`)
                .then(({ data: { data } }) => {
                    this.r2usages = data;
                });
        },
    },
};
</script>

<style lang="scss" scoped>
.page-h-limit {
    max-height: calc(100vh - #{$navbar-height} - 3.5rem);
    min-height: calc(100vh - #{$navbar-height} - 3.5rem);
    overflow-y: hidden;
}
</style>
