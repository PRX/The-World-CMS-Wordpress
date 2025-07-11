<?php

require_once __DIR__ . '/wp-config-constants.php';

if ( file_exists( __DIR__ . '/wp-config-' . SERVER_PLATFORM_NAME . '.php' ) && isset( $_ENV[ SERVER_PLATFORM_ENVIRONMENT_VARIABLE_NAME ] ) ) {

	/**
	 * Server platform settings.
	 *
	 * Loads on platform servers and Lando environments using a recipe for that platform.
	 *
	 * This config MUST translate platform environment type variable to the appropriate
	 * value for `WP_ENVIRONMENT_TYPE`, which will be used to load environment config settings.
	 */

	require_once __DIR__ . '/wp-config-' . SERVER_PLATFORM_NAME . '.php';
}

// adjust Redis host and port if necessary
define( 'WP_REDIS_HOST', '127.0.0.1' );
define( 'WP_REDIS_PORT', 6379 );

// change the prefix and database for each site to avoid cache data collisions
define( 'WP_REDIS_PREFIX', 'wordpress' );
define( 'WP_REDIS_DATABASE', 0 ); // 0-15

// reasonable connection and read+write timeouts
define( 'WP_REDIS_TIMEOUT', 1 );
define( 'WP_REDIS_READ_TIMEOUT', 1 );

/**
 * Wire up S3 Uploads key and secret values to ENV variable.
 * Needs to be assigned AFTER platform config.
 */
define( 'S3_UPLOADS_KEY', getenv( 'S3_KEY' ) );
define( 'S3_UPLOADS_SECRET', getenv( 'S3_SECRET' ) );


/** Standard wp-config.php stuff from here on down. */

/**
 * WordPress Database Table prefix.
 *
 * You can have multiple installations in one database if you give each a unique
 * prefix. Only numbers, letters, and underscores please!
 */
$table_prefix = 'wp_';

if ( in_array( getenv( 'WP_ENVIRONMENT_TYPE' ), array( 'staging', 'production' ), true ) ) {

	/**
	 * Enable production environment configs.
	 * - Staging
	 * - Production
	*/

	// IMPORTANT: ensure production config does not include wp-settings.php.
	require_once __DIR__ . '/wp-config-production.php';
} else {

	/**
	 * Enable development environment configs.
	 * - Local
	 * - Development
	*/

	// IMPORTANT: ensure development config does not include wp-settings.php.
	require_once __DIR__ . '/wp-config-development.php';
}

if ( file_exists( __DIR__ . '/wp-config-local.php' ) ) {

	/**
	 * Local configuration information.
	 *
	 * If you are working in a local/desktop development environment and want to
	 * keep your config separate, we recommend using a 'wp-config-local.php' file,
	 * which you should also make sure you .gitignore.
	 *
	 * Not loaded when server platform config would be used, eg. Lando environment using a platform recipe.
	 */

	/**
	 * Set WP_ENVIRONMENT_TYPE to "local".
	 */
	if ( getenv( 'WP_ENVIRONMENT_TYPE' ) === false ) {
		putenv( 'WP_ENVIRONMENT_TYPE=local' );
	}
	// IMPORTANT: ensure your local config does not include wp-settings.php.
	require_once __DIR__ . '/wp-config-local.php';
}

/* That's all, stop editing! Happy Pressing. */




/** Absolute path to the WordPress directory. */
if ( ! defined( 'ABSPATH' ) ) {
	define( 'ABSPATH', __DIR__ . '/' );
}

/** Sets up WordPress vars and included files. */
require_once ABSPATH . 'wp-settings.php';
