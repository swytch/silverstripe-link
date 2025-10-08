const Path = require('path');
const { JavascriptWebpackConfig, CssWebpackConfig } = require('@silverstripe/webpack-config');

const PATHS = {
  ROOT: Path.resolve(),
  SRC: Path.resolve('client/src'),
  DIST: Path.resolve('client/dist'),
};

const isDevelopment = process.env.NODE_ENV === 'development';

const config = [
  new JavascriptWebpackConfig('js', PATHS)
    .setEntry({
      bundle: `${PATHS.SRC}/bundles/bundle.js`
    })
    .mergeConfig({
      mode: isDevelopment ? 'development' : 'production',
      devtool: isDevelopment ? 'source-map' : false,
      output: {
        path: PATHS.DIST,
        filename: isDevelopment ? 'js/[name].js' : 'js/[name].js',
      },
    })
    .getConfig(),
  // new CssWebpackConfig('css', PATHS)
  //   .setEntry({
  //     bundle: `${PATHS.SRC}/styles/bundle.scss`,
  //   })
  //   .mergeConfig({
  //     mode: isDevelopment ? 'development' : 'production',
  //     devtool: isDevelopment ? 'source-map' : false,
  //     output: {
  //       path: PATHS.DIST,
  //       filename: isDevelopment ? 'css/[name].css' : 'css/[name].min.css',
  //     },
  //   })
  //   .getConfig(),
];

// Use WEBPACK_CHILD=js or WEBPACK_CHILD=css env var to run a single config
module.exports = (process.env.WEBPACK_CHILD)
  ? config.find((entry) => entry.name === process.env.WEBPACK_CHILD)
  : module.exports = config;

