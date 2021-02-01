<template>
    <div class="container">
        <div class="body box">
            <div class="form-header">
                <p class="title is-inline is-5" v-if="presetData">
                    <span>異名表編輯區 |</span>
                    <span class="has-text-orange" v-html="presetData.taxonName.formattedName.replace(/_(.*)_/, '<i>$1</i>')"></span>
                    <author-name v-bind="{
                        authors: presetData.taxonName.authors,
                        exAuthors: presetData.taxonName.exAuthors,
                        type: presetData.taxonName.nomenclature.group,
                        originalTaxonName: presetData.taxonName.originalTaxonName,
                        publishYear: presetData.taxonName.publishYear,
                    }">
                    </author-name>
                </p>
            </div>
            <usage-form ref="form"
                        v-if="presetData"
                        :on-after-submit="onAfterFormSubmit"
                        :preset-data="presetData"/>
            <loading v-else/>
        </div>
        <div class="form-footer">
            <div class="buttons is-right">
                <button class="button"
                        v-on:click="submit"
                        v-text="$t('forms.actions.confirm')">
                </button>
            </div>
        </div>
    </div>
</template>
<script>
    import UsageForm from '../components/forms/UsageForm';
    import Loading from '../components/LoadingSection';
    import AuthorName from '../components/AuthorName';

    export default {
        data() {
            return {
                formStatus: this.$c.PAGE_IS_LOADING,
                presetData: null,
            }
        },
        mounted() {
            const { namespaceId, usageId } = this.$route.params;
            this.axios
                .get(`namespaces/${namespaceId}/usages/${usageId}`)
                .then(({ data }) => {
                    this.presetData = data;
                    this.formStatus = this.$c.PAGE_IS_SUCCESS;

                    this.$store.commit('breadcrumb/SET_ITEMS', [{
                        url: '/namespaces',
                        name: '我的名錄',
                    }, {
                        url: `/namespaces/${namespaceId}`,
                        name: data.namespace.title,
                    }]);
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
                this.$router.push(`/namespaces/${this.$route.params.namespaceId}`)
            },
        },
        components: {
            AuthorName,
            UsageForm,
            Loading,
        },
    }
</script>
<style lang="scss" scoped>
    .form-header {
        border-bottom: 1px solid $light-grey;
        padding-bottom: 1rem;
    }

    .body {
        overflow: hidden;
        height: calc(100vh - #{$navbar-height} - #{$breadcrumb-height} - 4rem;
    }

    .taxon-name-column {
        width: 100%;
    }
</style>
