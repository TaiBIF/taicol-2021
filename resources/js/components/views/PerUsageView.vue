<template>
    <span v-if="refPreview" v-text="refPreview"/>
    <span v-else>&nbsp;</span>
</template>
<script>
import { comboAnimal, comboPlant } from '../../utils/preview/reference';
import { comboLast } from '../../utils/preview/person';

export default {
    props: {
        perUsage: {
            type: Object,
            required: true,
        },
        taxonName: {
            type: Object,
            required: true,
        },
    },
    computed: {
        refPreview() {
            if (this.taxonName.nomenclature?.group === 'animal') {
                return comboAnimal([this.perUsage], comboLast);
            }
            return comboPlant([this.perUsage], comboLast);
        },
    },
};
</script>
<style lang="scss" scoped>
.is-orange {
    color: $orange;
}
</style>
