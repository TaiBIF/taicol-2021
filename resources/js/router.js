/**
 * Vue Router Settings
 *
 */
import Vue from 'vue';
import VueRouter from 'vue-router';
import store from './store';

import SearchBarLayout from './layout/SearchBarLayout.vue';

Vue.use(VueRouter);

// Lazy load pages
const Index = () => import(/* webpackChunkName: "index" */'./pages/IndexPage.vue');

const routes = [
    {
        name: 'login',
        path: 'login',
        component: () => import(/* webpackChunkName: "login" */'./pages/LoginPage.vue'),
        meta: {
            backgroundColor: 'grey',
            allowAnonymous: true,
        },
    },
    {
        name: 'register',
        path: 'register',
        component: () => import(/* webpackChunkName: "register" */'./pages/RegisterPage.vue'),
        meta: {
            backgroundColor: 'grey',
            allowAnonymous: true,
        },
    },
    {
        name: 'index',
        path: '',
        component: Index,
        meta: {
            backgroundColor: 'grey',
            allowAnonymous: true,
        },
    },
    {
        path: '',
        component: SearchBarLayout,
        children: [
            {
                name: 'taxon-name-list-page',
                path: 'taxon-names',
                component: () => import(/* webpackChunkName: "taxon-name-list-page" */'./pages/TaxonNameListPage.vue'),
                meta: {
                    backgroundColor: 'white',
                    allowAnonymous: true,
                },
            },
            {
                name: 'taxon-name-page',
                path: 'taxon-names/:id',
                component: () => import(/* webpackChunkName: "taxon-name-page" */'./pages/TaxonNamePage.vue'),
                meta: {
                    backgroundColor: 'grey',
                    allowAnonymous: true,
                },
            },
            {
                name: 'reference-list-page',
                path: 'references',
                component: () => import(/* webpackChunkName: "reference-list-page" */'./pages/ReferenceListPage.vue'),
                meta: {
                    allowAnonymous: true,
                },
            },
            {
                name: 'reference-page',
                path: 'references/:id',
                component: () => import(/* webpackChunkName: "reference-page" */'./pages/ReferencePage.vue'),
                meta: {
                    allowAnonymous: true,
                },
            },

            {
                name: 'person-list-page',
                path: 'persons',
                component: () => import(/* webpackChunkName: "person-list-page" */'./pages/PersonListPage.vue'),
                meta: {
                    allowAnonymous: true,
                },
            },
            {
                name: 'person-page',
                path: 'persons/:id',
                component: () => import(/* webpackChunkName: "person-page" */'./pages/PersonPage.vue'),
                meta: {
                    backgroundColor: 'grey',
                    allowAnonymous: true,
                },

            },
        ],
    },
    {
        name: 'reference-create',
        path: 'reference',
        component: () => import(/* webpackChunkName: "reference-create" */'./pages/ReferenceCreatePage.vue'),
        meta: {
            backgroundColor: 'grey',
        },
    },
    {
        name: 'reference-edit',
        path: 'references/:id/edit',
        component: () => import(/* webpackChunkName: "reference-edit" */'./pages/ReferenceFormPage.vue'),
        meta: {
            backgroundColor: 'grey',
        },
    },
    {
        name: 'reference-usages',
        path: 'references/:id/usages',
        component: () => import(/* webpackChunkName: "reference-list" */'./pages/ReferenceUsagePage.vue'),
        meta: {
            allowAnonymous: true,
        },
    },
    {
        name: 'reference-usages-edit-container',
        path: 'references/:id/usages-edit',
        component: () => import(
            /* webpackChunkName: "usage-container-page" */ './pages/UsageContainerPage.vue'
        ),
        children: [
            {
                name: 'reference-usages-list',
                path: '',
                component: () => import(
                    /* webpackChunkName: "reference-usage-list" */'./pages/UsageListPage.vue'
                ),
                meta: {
                    type: 'reference',
                    allowAnonymous: false,
                    backgroundColor: 'grey',
                },
            },
            {
                name: 'reference-usages-edit',
                path: ':usageId',
                component: () => import(
                    /* webpackChunkName: "reference-usage-page" */ './pages/UsagePage.vue'
                ),
                meta: {
                    type: 'reference',
                    backgroundColor: 'grey',
                },
            },
        ],
    },
    {
        name: 'person-create',
        path: 'person',
        component: () => import(/* webpackChunkName: "person-create" */'./pages/PersonFormPage.vue'),
        meta: {
            backgroundColor: 'grey',
        },

    },
    {
        name: 'person-edit',
        path: 'persons/:id/edit',
        component: () => import(/* webpackChunkName: "person-page" */'./pages/PersonFormPage.vue'),
        meta: {
            backgroundColor: 'grey',
        },

    },
    {
        name: 'taxon-name-create',
        path: 'taxon-name',
        component: () => import(/* webpackChunkName: "taxon-name-form-page" */ './pages/TaxonNameEditPage.vue'),
        meta: {
            backgroundColor: 'grey',
        },
    },
    {
        name: 'taxon-name-edit',
        path: 'taxon-names/:id/edit',
        component: () => import(/* webpackChunkName: "taxon-name-form-page" */'./pages/TaxonNameFormPage.vue'),
        meta: {
            backgroundColor: 'grey',
        },
    },
    {
        name: 'taxon-name-usages-compare',
        path: 'taxon-names/:id/compare',
        component: () => import(/* webpackChunkName: "usage-compare-page" */'./pages/UsageComparePage.vue'),
        meta: {
            allowAnonymous: true,
        },
    },
    {
        name: 'namespace-list',
        path: 'namespaces',
        component: () => import(/* webpackChunkName: "namespace-list-page" */ './pages/NamespaceListPage.vue'),
        meta: {
            backgroundColor: 'grey',
        },
    },
    {
        name: 'namespace',
        path: 'namespaces/:id/usages',
        component: () => import(/* webpackChunkName: "namespace-page" */ './pages/UsageContainerPage.vue'),
        meta: {
            backgroundColor: 'grey',
        },
        children: [
            {
                name: 'namespace-usage-list',
                path: '',
                component: () => import(/* webpackChunkName: "namespace-page" */ './pages/UsageListPage.vue'),
                meta: {
                    type: 'namespace',
                    backgroundColor: 'grey',
                },
            },
            {
                name: 'namespace-usages',
                path: ':usageId',
                component: () => import(/* webpackChunkName: "namespace-usage-page" */ './pages/UsagePage.vue'),
                meta: {
                    type: 'namespace',
                    backgroundColor: 'grey',
                },
            },
        ],
    },
    {
        name: 'favorite-folder-list',
        path: 'favorite-folders',
        component: () => import(/* webpackChunkName: "favorite-folder-page" */ './pages/FavoriteFolderPage.vue'),
        meta: {
            backgroundColor: 'grey',
        },
    },
    {
        name: 'favorite-folder',
        path: 'favorite-folders/:id',
        component: () => import(
            /* webpackChunkName: "favorite-folder-item-page" */ './pages/FavoriteFolderItemPage.vue'
        ),
        meta: {
            backgroundColor: 'grey',
        },
    },

    /**
     * 管理員介面
     */
    {
        name: 'admin-manager',
        path: 'admin',
        component: () => import(/* webpackChunkName: "admin" */ './pages/admin/AdminGroup.vue'),
        children: [
            {
                name: 'admin-user-list-page',
                path: 'users',
                component: () => import(/* webpackChunkName: "user-list-page" */ './pages/admin/UserListPage.vue'),
            },
            {
                name: 'admin-user-edit',
                path: 'users/:id',
                component: () => import(/* webpackChunkName: "user-page" */ './pages/admin/UserPage.vue'),
                meta: {
                    backgroundColor: 'grey',
                },
            },
            {
                name: 'admin-persons-import',
                path: 'persons-import',
                component: () => import(
                    /* webpackChunkName: "person-import-page" */ './pages/admin/PersonImportPage.vue'
                ),
                meta: {
                    backgroundColor: 'grey',
                },
            },
            {
                name: 'admin-taxon-names-import',
                path: 'taxon-names-import',
                component: () => import(
                    /* webpackChunkName: "taxon-name-import-page" */ './pages/admin/TaxonNameImportPage.vue'
                ),
                meta: {
                    backgroundColor: 'grey',
                },
            },
            {
                name: 'admin-test',
                path: 'test',
                component: () => import(
                    /* webpackChunkName: "taxon-name-import-page" */ './pages/admin/ReferenceImportPage.vue'
                ),
                meta: {
                    backgroundColor: 'grey',
                },
            },
            {
                name: 'admin-references-import',
                path: 'references-import',
                component: () => import(
                    /* webpackChunkName: "reference-import-page" */ './pages/admin/ReferenceImportPage.vue'
                ),
                meta: {
                    backgroundColor: 'grey',
                },
            },
        ],
    },
];

const router = new VueRouter({
    mode: 'history',
    routes: [{
        path: '/:lang(zh-tw|en-us)',
        component: {
            template: '<router-view />',
        },
        meta: {
            backgroundColor: 'grey',
            allowAnonymous: true,
        },
        children: routes,
    }],
});

const originalPush = VueRouter.prototype.push;
VueRouter.prototype.push = function push(location, onResolve, onReject) {
    if (onResolve || onReject) return originalPush.call(this, location, onResolve, onReject);
    return originalPush.call(this, location).catch((err) => err);
};

router.beforeEach((to, from, next) => {
    let { lang } = to.params;

    if (from === VueRouter.START_LOCATION) {
        console.info('- router initial');
        lang = !['zh-tw', 'en-us'].includes(lang) ? Vue.i18n.locale() ?? 'zh-tw' : lang;
        store.commit('SET_LANG', lang);
    }

    if (!['zh-tw', 'en-us'].includes(lang)) {
        lang = !['zh-tw', 'en-us'].includes(lang) ? Vue.i18n.locale() ?? 'zh-tw' : lang;
        store.commit('SET_LANG', lang);
    }

    if (!to.name) {
        next({ name: 'index', params: { lang } });
    } else if (!to.meta?.allowAnonymous && !store.getters['auth/authenticated']) {
        next({ name: 'login', query: { redirect: to.fullPath }, params: { lang } });
    } else {
        next();
    }
});

export default router;
