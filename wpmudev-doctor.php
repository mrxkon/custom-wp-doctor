<?php // phpcs:ignore

/**
 * Combined `wp doctor` checks for WPMU DEV Hosting.
 *
 * Author:      Konstantinos Xenos
 * Author URI:  https://xkon.gr
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

			// Initialize plugin_updates array.
			$plugin_updates = array();

			// Initialize theme count.
			$total_plugins = 0;

			// Initialize updates message.
			$updates = '';

			// Gather various plugin stats.
			$plugins                = WP_CLI::runcommand( 'plugin list --format=json', self::$runcommand_options );
			$total_plugins          = count( $plugins );
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

			// Check plugins for updates.
			foreach ( $plugins as $plugin ) {
				if ( 'available' === $plugin['update'] ) {
					$plugin_updates[] = $plugin['name'];
				}
			}

			// If plugins have updates set status to warning and adjust the return message.
			if ( ! empty( $plugin_updates ) ) {
				$this->set_status( 'warning' );
				if ( 1 === count( $plugin_updates ) ) {
					$txt = '1 update';
				} else {
					$txt = count( $plugin_updates ) . ' updates';
				}
				$updates = $txt . ' available for: ' . implode( ', ', $plugin_updates ) . '.';
			}

			// Return message.
			$this->set_message( $total_plugins . ' Total, ' . $total_active_plugins . ' Active (limit ' . self::$limit_active . '), ' . $inactive_plugins . ' Inactive (limit ' . self::$limit_inactive_percent . '%), ' . $mu_plugins . ' Must-use, ' . $dropin_plugins . ' Dropins. ' . $updates );
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

			// Set default success return message.
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

			// If there are errors set status to warning and adjust the return message.
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

			// Initialize theme_updates array.
			$theme_updates = array();

			// Initialize theme count.
			$count = 0;

			// Initialize updates message.
			$updates = '';

			// Gather the theme list.
			$themes = WP_CLI::runcommand( 'theme list --format=json', self::$runcommand_options );

			// Set the total count of themes.
			$total_themes = count( $themes );
			$count        = $total_themes . ' Total.';

			// Check themes for updates.
			foreach ( $themes as $theme ) {
				if ( 'available' === $theme['update'] ) {
					$theme_updates[] = $theme['name'];
				}
			}

			// If themes have updates set status to warning and adjust the return message.
			if ( ! empty( $theme_updates ) ) {
				$this->set_status( 'warning' );
				if ( 1 === count( $theme_updates ) ) {
					$txt = '1 update';
				} else {
					$txt = count( $theme_updates ) . ' updates';
				}
				$updates = $txt . ' available for: ' . implode( ', ', $theme_updates ) . '.';
			}

			// Set status as error if there are no themes found.
			if ( 0 === $total_themes ) {
				$this->set_status( 'error' );
			}

			// Return message.
			$this->set_message( $count . ' ' . $updates );
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

			// Initialize the return message.
			$message = '';

			// Gather user information.
			if ( is_multisite() ) {
				$total_users = WP_CLI::runcommand( 'user list --format=count --network', self::$runcommand_options );
			} else {
				$total_users = WP_CLI::runcommand( 'user list --format=count', self::$runcommand_options );
			}

			// If there are users adjust the return message.
			if ( 0 !== $total_users ) {
				$message = $total_users . ' Total.';
			} else {
				// If there are no users adjust the return message and set status as error.
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

			// Initialize super admins message.
			$super_admin = '';

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

			// Initialize the return message.
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
					array_push( $final_data, $data[ $key ]['option_name'] . ' (' . self::format_bytes( $data[ $key ]['size_bytes'] ) . ')' );
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
	 *
	 * command: wp doctor check wpmudev-ttfb --config=PATH
	 */
	class WPMUDEV_Doctor_TTFB extends runcommand\Doctor\Checks\Check {
		// Main function.
		public function run() {
			// Set status as success by default.
			$this->set_status( 'success' );

			// Initialize the return message.
			$message = '';

			// Get the site url.
			$url = get_site_url();

			// Gather the stats via cURL.
			$curl = curl_init();

			curl_setopt( $curl, CURLOPT_URL, $url );
			curl_setopt( $curl, CURLOPT_HEADER, 0 );
			curl_setopt( $curl, CURLOPT_NOBODY, 1 );
			curl_setopt( $curl, CURLOPT_FOLLOWLOCATION, 1 );
			curl_setopt( $curl, CURLOPT_RETURNTRANSFER, 0 );

			curl_exec( $curl );
			$curl_info = curl_getinfo( $curl );
			curl_close( $curl );

			if ( 0 == $curl_info['starttransfer_time'] ) { // phpcs:ignore
				// Set status as warning if there's no response and adjust the return message.
				$this->set_status( 'warning' );
				$message = 'Could not retrieve Time to first byte.';
			} else {
				// Adjust the return message.
				$message = $curl_info['starttransfer_time'] . 's Time to first byte (TTFB).';
			}

			// Return message.
			$this->set_message( $message );
		}
	}

	/**
	 * Checks for Cache headers.
	 *
	 * command: wp doctor check wpmudev-cache-headers --config=PATH
	 */
	class WPMUDEV_Doctor_Cache_Headers extends runcommand\Doctor\Checks\Check {
		// Main function.
		public function run() {
			// Set status as success by default.
			$this->set_status( 'success' );

			// Initialize the return message.
			$message = '';

			// Get the site url.
			$url = get_site_url();

			// Gather headers.
			$response = wp_remote_get( $url );
			$headers  = self::headers_to_array( wp_remote_retrieve_headers( $response ), 'data' );

			// Initialize found_headers array.
			$found_headers = array();

			// Set up the found headers.
			if ( array_key_exists( 'hummingbird-cache', $headers ) ) {
				$found_headers[] = 'Hummingbird';
			}

			if ( array_key_exists( 'x-cache', $headers ) ) {
				$found_headers[] = 'x-cache';
			}

			if ( array_key_exists( 'cf-cache-status', $headers ) ) {
				$found_headers[] = 'Cloudflare';
			}

			// If there are no headers set status to warning and adjust the return message.
			if ( empty( $found_headers ) ) {
				$this->set_status( 'warning' );
				$message = 'Could not find any cache headers.';
			} else {
				// Adjust the return message.
				$message = implode( ', ', $found_headers ) . '.';
			}

			// Return message.
			$this->set_message( $message );
		}

		// Converts headers to array.
		private static function headers_to_array( $obj, $prop ) {
			$array  = (array) $obj;
			$prefix = chr( 0 ) . '*' . chr( 0 );
			return $array[ $prefix . $prop ];
		}
	}

	/**
	 * Verify Core Checksums.
	 *
	 * command: wp doctor check wpmudev-verify-core-checksums --config=PATH
	 */
	class WPMUDEV_Doctor_Verify_Core_Checksums extends runcommand\Doctor\Checks\Check {
		// Main function.
		public function run() {
			// Set status as success by default.
			$this->set_status( 'success' );

			// Initialize the return message.
			$message = '';

			// Gather information on checksums.
			$checksums = WP_CLI::launch_self( 'core verify-checksums', array(), array(), false, true );

			// If no errors are found adjust the return message.
			if ( 0 === $checksums->return_code && empty( $checksums->stderr ) ) {
				$message = 'WordPress verifies against its checksums.';
			} else {
				// Set status as error if there are checksum issues and adjust the return message.
				$this->set_status( 'error' );
				$message = 'Issues have been found. Please run "wp core verify-checksums".';
			}

			// Return message.
			$this->set_message( $message );
		}
	}

	/**
	 * Cron statistics.
	 *
	 * command: wp doctor check wpmudev-cron-stats --config=PATH
	 */
	class WPMUDEV_Doctor_Cron_Stats extends runcommand\Doctor\Checks\Check {
		// Limit of crons in total.
		private static $limit_count = 50;

		// Limit of duplicate crons.
		private static $dup_limit_count = 10;

		// WP_CLI::runcommand options.
		private static $runcommand_options = array(
			'return'     => true,
			'parse'      => 'json',
			'launch'     => false,
			'exit_error' => true,
		);

		// Main function.
		public function run() {
			// Initialize duplicate message.
			$dup_msg = '';

			// Count crons.
			$crons      = WP_CLI::runcommand( 'cron event list --format=json', self::$runcommand_options );
			$cron_count = count( $crons );

			// Adjust the status if the crons exceed the limit.
			if ( $cron_count >= self::$limit_count ) {
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
				if ( $job_counts[ $job['hook'] ] >= self::$dup_limit_count ) {
					$excess_duplicates = true;
				}
			}

			// Adjust the status to warning if the duplicate crons exceed the limit.
			if ( $excess_duplicates ) {
				$this->set_status( 'warning' );
				$dup_msg = ' Detected ' . self::$dup_limit_count . ' or more of the same cron job.';
			}

			// Return message.
			$this->set_message( $cron_count . ' Total (limit ' . self::$limit_count . ').' . $dup_msg );
		}
	}

	/**
	 * Constants Checks.
	 *
	 * command: wp doctor check wpmudev-constants --config=PATH
	 */
	class WPMUDEV_Doctor_Constants extends runcommand\Doctor\Checks\Check {
		// Array of undefined constants.
		private static $undefined_constants = array(
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

		// Array of predefined constants.
		private static $predefined_constants = array(
			'WP_CONTENT_DIR',
			'WP_CONTENT_URL',
			'FS_CHMOD_DIR',
			'FS_CHMOD_FILE',
		);

		// Main function.
		public function run() {
			// Set status as success by default.
			$this->set_status( 'success' );

			// Initialize the return message.
			$message = 'All constants are ok.';

			// Set default wp-content path.
			$content_dir = ABSPATH . 'wp-content';

			// Set default wp-content url.
			$content_url = rtrim( get_site_url(), '/' ) . '/wp-content';

			// Initialize wrong_constants array.
			$wrong_constants = array();

			// Gather information about constants.
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

			// If wrong_constants is not empty set status to warning and adjust the return message.
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

			// Return message.
			$this->set_message( $message );
		}
	}

	/**
	 * Log Scanner.
	 *
	 * command: wp doctor check wpmudev-log-scan --config=PATH
	 */
	class WPMUDEV_Doctor_Log_Scan extends runcommand\Doctor\Checks\Check {

		// List of known filenames to skip.
		private static $skip_names = array(
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

		// List of known files to accept without extension.
		private static $accept_names = array( 'error_log' );

		// List of extensions to test against.
		private static $extensions = array(
			'log',
			'txt',
		);

		// Size limit in bytes.
		private static $size_limit = 10485750; // 10MB

		// Main function.
		public function run() {
			// Set the limit to integer.
			$limit = (int) self::$size_limit;

			// Set status as success by default.
			$this->set_status( 'success' );

			// Initialize the return message.
			$message = 'No big log files detected (limit ' . self::format_bytes( $limit ) . ').';

			// Initialize log_files array.
			$log_files = array();

			// Go through the folders and files to gather information.
			$directory = new RecursiveDirectoryIterator( ABSPATH, RecursiveDirectoryIterator::SKIP_DOTS );
			$iterator  = new RecursiveIteratorIterator( $directory, RecursiveIteratorIterator::CHILD_FIRST );

			foreach ( $iterator as $file ) {
				if ( is_file( $file ) ) {
					$filename = $file->getBasename( '.' . $file->getExtension() );
					if ( ! in_array( $filename, self::$skip_names, true ) ) {
						if ( in_array( $file->getExtension(), self::$extensions, true ) || in_array( $filename, self::$accept_names, true ) ) {
							if ( $file->getSize() > $limit ) {
								$log_files[] = str_replace( ABSPATH, '', $file->getPathname() ) . ' (' . self::format_bytes( $file->getSize() ) . ')';
							}
						}
					}
				}
			}

			// If the log_files array is not empty adjust the return message and set status to warning.
			if ( ! empty( $log_files ) ) {
				$this->set_status( 'warning' );
				$message = implode( ', ', $log_files ) . '.';
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
}
