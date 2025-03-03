<template>
    <div class="export-tags">
        <div>　{{ createPropArray(p) }}</div>
    </div>
</template>
<script>
import { defineComponent } from '@vue/composition-api';

export default defineComponent({
    props: {
        p: {
            type: Object,
            required: true,
        },
    },
    methods: {
        createPropArray(prop){
            let props = [];
            if (prop.isInTaiwan==1 || prop.isInTaiwan==true){
                props.push('Exist in Taiwan')
            } else if (prop.isInTaiwan==0 || prop.isInTaiwan==false){
                props.push('Not Exist in Taiwan')
            }
            
            prop.isEndemic ? props.push('Endemic to Taiwan') : '';
            prop.isNewRecord ? props.push('New Record') : '';
            prop.alienType ? props.push(prop.alienType[0].toUpperCase() + prop.alienType.slice(1)) : '';
            prop.isFossil ? props.push('Fossil') : '';
            prop.isTerrestrial ? props.push('Terrestrial') : '';
            prop.isFreshwater ? props.push('Freshwater') : '';
            prop.isBrackish ? props.push('Brackish') : '';
            prop.isMarine ? props.push('Marine') : '';
            
            return props.join(', ');
        }
    }
});
</script>

<style lang="scss">
.tag {
    border: 1px solid #ccc;
    @apply px-1;
}
</style>
