<template>
    <div>
        <div class="columns">
            <div class="column is-6">
                <div class="field">
                    <label class="label is-marked"
                           v-text="$t('forms.typeSpecimen.use')"/>
                    <div class="control">
                        <use-type-select v-model="form.use"
                                         :errors="errors[`typeSpecimens${index}Use`]"/>
                    </div>
                </div>
            </div>
            <div class="column is-6">
                <div class="field">
                    <label class="label is-marked"
                           v-text="$t('forms.typeSpecimen.kind')"/>
                    <div class="control">
                        <kind-type-select v-model="form.kind"
                                          :errors="errors[`typeSpecimens${index}Kind`]"
                                          v-on:input="onUpdateKind"
                        />
                    </div>
                </div>
            </div>
        </div>
        <template v-if="form.kind === 1">
            <div class="columns">
                <div class="column is-4">
                    <div class="field">
                        <label class="label"
                               v-text="$t('forms.typeSpecimen.sex')"/>
                        <sex-type v-model="form.sex"/>
                    </div>
                </div>
                <div class="column is-4">
                    <div class="field">
                        <label class="label is-marked"
                               v-text="$t('forms.typeSpecimen.country')"/>
                        <country-select v-model="form.country"
                                        :errors="errors[`typeSpecimens${index}Country`]"/>
                    </div>
                </div>
                <div class="column is-4">
                    <div class="field">
                        <label class="label" v-text="$t('forms.typeSpecimen.locality')"/>
                        <input v-model="form.locality" class="input" type="text"/>
                    </div>
                </div>
            </div>
            <div class="columns">
                <div class="column is-6">
                    <div class="field">
                        <label class="label"
                               v-text="$t('forms.typeSpecimen.localityVerbatim')"/>
                        <div class="control">
                            <input v-model="form.localityVerbatim" class="input" type="text"/>
                        </div>
                    </div>
                </div>
                <div class="column is-6 is-horizontal">
                    <div class="field">
                        <label class="label" v-text="$t('forms.typeSpecimen.collectDate')"/>
                        <div class="control is-flex" style="line-height: 2.5rem">
                            <input v-model="form.collectionYear" class="input" placeholder="YYYY" type="text"/>&nbsp;
                            <month-select v-model="form.collectionMonth" style="flex:0 0; flex-basis:80px"/>&nbsp;
                            <input v-model="form.collectionDay" class="input" placeholder="DD" type="text"/>
                        </div>
                    </div>
                </div>
            </div>
            <div class="columns">
                <div class="column is-9">
                    <div class="field">
                        <label class="label is-marked" v-text="$t('forms.typeSpecimen.collector')"/>
                        <person-select v-model="form.collectors"
                                       :errors="errors[`typeSpecimens${index}Collectors`]"/>
                    </div>
                </div>
                <div class="column is-3">
                    <div class="field">
                        <label class="label" v-text="$t('forms.typeSpecimen.collectorNumber')"/>
                        <input v-model="form.collectorNumber" class="input" type="text"/>
                    </div>
                </div>
            </div>
            <div v-if="form.use === 'lectotype'" class="columns">
                <div class="column is-9">
                    <div class="field">
                        <label class="label is-marked">
                            選定模式文獻
                            <span class="is-pulled-right" v-if="isShowIsPointed">
                                <label :for="`ispointed_${index}`">
                                    <input :id="`ispointed_${index}`" v-model="form.isDesignated" class="checkbox" type="checkbox"/>&nbsp;
                                    在此被指定
                                </label>
                            </span>
                        </label>

                        <reference-select v-model="form.lectoDesignatedReference"
                                          :on-after-create="onAfterCreateReference"/>
                    </div>
                </div>
                <div class="column is-3">
                    <div class="field">
                        <label class="label"
                               v-text="$t('forms.typeSpecimen.lectoCitePage')"/>
                        <general-input v-model="form.lectoCitePage"
                                       :errors="errors[`typeSpecimens${index}lectoCitePage`]"/>
                    </div>
                </div>
            </div>
            <div class="columns">
                <div class="column is-8">
                    <div class="field">
                        <label class="label is-marked"
                               v-text="$t('forms.typeSpecimen.herbarium')"/>
                        <general-input v-model="form.specimens[0].herbarium"
                                       :errors="errors[`typeSpecimens0Specimens0Herbarium`]"/>
                    </div>
                </div>
                <div class="column is-4">
                    <div class="field">
                        <label class="label"
                               v-text="$t('forms.typeSpecimen.accessionNumber')"/>
                        <general-input v-model="form.specimens[0].accessionNumber"/>
                    </div>
                </div>
            </div>
            <div class="columns">
                <div class="column is-12">
                    <div class="field">
                        <label class="label" v-text="$t('forms.typeSpecimen.url')"/>
                        <input v-model="form.url" class="input" type="text"/>
                    </div>
                </div>
            </div>
            <div class="columns">
                <div class="column is-12">
                    <div class="columns" v-if="extraSpecimens.length">
                        <p class="is-size-5 title" style="margin-top: 1rem;margin-bottom: 1rem;">同模式</p>
                    </div>
                    <section v-for="(isotype, isotypeIndex) in extraSpecimens"
                             class="iso-section"
                    >
                        <div class="columns has-background-light">
                            <div class="column is-6">
                                <div class="field">
                                    <label class="label is-marked"
                                           v-text="$t('forms.typeSpecimen.herbarium')"/>
                                    <general-input
                                        v-model="isotype.herbarium"
                                        :errors="errors[`typeSpecimens${index}Specimens${isotypeIndex + 1}Herbarium`]"/>
                                </div>
                            </div>
                            <div class="column is-4">
                                <div class="field">
                                    <label class="label" v-text="$t('forms.typeSpecimen.accessionNumber')"/>
                                    <general-input v-model="isotype.accessionNumber"/>
                                </div>
                            </div>
                            <div class="column">
                                <a class="close-button is-pulled-right"
                                   v-on:click="() => onDeleteIsotype(isotypeIndex + 1)">
                                </a>
                            </div>
                        </div>
                        <div class="columns has-background-light">
                            <div class="column is-12">
                                <div class="field">
                                    <label class="label" v-text="'連結URL'"/>
                                    <div class="control">
                                        <general-input v-model="isotype.url"/>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </section>
                </div>
            </div>

            <div class="columns">
                <div class="column is-4">
                    <button class="button is-text" v-on:click="onAddIsotype">
                        <i class="fa fa-plus-circle"></i>&nbsp;{{ $t('forms.typeSpecimen.addIsotype') }}
                    </button>
                </div>
            </div>
        </template>
        <template v-else>
            <div class="columns">
                <div class="column is-12">
                    <div class="field">
                        <label class="label"
                               v-text="$t('forms.typeSpecimen.citationNoteNumber')"/>
                        <general-input v-model="form.citationNoteNumber"
                                       :errors="errors[`typeSpecimens${index}CitationNoteNumber`]"/>
                    </div>
                </div>
            </div>
            <div v-if="form.use === 'lectotype'" class="columns">
                <div class="column is-9">
                    <div class="field">
                        <label class="label is-marked">
                            {{ $t('forms.typeSpecimen.lectoDesignatedReference') }}
                            <span class="is-pulled-right" v-if="isShowIsPointed">
                                <label :for="`ispointed_${index}`">
                                    <input :id="`ispointed_${index}`" v-model="form.isDesignated" class="checkbox" type="checkbox"/>&nbsp;
                                    在此被指定
                                </label>
                            </span>
                        </label>
                        <reference-select v-model="form.lectoDesignatedReference" :errors="errors[`typeSpecimens${index}LectoDesignatedReference`]"/>
                    </div>
                </div>
                <div class="column is-3">
                    <div class="field">
                        <label class="label"
                               v-text="$t('forms.typeSpecimen.lectoCitePage')"/>
                        <input v-model="form.lectoCitePage" class="input" type="text"/>
                    </div>
                </div>
            </div>
            <div class="columns">
                <div class="column is-12">
                    <div class="field">
                        <label class="label" v-text="$t('forms.typeSpecimen.url')"/>
                        <input v-model="form.url" class="input" type="text"/>
                    </div>
                </div>
            </div>
        </template>
    </div>
</template>
<script>
    import PersonSelect from '../selects/PersonSelect';
    import UseTypeSelect from '../selects/UseTypeSelect';
    import KindTypeSelect from '../selects/KindTypeSelect';
    import SexType from '../selects/SexType';
    import CountrySelect from '../selects/CountrySelect';
    import ReferenceSelect from '../selects/ReferenceSelect';
    import GeneralInput from '../GeneralInput';
    import MonthSelect from '../selects/MonthSelect';

    export default {
        components: {
            MonthSelect,
            GeneralInput,
            ReferenceSelect,
            CountrySelect,
            SexType,
            KindTypeSelect,
            UseTypeSelect,
            PersonSelect,
        },
        props: {
            value: {
                type: Object,
            },
            index: {
                type: Number,
                required: true,
            },
            errors: {
                type: Object,
                default() {
                    return {};
                },
            },
            isShowIsPointed: {
                type: Boolean,
                default: true,
            }
        },
        data() {
            return {
                form: {
                    id: this.value?.id || null,
                    use: this.value?.use || null,
                    kind: this.value?.kind || null,
                    sex: this.value?.sex || null,
                    country: this.value?.country || null,
                    locality: this.value?.locality || '',
                    localityVerbatim: this.value.localityVerbatim || '',
                    collectionYear: this.value.collectionYear || '',
                    collectionMonth: this.value.collectionMonth || '',
                    collectionDay: this.value.collectionDay || '',
                    collectors: this.value?.collectors || [],
                    collectorNumber: this.value?.collectorNumber || '',
                    specimens: this.value?.specimens || [],
                    isDesignated: this.value?.isDesignated || false,
                    lectoDesignatedReference: this.value?.lectoDesignatedReference || null,
                    lectoCitePage: this.value?.lectoCitePage || '',
                    citationNoteNumber: this.value?.citationNoteNumber || '',
                },
            }
        },
        mounted() {
            // initial type specimen model
            this.$emit('input', this.form);
        },
        watch: {
            form: {
                handler(values) {
                    this.$emit('input', values);
                },
                deep: true,
            },
        },
        computed: {
            extraSpecimens() {
                return this.form.specimens.filter((isotype, index) => index >= 1);
            },
        },
        methods: {
            onAddIsotype() {
                this.form.specimens.push({
                    herbarium: '',
                    accessionNumber: '',
                    url: '',
                });
            },
            onDeleteIsotype(index) {
                if (index !== 0) {
                    this.form.specimens.splice(index, 1);
                }
            },
            onAddNewPerson(data) {
                this.form.collectors.push(data);
            },
            onUpdateKind(value) {
                if (value === 1) {
                    this.form.specimens.push({
                        herbarium: '',
                        accessionNumber: '',
                        url: '',
                    });
                } else {
                    this.form.specimens = [];
                }
            },
            onAfterCreateReference() {

            },
        },
    }
</script>
<style lang="scss">
    .iso-section {
        margin-bottom: 1.5rem;
    }
</style>
