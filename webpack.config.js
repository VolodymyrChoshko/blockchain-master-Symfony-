const path                    = require('path');
const webpack                 = require('webpack');
const TerserJSPlugin          = require('terser-webpack-plugin');
const MiniCssExtractPlugin    = require('mini-css-extract-plugin');
const OptimizeCSSAssetsPlugin = require('optimize-css-assets-webpack-plugin');
const CleanObsoleteChunks     = require('webpack-clean-obsolete-chunks');
const { WebpackManifestPlugin } = require('webpack-manifest-plugin');
// const BundleAnalyzerPlugin    = require('webpack-bundle-analyzer').BundleAnalyzerPlugin;

module.exports = {
  mode: 'development',
  entry: {
    builder:   ['./assets/js/builder/index.jsx', './assets/css/builder/index.scss'],
    dashboard: ['./assets/js/dashboard/index.jsx'],
    modals:    './assets/js/modals.jsx',
    style:     './assets/css/index.scss',
    admin:     ['./assets/js/admin.js', './assets/css/admin.scss'],
    ticker:    './assets/js/ticker.js',
    sw:        './assets/js/sw.js',
  },
  output: {
    path:     path.resolve(__dirname, 'public/assets/build'),
    filename: function(data) {
      return data.chunk.name === 'sw'
        ? 'js/[name].js'
        : 'js/[name].[contenthash].js';
    }
  },
  devtool:      'source-map',
  module:       {
    rules: [
      {
        test:    /\.jsx?$/,
        exclude: /(node_modules)/,
        resolve: {
          extensions: [".js", ".jsx"]
        },
        use:     {
          loader: 'babel-loader'
        }
      },
      {
        test: /\.scss$/,
        use:  [
          MiniCssExtractPlugin.loader,
          'css-loader',
          'postcss-loader',
          'sass-loader'
        ]
      },
      {
        test: /\.(png|jpe?g|gif)$/,
        use:  [
          {
            loader:  'file-loader',
            options: {
              publicPath: '/assets/build/css/images',
              outputPath: 'css/images',
            },
          },
        ],
      },
      {
        test: /\.(ttf)$/,
        use:  [
          {
            loader:  'file-loader',
            options: {
              publicPath: '/assets/build/css/fonts',
              outputPath: 'css/fonts',
            },
          },
        ],
      },
      {
        test: /\.(html)$/,
        use:  {
          loader:  'html-loader',
          options: {
            attrs: [':data-src']
          }
        }
      },
    ]
  },
  plugins:      [
    new webpack.DefinePlugin({
      __ENV__: JSON.stringify(process.env.NODE_ENV)
    }),
    new MiniCssExtractPlugin({
      filename: 'css/[name].[contenthash].css'
    }),
    new WebpackManifestPlugin({
      fileName: path.resolve(__dirname, 'public/assets/build/manifest.json'),
      basePath: 'build',
      publicPath: '/assets/build',
    }),
    new CleanObsoleteChunks(),
    // new BundleAnalyzerPlugin()
  ],
  optimization: {
    minimizer: [
      new TerserJSPlugin({}),
      new OptimizeCSSAssetsPlugin({})
    ]
  },
  resolve: {
    alias: {
      fs: false,
      path: require.resolve('path-browserify')
    }
  }
};
