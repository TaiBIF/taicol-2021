<template>
    <div>
        <div class="flex">
            <div class="p-2">
                <div class="field">
                    <label class="label is-marked"
                           v-text="'存在於臺灣？'"/>
                    <div class="buttons has-addons">
                        <deselectable-radio-button v-model="isInTaiwan" :v="1" label="是"/>
                        <deselectable-radio-button v-model="isInTaiwan" :v="0" label="否"/>
                    </div>
                </div>
            </div>
            <template v-if="isInTaiwan">
                <div class="field p-2">
                    <label class="label"
                           v-text="'臺灣特有種'"/>
                    <div class="buttons has-addons">
                        <deselectable-radio-button v-model="isEndemic" :v="1" label="是"/>
                        <deselectable-radio-button v-model="isEndemic" :v="0" label="否"/>
                    </div>
                </div>
                <div class="field p-2">
                    <label class="label"
                           v-text="'臺灣分布地'"/>
                    <general-input v-model="distributionInTw"/>
                </div>
                <div class="field p-2">
                    <label class="label"
                           v-text="'原生/外來類型'"/>
                    <div class="buttons has-addons">
                        <deselectable-radio-button v-model="alienType" label="原生" v="native"/>
                        <deselectable-radio-button v-model="alienType" label="歸化" v="naturalized"/>
                        <deselectable-radio-button v-model="alienType" label="入侵" v="invasive"/>
                        <deselectable-radio-button v-model="alienType" label="栽培豢養" v="vultured"/>

                    </div>
                </div>
            </template>
        </div>
        <div class="flex">
            <div class="field p-2">
                <label class="label"
                       v-text="'化石種'"/>
                <div class="buttons has-addons">
                    <deselectable-radio-button v-model="isFossil" :v="1" label="是"/>
                    <deselectable-radio-button v-model="isFossil" :v="0" label="否"/>
                </div>
            </div>

            <div class="field p-2">
                <label class="label"
                       v-text="'陸生'"/>
                <div class="buttons has-addons">
                    <deselectable-radio-button v-model="isTerrestrial" :v="1" label="是"/>
                    <deselectable-radio-button v-model="isTerrestrial" :v="0" label="否"/>
                </div>
            </div>

            <div class="field p-2">
                <label class="label"
                       v-text="'淡水'"/>
                <div class="buttons has-addons">
                    <deselectable-radio-button v-model="isFreshwater" :v="1" label="是"/>
                    <deselectable-radio-button v-model="isFreshwater" :v="0" label="否"/>
                </div>
            </div>

            <div class="field p-2">
                <label class="label"
                       v-text="'半鹹水域'"/>
                <div class="buttons has-addons">
                    <deselectable-radio-button v-model="isBrackish" :v="1" label="是"/>
                    <deselectable-radio-button v-model="isBrackish" :v="0" label="否"/>
                </div>
            </div>

            <div class="field p-2">
                <label class="label"
                       v-text="'海洋'"/>
                <div class="buttons has-addons">
                    <deselectable-radio-button v-model="isMarine" :v="1" label="是"/>
                    <deselectable-radio-button v-model="isMarine" :v="0" label="否"/>
                </div>
            </div>
        </div>
        <div class="columns">
            <div class="column is-12">
                <div class="field">
                    <label class="label"
                           v-text="'俗名'"/>
                    <div v-for="(commonName, index) in commonNames" class="box">
                        <a class="is-pulled-right close-button"
                           v-on:click="() => onRemoveCommonName(index)">
                        </a>
                        <div class="columns">
                            <div class="column is-5">
                                <label class="label is-marked"
                                       v-text="'俗名'"/>
                                <general-input v-model="commonName['name']"/>
                            </div>
                            <div class="column is-3">
                                <label class="label is-marked"
                                       v-text="'語言'"/>
                                <language-select v-model="commonName['language']" :is-use-key-id="true"/>
                            </div>
                            <div class="column is-3">
                                <label class="label"
                                       v-text="'使用地區'"/>
                                <general-input v-model="commonName['area']"/>
                            </div>
                        </div>
                    </div>

                    <button class="button is-text"
                            v-on:click="onAddCommonName">
                        <i class="fa fa-plus-circle"></i>
                        &nbsp;&nbsp;俗名
                    </button>
                </div>
            </div>
        </div>
        <div class="columns">
            <div class="column is-12">
                <div class="field">
                    <label class="label"
                           for="note"
                           v-text="'其他備註'"/>
                    <textarea id="note" v-model="note" class="textarea"/>
                </div>
            </div>
        </div>
    </div>
</template>
<script>
    import GeneralInput from '../GeneralInput';
    import DeselectableRadioButton from '../DeselectableRadioButton';
    import LanguageSelect from '../selects/LanguageSelect';

    export default {
        props: {
            preset: {
                type: Object,
            }
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
            }
        },
        created() {
            Object.assign(this.$data, this.$attrs.value);
        },
        destroyed() {
            const value = Object.assign({}, this.$data);
            Object.keys(this.$data).forEach(key => delete value[key]);
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
        components: { LanguageSelect, DeselectableRadioButton, GeneralInput },
    }
</script>
