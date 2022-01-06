<template>
    <nav aria-label="breadcrumbs" class="breadcrumb py-2">
        <ul>
            <li>
                <router-link to='/' v-text="$t('functions.index')"/>
            </li>
            <li v-for="(breadcrumb, index) in items">
                <a href="#" v-if="breadcrumb.url === '#' && breadcrumb.html">
                    <component :is="breadcrumb.html.template" v-bind="breadcrumb.html.props" />
                </a>
                <a href="#" v-else-if="breadcrumb.url === '#'" v-text="breadcrumb.name"/>

                <router-link :to="breadcrumb.url" v-else-if="breadcrumb.html">
                    <component :is="breadcrumb.html.template" v-bind="breadcrumb.html.props" />
                </router-link>
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
        a {
            padding: 0 .2rem;
            color: #AAAAAA;
        }

        li + li::before {
            content: ">";
        }
    }
</style>
