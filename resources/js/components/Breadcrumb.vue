<template>
    <nav aria-label="breadcrumbs" class="breadcrumb">
        <ul>
            <li>
                <router-link to='/' v-text="$t('functions.index')"/>
            </li>
            <li v-for="(breadcrumb, index) in items">
                <a href="#" v-if="breadcrumb.url === '#'" v-text="breadcrumb.name"/>
                <router-link :to="breadcrumb.url" v-else v-text="breadcrumb.name"/>
            </li>
        </ul>
    </nav>
</template>
<script>
    import { mapGetters } from 'vuex';

    export default {
        computed: {
            ...mapGetters({
                items: 'breadcrumb/getItems',
            }),
        },
    }
</script>
<style lang="scss" scoped>
    .breadcrumb {
        padding: .8rem 0;

        a {
            padding: 0 .2rem;
            color: #AAAAAA;
        }

        li + li::before {
            content: ">";
        }
    }
</style>
