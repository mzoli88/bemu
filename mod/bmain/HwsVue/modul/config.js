var is_dev = process.argv.includes("--mode=development");

if (is_dev) {
  var path = require("path").resolve(__dirname, "../../dev");
} else {
  var path = require("path").resolve(__dirname, "../../build");
}

const webpack = require('webpack');
const { VueLoaderPlugin } = require("vue-loader");
var MiniCssExtractPlugin = require("mini-css-extract-plugin");
var HtmlWebpackPlugin = require("html-webpack-plugin");
const CssMinimizerPlugin = require("css-minimizer-webpack-plugin");


const fs = require("fs");
if (!fs.existsSync(path)) fs.mkdirSync(path);
fs.writeFile(path + "/.htaccess", 'AddDefaultCharset utf-8', function (err) {
  if (err) throw err;
  // fs.copyFile('./hws/TestData.js', 'dev/TestData.js', err => { });
});


module.exports = {
  mode: is_dev ? "development" : "production",

  entry: "./hws/modul/init.js",


  output: {
    filename: '[name].bundle.[contenthash].js',
    clean: {
      keep: /^(\.htaccess|config.php)/,
    },
    path: path,
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
    ],
  },
  plugins: [
    new VueLoaderPlugin(),
    new MiniCssExtractPlugin({
      filename: '[name].style.[contenthash].css',
    }),
    new HtmlWebpackPlugin({
      template: "hws/modul/index.html",
    }),
    new webpack.DefinePlugin({
      __VUE_OPTIONS_API__: true,
      __VUE_PROD_DEVTOOLS__: is_dev ? true : false, // false If you don't want people sneaking around your components in production.
    }),
  ],
};
