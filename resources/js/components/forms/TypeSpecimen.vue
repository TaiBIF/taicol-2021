<template>
    <div>
        <div class="columns">
            <div class="column is-6">
                <div class="field">
                    <label class="label is-marked"
                           v-text="$t('forms.typeSpecimen.use')"/>
                    <div class="control">
                        <use-type-select :errors="errors[`typeSpecimens${index}Use`]"
                                         v-model="form.use"/>
                    </div>
                </div>
            </div>
            <div class="column is-6">
                <div class="field">
                    <label class="label is-marked"
                           v-text="$t('forms.typeSpecimen.kind')"/>
                    <div class="control">
                        <kind-type-select :errors="errors[`typeSpecimens${index}Kind`]"
                                          v-model="form.kind"/>
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
                        <sex-type v-model="form.sex" />
                    </div>
                </div>
                <div class="column is-4">
                    <div class="field">
                        <label class="label is-marked"
                               v-text="$t('forms.typeSpecimen.country')"/>
                        <country-select :errors="errors[`typeSpecimens${index}Country`]"
                                        v-model="form.country"/>
                    </div>
                </div>
                <div class="column is-4">
                    <div class="field">
                        <label class="label" v-text="$t('forms.typeSpecimen.locality')"/>
                        <input class="input" type="text" v-model="form.locality"/>
                    </div>
                </div>
            </div>
            <div class="columns">
                <div class="column is-6">
                    <div class="field">
                        <label class="label"
                               v-text="$t('forms.typeSpecimen.localityVerbatim')"/>
                        <div class="control">
                            <input class="input" type="text" v-model="form.localityVerbatim"/>
                        </div>
                    </div>
                </div>
                <div class="column is-6 is-horizontal">
                    <div class="field">
                        <label class="label" v-text="$t('forms.typeSpecimen.collectDate')"/>
                        <div class="control is-flex" style="line-height: 2.5rem">
                            <input class="input" type="text" v-model="form.collectionYear"/>&nbsp;&nbsp;/&nbsp;&nbsp;
                            <input class="input" type="text" v-model="form.collectionMonth"/>&nbsp;&nbsp;/&nbsp;&nbsp;
                            <input class="input" type="text" v-model="form.collectionDay"/>&nbsp;&nbsp;/&nbsp;&nbsp;
                        </div>
                    </div>
                </div>
            </div>
            <div class="columns">
                <div class="column is-9">
                    <div class="field">
                        <label class="label is-marked" v-text="$t('forms.typeSpecimen.collector')"/>
                        <person-select :errors="errors[`typeSpecimens${index}Collectors`]"
                                       v-model="form.collectors"/>
                    </div>
                </div>
                <div class="column is-3">
                    <div class="field">
                        <label class="label" v-text="$t('forms.typeSpecimen.collectorNumber')"/>
                        <input class="input" type="text" v-model="form.collectorNumber"/>
                    </div>
                </div>
            </div>
            <div class="columns" v-if="form.use === 'lectotype'">
                <div class="column is-9">
                    <div class="field">
                        <label class="label is-marked">
                            選定模式文獻
                            <span class="is-pulled-right">
                                <label for="ispointed">
                                    <input class="checkbox" id="ispointed" type="checkbox"/>&nbsp;
                                    在此被指定
                                </label>
                            </span>
                        </label>

                        <reference-select :on-after-create="onAfterCreateReference"
                                          v-model="form.lectoDesignatedReference"/>
                    </div>
                </div>
                <div class="column is-3">
                    <div class="field">
                        <label class="label"
                               v-text="$t('forms.typeSpecimen.lectoCitePage')"/>
                        <general-input :errors="errors[`typeSpecimens${index}lectoCitePage`]"
                                       v-model="form.lectoCitePage"/>
                    </div>
                </div>
            </div>
            <div class="columns">
                <div class="column is-8">
                    <div class="field">
                        <label class="label is-marked"
                               v-text="$t('forms.typeSpecimen.herbarium')"/>
                        <general-input :errors="errors[`typeSpecimens0Specimens0Herbarium`]"
                                       v-model="form.specimens[0].herbarium"/>
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
                        <input class="input" type="text" v-model="form.url"/>
                    </div>
                </div>
            </div>
            <div class="columns">
                <div class="column is-12">
                    <section class="iso-section"
                             v-for="(isotype, isotypeIndex) in extraSpecimens"
                    >
                        <div class="columns has-background-light">
                            <div class="column is-6">
                                <div class="field">
                                    <label class="label is-marked"
                                           v-text="$t('forms.typeSpecimen.herbarium')"/>
                                    <general-input
                                        :errors="errors[`typeSpecimens${index}Specimens${isotypeIndex + 1}Herbarium`]"
                                        v-model="isotype.herbarium"/>
                                </div>
                            </div>
                            <div class="column is-4">
                                <div class="field">
                                    <label class="label" v-text="$t('forms.typeSpecimen.accessionNumber')"/>
                                    <general-input v-model="isotype.accessionNumber"/>
                                </div>
                            </div>
                            <a class="close-button"
                               v-on:click="() => onDeleteIsotype(isotypeIndex + 1)">
                            </a>
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
                <div class="column is-6">
                    <div class="field">
                        <label class="label"
                               v-text="$t('forms.typeSpecimen.citationNoteNumber')"/>
                        <general-input :errors="errors[`typeSpecimens${index}CitationNoteNumber`]"
                                       v-model="form.citationNoteNumber"/>
                    </div>
                </div>
                <div class="column is-6">
                    <div class="field">
                        <label class="label is-marked"
                               v-text="$t('forms.typeSpecimen.herbarium')"/>
                        <general-input :errors="errors[`typeSpecimens${index}Specimens0Herbarium`]"
                                       v-model="form.specimens[0].herbarium"/>
                    </div>
                </div>
            </div>
            <div class="columns" v-if="form.use === 'lectotype'">
                <div class="column is-9">
                    <div class="field">
                        <label class="label is-marked"
                               v-text="$t('forms.typeSpecimen.lectoDesignatedReference')"/>
                        <reference-select v-model="form.lectoDesignatedReference"/>
                    </div>
                </div>
                <div class="column is-3">
                    <div class="field">
                        <label class="label"
                               v-text="$t('forms.typeSpecimen.lectoCitePage')"/>
                        <input class="input" type="text" v-model="form.lectoCitePage"/>
                    </div>
                </div>
            </div>
            <div class="columns">
                <div class="column is-12">
                    <div class="field">
                        <label class="label" v-text="$t('forms.typeSpecimen.url')"/>
                        <input class="input" type="text" v-model="form.url"/>
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

    export default {
        components: {
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
                    specimens: this.value?.specimens || [
                        {
                            herbarium: '',
                            accessionNumber: '',
                            url: '',
                        },
                    ],
                    lectoDesignatedReference: this.value?.lectoDesignatedReference || '',
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
                    url: ''
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
