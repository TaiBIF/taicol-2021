<template>
    <div class="flex w-full">
        <i class="fas fa-play text-lg mr-2 cursor-pointer transform rotate-180"
           v-on:click="() => onGoPrev()"
        ></i>
        <select v-model="localValue"
                v-on:change="onUpdateReference"
                class="border border-solid px-4 w-full"
        >
            <option v-for="r in references"
                    :selected="r === localValue"
                    :value="r">
                {{ r.subtitle }}
            </option>
        </select>
        <button v-on:click="() => onGoNext()">
            <i class="fas fa-play text-lg ml-2 cursor-pointer"></i>
        </button>
    </div>
</template>
<script>
export default {
    props: {
        references: {
            type: Array,
            required: true,
        },
        value: {
            type: Object | null,
            required: true,
        }
    },
    methods: {
        onUpdateReference(e) {
            this.$emit('input', this.localValue);
        },
        onGoPrev() {
            const prevIndex = this.currentIndex - 1 < 0 ? this.references.length - 1 : this.currentIndex - 1;
            this.localValue = this.references[prevIndex];
            this.onUpdateReference();
        },
        onGoNext() {
            console.log(this.currentIndex + 1);
            this.localValue = this.references[(this.currentIndex + 1) % this.references.length];
            this.onUpdateReference();
        }
    },
    computed: {
        currentIndex() {
            if (!this.localValue) return null;
            return this.references.indexOf(this.localValue);
        }
    },
    data() {
        return {
            localValue: this.value,
        };
    },
    watch: {
        value(v) {
            this.localValue = v;
        },
    }
}
</script>
