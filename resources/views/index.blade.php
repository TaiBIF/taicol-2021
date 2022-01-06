<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>物種學名管理工具</title>
        <link href="https://fonts.googleapis.com/css2?family=Noto+Sans+TC:wght@500;700;900&display=swap" rel="stylesheet">
        <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.14.0/css/all.min.css" rel="stylesheet">
        <link href="{{ asset(mix('/css/app.css')) }}" rel="stylesheet">
    </head>
    <body>
        <div id="app" :class="{'is-grey': $route.meta.backgroundColor === 'grey'}">
            <t-header></t-header>
            <div class="page-container">
                <router-view v-if="$route.name === 'taxon-name'" :key="$route.path + JSON.stringify($route.query)"></router-view>
                <router-view v-else></router-view>
            </div>
            <layers></layers>
            <modal></modal>
        </div>
        <script src="{{ asset(mix('/js/app.js')) }}"></script>
    </body>
    @include('ga')
</html>
