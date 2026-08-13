<template>
    <div>
        <div class="px-16 py-12 w-[768px]">
            
            <p class="is-danger">{{ $t('common.checkDuplicates') }}</p>
            <div v-for="item in duplicates" :key="item.id" class="my-2">
                <router-link target="_blank" :to="{ name: 'taxon-name-page', params: { id: item.id } }" class="my-link">
                    <taxon-name-label :taxon-name="item" class="is-inline-block"/><!--
                    -->&nbsp;<!--
                    --><author-name v-bind="{
                        authors: item.authors,
                        exAuthors: item.exAuthors,
                        type: item.nomenclature.group,
                        originalTaxonName: item.originalTaxonName,
                        publishYear: item.publishYear,
                        taxonName: item,
                    }"/>
                    <br/>
                    <span class="my-subtitle">
                        {{ item.nomenclature.name }}:
                        {{ item.root ? item.root.name : '' }}
                        [{{ item.rank.display.zhTw }}]
                        {{ showReference(item) }}
                    </span>
                </router-link>
            </div>
        </div>
        <div class="flex justify-end sticky bottom-0 p-4 bg-white border-t gap-2">
            <button class="button is-primary" v-on:click="handleForceSave">{{ $t('common.publish') }}</button>
            <button class="button" v-on:click="onClose">{{ $t('common.goBack') }}</button>
        </div>
    </div>
</template>
<script>
import AuthorName from '../AuthorName.vue';
import TaxonNameLabel from '../views/TaxonNameLabel.vue';

export default {
    components: { TaxonNameLabel, AuthorName },
    props: {
        duplicates: {
            type: Array,
            default: () => [],
        },
        onForceSave: {
            type: Function,
            required: false,
        },
    },
    methods: {
        showReference(o) {
            return o.reference?.subtitle ?? o.properties?.referenceName ?? '';
        },
        onClose() {
            this.$store.commit('closeModal');
        },
        handleForceSave() {
            if (this.onForceSave) {
                this.onClose();
                this.onForceSave();
            }
        },
    },
};
</script>
<style lang="scss" scoped>
.my-link {
    margin: 5px 0;
}
.my-subtitle {
    color: darkgray;
    font-size: 13px;
}
</style>