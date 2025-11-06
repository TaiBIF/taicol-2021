<template>
    <t-select ref="mySelect"
              v-model="localValue"
              :disabled="disabled"
              :errors="errors"
              :loading="isLoading"
              :options="filteredPersons"
              clearable
              label="fullName"
              :multiple="multiple"
              v-on:input="onUpdateValue"
              v-on:typing="fetchFilteredPersons"
              :insertedAuthors="insertedAuthors"
              :insertAuthorsAction="insertAuthorsAction"
    >
        <template v-slot:option="{ option }">
            <span v-if="group" v-text="`${option.lastName}, ${option.firstName}`"/>
            <span v-else v-text="`${option.fullName}`"/>
            <span v-if="option.abbreviationName">({{ option.abbreviationName }})</span>

            <span class="help has-text-grey-light">
                {{ option.originalFullName }} {{ option.yearLife || yearOfPublication }}
                {{
                    option.biologyDepartments.map(d => $t(`person.biologyDepartmentOptions.${d}`)).join(', ')
                }}:{{ option.biologicalGroup.replace('、', ',') }}
            </span>
        </template>
        <template v-slot:selected-option="{ option }">
            <span v-if="group" v-text="`${renderFormatName(option, group)} (${renderFormatFullName(option)})`"/>
            <span v-else v-text="`${option.fullName}`"/>
        </template>
        <template v-slot:no-options>
            {{ $t('common.enterForOptions') }} {{ $t('common.or') }} <a v-on:click="onAddPersonFormLayer"
                                                                        v-text="$t('person.create')"/>
        </template>
    </t-select>
</template>
<script>
import { debounce } from 'lodash';
import Select from '../Select.vue';
import { factory as personFactory, fullName } from '../../utils/preview/person';

export default {
    props: {
        value: {
            type: [Array, Object, null],
            required: false,
            default: () => [],
        },
        errors: {
            type: Array,
        },
        disabled: {
            type: Boolean,
            default: false,
        },
        multiple: {
            type: Boolean,
            default: true, // 預設為多選
        },
        // Nomenclauture 類別「動物」或「植物」
        group: {
            type: String,
        },
        insertedAuthors: {
            type: Array,
        },
        insertAuthorsAction: {
            type: Number,
        },
        // 新增：作者資料，用於預填表單
        authorData: {
            type: Object,
            required: false,
            default: () => ({}),
        }
    },
    data() {
        return {
            filteredPersons: [],
            localValue: this.multiple ? (Array.isArray(this.value) ? this.value : []) : (Array.isArray(this.value) ? this.value : []),
            isLoading: false,
        };
    },
    components: {
        tSelect: Select,
    },
    watch: {
        value: {
            handler(newValue) {
                this.localValue = Array.isArray(newValue) ? newValue : [];
            },
            deep: true,
            immediate: true,
        },
        disabled(value) {
            if (value) {
                this.localValue = [];
            }
        },
        insertAuthorsAction() { 
            // 每按一次就更新一次作者
            this.onUpdateValueOutside(this.insertedAuthors);
        }
    },
    methods: {
        renderFormatName(person, group) {
            return personFactory(group)([person]);
        },
        renderFormatFullName(person) {
            return fullName(person);
        },
        onUpdateValue(value) {
            this.filteredPersons = [];
            this.$emit('input', value);
        },
        onUpdateValueOutside(value) {
            // 要保留原本的作者
            value.forEach(element => {
                this.localValue.push(element);
            });
            this.localValue =[...new Set(this.localValue)];
            this.onUpdateValue(this.localValue);
        },
        onAfterCreate(data) {
            this.localValue.push(data);
            this.onUpdateValue(this.localValue);
        },
        onAddPersonFormLayer() {
            this.$refs.mySelect.$refs.mySelect.onEscape();
            
            // 準備預設資料
            const presetData = {};
            if (this.authorData.given) {
                presetData.firstName = this.authorData.given;
            }
            if (this.authorData.family) {
                presetData.lastName = this.authorData.family;
            }


            this.$store.commit('layer/ADD', {
                template: () => import('../layers/PersonLayer.vue'),
                defaultText: this.localValue,
                props: {
                    presetName: presetData
                },
                events: {
                    onAfterSubmit: this.onAfterCreate,
                },
            });
        },
        fetchFilteredPersons: debounce(function ({ value, keyword }) {
            this.isLoading = true;

            if (!keyword) {
                this.isLoading = false;
                this.filteredPersons = [];
                return;
            }

            this.axios.get('persons', {
                params: { keyword },
            }).then(({ data }) => {
                this.isLoading = false;
                this.filteredPersons = data;
            });
        }),
    },
};
</script>