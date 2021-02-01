<template>
    <div class="section">
        <p class="title is-3 has-text-centered">匯入異名表</p>
        <div class="box has-background-light">
            <ul>
                <li v-for="namespace in namespaces">
                    <label class="label">
                        <input v-model="namespaceIds" :value="namespace.id" type="checkbox"/>
                        &nbsp;&nbsp;
                        <span v-text="namespace.title"></span>
                    </label>
                </li>
            </ul>
        </div>
        <div class="buttons is-right">
            <button class="button" v-on:click="closeModal">取消</button>
            <button class="button" v-on:click="onSubmit">確定</button>
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
            onSubmit() {
                this.axios.post(`/namespaces/import/${this.referenceId}`, {
                    ids: this.namespaceIds,
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
