<template>
    <div>
        <div class="usage-form shadow-md bg-white">
            <template v-if="presetData">
                <div class="py-4 px-5 border-b">
                    <p v-if="!!presetData.acceptedUsage" class="is-size-6 op">
                        <span>{{ $t('usage.acceptedTaxon') }}</span>
                        <taxon-name-full-label :taxon-name="presetData.acceptedUsage" :with-color="true"/>
                    </p>
                    <p class="has-text-weight-bold is-inline is-size-5 taxon-name-title">
                        <span>{{ $t('usage.editingBlock') }} |</span>
                        <taxon-name-full-label :taxon-name="presetData.taxonName" :with-color="true"/>
                        <button class="button is-small" v-on:click="onEditingTaxonName" v-text="$t('taxonName.edit')"/>
                    </p>
                </div>

                <usage-form ref="form"
                            :class="{
                                'form-body-1': !!presetData.acceptedUsage,
                                'form-body-2': !presetData.acceptedUsage
                            }"
                            :on-after-submit="onAfterFormSubmit"
                            :preset-data="presetData"/>

                <div class="buttons is-right px-4 py-3 border-t">
                    <button class="button"
                            v-on:click="submit"
                            v-text="$t('common.save')">
                    </button>
                </div>
            </template>
            <loading v-else/>
        </div>
    </div>
</template>
<script>
import UsageForm from '../components/forms/UsageForm.vue';
import Loading from '../components/LoadingSection.vue';
import AuthorName from '../components/AuthorName.vue';
import TaxonNameLabel from '../components/views/TaxonNameLabel.vue';
import TaxonNameFullLabel from '../components/views/TaxonNameFullLabel.vue';

export default {
    data() {
        return {
            formStatus: this.$c.PAGE_IS_LOADING,
            presetData: null,
        };
    },
    mounted() {
        const { id, usageId } = this.$route.params;

        const url = this.$route.name === 'reference-usages-edit' ?
            `references/${id}/usages-edit/${usageId}` : `namespaces/${id}/usages/${usageId}`;

        this.axios
            .get(url)
            .then(({ data }) => {
                this.presetData = data;
                this.formStatus = this.$c.PAGE_IS_SUCCESS;

                const { items } = this.$store.state.breadcrumb;

                if (this.$route.meta.type === 'reference') {
                    items.push({
                        url: `/reference/${id}/usages-edit`,
                        name: this.$t('reference.editNameArea'),
                        to: { name: 'reference-usages-list', params: { id } },
                        type: 'reference-usages-list',
                    });
                }

                this.$store.commit('breadcrumb/SET_ITEMS', items);
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
            const url = this.$route.name === 'reference-usages-edit' ?
                {
                    name: 'reference-usages-list',
                    params: { id: this.$route.params.id },
                }
                : {
                    name: 'namespace-usage-list',
                    params: { id: this.$route.params.id },
                };
            this.$router.push(url);
        },
        async onEditingTaxonName() {
            const app = this;
            const { id, usageId } = app.$route.params;
            const { data: { data } } = await app.axios.get(`/taxon-names/${app.presetData.taxonName.id}/info`);

            this.$store.commit('layer/TAXON_NAME', {
                onAfterSubmit: async () => {
                    // update taxon name data
                    const url = app.$route.name === 'reference-usages-edit' ?
                        `references/${id}/usages-edit/${usageId}`
                        : `namespaces/${id}/usages/${usageId}`;

                    const { data: { taxonName } } = await app.axios.get(url);
                    app.presetData.taxonName = taxonName;
                },
                defaultValue: data,
            });
        },
    },
    components: {
        TaxonNameFullLabel,
        TaxonNameLabel,
        AuthorName,
        UsageForm,
        Loading,
    },
};
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
