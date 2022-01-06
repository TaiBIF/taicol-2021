<template>
    <div>
        <div class="px-16 py-12 w-sc3 min-w-300">
            <div>
                <p class="title has-text-centered">匯入異名表</p>
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
            <div class="buttons is-right">
                <button class="button" v-on:click="closeModal">取消</button>
                <button class="button" v-on:click="() => onSubmit(true)">覆蓋</button>
                <button class="button" v-on:click="() => onSubmit(false)">加入</button>
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
            }
        },
        mounted() {
            this.axios.get(`/namespaces`)
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
                }).then(() => {
                    location.reload();
                })
            },
        },
    }
</script>
<style lang="scss">
    .section {
        padding-left: 4rem;
        padding-right: 4rem;
    }
</style>
