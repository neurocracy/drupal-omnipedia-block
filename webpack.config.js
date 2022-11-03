'use strict';

const autoprefixer = require('autoprefixer');
const componentPaths = require('ambientimpact-drupal-modules/componentPaths');
const Encore = require('@symfony/webpack-encore');
const glob = require('glob');
const ImageMinimizerPlugin = require('image-minimizer-webpack-plugin');
const MiniCssExtractPlugin = require('mini-css-extract-plugin');
const path = require('path');
const RemoveEmptyScriptsPlugin = require('webpack-remove-empty-scripts');

const distPath = '.webpack-dist';

/**
 * Whether to output to the paths where the source files are found.
 *
 * If this is true, compiled Sass files, source maps, etc. will be placed
 * alongside their source files. If this is false, built files will be placed in
 * the dist directory defined by distPath.
 *
 * @type {Boolean}
 */
const outputToSourcePaths = true;

/**
 * Get globbed entry points.
 *
 * This uses the 'glob' package to automagically build the array of entry
 * points, as there are a lot of them spread out over many components.
 *
 * @return {Array}
 *
 * @see https://www.npmjs.com/package/glob
 */
function getGlobbedEntries() {

  return glob.sync(
    // This specifically only searches for SCSS files that aren't partials, i.e.
    // do not start with '_'.
    `./!(${distPath})/**/!(_)*.scss`
  ).reduce(function(entries, currentPath) {

      const parsed = path.parse(currentPath);

      entries[`${parsed.dir}/${parsed.name}`] = currentPath;

      return entries;

  }, {});

};

// @see https://symfony.com/doc/current/frontend/encore/installation.html#creating-the-webpack-config-js-file
if (!Encore.isRuntimeEnvironmentConfigured()) {
  Encore.configureRuntimeEnvironment(process.env.NODE_ENV || 'dev');
}

Encore
.setOutputPath(path.resolve(__dirname, (outputToSourcePaths ? '.' : distPath)))
// Encore will complain if the public path doesn't start with a slash.
// Unfortunately, it doesn't seem Webpack's automatic public path works here.
//
// @see https://webpack.js.org/guides/public-path/#automatic-publicpath
.setPublicPath('/')
.setManifestKeyPrefix('')
.disableSingleRuntimeChunk()
.configureFilenames({
  js:     'temp/[name].js',
  assets: '[file][query]',
})
.addEntries(getGlobbedEntries())
.enableSourceMaps(!Encore.isProduction())

// We don't use Babel so we can probably just remove all presets to speed it up.
//
// @see https://github.com/symfony/webpack-encore/issues/154#issuecomment-361277968
.configureBabel(function(config) {
  config.presets = [];
})
.addPlugin(new RemoveEmptyScriptsPlugin())
.enableSassLoader(function(options) {
  options.sassOptions = {includePaths: componentPaths().all};
})
.enablePostCssLoader(function(options) {
  options.postcssOptions = {
    plugins: [
      autoprefixer(),
    ],
  };
})
// Re-enable automatic public path for paths referenced in CSS.
//
// @see https://github.com/symfony/webpack-encore/issues/915#issuecomment-1189319882
.configureMiniCssExtractPlugin(function(config) {
  config.publicPath = 'auto';
})
// Disable the Encore image rule because we provide our own loader config.
.configureImageRule({enabled: false})
.addLoader({
  test: /\.(jpe?g|png)$/i,
  loader: ImageMinimizerPlugin.loader,
  enforce: 'pre',
  options: {
    generator: [{
      preset: 'webp',
      implementation: ImageMinimizerPlugin.sharpGenerate,
      options: {
        plugins: ['sharp-webp'],
        encodeOptions: {
          webp: {
            quality: 80,
          },
        },
      },
      // Annoyingly, file URLs that are altered (e.g. PNG to WebP) by this
      // loader appear to incorrectly generate paths using the platform's path
      // separator. This means that if built on Windows, the URLs will use a
      // backslash (\), which is not a path separator in an HTTP URL but rather
      // an escape character, meaning that the URL will be incorrect and a 404.
      filename: function(file) {
        return file.filename.replaceAll('\\', '/');
      },
    }],
  },
});

module.exports = Encore.getWebpackConfig();
