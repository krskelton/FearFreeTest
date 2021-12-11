<?php
/**
 * The base configuration for WordPress
 *
 * The wp-config.php creation script uses this file during the installation.
 * You don't have to use the web site, you can copy this file to "wp-config.php"
 * and fill in the values.
 *
 * This file contains the following configurations:
 *
 * * MySQL settings
 * * Secret keys
 * * Database table prefix
 * * ABSPATH
 *
 * @link https://wordpress.org/support/article/editing-wp-config-php/
 *
 * @package WordPress
 */

// ** MySQL settings - You can get this info from your web host ** //
/** The name of the database for WordPress */
define( 'DB_NAME', 'fear_free' );

/** MySQL database username */
define( 'DB_USER', 'KristinSkelton' );

/** MySQL database password */
define( 'DB_PASSWORD', 'Bailey1!' );

/** MySQL hostname */
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
define( 'AUTH_KEY',         '0&z$NID<BW7W)PnVj*PZ.FKYEYY]RLTZ&>VY#eHOn>H^0`jr=l68,YY$b-xK>lX+' );
define( 'SECURE_AUTH_KEY',  '.AX3dpQr*o;=Goz{m|j!<Hz8O*~K}}gbLII+L=42-M-ZkRR;D!a4obK$p={tj1+0' );
define( 'LOGGED_IN_KEY',    '0Q!hB 99`Vt=vF7)8JjiF6QZV|/9z$K,@yhL0Wv)FNrr7h3tWzD;|]4lL_r1Y7DZ' );
define( 'NONCE_KEY',        'xV7k2FA^bvJh=Cn=?k f9Gp-FcqXVB~}sA9&))8ud%7kiC&fz}X$mjiM~j#<5lz`' );
define( 'AUTH_SALT',        '>|V-m2QCrmWh%2?KGh<Ij v3SdfI3#V10h)V:BF4srIX7z3uwS)/T`0h||)YASQf' );
define( 'SECURE_AUTH_SALT', 'XtB}.$Mmb/:ckZnbM*h<c*581XrD$bO$Wwf!SjE|q_HtPAx?6@J~_{WTfe- v;2+' );
define( 'LOGGED_IN_SALT',   'o;3V9]:u;0C*4LU~{>Y wqv9>];{UMRBGB(a~+@I,<Zg[|:C5(}U%^xHQ)n.,3^0' );
define( 'NONCE_SALT',       '%!,=HnBUT<(!sW=$;ix2H~p:X+ug4rt1B<=b[Lbw/Et7DSW|Qf_.AGfvM~bRwZiR' );

/**#@-*/

/**
 * WordPress database table prefix.
 *
 * You can have multiple installations in one database if you give each
 * a unique prefix. Only numbers, letters, and underscores please!
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
 * @link https://wordpress.org/support/article/debugging-in-wordpress/
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
