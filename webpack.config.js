/**
 * External Dependencies
 */
const path                 = require( 'path' );
const MiniCssExtractPlugin = require( 'mini-css-extract-plugin' );

/**
 * WordPress Dependencies
 */
const defaultConfig = require( '@wordpress/scripts/config/webpack.config.js' );

module.exports = {
	...defaultConfig,
	mode: 'production',
	entry: {
		frontend: path.resolve( __dirname, 'assets/js/src', 'frontend.js' ),
		admin: path.resolve( __dirname, 'assets/js/src', 'admin.js' ),
	},
	output: {
		path: path.resolve( __dirname, 'assets/js/build' ),
		filename: '[name].js',
	},/*
	module: {
		...defaultConfig.module,
		rules: [
			...defaultConfig.module.rules,
			{
				test: /\.(sc|sa|c)ss$/,
				use: [
					MiniCssExtractPlugin.loader,
					'css-loader',
					'sass-loader',
				],
			},
		],
	},
	plugins: [
		...defaultConfig.plugins,
		new MiniCssExtractPlugin({
			filename: '../../css/[name].css',
		}),
	],*/
};
