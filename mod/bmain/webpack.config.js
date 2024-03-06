
const path = require('path');

const webpack = require('webpack');
const { VueLoaderPlugin } = require("vue-loader");
var MiniCssExtractPlugin = require("mini-css-extract-plugin");
var HtmlWebpackPlugin = require("html-webpack-plugin");
const CssMinimizerPlugin = require("css-minimizer-webpack-plugin");

module.exports = {
  mode: 'production',

  entry: "./init.js",

  devtool: false,

  performance: {
    //ne dobjon warningot mert a mére nagyobb
    maxEntrypointSize: 512000,
    maxAssetSize: 512000
  },

  output: {
    filename: '[name].[contenthash].bundle.js',
    path: path.resolve(__dirname, 'public/page'),
    clean: {
      keep: /^(\.htaccess|config.json)/,
    },
    assetModuleFilename: 'assets/[name][ext][query]'
  },

  watchOptions: {
    ignored: ["node_modules/**"],
  },

  optimization: {
    splitChunks: {
      chunks: 'all',
    },
    minimizer: [
      `...`,
      new CssMinimizerPlugin(),
    ],
  },

  module: {
    rules: [
      {
        test: /\.vue$/,
        loader: "vue-loader",
        options: {
          reactivityTransform: true
        }
      },
      {
        test: /\.css$/,
        use: [
          {
            loader: MiniCssExtractPlugin.loader,
            options: {
              esModule: false,
            },
          },
          {
            loader: 'css-loader',
            options: {
              url: false
            }
          },
        ],
      },
      {
        test: /\.(png|svg|jpg|jpeg|gif|ico)$/i,
        dependency: { not: ['url'] },
        type: 'asset/resource',
      },
      {
        test: /\.(woff|woff2|eot|ttf|otf)$/i,
        dependency: { not: ['url'] },
        type: 'asset'
      },
      {
        test: /\.json$/i,
        dependency: { not: ['url'] },
        type: 'asset'
      },
    ],
  },
  plugins: [
    new VueLoaderPlugin(),
    new MiniCssExtractPlugin({
      filename: '[name].[contenthash].style.css',
    }),
    new HtmlWebpackPlugin({
      template: "./src/index.html",
      filename: '../index.html',
    }),
    new webpack.DefinePlugin({
      __VUE_OPTIONS_API__: true,
      __VUE_PROD_DEVTOOLS__: false,
    }),
  ],
};
