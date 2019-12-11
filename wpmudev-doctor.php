<?php

/**
 * Combined `wp doctor` checks for WPMU DEV Hosting.
 *
 * Author:      Xenos Konstantinos
 * License:     GPLv2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 */

/**
 * Props to original contributors of https://github.com/wp-cli/doctor-command .
 */

if ( defined( 'WP_CLI' ) && WP_CLI ) {
	/**
	 * Checks for Core stats.
	 *
	 * command: wp doctor check wpmudev-core-stats --config=PATH
	 */
	class WPMUDEV_Doctor_Core_Stats extends runcommand\Doctor\Checks\Check {
		// WP_CLI::runcommand options.
		private static $runcommand_options = array(
			'return'     => true,
			'parse'      => 'json',
			'launch'     => false,
			'exit_error' => true,
		);

		// Main function.
		public function run() {
			// Check if Core needs updates.
			ob_start();
			WP_CLI::run_command( array( 'core', 'check-update' ), array( 'format' => 'json' ) );
			$ret = ob_get_clean();

			$updates   = ! empty( $ret ) ? json_decode( $ret, true ) : array();
			$has_minor = false;
			$has_major = false;

			foreach ( $updates as $update ) {
				switch ( $update['update_type'] ) {
					case 'minor':
						$has_minor = true;
						break;
					case 'major':
						$has_major = true;
						break;
				}
			}

			if ( $has_minor ) {
				// If it's a minor update set as a warning.
				$this->set_status( 'warning' );
				$core = 'A new minor version is available.';
			} elseif ( $has_major ) {
				// If it's a major update set as an error.
				$this->set_status( 'error' );
				$core = 'A new major version is available.';
			} else {
				$this->set_status( 'success' );
				$core = 'WordPress is at the latest version.';
			}

			// Check if this is a Multisite.
			if ( is_multisite() ) {
				$total_sites = WP_CLI::runcommand( 'site list --format=count', self::$runcommand_options );

				// Check if Multisite is Subdirectory or Subdomain.
				if ( defined( 'SUBDOMAIN_INSTALL' ) && SUBDOMAIN_INSTALL ) {
					$wpmu_type = 'Subdomain Multisite';
				} else {
					$wpmu_type = 'Subdirectory Multisite';
				}

				$multisite_stats = $wpmu_type . ', ' . $total_sites . ' Sites Total';
			} else {
				$multisite_stats = 'Single Site';
			}

			// Check if the this is a Public site.
			$public = WP_CLI::runcommand( 'option get blog_public', self::$runcommand_options );

			if ( 1 === $public ) {
				$public_msg = 'Public.';
			} else {
				$public_msg = 'Not Public.';
			}

			// Return message.
			$this->set_message( $core . ' ' . $multisite_stats . ', ' . $public_msg );
		}
	}

	/**
	 * Checks for Plugin stats.
	 *
	 * command: wp doctor check wpmudev-plugin-stats --config=PATH
	 */
	class WPMUDEV_Doctor_Plugin_Stats extends runcommand\Doctor\Checks\Check {
		// Limit of active plugins.
		private static $limit_active = 80;

		// Percentage limi of inactive plugins ( against total ).
		private static $limit_inactive_percent = 40;

		// WP_CLI::runcommand options.
		private static $runcommand_options = array(
			'return'     => true,
			'parse'      => 'json',
			'launch'     => false,
			'exit_error' => true,
		);

		// Main function.
		public function run() {
			// Set status as success by default.
			$this->set_status( 'success' );

			// Gather various plugin stats.
			$total_plugins          = WP_CLI::runcommand( 'plugin list --format=count', self::$runcommand_options );
			$active_plugins         = WP_CLI::runcommand( 'plugin list --status=active --format=count', self::$runcommand_options );
			$active_network_plugins = WP_CLI::runcommand( 'plugin list --status=active-network --format=count', self::$runcommand_options );
			$inactive_plugins       = WP_CLI::runcommand( 'plugin list --status=inactive --format=count', self::$runcommand_options );
			$mu_plugins             = WP_CLI::runcommand( 'plugin list --status=must-use --format=count', self::$runcommand_options );
			$dropin_plugins         = WP_CLI::runcommand( 'plugin list --status=dropin --format=count', self::$runcommand_options );
			$total_active_plugins   = $active_plugins + $active_network_plugins;

			// Set warning if total plugins is over the limit.
			if ( $total_active_plugins > self::$limit_active ) {
				$this->set_status( 'warning' );
			}

			// Set warning if inactive plugins is over the percentage limit.
			$inactive_percent = (int) self::$limit_inactive_percent;

			if ( ( $inactive_plugins / $total_plugins ) > ( $inactive_percent / 100 ) ) {
				$this->set_status( 'warning' );
			}

			// Return message.
			$this->set_message( $total_plugins . ' Total, ' . $total_active_plugins . ' Active (limit ' . self::$limit_active . '), ' . $inactive_plugins . ' Inactive (limit ' . self::$limit_inactive_percent . '%), ' . $mu_plugins . ' Must-use, ' . $dropin_plugins . ' Dropins.' );
		}
	}

	/**
	 * Checks for WPMUDEV Plugins.
	 *
	 * command: wp doctor check wpmudev-plugin-check --config=PATH
	 */
	class WPMUDEV_Doctor_Plugin_Check extends runcommand\Doctor\Checks\Check {
		// WP_CLI::runcommand options.
		private static $runcommand_options = array(
			'return'     => true,
			'parse'      => 'json',
			'launch'     => false,
			'exit_error' => true,
		);

		// Main function.
		public function run() {
			// Set status as success by default.
			$this->set_status( 'success' );

			// Set default success message.
			$message = 'WPMU DEV Dashboard, Hummingbird, Defender & Smush are installed and activated.';

			// Initialize errors array.
			$errors = array();

			// Check for wpmudev-updates (WPMU DEV Dashboard plugin).
			$dash_active         = WP_CLI::runcommand( 'plugin list --format=count  --name=wpmudev-updates --status=active', self::$runcommand_options );
			$dash_network_active = WP_CLI::runcommand( 'plugin list --format=count  --name=wpmudev-updates --status=active-network', self::$runcommand_options );
			$dash_count          = $dash_active + $dash_network_active;

			if ( 0 === $dash_count ) {
				$errors[] = 'WPMU DEV Dashboard';
			}

			// Check for hummingbird-performance & wp-hummingbird (Hummingbird plugin).
			$hb_active            = WP_CLI::runcommand( 'plugin list --format=count --name=hummingbird-performance --status=active', self::$runcommand_options );
			$hb_network_active    = WP_CLI::runcommand( 'plugin list --format=count --name=hummingbird-performance --status=active-network', self::$runcommand_options );
			$hbpro_active         = WP_CLI::runcommand( 'plugin list --format=count --name=wp-hummingbird --status=active', self::$runcommand_options );
			$hbpro_network_active = WP_CLI::runcommand( 'plugin list --format=count --name=wp-hummingbird --status=active-network', self::$runcommand_options );
			$hb_count             = $hb_active + $hb_network_active + $hbpro_active + $hbpro_network_active;

			if ( 0 === $hb_count ) {
				$errors[] = 'Hummingbird';
			}

			// Check for defender-security & wp-defender (Defender plugin).
			$def_active            = WP_CLI::runcommand( 'plugin list --format=count --name=defender-security --status=active', self::$runcommand_options );
			$def_network_active    = WP_CLI::runcommand( 'plugin list --format=count --name=defender-security --status=active-network', self::$runcommand_options );
			$defpro_active         = WP_CLI::runcommand( 'plugin list --format=count --name=wp-defender --status=active', self::$runcommand_options );
			$defpro_network_active = WP_CLI::runcommand( 'plugin list --format=count --name=wp-defender --status=active-network', self::$runcommand_options );
			$def_count             = $def_active + $def_network_active + $defpro_active + $defpro_network_active;

			if ( 0 === $def_count ) {
				$errors[] = 'Defender';
			}

			// Check for wp-smush-pro & wp-smushit (Smush plugin).
			$smush_active            = WP_CLI::runcommand( 'plugin list --format=count --name=wp-smushit --status=active', self::$runcommand_options );
			$smush_network_active    = WP_CLI::runcommand( 'plugin list --format=count --name=wp-smushit --status=active-network', self::$runcommand_options );
			$smushpro_active         = WP_CLI::runcommand( 'plugin list --format=count --name=wp-smush-pro --status=active', self::$runcommand_options );
			$smushpro_network_active = WP_CLI::runcommand( 'plugin list --format=count --name=wp-smush-pro --status=active-network', self::$runcommand_options );
			$smush_count             = $smush_active + $smush_network_active + $smushpro_active + $smushpro_network_active;

			if ( 0 === $smush_count ) {
				$errors[] = 'Smush';
			}

			// If there are errors set status to warning and alter the response message.
			if ( ! empty( $errors ) ) {
				$this->set_status( 'warning' );
				$message = 'Not installed or activated: ' . implode( ', ', $errors );
			}

			// Return message.
			$this->set_message( $message );
		}
	}

	/**
	 * Checks for Theme stats.
	 *
	 * command: wp doctor check wpmudev-theme-stats --config=PATH
	 */
	class WPMUDEV_Doctor_Theme_Stats extends runcommand\Doctor\Checks\Check {
		// WP_CLI::runcommand options.
		private static $runcommand_options = array(
			'return'     => true,
			'parse'      => 'json',
			'launch'     => false,
			'exit_error' => true,
		);

		// Main function.
		public function run() {
			// Set status as success by default.
			$this->set_status( 'success' );

			$total_themes = WP_CLI::runcommand( 'theme list --format=count', self::$runcommand_options );

			// Return message.
			$this->set_message( $total_themes . ' Total.' );
		}
	}

	/**
	 * Checks for User stats.
	 *
	 * command: wp doctor check wpmudev-user-stats --config=PATH
	 */
	class WPMUDEV_Doctor_User_Stats extends runcommand\Doctor\Checks\Check {
		// WP_CLI::runcommand options.
		private static $runcommand_options = array(
			'return'     => true,
			'parse'      => 'json',
			'launch'     => false,
			'exit_error' => true,
		);

		// Main function.
		public function run() {
			// Set status as success by default.
			$this->set_status( 'success' );

			// Initialize message.
			$message = '';

			// Gather user information.
			if ( is_multisite() ) {
				$total_users = WP_CLI::runcommand( 'user list --format=count --network', self::$runcommand_options );
			} else {
				$total_users = WP_CLI::runcommand( 'user list --format=count', self::$runcommand_options );
			}

			// If there are users adjust the message.
			if ( 0 !== $total_users ) {
				$message = $total_users . ' Total.';
			} else {
				// If there are no users adjust the message and set status as error.
				$this->set_status( 'error' );
				$message = 'No Users found.';
			}

			// Return message.
			$this->set_message( $message );
		}
	}

	/**
	 * Checks for Role stats.
	 *
	 * command: wp doctor check wpmudev-roles-stats --config=PATH
	 */
	class WPMUDEV_Doctor_Role_Stats extends runcommand\Doctor\Checks\Check {
		// WP_CLI::runcommand options.
		private static $runcommand_options = array(
			'return'     => true,
			'parse'      => 'json',
			'launch'     => false,
			'exit_error' => true,
		);

		// Main function.
		public function run() {
			// Set status as success by default.
			$this->set_status( 'success' );

			// Initialize roles array.
			$role_list = array();

			// Gather roles.
			$roles = WP_CLI::runcommand( 'role list --format=json', self::$runcommand_options );

			if ( ! empty( $roles ) ) {
				foreach ( $roles as $role ) {
					$count_users = WP_CLI::runcommand( 'user list --format=count --role=' . $role['role'], self::$runcommand_options );
					array_push( $role_list, $count_users . ' ' . $role['role'] );
				}

				$role_result = implode( ', ', $role_list ) . '.';
			} else {
				// If there are no roles set status as error.
				$this->set_status( 'error' );
				$role_result = 'No roles found.';
			}

			// Check for Super Admins if Multisite.
			if ( is_multisite() ) {
				// Gather Super Admins.
				$super_admins = WP_CLI::runcommand( 'super-admin list --format=count', self::$runcommand_options );

				if ( 0 === $super_admins ) {
					// If there are no Super Admins set status as error.
					$this->set_status( 'error' );
					$super_admin = '0 Super Admins, ';
				} else {
					$super_admin = $super_admins . ' Super Admins, ';
				}
			}

			// Return message.
			$this->set_message( $super_admin . $role_result );
		}
	}

	/**
	 * Checks for Posts stats.
	 *
	 * command: wp doctor check wpmudev-posts-stats --config=PATH
	 */
	class WPMUDEV_Doctor_Posts_Stats extends runcommand\Doctor\Checks\Check {
		// WP_CLI::runcommand options.
		private static $runcommand_options = array(
			'return'     => true,
			'parse'      => 'json',
			'launch'     => false,
			'exit_error' => true,
		);

		// Main function.
		public function run() {
			// Set status as success by default.
			$this->set_status( 'success' );

			// Gather post stats.
			$posts       = WP_CLI::runcommand( 'post list --post_type=post --format=count', self::$runcommand_options );
			$pages       = WP_CLI::runcommand( 'post list --post_type=page --format=count', self::$runcommand_options );
			$attachments = WP_CLI::runcommand( 'post list --post_type=attachment --format=count', self::$runcommand_options );

			// Return message.
			$this->set_message( $posts . ' Posts, ' . $pages . ' Pages, ' . $attachments . ' Attachments.' );
		}
	}

	/**
	 * Checks for Autoload options to not be over 900kb.
	 *
	 * command: wp doctor check wpmudev-autoload-report --config=PATH
	 */
	class WPMUDEV_Doctor_Autoload_Report extends runcommand\Doctor\Checks\Check {
		// Limit in bytes.
		private static $limit_bytes = 900 * 1024;

		// WP_CLI::runcommand options.
		private static $runcommand_options = array(
			'return'     => true,
			'parse'      => 'json',
			'launch'     => false,
			'exit_error' => true,
		);

		// Main function.
		public function run() {
			// Set status as success by default.
			$this->set_status( 'success' );

			// Initialize message.
			$message = '';

			// Get total bytes of autoloaded options.
			$total_bytes = WP_CLI::runcommand( 'option list --autoload=on --format=total_bytes', self::$runcommand_options );

			// Convert bytes to readable format.
			$human_limit = self::format_bytes( self::$limit_bytes );
			$human_total = self::format_bytes( $total_bytes );

			if ( self::$limit_bytes < $total_bytes ) {
				// Set status as a warning.
				$this->set_status( 'warning' );

				// Gather autoloaded options.
				$data = WP_CLI::runcommand( 'option list --fields=option_name,size_bytes --autoload=on --format=json', self::$runcommand_options );

				// Sort options by size.
				usort(
					$data,
					function( $a, $b ) {
						return $a['size_bytes'] < $b['size_bytes'];
					}
				);

				// Collect only the 3 first options.
				$data = array_slice( $data, 0, 3 );

				// Initialize final_data array.
				$final_data = array();

				foreach ( $data as $key => $value ) {
					array_push( $final_data, $data[ $key ]['option_name'] . '(' . self::format_bytes( $data[ $key ]['size_bytes'] ) . ')' );
				}

				// Adjust the return message if the check fails.
				$message = "{$human_total} Total (limit {$human_limit}). 3 biggest options: " . implode( ', ', $final_data ) . '.';
			} else {
				// Adjust the return message if the check passes.
				$message = "{$human_total} Total (limit {$human_limit}).";
			}

			// Return message.
			$this->set_message( $message );
		}

		// Change bytes into a human readable format.
		private static function format_bytes( $size, $precision = 2 ) {
			$base     = log( $size, 1024 );
			$suffixes = array( '', 'kb', 'mb', 'g', 't' );
			return round( pow( 1024, $base - floor( $base ) ), $precision ) . $suffixes[ floor( $base ) ];
		}
	}

	/**
	 * Checks for TTFB.
	 */
	class WPMUDEV_Doctor_TTFB extends runcommand\Doctor\Checks\Check {

		public function run() {

			$url = get_site_url();

			$curl = curl_init();

			curl_setopt( $curl, CURLOPT_URL, $url );
			curl_setopt( $curl, CURLOPT_HEADER, 0 );
			curl_setopt( $curl, CURLOPT_NOBODY, 1 );
			curl_setopt( $curl, CURLOPT_FOLLOWLOCATION, 1 );
			curl_setopt( $curl, CURLOPT_RETURNTRANSFER, 0 );

			curl_exec( $curl );
			$curl_info = curl_getinfo( $curl );
			curl_close( $curl );

			if ( 0 == $curl_info['starttransfer_time'] ) {
				$this->set_status( 'warning' );
				$this->set_message( 'Could not retrieve Time to first byte.' );
			} else {
				$this->set_status( 'success' );
				$this->set_message( $curl_info['starttransfer_time'] . 's Time to first byte (TTFB).' );
			}
		}
	}

	/**
	 * Checks for Cache headers.
	 */
	class WPMUDEV_Doctor_Cache_Headers extends runcommand\Doctor\Checks\Check {

		public function run() {
			$url           = get_site_url();
			$response      = wp_remote_get( $url );
			$headers       = $this->headers_to_array( wp_remote_retrieve_headers( $response ), 'data' );
			$found_headers = array();

			if ( array_key_exists( 'hummingbird-cache', $headers ) ) {
				$found_headers[] = 'Hummingbird';
			}

			if ( array_key_exists( 'x-cache', $headers ) ) {
				$found_headers[] = 'x-cache';
			}

			if ( array_key_exists( 'cf-cache-status', $headers ) ) {
				$found_headers[] = 'Cloudflare';
			}

			if ( ! empty( $found_headers ) ) {
				$this->set_status( 'success' );
				$this->set_message( implode( ', ', $found_headers ) . '.' );
			} else {
				$this->set_status( 'warning' );
				$this->set_message( 'Could not find any cache headers.' );
			}
		}

		private function headers_to_array( $obj, $prop ) {
			$array  = (array) $obj;
			$prefix = chr( 0 ) . '*' . chr( 0 );
			return $array[ $prefix . $prop ];
		}
	}

	/**
	 * Verify Core Checksums.
	 */
	class WPMUDEV_Doctor_Verify_Core_Checksums extends runcommand\Doctor\Checks\Check {

		public function __construct( $options = array() ) {
			parent::__construct( $options );
			$this->set_when( 'before_wp_load' );
		}

		public function run() {
			$checksums = WP_CLI::launch_self( 'core verify-checksums', array(), array(), false, true );

			if ( 0 === $checksums->return_code && empty( $checksums->stderr ) ) {
				$this->set_status( 'success' );
				$message = 'WordPress verifies against its checksums.';
			} else {
				$this->set_status( 'error' );
				$message = 'Some files did not verify against their checksum or should not exist.';
			}

			$this->set_message( $message );
		}
	}

	/**
	 * Cron Checks.
	 */
	class WPMUDEV_Doctor_Cron_Stats extends runcommand\Doctor\Checks\Check {

		protected static $threshold_count = 50;

		protected static $dup_threshold_count = 10;

		public function run() {
			// Count crons.
			$cmd_options = array(
				'return'     => true,
				'parse'      => 'json',
				'launch'     => false,
				'exit_error' => true,
			);

			$crons      = WP_CLI::runcommand( 'cron event list --format=json', $cmd_options );
			$cron_count = count( $crons );

			if ( $cron_count >= self::$threshold_count ) {
				$this->set_status( 'warning' );
			} else {
				$this->set_status( 'success' );
			}

			// Cound duplicates.
			$job_counts        = array();
			$excess_duplicates = false;

			foreach ( $crons as $job ) {
				if ( ! isset( $job_counts[ $job['hook'] ] ) ) {
					$job_counts[ $job['hook'] ] = 0;
				}
				$job_counts[ $job['hook'] ]++;
				if ( $job_counts[ $job['hook'] ] >= self::$dup_threshold_count ) {
					$excess_duplicates = true;
				}
			}

			if ( $excess_duplicates ) {
				$this->set_status( 'warning' );
				$dup_msg = ' Detected ' . self::$dup_threshold_count . ' or more of the same cron job.';
			}

			$this->set_message( $cron_count . ' Total (limit ' . self::$threshold_count . ').' . $dup_msg );

		}
	}

	/**
	 * Constants Checks.
	 */
	class WPMUDEV_Doctor_Constants extends runcommand\Doctor\Checks\Check {

		protected static $undefined_constants = array(
			'WP_DEBUG',
			'SAVEQUERIES',
			'DISABLE_WP_CRON',
			'ALTERNATE_WP_CRON',
			'FS_METHOD',
			'FTP_BASE',
			'FTP_CONTENT_DIR',
			'FTP_PLUGIN_DIR',
			'FTP_PUBKEY',
			'FTP_PRIKEY',
			'FTP_USER',
			'FTP_PASS',
			'FTP_HOST',
			'FTP_SSL',
		);

		protected static $predefined_constants = array(
			'WP_CONTENT_DIR',
			'WP_CONTENT_URL',
			'FS_CHMOD_DIR',
			'FS_CHMOD_FILE',
		);

		public function run() {
			$this->set_status( 'success' );

			$message         = 'All constants are ok.';
			$content_dir     = ABSPATH . 'wp-content';
			$content_url     = rtrim( get_site_url(), '/' ) . '/wp-content';
			$wrong_constants = array();

			foreach ( self::$undefined_constants as $constant ) {
				if ( defined( $constant ) ) {
					if ( 'SAVEQUERIES' === $constant || 'WP_DEBUG' === $constant ) {
						if ( 'SAVEQUERIES' === $constant && true === constant( 'SAVEQUERIES' ) ) {
							$wrong_constants['defined'][] = $constant;
						}
						if ( 'WP_DEBUG' === $constant && true === constant( 'WP_DEBUG' ) ) {
							$wrong_constants['defined'][] = $constant;
						}
					} else {
						$wrong_constants['defined'][] = $constant;
					}
				}
			}

			foreach ( self::$predefined_constants as $constant ) {
				switch ( $constant ) {
					case 'WP_CONTENT_DIR':
						if ( defined( 'WP_CONTENT_DIR' ) && constant( 'WP_CONTENT_DIR' ) !== $content_dir ) {
							$wrong_constants['changed'][] = $constant;
						}
						break;
					case 'WP_CONTENT_URL':
						if ( defined( 'WP_CONTENT_URL' ) && constant( 'WP_CONTENT_URL' ) !== $content_url ) {
							$wrong_constants['changed'][] = $constant;
						}
						break;
					case 'FS_CHMOD_DIR':
						if ( defined( 'FS_CHMOD_DIR' ) && 493 !== constant( 'FS_CHMOD_DIR' ) ) {
							$wrong_constants['changed'][] = $constant;
						}
						break;
					case 'FS_CHMOD_FILE':
						if ( defined( 'FS_CHMOD_FILE' ) && 420 !== constant( 'FS_CHMOD_FILE' ) ) {
							$wrong_constants['changed'][] = $constant;
						}
						break;
				}
			}

			if ( ! empty( $wrong_constants ) ) {
				$this->set_status( 'warning' );
				$message = '';

				if ( array_key_exists( 'defined', $wrong_constants ) ) {
					$message .= 'Defined: ' . implode( ', ', $wrong_constants['defined'] ) . '.';
				}
				if ( array_key_exists( 'defined', $wrong_constants ) && array_key_exists( 'changed', $wrong_constants ) ) {
					$message .= ' ';
				}
				if ( array_key_exists( 'changed', $wrong_constants ) ) {
					$message .= 'Changed: ' . implode( ', ', $wrong_constants['changed'] );
				}
			}

			$this->set_message( $message );

		}
	}

	/**
	 * Log Scanner.
	 */
	class WPMUDEV_Doctor_Log_Scan extends runcommand\Doctor\Checks\Check {

		protected static $skip_names = array(
			'changelog',
			'CHANGELOG',
			'readme',
			'README',
			'license',
			'LICENSE',
			'copying',
			'COPYING',
			'contributors',
			'CONTRIBUTORS',
			'license.commercial',
			'LICENSE.COMMERCIAL',
		);

		protected static $accept_names = array( 'error_log' );

		protected static $extensions = array(
			'log',
			'txt',
		);

		protected static $size_limit = 10485750; // 10MB

		public function run() {
			$directory = new RecursiveDirectoryIterator( ABSPATH, RecursiveDirectoryIterator::SKIP_DOTS );
			$iterator  = new RecursiveIteratorIterator( $directory, RecursiveIteratorIterator::CHILD_FIRST );

			$limit = (int) self::$size_limit;

			$files_array = array();

			foreach ( $iterator as $file ) {
				if ( is_file( $file ) ) {
					$filename = $file->getBasename( '.' . $file->getExtension() );
					if ( ! in_array( $filename, self::$skip_names, true ) ) {
						if ( in_array( $file->getExtension(), self::$extensions, true ) || in_array( $filename, self::$accept_names, true ) ) {
							if ( $file->getSize() > $limit ) {
								$files_array[] = str_replace( ABSPATH, '', $file->getPathname() ) . ' (' . self::format_bytes( $file->getSize() ) . ')';
							}
						}
					}
				}
			}

			if ( ! empty( $files_array ) ) {
				$this->set_status( 'warning' );
				$this->set_message( implode( ', ', $files_array ) . '.' );
			} else {
				$this->set_status( 'success' );
				$this->set_message( 'No big log files detected (limit ' . self::format_bytes( $limit ) . ').' );
			}
		}

		private static function format_bytes( $size, $precision = 2 ) {
			$base     = log( $size, 1024 );
			$suffixes = array( '', 'kb', 'mb', 'g', 't' );
			return round( pow( 1024, $base - floor( $base ) ), $precision ) . $suffixes[ floor( $base ) ];
		}
	}
}
