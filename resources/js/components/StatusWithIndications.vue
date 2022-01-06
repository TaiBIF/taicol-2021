<template>
    <div class="tags">
        <template v-if="indications.length">
            <span class="bg-gray-50 px-3" v-for="indication in indications">
                <template v-if="status === 'undetermined'">
                    未決
                </template>
                <template v-else-if="status === 'misapplied'">
                    誤用
                </template>
                <template v-else>
                    {{ getIndicationObject(indication).display[$i18n.locale()] }}
                </template>
            </span>
        </template>
        <template v-else>
            <template v-if="status === 'not-accepted'">
                <span class="bg-gray-50 px-3">
                    同物異名
                </span>
            </template>
            <template v-else-if="status === 'accepted'">
                <span class="bg-gray-50 px-3" v-if="nomenclature.group === 'plant'">
                    正確學名
                </span>
                <span class="bg-gray-50 px-3" v-else-if="nomenclature.group === 'animal'">
                    接受學名
                </span>
            </template>
        </template>
    </div>
</template>
<script>
    import indications from './selects/indications';

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
            getIndicationObject(abbr) {
                return indications.find(i => i.abbreviation === abbr);
            },
        },
    }
</script>
