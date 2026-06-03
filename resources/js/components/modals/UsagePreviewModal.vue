<template>
    <div>
        <div class="px-16 py-12 shadow-md bg-white min-h-6  w-[768px]">

            <div>
                <!-- 這邊改顯示文獻title -->
                <p class="text-center">{{ this.reference.title }}</p>
                <span class="help has-text-grey-light text-center" v-text="this.reference.subtitle"></span>
                <div v-if="isLoading" class="flex w-full items-center justify-center">
                    <loading></loading>
                </div>
            </div>
            <div v-if="!isLoading" class="flex items-center justify-end mt-[1rem] mb-[1rem]">
                <!-- <label class="label mb-0" v-text="$t('classification.checklistPreview')"/> -->
                <button class="button is-small" @click="isSimple = !isSimple">
                    {{ isSimple ? $t('namespace.listDetail') : $t('namespace.listSimple') }}
                </button>
            </div>
            <div v-if="!isLoading" class="box has-background-light h-full">

                <template v-for="(usages, index) in usageGroups">
                    <template v-for="(usage, index) in usages">
                        <div :class="{
                                            'is-title': usage.isTitle,
                                            'is-indent': usage.isIndent,
                                        }" class="usage-row"
                                tabindex="-1">
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
                                    :is-simple="isSimple"
                                    :per-usages="usage.perUsages"
                                    :status="usage.status"
                                    :taxon-name="usage.taxonName"
                                    :type-name="usage.typeName"
                                    :type-specimens="usage.typeSpecimens"
                                    class="inline-block"
                                />
                            </div>
                        </div>
                    </template>
                </template>


            </div>
        </div>
        <div class="sticky bottom-0 p-4 bg-white border-t">
            <div class="buttons is-right">
                <button class="button" v-on:click="closeModal">{{ $t('common.close') }}</button>
            </div>
        </div>
    </div>
</template>
<script>
import GeneralInput from '../GeneralInput.vue';
import Loading from '../Loading.vue';
import { mapGetters } from 'vuex';
import UsagePreview from '../UsagePreview.vue';
import indications from '../selects/map/indications.js';
import { subTitle, title } from '../../utils/preview/reference';

export default {
    props: {
        referenceId: {
            type: Number,
            required: true,
        },
    },
    data() {

        return {
            reference: {},
            title: '',
            usageGroups: [],
            isLoading: true,
            isSimple: true,
        };
    },
    mounted() {
        this.loadReference();
    },
    computed: {
        ...mapGetters({
            speciesRank: 'rank/getSpeciesRank',
        }),
        usageGroupsCount() {
            return Object.values(this.usageGroups);
        },

    },
    methods: {
        getIndications(indicationArray) {
            return indicationArray?.map(
                (abbreviation) => indications.find((i) => i.abbreviation === abbreviation),
            ).filter(Boolean);
        },
        // checkIfIndent(status) {
        //     if (status !== 'accepted') {
        //         return true
        //     } else {
        //         return false
        //     }
        // },
        closeModal() {
            this.$store.commit('closeModal');
        },
        async loadUsages() {
            try {
                let offset = 0;
                const resp = await this.axios.get(`/references/${this.referenceId}/usages?offset=${offset}`);
                let data = resp.data.data;
                this.isLoading = false;
                let groupCount = resp.data.groupCount;
                this.usageGroups = data;

                if (groupCount > 100) {
                    for (let i = 100; i <= groupCount; i += 100) {
                        await this.loadUsageWithDelay(i);
                    }
                }
            } catch (error) {
                console.error("load usage:", error);
            }
        },
        async loadUsageWithDelay(offset) {
            try {
                const resp = await this.axios.get(`/references/${this.referenceId}/usages?offset=${offset}`);
                let data = resp.data.data;

                this.usageGroups = {...this.usageGroups, ...data};
                this.usageGroups = {...this.usageGroups };

                // wait 1 sec avoid Too Many Attempts
                await new Promise(resolve => setTimeout(resolve, 1000));
            } catch (error) {
                console.error(`loadUsageWithDelay ${offset}:`, error);
            }
        },
        async loadReference() {
            try {
                const { data: { data } } = await this.axios.get(`/references/${this.referenceId}`);

                this.reference = data;
                this.reference.id = parseInt(this.referenceId);
                this.reference.language = data.language ? { id: data.language } : null;
                this.reference.title = title(data);
                this.reference.subtitle = subTitle(data);

                this.loadUsages();

            } catch (error) {
                console.error("load reference:", error);
            }
        },
    },
    components: {
        GeneralInput,
        Loading,
        UsagePreview
    }
};
</script>
<style lang="scss">
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
