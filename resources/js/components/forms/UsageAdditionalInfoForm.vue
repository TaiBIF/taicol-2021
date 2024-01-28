<template>
    <div>
        <div class="flex">
            <div class="p-2">
                <div class="field">
                    <label class="label is-marked">
                        {{ $t('usage.isInTaiwan') }}
                        <span v-for="m in errors['propertiesIsInTaiwan']"
                              class="is-danger">
                            {{ $t(`validation.${m}`) }}
                        </span>
                    </label>
                    <div class="buttons has-addons">
                        <deselectable-radio-button v-model="isInTaiwan" :label="$t('usage.yes')" :v="1"/>
                        <deselectable-radio-button v-model="isInTaiwan" :label="$t('usage.unknown')" :v="2"/>
                        <deselectable-radio-button v-model="isInTaiwan" :label="$t('usage.no')" :v="0"/>
                    </div>

                </div>
            </div>
            <template v-if="isInTaiwan === 1">
                <div class="field p-2">
                    <label class="label"
                           v-text="$t('usage.isEndemic')"/>
                    <div class="buttons has-addons">
                        <deselectable-radio-button v-model="isEndemic" :label="$t('usage.yes')" :v="1"/>
                        <deselectable-radio-button v-model="isEndemic" :label="$t('usage.no')" :v="0"/>
                    </div>
                </div>
                <div class="field p-2">
                    <label class="label"
                           v-text="$t('usage.distributionInTw')"/>
                    <general-input v-model="distributionInTw"/>
                </div>
                <div class="field p-2">
                    <label class="label"
                           v-text="$t('usage.alienType')"/>
                    <div class="buttons has-addons">
                        <deselectable-radio-button v-model="alienType" :label="$t('usage.alienTypeOptions.native')"
                                                   v="native"/>
                        <deselectable-radio-button v-model="alienType" :label="$t('usage.alienTypeOptions.naturalized')"
                                                   v="naturalized"/>
                        <deselectable-radio-button v-model="alienType" :label="$t('usage.alienTypeOptions.invasive')"
                                                   v="invasive"/>
                        <deselectable-radio-button v-model="alienType" :label="$t('usage.alienTypeOptions.cultured')"
                                                   v="cultured"/>

                    </div>
                </div>
            </template>
        </div>
        <div class="flex">
            <div class="field p-2">
                <label class="label"
                       v-text="$t('usage.fossil')"/>
                <div class="buttons has-addons">
                    <deselectable-radio-button v-model="isFossil" :label="$t('usage.yes')" :v="1"/>
                    <deselectable-radio-button v-model="isFossil" :label="$t('usage.no')" :v="0"/>
                </div>
            </div>

            <div class="field p-2">
                <label class="label"
                       v-text="$t('usage.terrestrial')"/>
                <div class="buttons has-addons">
                    <deselectable-radio-button v-model="isTerrestrial" :label="$t('usage.yes')" :v="1"/>
                    <deselectable-radio-button v-model="isTerrestrial" :label="$t('usage.no')" :v="0"/>
                </div>
            </div>

            <div class="field p-2">
                <label class="label"
                       v-text="$t('usage.freshWater')"/>
                <div class="buttons has-addons">
                    <deselectable-radio-button v-model="isFreshwater" :label="$t('usage.yes')" :v="1"/>
                    <deselectable-radio-button v-model="isFreshwater" :label="$t('usage.no')" :v="0"/>
                </div>
            </div>

            <div class="field p-2">
                <label class="label"
                       v-text="$t('usage.brackish')"/>
                <div class="buttons has-addons">
                    <deselectable-radio-button v-model="isBrackish" :label="$t('usage.yes')" :v="1"/>
                    <deselectable-radio-button v-model="isBrackish" :label="$t('usage.no')" :v="0"/>
                </div>
            </div>

            <div class="field p-2">
                <label class="label"
                       v-text="$t('usage.marine')"/>
                <div class="buttons has-addons">
                    <deselectable-radio-button v-model="isMarine" :label="$t('usage.yes')" :v="1"/>
                    <deselectable-radio-button v-model="isMarine" :label="$t('usage.no')" :v="0"/>
                </div>
            </div>
        </div>
        <div class="columns">
            <div class="column is-12">
                <div class="field">
                    <label class="label"
                           v-text="$t('taxonName.commonName')"/>
                    <div v-for="(commonName, index) in commonNames" class="box">
                        <a class="is-pulled-right close-button"
                           v-on:click="() => onRemoveCommonName(index)">
                        </a>
                        <div class="columns">
                            <div class="column is-5">
                                <label class="label is-marked"
                                       v-text="$t('usage.commonName')"/>
                                <general-input v-model="commonName['name']"
                                               :errors="errors[`propertiesCommonNames${index}Name`]"/>
                            </div>
                            <div class="column is-3">
                                <label class="label is-marked"
                                       v-text="$t('usage.language')"/>
                                <language-select v-model="commonName['language']"
                                                 :errors="errors[`propertiesCommonNames${index}Language`]"
                                                 :is-use-key-id="true"/>
                            </div>
                            <div class="column is-3">
                                <label class="label"
                                       v-text="$t('usage.area')"/>
                                <general-input v-model="commonName['area']"/>
                            </div>
                        </div>
                    </div>

                    <button class="button is-text"
                            v-on:click="onAddCommonName">
                        <i class="fa fa-plus-circle"></i>
                        &nbsp;&nbsp;{{ $t('usage.commonName') }}
                    </button>
                </div>
            </div>
        </div>
        <div class="columns">
            <div class="column is-12">
                <div class="field">
                    <label class="label"
                           for="note"
                           v-text="$t('usage.note')"/>
                    <textarea id="note" v-model="note" class="textarea"/>
                </div>
            </div>
        </div>
    </div>
</template>
<script>
import GeneralInput from '../GeneralInput.vue';
import DeselectableRadioButton from '../DeselectableRadioButton.vue';
import LanguageSelect from '../selects/LanguageSelect.vue';

export default {
    props: {
        preset: {
            type: Object,
        },
        errors: {
            type: Object,
        },
    },
    data() {
        return {
            isInTaiwan: this.preset.isInTaiwan ?? null,
            isEndemic: this.preset.isEndemic ?? null,
            distributionInTw: this.preset.distributionInTw ?? '',
            alienType: this.preset.alienType ?? null,
            isFossil: this.preset.isFossil ?? null,
            isTerrestrial: this.preset.isTerrestrial ?? null,
            isFreshwater: this.preset.isFreshwater ?? null,
            isBrackish: this.preset.isBrackish ?? null,
            isMarine: this.preset.isMarine ?? null,
            commonNames: this.preset.commonNames ?? [],
            note: this.preset.note ?? '',
        };
    },
    created() {
        Object.assign(this.$data, this.$attrs.value);
    },
    destroyed() {
        const value = { ...this.$data };
        Object.keys(this.$data)
            .forEach((key) => delete value[key]);
        this.$emit('input', value);
    },
    watch: {
        isInTaiwan: {
            handler(value) {
                if (!value) {
                    this.isEndemic = null;
                    this.distributionInTw = '';
                    this.alienType = null;
                }
            },
        },
    },
    updated() {
        this.$emit('input', { ...this.value, ...this.$data });
    },
    methods: {
        onAddCommonName() {
            this.commonNames.push({
                name: '',
                language: null,
                area: '',
            });
        },
        onRemoveCommonName(index) {
            this.commonNames.splice(index, 1);
        },
    },
    components: {
        LanguageSelect,
        DeselectableRadioButton,
        GeneralInput
    },
};
</script>
