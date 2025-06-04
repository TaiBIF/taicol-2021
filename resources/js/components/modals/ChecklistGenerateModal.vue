<template>
    <div>
        <div class="px-16 py-12 shadow-md bg-white min-h-6  w-[768px]">

            <div>
                <p class="title text-center">{{ $t('classification.importToMyChecklist') }}</p>
                <div v-if="isLoading" class="flex w-full items-center justify-center">
                    <loading></loading>
                </div>
                <div class="form field">
                    <label class="label is-marked" v-text="$t('classification.checklistTitle')"/>
                    <general-input v-model="title" :disabled="isLoading" />
                </div>
            </div>

            <label v-if="!isLoading" class="label mt-[1rem]" v-text="$t('classification.checklistPreview')"/>
            <div v-if="!isLoading" class="box has-background-light h-full">
                <template v-for="(usages, index) in usageGroups">
                    <template v-for="(usage, index) in usages">
                        <div :class="{ 'is-indent': checkIfIndent(usage.status)}" 
                             class="usage-row"
                             tabindex="-1">
                            <div class="usage-content">
                                <span
                                    v-if="(usage.status === '' || usage.status === 'accepted') &&
                                        usage.taxonName.rank.order < speciesRank.order &&
                                        !checkIfIndent(usage.status)"
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
                        </div>
                    </template>
                    <hr v-if="parseInt(index) !== usageGroupsCount" class="my-2 group-border"/>
                </template>
            </div>
        </div>
        <div class="sticky bottom-0 p-4 bg-white border-t">
            <div class="buttons is-right">
                <button class="button" v-on:click="closeModal"  
                    :class="{ 'is-loading': isUsageLoading }"
                    :disabled="isUsageLoading">{{ $t('common.cancel') }}</button>
                <button class="button" v-on:click="() => onSubmit(false)" 
                     :class="{ 'is-loading': isUsageLoading }"
                     :disabled="isUsageLoading">
                    {{ $t('common.add') }}
                </button>
            </div>
        </div>
    </div>
</template>
<script>
import GeneralInput from '../GeneralInput.vue';
import Loading from '../Loading.vue';
import { mapGetters } from 'vuex';
import UsagePreview from '../UsagePreview.vue';
import { openNotify } from '../../utils';
import indications from '../selects/map/indications';

export default {
    props: {
        form: {
            type: Object,
            required: true,
        },
    },
    data() {

        return {
            title: '',
            usageGroups: [],
            isLoading: true,
            tmpChecklistId: null,
            isUsageLoading: true
        };
    },
    mounted() {
        
        this.axios.get('/selected-usages', {
                params: {
                    references: this.form.referenceIds,
                    onlyInTaiwan: this.form.onlyInTaiwan,
                    excludeCultured: this.form.excludeCultured,
                    higherTaxa: this.form.higherTaxa.map((f) => (f.taxonId)),
                    taxonIds: this.form.taxonIds,
                    method: this.form.targetMethod.id
                },
            })
            .then(({ data: { data, message } }) => {

                if (data !== null){

                    this.tmpChecklistId = data;
                    this.loadUsages(data);
                } else if (message) {
                
                    openNotify(message, 'is-danger');
                    this.closeModal();

                } else {
                    openNotify('無對應學名使用，請重新設置篩選條件', 'is-danger');
                    this.closeModal();
                }
                this.isLoading = false;

            });


    },
    computed: {
        ...mapGetters({
            speciesRank: 'rank/getSpeciesRank',
            user: 'auth/user',
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
        checkIfIndent(status) {
            if (status !== 'accepted') {
                return true
            } else {
                return false
            }
        },
        closeModal() {

            // 這邊要把tmp_checklist_清空

            if (this.tmp_checklist_id !== null){

                this.axios.post(`/clear/checklist/usages`, {
                    tmpChecklistId: this.tmpChecklistId,
                })
            }

            this.$store.commit('closeModal');
        },
        onSubmit() {

            this.isUsageLoading = true;


            this.axios.post(`/import/checklist/usages`, {
                tmpChecklistId: this.tmpChecklistId,
                title: this.title,
                // 下面是為了存edit log的資訊
                references: this.form.referenceIds,
                onlyInTaiwan: this.form.onlyInTaiwan,
                excludeCultured: this.form.excludeCultured,
                higherTaxa: this.form.higherTaxa.map((f) => (f.taxonId)),
                taxonIds: this.form.taxonIds,
                method: this.form.targetMethod.id,
                county: this.form.targetCounty?.name,
                municipality: this.form.targetMunicipality?.name
            })
            .then(({ data: { data } }) => {

               if (data != null)  {
                    this.closeModal();
                    this.$router.push({ name: 'namespace-usage-list', params: { id: data } });
                } else {
                    openNotify('發生錯誤，請通知管理員', 'is-danger');

                }

                this.isUsageLoading = false;

            })


    },


        async loadUsages(tmp_checklist_id) {
            try {
                this.isUsageLoading = true;

                let offset = 0;
                const resp = await this.axios.get(`/tmp-usages?tmp_checklist_id=${tmp_checklist_id}&offset=${offset}`);
                let data = resp.data.data;
                let groupCount = resp.data.groupCount;
                this.usageGroups = data;

                let nowDate = new Date()
                nowDate = nowDate.toISOString().split('T')[0]
                this.isUsageLoading = false;
                this.title = `${data[1][0]['taxonName']['name']}-${this.user.name}-${nowDate}`;
                if (groupCount > 100) {
                    for (let i = 100; i <= groupCount; i += 100) {
                        await this.loadUsageWithDelay(i);
                    }
                }
            } catch (error) {
                console.error("load usage:", error);
            }
        },
        async loadUsageWithDelay(tmp_checklist_id,offset) {
            try {
                const resp = await this.axios.get(`/tmp-usages?tmp_checklist_id=${tmp_checklist_id}&offset=${offset}`);

                let data = resp.data.data;

                this.usageGroups = {...this.usageGroups, ...data};
                this.usageGroups = {...this.usageGroups };

                // wait 1 sec avoid Too Many Attempts
                await new Promise(resolve => setTimeout(resolve, 1000));
            } catch (error) {
                console.error(`loadUsageWithDelay ${offset}:`, error);
            }
        },
        async load() {
            try {
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
.section {
    padding-left: 4rem;
    padding-right: 4rem;
}

.group-border {
    border-bottom: 1px solid lightgrey;
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
