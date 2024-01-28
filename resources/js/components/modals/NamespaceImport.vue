<template>
    <div>
        <div class="px-16 py-12 max-w-screen-md min-w-300">
            <div>
                <p class="title text-center">{{ $t('reference.importNamesFromChecklist') }}</p>
            </div>

            <div class="pt-6 px-4 min-h-3/5">
                <div class="bg-gray-100">
                    <ul>
                        <li v-for="namespace in namespaces">
                            <label class="label p-1 px-4 my-2 hover:bg-gray-200 cursor-pointer">
                                <input v-model="namespaceIds" :value="namespace.id" type="checkbox"/>
                                &nbsp;&nbsp;
                                <span v-text="namespace.title"></span>
                            </label>
                        </li>
                    </ul>
                </div>
            </div>
        </div>
        <div class="sticky bottom-0 p-4 bg-white border-t">
            <textarea v-model="note" :placeholder="$t('common.description')" class="textarea mb-2" rows="3"></textarea>
            <div class="buttons is-right">
                <button class="button" v-on:click="closeModal">{{ $t('common.cancel') }}</button>
                <button class="button" v-on:click="() => onSubmit(true)">
                    {{ $t('common.overwrite') }}
                </button>
                <button class="button" v-on:click="() => onSubmit(false)">
                    {{ $t('common.add') }}
                </button>
            </div>
        </div>
    </div>
</template>
<script>
export default {
    props: {
        referenceId: {
            type: Number,
            required: true,
        },
    },
    data() {
        return {
            namespaces: [],
            namespaceIds: [],
            note: '',
        };
    },
    mounted() {
        this.axios.get('/namespaces')
            .then(({ data: { data } }) => {
                this.namespaces = data;
            });
    },
    methods: {
        closeModal() {
            this.$store.commit('closeModal');
        },
        onSubmit(overwrite = false) {
            this.axios.post(`/namespaces/import/${this.referenceId}`, {
                ids: this.namespaceIds,
                overwrite,
                note: this.note,
            }).then(() => {
                location.reload();
            });
        },
    },
};
</script>
<style lang="scss">
.section {
    padding-left: 4rem;
    padding-right: 4rem;
}
</style>
