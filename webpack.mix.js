const mix = require('laravel-mix');


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

const MomentLocalesPlugin = require('moment-locales-webpack-plugin');
const { CleanWebpackPlugin } = require('clean-webpack-plugin');


mix.js('resources/src/main.js', 'public').js('resources/src/login.js', 'public')
    .vue();

    mix.webpackConfig({
        output: {

            filename:'js/[name].min.js',
            chunkFilename: 'js/bundle/[name].[hash].js',
          },
        plugins: [
            new MomentLocalesPlugin(),
            new CleanWebpackPlugin({
                cleanOnceBeforeBuildPatterns: ['./js/*']
              }),
        ]
    });

    // Silence noisy Dart Sass deprecation warnings coming from vendored
    // Bootstrap/Bootstrap-Vue SCSS (legacy @import + old color functions).
    mix.override(webpackConfig => {
        webpackConfig.module.rules.forEach(rule => {
            (rule.oneOf || [rule]).forEach(subRule => {
                (subRule.use || []).forEach(loader => {
                    if (typeof loader === 'object' && loader.loader && loader.loader.includes('sass-loader')) {
                        loader.options = loader.options || {};
                        loader.options.sassOptions = {
                            ...loader.options.sassOptions,
                            quietDeps: true,
                            silenceDeprecations: ['import', 'global-builtin', 'color-functions', 'legacy-js-api', 'slash-div', 'if-function'],
                        };
                    }
                });
            });
        });
    });

