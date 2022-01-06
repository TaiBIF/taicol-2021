/**
 * Vue Router Settings
 *
 */
import Vue from 'vue';
import VueRouter from 'vue-router';
import store from './store';

Vue.use(VueRouter);

// Lazy load pages
const Index = () => import(/* webpackChunkName: "index" */'./pages/IndexPage');
const Result = () => import(/* webpackChunkName: "results" */'./pages/ResultPage');
const Reference = () => import(/* webpackChunkName: "reference" */'./pages/ReferencePage');
const TaxonNameList = () => import(/* webpackChunkName: "taxon-name-list" */'./pages/TaxonNameListPage')
const ReferenceList = () => import(/* webpackChunkName: "reference-list" */'./pages/ReferenceListPage');

const routes = [
    {
        name: 'login',
        path: '/login',
        component: () => import(/* webpackChunkName: "login" */'./pages/LoginPage'),
        meta: {
            backgroundColor: 'grey',
            allowAnonymous: true,
        },
    },
    {
        name: 'register',
        path: '/register',
        component: () => import(/* webpackChunkName: "register" */'./pages/RegisterPage'),
        meta: {
            backgroundColor: 'grey',
            allowAnonymous: true,
        },
    },
    {
        name: 'index',
        path: '/',
        component: Index,
        meta: {
            backgroundColor: 'grey',
            allowAnonymous: true,
        },
    },
    {
        name: 'referenceForm',
        path: '/reference',
        component: () => import(/* webpackChunkName: "reference-create" */'./pages/ReferenceCreatePage'),
        meta: {
            backgroundColor: 'grey',
        },
    },
    {
        name: 'reference-list',
        path: '/references',
        component: ReferenceList,
        meta: {
            allowAnonymous: true,
            searchbar: true,
        },
    },
    {
        name: 'reference',
        path: '/references/:id',
        component: Reference,
        meta: {
            allowAnonymous: true,
        },
    },
    {
        name: 'reference-edit',
        path: '/references/:id/edit',
        component: () => import(/* webpackChunkName: "reference-edit" */'./pages/ReferenceFormPage'),
    },
    {
        name: 'reference-usages',
        path: '/references/:id/usages',
        component: () => import(/* webpackChunkName: "reference-list" */'./pages/ReferenceUsagePage'),
        meta: {
            allowAnonymous: true,
        },
    },
    {
        name: 'person',
        path: '/persons/:id',
        component: () => import(/* webpackChunkName: "person-page" */'./pages/PersonPage'),
        meta: {
            backgroundColor: 'grey',
            allowAnonymous: true,
        },

    },
    {
        name: 'person-edit',
        path: '/persons/:id/edit',
        component: () => import(/* webpackChunkName: "person-page" */'./pages/PersonFormPage'),
        meta: {
            backgroundColor: 'grey',
        },

    },
    {
        name: 'taxon-name-list',
        path: '/taxon-names',
        component: TaxonNameList,
        meta: {
            backgroundColor: 'white',
            allowAnonymous: true,
            searchbar: true,
        },
    },
    {
        name: 'taxon-name',
        path: '/taxon-names/:id',
        component: () => import(/* webpackChunkName: "taxon-name-page" */ './pages/TaxonNamePage'),
        meta: {
            backgroundColor: 'grey',
            allowAnonymous: true,
        },
    },
    {
        name: 'taxon-name-create',
        path: '/taxon-name',
        component: () => import(/* webpackChunkName: "taxon-name-page" */ './pages/TaxonNameEditPage'),
        meta: {
            backgroundColor: 'grey',
        },
    },
    {
        name: 'taxon-name-usages-compare',
        path: '/taxon-names/:id/compare',
        component: () => import(/* webpackChunkName: "usage-compare-page" */'./pages/UsageComparePage'),
        meta: {
            allowAnonymous: true,
        },
    },
    {
        name: 'taxon-name-edit',
        path: '/taxon-names/:id/edit',
        component: () => import(/* webpackChunkName: "taxon-name-form" */'./pages/TaxonNameFormPage'),
        meta: {
            backgroundColor: 'grey',
        },
    },
    {
        name: 'namespace-list',
        path: '/namespaces',
        component: () => import(/* webpackChunkName: "namespace-list-page" */ './pages/NamespaceListPage'),
        meta: {
            backgroundColor: 'grey',
        },
    },
    {
        name: 'namespace',
        path: '/namespaces/:id/usages',
        component: () => import(/* webpackChunkName: "namespace-page" */ './pages/UsageContainerPage'),
        meta: {
            backgroundColor: 'grey',
        },
        children: [
            {
                name: 'usage',
                path: '',
                component: () => import(/* webpackChunkName: "namespace-page" */ './pages/NamespacePage'),
                meta: {
                    backgroundColor: 'grey',
                },
            },
            {
                name: 'namespace-usages',
                path: ':usageId',
                component: () => import(/* webpackChunkName: "namespace-usage-page" */ './pages/UsagePage'),
                meta: {
                    backgroundColor: 'grey',
                },
            },
        ]
    },
    {
        name: 'favorite',
        path: '/favorite-folders',
        component: () => import(/* webpackChunkName: "favorite-folder-page" */ './pages/FavoriteFolderPage'),
        meta: {
            backgroundColor: 'grey',
        },
    },
    {
        name: 'favorite',
        path: '/favorite-folders/:id',
        component: () => import(/* webpackChunkName: "favorite-folder-item-page" */ './pages/FavoriteFolderItemPage'),
        meta: {
            backgroundColor: 'grey',
        },
    },
    {
        name: 'admin-manager',
        path: '/admin',
        component: () => import(/* webpackChunkName: "admin" */ './pages/admin/AdminGroup'),
        children: [
            {
                name: 'admin-users',
                path: 'users',
                component: () => import(/* webpackChunkName: "user-list-page" */ './pages/admin/UserListPage'),
            },
            {
                name: 'admin-users',
                path: 'users/:id',
                component: () => import(/* webpackChunkName: "user-page" */ './pages/admin/UserPage'),
            }
        ]
    }
];

const router = new VueRouter({
    mode: 'history',
    routes,
});

const originalPush = VueRouter.prototype.push
VueRouter.prototype.push = function push(location, onResolve, onReject) {
    if (onResolve || onReject) return originalPush.call(this, location, onResolve, onReject)
    return originalPush.call(this, location).catch(err => err)
}

router.beforeEach((to, from, next) => {
    if (!to.meta?.allowAnonymous && !store.getters['auth/authenticated']) {
        next({
            path: '/login',
            query: { redirect: to.fullPath },
        })
    } else {
        next();
    }
});


export default router;
