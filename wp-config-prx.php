<?php
/**
 * PRX platform settings.
 *
 * IMPORTANT NOTE:
 * Do not modify this file. This file is maintained by PRX.
 *
 * Site-specific modifications belong in wp-config.php, not this file. This
 * file may change in future releases and modifications would cause conflicts
 * when attempting to apply upstream updates.
 */

error_log( 'wp-config-prx', 0 );

// If we're behind a proxy server and using HTTPS, we need to alert WordPress of that fact
// see also https://wordpress.org/support/article/administration-over-ssl/#using-a-reverse-proxy
if ( isset( $_SERVER['HTTP_X_FORWARDED_PROTO'] ) && strpos( $_SERVER['HTTP_X_FORWARDED_PROTO'], 'https' ) !== false ) {
	$_SERVER['HTTPS'] = 'on';
}

if ( null !== getenv( 'WP_DEBUG' ) ) {
	define( 'WP_DEBUG', filter_var( getenv( 'WP_DEBUG' ) ?? false, FILTER_VALIDATE_BOOLEAN ) );
	define( 'WP_DEBUG_LOG', filter_var( getenv( 'WP_DEBUG_LOG' ) ?? false, FILTER_VALIDATE_BOOLEAN ) );
	define( 'WP_DEBUG_DISPLAY', filter_var( getenv( 'WP_DEBUG_DISPLAY' ) ?? false, FILTER_VALIDATE_BOOLEAN ) );
}

define( 'WPMS_ON', true );
define( 'WPMS_SMTP_HOST', getenv( 'WPMS_SMTP_HOST' ) );
define( 'WPMS_SMTP_PORT', getenv( 'WPMS_SMTP_PORT' ) );
define( 'WPMS_SSL', getenv( 'WPMS_SSL' ) );
define( 'WPMS_SMTP_AUTH', filter_var( getenv( 'WPMS_SMTP_AUTH' ) ?? false, FILTER_VALIDATE_BOOLEAN ) );
define( 'WPMS_SMTP_USER', getenv( 'WPMS_SMTP_USER' ) );
define( 'WPMS_SMTP_PASS', getenv( 'WPMS_SMTP_PASS' ) );
define( 'WPMS_SMTP_AUTOTLS', filter_var( getenv( 'WPMS_SMTP_AUTOTLS' ) ?? false, FILTER_VALIDATE_BOOLEAN ) );
define( 'WPMS_MAILER', getenv( 'WPMS_MAILER' ) );
define( 'WPMS_MAIL_FROM', getenv( 'WPMS_MAIL_FROM' ) );
define( 'WPMS_MAIL_FROM_FORCE', filter_var( getenv( 'WPMS_MAIL_FROM_FORCE' ) ?? false, FILTER_VALIDATE_BOOLEAN ) );
define( 'WPMS_MAIL_FROM_NAME', getenv( 'WPMS_MAIL_FROM_NAME' ) );
define( 'WPMS_MAIL_FROM_NAME_FORCE', filter_var( getenv( 'WPMS_MAIL_FROM_NAME_FORCE' ) ?? false, FILTER_VALIDATE_BOOLEAN ) );

// ** MySQL settings - included in the Pantheon Environment ** //
/** The name of the database for WordPress */
define( 'DB_NAME', getenv( 'DB_NAME' ) );

/** MySQL database username */
define( 'DB_USER', getenv( 'DB_USER' ) );

/** MySQL database password */
define( 'DB_PASSWORD', getenv( 'DB_PASSWORD' ) );

/** MySQL hostname; on Pantheon this includes a specific port number. */
define( 'DB_HOST', getenv( 'DB_HOST' ) );

/** Database Charset to use in creating database tables. */
define( 'DB_CHARSET', 'utf8mb4' );

/** The Database Collate type. Don't change this if in doubt. */
define( 'DB_COLLATE', '' );

/**#@+
 * Authentication Unique Keys and Salts.
 *
 * Changing these will force all users to have to log in again.
 * Pantheon sets these values for you. If you want to shuffle them you could
 * use terminus env:rotate-random-seed command:
 * https://docs.pantheon.io/terminus/commands/env-rotate-random-seed
 *
 * @since 2.6.0
 */
define( 'AUTH_KEY', getenv( 'AUTH_KEY' ) );
define( 'SECURE_AUTH_KEY', getenv( 'SECURE_AUTH_KEY' ) );
define( 'LOGGED_IN_KEY', getenv( 'LOGGED_IN_KEY' ) );
define( 'NONCE_KEY', getenv( 'NONCE_KEY' ) );
define( 'AUTH_SALT', getenv( 'AUTH_SALT' ) );
define( 'SECURE_AUTH_SALT', getenv( 'SECURE_AUTH_SALT' ) );
define( 'LOGGED_IN_SALT', getenv( 'LOGGED_IN_SALT' ) );
define( 'NONCE_SALT', getenv( 'NONCE_SALT' ) );
/**#@-*/

/** A couple extra tweaks to help things run well on Pantheon. */
if ( isset( $_SERVER['HTTP_HOST'] ) ) {
	// HTTP is still the default scheme for now.
	$scheme = 'http';
	// If we have detected that the end use is HTTPS, make sure we pass that
	// through here, so <img> tags and the like don't generate mixed-mode
	// content warnings.
	if ( ! empty( $_SERVER['HTTPS'] ) && $_SERVER['HTTPS'] !== 'off' ) {
		$scheme = 'https';
	}
	define( 'WP_HOME', $scheme . '://' . $_SERVER['HTTP_HOST'] );
	define( 'WP_SITEURL', $scheme . '://' . $_SERVER['HTTP_HOST'] );
}
// Don't show deprecations; useful under PHP 5.5
error_reporting( E_ALL ^ E_DEPRECATED );
/** Define appropriate location for default tmp directory on Pantheon */
define( 'WP_TEMP_DIR', sys_get_temp_dir() );

// FS writes aren't permitted in test or live, so we should let WordPress know to disable relevant UI.
if ( in_array( getenv( 'PRX_ENVIRONMENT' ), array( 'production', 'staging' ) ) && ! defined( 'DISALLOW_FILE_MODS' ) ) {
	define( 'DISALLOW_FILE_MODS', true );
}

/**
 * Set WP_ENVIRONMENT_TYPE according to the Pantheon Environment
 */
if ( getenv( 'WP_ENVIRONMENT_TYPE' ) === false ) {
	switch ( getenv( 'PRX_ENVIRONMENT' ) ) {
		case 'production':
			putenv( 'WP_ENVIRONMENT_TYPE=production' );
			break;
		case 'staging':
			putenv( 'WP_ENVIRONMENT_TYPE=staging' );
			define( 'WP_ENVIRONMENT_TYPE', 'staging' );
			break;
		default:
			putenv( 'WP_ENVIRONMENT_TYPE=development' );
			break;
	}
}

if ( null !== getenv( 'WP_REDIS_HOST' ) ) {
	// Adjust Redis host and port if necessary.
	define( 'WP_REDIS_HOST', getenv( 'WP_REDIS_HOST' ) );
	define( 'WP_REDIS_PORT', getenv( 'WP_REDIS_PORT' ) );

	// Change the prefix and database for each site to avoid cache data collisions.
	define( 'WP_REDIS_PREFIX', getenv( 'WP_REDIS_PREFIX' ) );
	define( 'WP_REDIS_DATABASE', getenv( 'WP_REDIS_DATABASE' ) ); // 0-15

	// Reasonable connection and read+write timeouts.
	define( 'WP_REDIS_TIMEOUT', getenv( 'WP_REDIS_TIMEOUT' ) );
	define( 'WP_REDIS_READ_TIMEOUT', getenv( 'WP_REDIS_READ_TIMEOUT' ) );

	define( 'WP_REDIS_MAXTTL', getenv( 'WP_REDIS_MAXTTL' ) );

	if ( null !== getenv( 'WP_REDIS_SCHEME' ) ) {
		define( 'WP_REDIS_SCHEME', getenv( 'WP_REDIS_SCHEME' ) );
	}
}
