<template>
    <div class="form">
        <div class="columns">
            <div class="column is-12">
                <i class="fas fa-info-circle"></i>
                <span v-html="$t('taxonName.commonNameInfo')"></span>
            </div>
        </div>
        <div class="columns">
            <div class="column is-8">

                <div class="field">
                    <label class="label is-marked inline-block">
                        {{ $t('taxonName.name') }}
                    </label>

                    <div class="columns is-multiline">
                        <div class="column is-6">
                            <taxon-name-select 
                                v-model="taxonName" 
                                :errors="errors['taxonNameId']"
                                :key="selectKey">
                            </taxon-name-select>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="columns">
            <div class="column is-12">
                <div class="field">
                    <label class="label"
                           v-text="$t('taxonName.commonName')"/>
                    <div v-for="(commonName, index) in commonNames" class="box">
                        <a class="is-pulled-right close-button"
                            v-if="index != 0"
                           v-on:click="() => onRemoveCommonName(index)">
                        </a>
                        <div class="columns">
                            <div class="column is-5">
                                <label class="label is-marked"
                                       v-text="$t('usage.commonName')"/>
                                <general-input v-model="commonName['name']" 
                                               :errors="errors[`propertiesCommonNames${index}Name`]"/>
                            </div>
                            <div class="column is-3">
                                <label class="label is-marked"
                                       v-text="$t('usage.language')"/>
                                <language-select v-model="commonName['language']"
                                                 :errors="errors[`propertiesCommonNames${index}Language`]"
                                                 :is-use-key-id="true"/>
                                                 <!--  -->
                            </div>
                            <div class="column is-3">
                                <label class="label"
                                       v-text="$t('usage.area')"/>
                                <general-input v-model="commonName['area']"/>
                            </div>
                        </div>
                    </div>

                    <button class="button is-text"
                            v-on:click="onAddCommonName">
                        <i class="fa fa-plus-circle"></i>
                        &nbsp;&nbsp;{{ $t('usage.commonName') }}
                    </button>
                </div>
            </div>
        </div>


            <!-- 編輯紀錄 -->
            <div class="columns">
            <div class="column is-8">
                <p class="text-[15px] mb-2 is-5 is-inline-block has-text-grey"
                    v-on:click="toggleEditLog()">
                    {{ $t('common.editHistory') }} <a><i class="fas" :class="{'fa-chevron-down': editLogHidden, 'fa-chevron-up': !editLogHidden}"></i></a>
                </p>
                <div :class="{ hidden: editLogHidden }">
                    <table class="table text-[14px] is-fullwidth max-w-full has-text-grey">
                        <thead class="font-bold">
                        <tr>
                            <th class="w-[80px] has-text-grey" v-text="$t('common.editDate')"/>
                            <th class="w-[80px] has-text-grey" v-text="$t('common.editAction')"/>
                            <th class="w-[270px] has-text-grey" v-text="$t('common.editItem')"/>
                            <th class="w-[90px] has-text-grey" v-text="$t('common.editBy')"/>
                        </tr>
                        </thead>
                        <tbody>
                        <tr v-for="editLog in editLogs">
                            <td>{{ editLog.createdAt }}</td>
                            <td>{{ editLog.action }}</td>
                            <td>
                                <router-link
                                    :to="{name: 'taxon-name-page', params: {id: editLog.editedTaxonNameId}}"
                                    class="my-link"
                                  >
                                  {{ editLog.item }}
                                </router-link>


                            </td>
                            <td>{{ editLog.by }}</td>
                        </tr>
                        </tbody>
                    </table>
                    <button v-if=" logMore === true " v-on:click="fetchEditLog('commonname',logOffset)"  class="button is-small"> more +</button>
                </div>
            </div>
        </div>
    </div>
</template>

<script>
import { debounce } from 'lodash';
import TaxonNameSelect from '../selects/TaxonNameSelect.vue';
import GeneralInput from '../GeneralInput.vue';
import LanguageSelect from '../selects/LanguageSelect.vue';

export default {
    // props: {
    //     onAfterSubmit: {
    //         type: Function,
    //         required: true,
    //     },
    // },
    props: {
        onContinueEditing: {
            type: Function,
            default: null,
        },
    },
    mounted() {
        this.onAddCommonName();
        this.fetchEditLog('commonname', 0);
    },
    data() {
        return {
            taxonName: null,
            commonNames: [],
            errors: {},
            editLogs: [],
            logMore: false,
            logOffset: 0,
            editLogHidden: true,
            selectKey: 0
        };
    },
    computed: {
        formData() {
            return {
                taxonNameId: this.taxonName?.id,
                properties: { commonNames: this.commonNames}
            }
        },
    },
    watch: {
    },
    methods: {
        toggleEditLog(){
            this[`editLogHidden`] = !this[`editLogHidden`];
        },
        fetchEditLog(log_type, offset) {
            this.axios.get(`/edit-logs?log_type=${log_type}&offset=${offset}`)
                .then(({ data: { editLogs, logMore, logOffset }  }) => {
                    this[`editLogs`].push(...editLogs);
                    this[`logMore`] = logMore;
                    this[`logOffset`] = logOffset;
                });
        },
        onAddCommonName() {
            this.commonNames.push({
                name: '',
                language: null,
                area: '',
            });
        },
        onRemoveCommonName(index) {
            this.commonNames.splice(index, 1);
        },
        submit: debounce(function () {
            this.axios({
                method: 'POST',
                url: '/common-name',
                data: { ...this.formData},
            }).then(({ data }) => {
                
                // this.onAfterSubmit(data);

                this.$store.commit('openModal', {
                    component: () => import('../modals/ConfirmCommonNameModal.vue'),
                    props: {
                        taxonNameId: this.taxonName?.id,
                        onContinueEditing: this.onContinueEditing
                    },
                });

                this.taxonName = null;
                this.selectKey++;
                this.commonNames = [];
                this.onAddCommonName();
                this.editLogs = [];
                this.fetchEditLog('commonname',0);

            }).catch(({ status, message, errors }) => {
                this.errors = errors;
                if (this.onContinueEditing) {
                    this.onContinueEditing();
                }
            });
        }),
    },
    components: {
        TaxonNameSelect,
        LanguageSelect,
        GeneralInput,
    },
};
</script>
