const mix = require('laravel-mix');
const path = require('path');

const tailwindcss = require('tailwindcss');

require('laravel-mix-eslint');
/*
 |--------------------------------------------------------------------------
 | Mix Asset Management
 |--------------------------------------------------------------------------
 |
 | Mix provides a clean, fluent API for defining some Webpack build steps
 | for your Laravel application. By default, we are compiling the Sass
 | file for the application as well as bundling up all the JS files.
 |
 */

mix
    // .eslint({
    //     extensions: ['js', 'vue', 'ts'], overrideConfigFile: './.eslintrc.js',
    // })
    .ts('resources/js/app.js', 'public/js')
    .vue({ version: 2 })
    .sass('resources/sass/app.scss', 'public/css')
    .js('resources/js/services/computeReference.js', 'public/js')
    .options({
        postCss: [tailwindcss('./tailwind.config.js')],
    })
    .webpackConfig({
        resolve: {
            alias: {
                '@': path.resolve('resources/sass/'),
            },
        },
        module: {
            rules: [{
                test: /\.scss$/,
                use: [{
                    loader: 'sass-loader',
                    options: {
                        implementation: require('node-sass'),
                        additionalData: '@import "~@/_variables.scss";',
                    },
                }],
            }],
        },
        output: {
            chunkFilename: mix.inProduction() ? 'js/chunks/[name].[chunkhash].js' : 'js/chunks/[name].js',
        },
    })
    .version();
