<?php
/**
 * The base configuration for WordPress
 *
 * The wp-config.php creation script uses this file during the installation.
 * You don't have to use the website, you can copy this file to "wp-config.php"
 * and fill in the values.
 *
 * This file contains the following configurations:
 *
 * * Database settings
 * * Secret keys
 * * Database table prefix
 * * ABSPATH
 *
 * @link https://developer.wordpress.org/advanced-administration/wordpress/wp-config/
 *
 * @package WordPress
 */

// ** Database settings - You can get this info from your web host ** //
/** The name of the database for WordPress */
define( 'DB_NAME', 'skydropx_help' );

/** Database username */
define( 'DB_USER', 'root' );

/** Database password */
define( 'DB_PASSWORD', 'root' );

/** Database hostname */
define( 'DB_HOST', 'localhost' );

/** Database charset to use in creating database tables. */
define( 'DB_CHARSET', 'utf8mb4' );

/** The database collate type. Don't change this if in doubt. */
define( 'DB_COLLATE', '' );

/**#@+
 * Authentication unique keys and salts.
 *
 * Change these to different unique phrases! You can generate these using
 * the {@link https://api.wordpress.org/secret-key/1.1/salt/ WordPress.org secret-key service}.
 *
 * You can change these at any point in time to invalidate all existing cookies.
 * This will force all users to have to log in again.
 *
 * @since 2.6.0
 */
define( 'AUTH_KEY',         '&1!s7#>NqV0EC<|5aHx^~!it9K,LzHHCD4;I6aio3-`%/Egc!X9W_b=TGa0p:+Rr' );
define( 'SECURE_AUTH_KEY',  '*@zoD#d]:?]o1W20i5Gux2P$hnRKRNl}IEMNBHWD+KL^K,I_3RUU/o-?S6V`-&~k' );
define( 'LOGGED_IN_KEY',    'P>C99ioZ5S9&[Ay80VzILlJL`~d?fwfLr|~n*a`b]FesC=Yxr= $w|D;8@gsf5I;' );
define( 'NONCE_KEY',        'j[VGEv$T mDSy ~ >$,El38(|l3p6;`7,mEV0U^>y+q<:yHfs??>6DsBC{<>;H`;' );
define( 'AUTH_SALT',        'Or|#6SW#leY$}GkAHN]oO`p&cb)/sCWe!:@JpcglBQBNG^I/?OVYE0@Q889@bZ27' );
define( 'SECURE_AUTH_SALT', 'r2YGW VZJEjp.uUU1<NqL]+GNLY$b9~pE<}Gw>j%OV,kk_4Vk;nQ(*YeE~9(R=W(' );
define( 'LOGGED_IN_SALT',   '?F-%W7KE+nk`%#h:F_Y~ig#-?VCN-6IO|V=PhB*i,ih0bMwucZ+o+XIW$NDhP0[H' );
define( 'NONCE_SALT',       'G*X])tq|F0B*gJ^2K9}FK(d_PV6=+UjkrRVG8Zj5d*8]pa/^TCQ^ &unH-p/BFsL' );

/**#@-*/

/**
 * WordPress database table prefix.
 *
 * You can have multiple installations in one database if you give each
 * a unique prefix. Only numbers, letters, and underscores please!
 *
 * At the installation time, database tables are created with the specified prefix.
 * Changing this value after WordPress is installed will make your site think
 * it has not been installed.
 *
 * @link https://developer.wordpress.org/advanced-administration/wordpress/wp-config/#table-prefix
 */
$table_prefix = 'wp_';

/**
 * For developers: WordPress debugging mode.
 *
 * Change this to true to enable the display of notices during development.
 * It is strongly recommended that plugin and theme developers use WP_DEBUG
 * in their development environments.
 *
 * For information on other constants that can be used for debugging,
 * visit the documentation.
 *
 * @link https://developer.wordpress.org/advanced-administration/debug/debug-wordpress/
 */
define( 'WP_DEBUG', false );

/* Add any custom values between this line and the "stop editing" line. */



/* That's all, stop editing! Happy publishing. */

/** Absolute path to the WordPress directory. */
if ( ! defined( 'ABSPATH' ) ) {
	define( 'ABSPATH', __DIR__ . '/' );
}

/** Sets up WordPress vars and included files. */
require_once ABSPATH . 'wp-settings.php';
