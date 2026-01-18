const defaultConfig = require( '@wordpress/scripts/config/webpack.config' );
const path = require( 'path' );
const glob = require( 'glob' );

// Define common aliases used in Spectra 3.
const commonAliases = {
	// Spectra Free Aliases.
	'@spectra-blocks': path.resolve( __dirname, '../../ultimate-addons-for-gutenberg/spectra-v3/src/blocks/' ),
	'@spectra-components': path.resolve( __dirname, '../../ultimate-addons-for-gutenberg/spectra-v3/src/components/' ),
	'@spectra-extensions': path.resolve( __dirname, '../../ultimate-addons-for-gutenberg/spectra-v3/src/extensions/' ),
	'@spectra-helpers': path.resolve( __dirname, '../../ultimate-addons-for-gutenberg/spectra-v3/src/helpers/' ),
	'@spectra-hooks': path.resolve( __dirname, '../../ultimate-addons-for-gutenberg/spectra-v3/src/hooks/' ),
	'@spectra-assets': path.resolve( __dirname, '../../ultimate-addons-for-gutenberg/spectra-v3/assets/' ),
	'@spectra-admin': path.resolve( __dirname, '../../ultimate-addons-for-gutenberg/admin-core/assets/src/' ),

	// Spectra Pro Aliases.
	'@spectra-pro-assets': path.resolve( __dirname, 'assets/' ),
	'@spectra-pro-data': path.resolve( __dirname, 'data/' ),
	'@spectra-pro-admin': path.resolve( __dirname, 'src/admin' ),
	'@spectra-pro-blocks': path.resolve( __dirname, 'src/blocks/' ),
	'@spectra-pro-components': path.resolve( __dirname, 'src/components/' ),
	'@spectra-pro-helpers': path.resolve( __dirname, 'src/helpers/' ),
	'@spectra-pro-constants': path.resolve( __dirname, 'src/constants/' ),
	'@spectra-pro-extensions': path.resolve( __dirname, 'src/extensions/' ),
};

module.exports = [
	{
		...defaultConfig[ 0 ],
		resolve: {
			alias: {
				...defaultConfig[ 0 ].resolve.alias,
				...commonAliases,
			},
		},
		entry: () => {
			const entries = defaultConfig[ 0 ].entry();

			// Get all style files.
			const styleFiles = glob.sync( './src/styles/**/*.scss' );

			// Get all extension files (JS and SCSS)
			const extensionFiles = glob.sync( './src/extensions/**/*.{js,scss}' );

			// Get all admin files (JS only, since it uses Tailwind with Force UI)
			const adminFiles = glob.sync( './src/admin/**/*.js' );

			// For each file, just get the directory and file name, and add it to the entries.
			styleFiles.forEach( ( file ) => {
				// Find all the '\\', replace them with '/'.
				file = file.replace( /\\/g, '/' );

				const name = file.replace( 'src/styles/', '' ).replace( '.scss', '' );
				
				// Get the filename.
				const filename = name.substring( name.lastIndexOf( '/' ) + 1 );

				// If the file is a Sass Partial file, skip it.
				if ( '_' === filename.charAt( 0 ) ) {
					return;
				}

				entries[ `styles/${ name }` ] = path.resolve( __dirname, file );
			} );

			// Add extension files.
			extensionFiles.forEach( ( file ) => {
				// Find all the '\\', replace them with '/'.
				file = file.replace( /\\/g, '/' );

				const name = file.replace( 'src/extensions/', '' ).replace( /\.(js|scss)$/, '' );
				entries[ `extensions/${ name }` ] = path.resolve( __dirname, file );
			} );

			// Add admin files.
			adminFiles.forEach( ( file ) => {
				// Find all the '\\', replace them with '/'.
				file = file.replace( /\\/g, '/' );

				const name = file.replace( 'src/admin/', '' ).replace( /\.js$/, '' );
				entries[ `admin/${ name }` ] = path.resolve( __dirname, file );
			} );

			// Return the modified entries.
			return entries;
		},
	},
	{
		...defaultConfig[ 1 ],
		resolve: {
			alias: {
				...defaultConfig[ 1 ].resolve.alias,
				...commonAliases,
			},
		},
	},
];
