const path = require( 'path' );
const fs   = require('fs');

const TerserPlugin = require( 'terser-webpack-plugin' );
const isProduction = true;

// Inspo from Sage Tubiz :).
const WooCommerceDependencyExtractionWebpackPlugin = require( '@woocommerce/dependency-extraction-webpack-plugin' );

const wcDepMap = {
	'@woocommerce/blocks-registry': ['wc', 'wcBlocksRegistry'],
	'@woocommerce/settings'       : ['wc', 'wcSettings']
};

const wcHandleMap = {
	'@woocommerce/blocks-registry': 'wc-blocks-registry',
	'@woocommerce/settings'       : 'wc-settings'
};

const requestToExternal = (request) => {
	if ( wcDepMap[ request ] ) {
		return wcDepMap[ request ];
	}
};

const requestToHandle = ( request ) => {
	if ( wcHandleMap[ request ]) {
		return wcHandleMap[ request ];
	}
};
// Be like say Inspo ends here. 👊

/**
 * WordPress Dependencies
 */
const defaultConfig = require( '@wordpress/scripts/config/webpack.config.js' );

// Function to automatically generate entries by looping through a folder.
const generateEntries = ( baseDir, folder ) => {
    const srcDir  = path.resolve( baseDir, folder );
    const files   = fs.readdirSync( srcDir );
    const entries = {};

    files.forEach( ( file ) => {
        const ext  = path.extname( file );
        const name = path.basename( file, ext ); // Remove the extension.

        if ( ext === '.js' ) {
            // Create entry for each JS file with folder subdirectory in the key.
            entries[`${folder}/${name}`] = path.resolve( srcDir, file );
        }
    });

    return entries;
};

const entries = {
    ...generateEntries( __dirname, 'assets/js/src/frontend' ),
    ...generateEntries( __dirname, 'assets/js/src/blocks' ),
    ...generateEntries( __dirname, 'assets/js/src/admin' ),
};

module.exports = {
	...defaultConfig,
	mode: 'production',
	entry: entries,
	output: {
		path: path.resolve( __dirname, 'assets/js/build' ),
		filename: ( pathData ) => {
            const folder = path.dirname( pathData.chunk.name ).replace( 'assets/js/src/', '' );
            const name   = path.basename( pathData.chunk.name );
            return isProduction ? `${folder}/${name}.min.js` : `${folder}/${name}.js`;
        },
	},
	optimization: {
		minimize: isProduction,
		minimizer: [
			new TerserPlugin({
				terserOptions: {
					compress: {
						drop_console: true, // Removes console logs in production.
					},
				},
				extractComments: false, // Prevents generating separate files for license comments.
			}),
		],
	},
	plugins: [
		...defaultConfig.plugins.filter(
			(plugin) =>
				plugin.constructor.name !== 'DependencyExtractionWebpackPlugin'
		),
		new WooCommerceDependencyExtractionWebpackPlugin({
			requestToExternal,
			requestToHandle
		})
	],
};
