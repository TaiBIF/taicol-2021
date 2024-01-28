<template>
    <div class="tags">
        <template v-if="indications.length">
            <span v-for="indication in indications" class="bg-gray-50 px-3">
                <template v-if="status === 'undetermined'">
                    {{ getStatusObject(status).display[$i18n.locale()] }}
                </template>
                <template v-else-if="status === 'misapplied'">
                    {{ getStatusObject(status).display[$i18n.locale()] }}
                </template>
                <template v-else>
                    {{
                        $i18n.locale() === 'zh-tw' ?
                            getIndicationObject(indication).display[$i18n.locale()] :
                            getIndicationObject(indication).abbreviation
                    }}
                </template>
            </span>
        </template>
        <template v-else>
            <template v-if="status === 'not-accepted'">
                <span class="bg-gray-50 px-3">
                     {{ $i18n.locale() === 'zh-tw' ? '同物異名' : 'Synonym' }}
                </span>
            </template>
            <template v-else-if="status === 'accepted'">
                <span v-if="nomenclature.group === 'plant'" class="bg-gray-50 px-3">
                    {{ $i18n.locale() === 'zh-tw' ? '正確學名' : 'Correct' }}
                </span>
                <span v-else-if="nomenclature.group === 'animal'" class="bg-gray-50 px-3">
                    {{ $i18n.locale() === 'zh-tw' ? '接受學名' : 'Valid' }}
                </span>
            </template>
        </template>
    </div>
</template>
<script>
import indications from './selects/indications';
import statuses from './selects/status';

export default {
    props: {
        nomenclature: {
            type: Object,
            required: true,
        },
        status: {
            type: String,
            required: true,
        },
        indications: {
            type: Array,
            required: true,
        },
    },
    methods: {
        getStatusObject(status) {
            return statuses.find((s) => s.key === status);
        },
        getIndicationObject(abbr) {
            return indications.find((i) => i.abbreviation === abbr);
        },
    },
};
</script>
