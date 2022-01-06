<template>
    <nav :class="{'has-background-grey': !isNotGrey}" class="header navbar">
        <div v-if="!isNotGrey" class="navbar-start">
            <router-link to="/">
                <h1 class="title" v-text="$t('title')"></h1>
            </router-link>
        </div>
        <div class="navbar-end ">
            <!--            <a class="navbar-item" v-text="$t('functions.myCollection')" />-->
            <template v-if="authenticated">
                <router-link class="navbar-item" to="/taxon-name" v-text="$t('functions.createTaxonName')"/>

                <router-link class="navbar-item" to="/reference" v-text="$t('functions.createReference')"/>

                <router-link class="navbar-item" to="/namespaces" v-text="$t('functions.myNamespaces')"/>

                <router-link class="navbar-item" to="/favorite-folders" v-text="'我的收藏夾'"/>

                <router-link class="navbar-item" to="/admin/users" v-if="user.roleId === 1" v-text="'管理使用者'"/>

                <div class="navbar-item" v-text="user ? user.name : ''"></div>

                <a class="navbar-item" v-on:click="onLogout" v-text="$t('functions.logout')"/>
            </template>
            <template v-else>
                <router-link class="navbar-item" to="/login" v-text="$t('functions.login')"/>
            </template>
        </div>
    </nav>
</template>
<script>
    import { mapGetters } from 'vuex';

    export default {
        computed: {
            ...mapGetters({
                authenticated: 'auth/authenticated',
                user: 'auth/user',
            }),
            isNotGrey() {
                return this.$route.path === '/' || this.$route.path === '/login';
            }
        },
        methods: {
            onLogout() {
                this.$store.dispatch('auth/logout');
                this.$router.push('/');
            },

        },
    }
</script>
<style lang="scss" scoped>
    .header {
        width: 100%;
        z-index: 100;
        position: fixed;
        top: 0;
        background-color: #f2f2f2;
        text-align: center;
        padding-right: 1rem;
        height: $navbar-height;

        .title {
            color: white;
            font-size: 1.8rem;
            line-height: 3rem;
            padding-left: 1rem
        }

        &.has-background-grey {
            .navbar-item {
                color: white;

                &:hover, &:focus, &:active {
                    background: $primary;
                }
            }
        }
    }
</style>
