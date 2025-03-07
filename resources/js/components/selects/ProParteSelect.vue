<template>
  <div>
    <t-select
      v-model="localValue"
      :clearable="true"
      :errors="errors"
      :filterable="false"
      :options="options"
      :searchable="false"
      :taggable="true"
      label="name"
      v-on:input="onUpdateValue"
    >
  
    <template v-slot:selected-option="{ option }">
            <span v-text="`${option.name}`"/>
    </template>
    <template v-slot:option="{ option }">
        <span v-text="`${option.name}`"/>
        <br/>
        <span v-if="$i18n.locale() === 'zh-tw'" class="help is-small has-text-grey-light"
              v-text="`${option.definition}`"/>
    </template>

  </t-select>

  </div>
</template>

<script>
import tSelect from '../Select.vue';
import proPartes from './proParte';

export default  {
  props: {
    value: {
            type: String,
        },
    errors: {
            type: Array,
        },
  },
  data() {

    const options = proPartes;

    return {
        localValue: this.value,
        options,
    };

  },
  methods: {
    onUpdateValue(proParteObject) {
          if (proParteObject === null) {
              this.$emit('input', '');
          } else {
              this.$emit('input', proParteObject.name);
          }
    },
  },
  components: { tSelect }
};
</script>
