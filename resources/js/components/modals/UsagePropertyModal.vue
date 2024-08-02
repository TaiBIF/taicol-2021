<template>
    <div>
        <div class="px-16 py-12 w-[768px]">
            <div>
                <p class="title text-center">{{ $t('namespace.setAllProperties') }}</p>
                <p class="text-sm">
                    <i class="fas fa-info-circle"></i>&nbsp;{{ $t('namespace.setAllPropertiesInfo') }}
                </p>
            </div>

            <div class="min-h-3/5 w-full">
                <div class="flex">
                    <div class="field p-2">
                        <label class="label is-marked"
                               v-text="$t('usage.isInTaiwan')"/>
                        <div class="buttons has-addons">
                            <deselectable-radio-button v-model="result.isInTaiwan" :label="$t('usage.yes')" :v="1"/>
                            <deselectable-radio-button v-model="result.isInTaiwan" :label="$t('usage.no')" :v="0"/>
                        </div>
                    </div>
                </div>
                <div v-if="result.isInTaiwan === 1" class="flex">
                    <div class="field p-2">
                        <label class="label"
                               v-text="$t('usage.isEndemic')"/>
                        <div class="buttons has-addons">
                            <deselectable-radio-button v-model="result.isEndemic" :label="$t('usage.yes')" :v="1"/>
                            <deselectable-radio-button v-model="result.isEndemic" :label="$t('usage.no')" :v="0"/>
                        </div>
                    </div>
                    <div class="field p-2">
                        <label class="label"
                               v-text="$t('usage.distributionInTw')"/>
                        <general-input v-model="result.distributionInTw"/>
                    </div>
                    <div class="field p-2">
                        <label class="label"
                               v-text="$t('usage.alienType')"/>
                        <div class="buttons has-addons">
                            <deselectable-radio-button v-model="result.alienType"
                                                       :label="$t('usage.alienTypeOptions.native')"
                                                       v="native"/>
                            <deselectable-radio-button v-model="result.alienType"
                                                       :label="$t('usage.alienTypeOptions.naturalized')"
                                                       v="naturalized"/>
                            <deselectable-radio-button v-model="result.alienType"
                                                       :label="$t('usage.alienTypeOptions.invasive')"
                                                       v="invasive"/>
                            <deselectable-radio-button v-model="result.alienType"
                                                       :label="$t('usage.alienTypeOptions.cultured')"
                                                       v="cultured"/>

                        </div>
                    </div>
                </div>
                <div class="flex">
                    <div class="field p-2">
                        <label class="label"
                               v-text="$t('usage.fossil')"/>
                        <div class="buttons has-addons">
                            <deselectable-radio-button v-model="result.isFossil" :label="$t('usage.yes')" :v="1"/>
                            <deselectable-radio-button v-model="result.isFossil" :label="$t('usage.no')" :v="0"/>
                        </div>
                    </div>

                    <div class="field p-2">
                        <label class="label"
                               v-text="$t('usage.terrestrial')"/>
                        <div class="buttons has-addons">
                            <deselectable-radio-button v-model="result.isTerrestrial" :label="$t('usage.yes')" :v="1"/>
                            <deselectable-radio-button v-model="result.isTerrestrial" :label="$t('usage.no')" :v="0"/>
                        </div>
                    </div>

                    <div class="field p-2">
                        <label class="label"
                               v-text="$t('usage.freshWater')"/>
                        <div class="buttons has-addons">
                            <deselectable-radio-button v-model="result.isFreshwater" :label="$t('usage.yes')" :v="1"/>
                            <deselectable-radio-button v-model="result.isFreshwater" :label="$t('usage.no')" :v="0"/>
                        </div>
                    </div>

                    <div class="field p-2">
                        <label class="label"
                               v-text="$t('usage.brackish')"/>
                        <div class="buttons has-addons">
                            <deselectable-radio-button v-model="result.isBrackish" :label="$t('usage.yes')" :v="1"/>
                            <deselectable-radio-button v-model="result.isBrackish" :label="$t('usage.no')" :v="0"/>
                        </div>
                    </div>

                    <div class="field p-2">
                        <label class="label"
                               v-text="$t('usage.marine')"/>
                        <div class="buttons has-addons">
                            <deselectable-radio-button v-model="result.isMarine" :label="$t('usage.yes')" :v="1"/>
                            <deselectable-radio-button v-model="result.isMarine" :label="$t('usage.no')" :v="0"/>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="flex justify-end sticky bottom-0 p-4 bg-white border-t gap-2">
            <button class="button" v-on:click="onClose">{{ $t('common.cancel') }}</button>
            <button class="button" v-on:click="onSave">{{ $t('common.save') }}</button>
        </div>
    </div>
</template>
<script lang="ts">
import { defineComponent, PropType, ref } from '@vue/composition-api';
import GeneralInput from '../GeneralInput.vue';
import DeselectableRadioButton from '../DeselectableRadioButton.vue';

export default defineComponent({
    name: 'usage-property-modal',
    props: {
        onUpdate: {
            type: Function as PropType<(data) => void>,
            required: true,
        },
    },
    setup(props, context) {
        const app: any = context.root;
        const result = ref<{
            isInTaiwan: number | null,
            isEndemic: number | null,
            alienType: string,
            isFossil: number | null,
            isTerrestrial: number | null,
            isFreshwater: number | null,
            isBrackish: number | null,
            isMarine: number | null,
            distributionInTw: string,
            isNewRecord: boolean | null,
            alienStatusNote: string,
        } | null>({
            isInTaiwan: null,
            isEndemic: null,
            alienType: '',
            isFossil: null,
            isTerrestrial: null,
            isFreshwater: null,
            isBrackish: null,
            isMarine: null,
            distributionInTw: '',
            isNewRecord: null,
            alienStatusNote: '',
        });

        const onClose = () => {
            app.$store.commit('closeModal');
        };

        const onSave = () => {
            props.onUpdate(result.value);
            app.$store.commit('closeModal');
        };

        return {
            result,
            onClose,
            onSave,
        };
    },
    components: { DeselectableRadioButton, GeneralInput },
});
</script>
