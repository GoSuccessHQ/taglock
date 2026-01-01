const defaultConfig = require('@wordpress/scripts/config/webpack.config');
const path = require('path');

module.exports = {
	...defaultConfig,
	entry: {
		admin: path.resolve(__dirname, 'assets/src/admin/index.js'),
		frontend: path.resolve(__dirname, 'assets/src/frontend/index.js'),
	},
	output: {
		path: path.resolve(__dirname, 'assets/build'),
		filename: '[name].js',
	},
};
