'use strict';

const autoprefixer = require('autoprefixer');
const componentPaths = require('ambientimpact-drupal-modules/componentPaths');
const glob = require('glob');
const ImageMinimizerPlugin = require('image-minimizer-webpack-plugin');
const MiniCssExtractPlugin = require('mini-css-extract-plugin');
const path = require('path');
const RemoveEmptyScriptsPlugin = require('webpack-remove-empty-scripts');
const SourceMapDevToolPlugin = require('webpack/lib/SourceMapDevToolPlugin');

const isDev = (process.env.NODE_ENV !== 'production');

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

/**
 * Array of plug-in instances to pass to Webpack.
 *
 * @type {Array}
 */
let plugins = [
  new RemoveEmptyScriptsPlugin(),
  new MiniCssExtractPlugin(),
];

if (isDev === true) {
  plugins.push(
    new SourceMapDevToolPlugin({
      filename: '[file].map',
    })
  );
}

module.exports = {

  mode:     isDev ? 'development' : 'production',
  devtool:  isDev ? 'eval-cheap-module-source-map': false,

  entry: getGlobbedEntries,

  plugins: plugins,

  output: {

    path: path.resolve(__dirname, (outputToSourcePaths ? '.' : distPath)),

    // Be very careful with this - if outputting to the source paths, this must
    // not be true or it'll delete everything contained in the directory without
    // warning.
    clean: !outputToSourcePaths,

    // Since Webpack started out primarily for building JavaScript applications,
    // it always outputs a JS files, even if empty. We place these in a
    // temporary directory by default.
    filename: 'temp/[name].js',

    // Asset bundling/copying disabled for now.
    //
    // @see https://stackoverflow.com/questions/68737296/disable-asset-bundling-in-webpack-5#68768905
    assetModuleFilename: '[file][query]',

  },

  module: {
    rules: [
      {
        test: /\.(scss)$/,
        use: [
          {
            loader: MiniCssExtractPlugin.loader,
          },
          {
            loader: 'css-loader',
            options: {
              sourceMap: isDev,
              importLoaders: 2,
            },
          },
          {
            loader: 'postcss-loader',
            options: {
              sourceMap: isDev,
              postcssOptions: {
                plugins: [
                  autoprefixer(),
                ],
              },
            },
          },
          {
            loader: 'sass-loader',
            options: {
              sourceMap: isDev,
              sassOptions: {
                includePaths: componentPaths().all,
              }
            },
          },
        ],
      },
      {
        test: /\.(jpe?g|png)$/i,
        loader: ImageMinimizerPlugin.loader,
        enforce: 'pre',
        options: {
          generator: [
            {
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
              // Annoyingly, file URLs that are altered (e.g. PNG to WebP) by
              // this loader appear to incorrectly generate paths using the
              // platform's path separator. This means that if built on Windows,
              // the URLs will use a backslash (\), which is not a path
              // separator in an HTTP URL but rather an escape character,
              // meaning that the URL will be incorrect and a 404.
              filename: function(file) {
                return file.filename.replaceAll('\\', '/');
              },
            },
          ],
        },
      },
    ],
  },
};