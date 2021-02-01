<template>
    <article class="media">
        <figure class="media-left">
            <p class="image is-64x64">
                <img :src="coverPath"/>
            </p>
        </figure>
        <div class="media-content">
            <div class="content">
                <p>
                    <strong>{{ title }}</strong><br/><small>{{ subtitle }}</small>
                </p>
                <div class="field is-horizontal">
                    <div class="field-label has-text-left is-small">
                        <label class="label" v-text="$t('forms.reference.publishYear')"/>
                    </div>
                    <div class="field-body">
                        <small v-text="publishYear"/>
                    </div>
                </div>
                <div class="field is-horizontal">
                    <div class="field-label has-text-left is-small">
                        <label class="label" v-text="$t('forms.reference.author')"/>
                    </div>
                    <div class="field-body">
                        <small v-html="authors.map(a => fullNameAbbreviation(a)).join('<br/>')" />
                    </div>
                </div>
            </div>
        </div>
    </article>
</template>
<script>
    import BookView from './BookDetailView';
    import referenceTypes from '../../utils/options/referenceTypes';
    import { fullNameAbbreviation } from '../../utils/preview/person';

    export default {
        props: {
            type: {
                type: Number,
                required: true,
            },
            publishYear: {
                type: String,
                required: true,
            },
            properties: {
                type: Object,
                required: true,
            },
            title: {
                type: String,
                required: true,
            },
            subtitle: {
                type: String,
            },
            authors: {
                type: Array,
                required: true,
            },
            coverPath: {
                type: String,
            },
        },
        components: {
            BookView,
        },
        data() {
            return {
                showBook: false,
            }
        },
        computed: {
            typeDisplay() {
                const typeObject = referenceTypes.find(type => type.value === this.type);
                return typeObject ? this.$t(`forms.reference.typeOptions.${typeObject.value}`) : '';
            },
        },
        methods: {
            fullNameAbbreviation: (author) => fullNameAbbreviation(author),
        },
    }
</script>
<style lang="scss" scoped>
    .media-content {
        .field {
            margin-bottom: 0;
            .field-body {
                padding-top: 0.375rem;
            }
        }
    }
</style>
