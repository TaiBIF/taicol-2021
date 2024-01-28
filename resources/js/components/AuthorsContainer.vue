<template>
    <div>
        <p class="subtitle mb-3"> • {{ $t('taxonName.linkToAuthors') }}</p>
        <div class="box">
            <div v-if="isNeedAuthors" class="columns">
                <div class="column is-6">
                    <div class="field">
                        <label class="label">
                            {{
                                targetNomenclature ?
                                    $t(`taxonName.author.${targetNomenclature.settings.keyOfAuthors}`) :
                                    $t(`taxonName.authors`)
                            }}
                        </label>
                        <person-select
                            v-model="targetAuthors"
                            :errors="errors.authors"
                            :group="targetNomenclature ? targetNomenclature.group : ''"
                        />
                    </div>
                </div>
            </div>

            <!-- 前述者/提出此名者 -->
            <div v-if="isNeedExAuthors" class="columns">
                <div class="column is-6">
                    <div class="field">
                        <label class="label">
                            {{ $t('taxonName.exAuthor') }}
                            <tooltip class="float-right">
                                <i class="fas fa-info-circle"></i>
                                <template v-slot:body>
                                    <div class="w-[300px]">{{ $t('taxonName.authorInfo') }}</div>
                                </template>
                            </tooltip>
                        </label>

                        <person-select
                            v-model="targetExAuthors"
                            :errors="errors.exAuthors"
                            :group="targetNomenclature ? targetNomenclature.group : ''"
                        />
                    </div>
                </div>

                <div v-if="isNeedInitialYear" class="column is-3">
                    <div class="field">
                        <label class="label" v-text="$t('taxonName.exAuthorYear')"/>
                        <general-input v-model="initialYear" :errors="errors.initialYear"/>
                    </div>
                </div>
            </div>
        </div>
        <p class="subtitle mb-3"> • {{ $t('taxonName.saveAsPlainText') }}</p>
        <div class="box">
            <div class="columns">
                <div class="column is-6">
                    <div class="field">
                        <label class="label">{{ $t('taxonName.reference') }}
                            <small>{{ $t('taxonName.authorNotApplyInfo') }}</small>
                        </label>
                        <general-input v-model="referenceCustomName"
                                       :disabled="targetAuthors.length || targetExAuthors.length"
                                       :errors="errors.referenceName"
                        />
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
<script>
import GeneralInput from './GeneralInput.vue';
import SimpleReferenceView from './views/SimpleReferenceView.vue';
import ReferenceSelect from './selects/ReferenceSelect.vue';
import { subTitle } from '../utils/preview/reference';
import PersonSelect from './selects/PersonSelect.vue';
import Tooltip from './Tooltip.vue';

export default {
    name: 'reference-container',
    props: {
        isNeedAuthors: {
            type: Boolean,
            required: true,
        },
        referenceName: {
            type: String,
            required: true,
        },
        type: {
            type: String,
            required: true,
        },
        errors: {
            type: Object,
            required: true,
        },
    },
    data() {
        return {
            reference: this.usage?.target,
            referenceCustomName: this.referenceName,
        };
    },
    watch: {
        usage: {
            deep: true,
            handler(usage) {
                const newUsage = usage;
                newUsage.target.properties.pagesRange = '';
                const referenceName = [subTitle(newUsage.target), usage.showPage].filter(Boolean).join(': ');
                this.$emit('update:referenceName', referenceName);
                this.referenceCustomName = referenceName;
            },
        },
        reference(value) {
            const usage = {
                ...this.usage,
                target: value,
            };
            this.$emit('update:usage', usage);
        },
        referenceCustomName(value) {
            this.$emit('update:referenceName', value);
        },
    },
    methods: {
        onAddNewReference(data) {
            this.$emit('update:usage', {
                target: data,
                ...this.usage,
            });
        },
    },
    components: {
        Tooltip, PersonSelect, ReferenceSelect, SimpleReferenceView, GeneralInput,
    },
};
</script>
