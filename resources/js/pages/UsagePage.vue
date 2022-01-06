<template>
    <div>
        <div class="usage-form shadow-md bg-white">
            <template v-if="presetData">
                <div class="py-4 px-5 border-b">
                    <p v-if="presetData.acceptedUsage" class="is-size-6 op">
                        <span>有效學名</span>
                        <taxon-name-label :taxon-name="presetData.acceptedUsage.taxonName" class="is-orange"/>
                        <author-name v-bind="{
                            authors: presetData.acceptedUsage.taxonName.authors,
                            exAuthors: presetData.acceptedUsage.taxonName.exAuthors,
                            type: presetData.acceptedUsage.taxonName.nomenclature.group,
                            originalTaxonName: presetData.acceptedUsage.taxonName.originalTaxonName,
                            publishYear: presetData.acceptedUsage.taxonName.publishYear,
                            taxonName: presetData.acceptedUsage.taxonName,
                        }"/>
                    </p>
                    <p class="has-text-weight-bold is-inline is-size-5 taxon-name-title">
                        <span>學名使用編輯區 |</span>
                        <taxon-name-label :taxon-name="presetData.taxonName" class="is-orange"/>
                        <author-name v-bind="{
                            authors: presetData.taxonName.authors,
                            exAuthors: presetData.taxonName.exAuthors,
                            type: presetData.taxonName.nomenclature.group,
                            originalTaxonName: presetData.taxonName.originalTaxonName,
                            publishYear: presetData.taxonName.publishYear,
                            taxonName: presetData.taxonName,
                        }"/>
                        <button class="button is-small" v-on:click="onEditingTaxonName">編輯學名</button>
                    </p>
                </div>

                <usage-form ref="form"
                            :class="{'form-body-1': presetData.acceptedUsage, 'form-body-2': !presetData.acceptedUsage}"
                            :on-after-submit="onAfterFormSubmit"
                            :preset-data="presetData"/>

                <div class="buttons is-right px-4 py-3 border-t">
                    <button class="button"
                            v-on:click="submit"
                            v-text="$t('forms.actions.confirm')">
                    </button>
                </div>
            </template>
            <loading v-else/>
        </div>
    </div>
</template>
<script>
    import UsageForm from '../components/forms/UsageForm';
    import Loading from '../components/LoadingSection';
    import AuthorName from '../components/AuthorName';
    import TaxonNameLabel from '../components/views/TaxonNameLabel';

    export default {
        data() {
            return {
                formStatus: this.$c.PAGE_IS_LOADING,
                presetData: null,
            }
        },
        mounted() {
            const { id: namespaceId, usageId } = this.$route.params;
            this.axios
                .get(`namespaces/${namespaceId}/usages/${usageId}`)
                .then(({ data }) => {
                    this.presetData = data;
                    this.formStatus = this.$c.PAGE_IS_SUCCESS;
                });
        },
        methods: {
            close() {
                this.$emit('close');
            },
            submit() {
                this.$refs.form.submit();
            },
            onAfterFormSubmit(data) {
                this.$router.push(`/namespaces/${this.$route.params.id}/usages`)
            },
            async onEditingTaxonName() {
                const app = this;
                const { id:namespaceId, usageId } = app.$route.params;
                const { data: { data } } = await app.axios.get(`/taxon-names/${app.presetData.taxonName.id}/info`);

                this.$store.commit('layer/TAXON_NAME', {
                    onAfterSubmit: async () => {
                        // update taxon name data
                        const { data: { taxonName } } = await app.axios.get(`namespaces/${namespaceId}/usages/${usageId}`);
                        app.presetData.taxonName = taxonName;
                    },
                    defaultValue: data,
                });
            },
        },
        components: {
            TaxonNameLabel,
            AuthorName,
            UsageForm,
            Loading,
        },
    }
</script>
<style lang="scss" scoped>
    .form-body-1 {
        height: calc(100% - 10rem);
    }
    .form-body-2 {
        height: calc(100% - 8.5rem);
    }

    .op {
        opacity: .8;
    }

    .usage-form {
        overflow: hidden;
        height: calc(100vh - #{$navbar-height} - #{$breadcrumb-height});
    }

    .taxon-name-title {
        vertical-align: middle;
    }
</style>
