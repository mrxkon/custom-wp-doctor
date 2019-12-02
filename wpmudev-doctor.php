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

/**
 * Checks for Core stats.
 */
class WPMUDEV_Doctor_Core_Stats extends runcommand\Doctor\Checks\Check {

	public function run() {
		// Multisite information.
		if ( is_multisite() ) {
			$cmd_options = array(
				'return'     => true,
				'parse'      => 'json',
				'launch'     => false,
				'exit_error' => true,
			);

			$total_sites = WP_CLI::runcommand( 'site list --format=count', $cmd_options );

			if ( defined( 'SUBDOMAIN_INSTALL' ) && SUBDOMAIN_INSTALL ) {
				$wpmu_type = 'Subdomain';
			} else {
				$wpmu_type = 'Subdirectory';
			}

			$multisite_stats = ' | ' . $wpmu_type . ' Multisite | Sites: ' . $total_sites;
		} else {
			$multisite_stats = ' | Single Site';
		}

		// Public option.
		$cmd_options = array(
			'return'     => true,
			'parse'      => 'json',
			'launch'     => false,
			'exit_error' => true,
		);

		$public = WP_CLI::runcommand( 'option get blog_public', $cmd_options );

		if ( ! $public ) {
			$public_msg = ' | Public: False.';
		} else {
			$public_msg = ' | Public: True.';
		}

		// Core updates.
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
			$this->set_status( 'warning' );
			$core = 'A new minor version is available.';
		} elseif ( $has_major ) {
			$this->set_status( 'warning' );
			$core = 'A new major version is available.';
		} else {
			$this->set_status( 'success' );
			$core = 'WordPress is at the latest version.';
		}

		if ( ! $public ) {
			$this->set_status( 'warning' );
		}

		$this->set_message( $core . $multisite_stats . $public_msg );
	}
}

/**
 * Checks for Plugin stats.
 */
class WPMUDEV_Doctor_Plugin_Stats extends runcommand\Doctor\Checks\Check {

	public function run() {
		$cmd_options = array(
			'return'     => true,
			'parse'      => 'json',
			'launch'     => false,
			'exit_error' => true,
		);

		$total_plugins          = WP_CLI::runcommand( 'plugin list --format=count', $cmd_options );
		$active_plugins         = WP_CLI::runcommand( 'plugin list --status=active --format=count', $cmd_options );
		$active_network_plugins = WP_CLI::runcommand( 'plugin list --status=active-network --format=count', $cmd_options );
		$inactive_plugins       = WP_CLI::runcommand( 'plugin list --status=inactive --format=count', $cmd_options );
		$mu_plugins             = WP_CLI::runcommand( 'plugin list --status=must-use --format=count', $cmd_options );
		$dropin_plugins         = WP_CLI::runcommand( 'plugin list --status=dropin --format=count', $cmd_options );
		$total_active_plugins   = $active_plugins + $active_network_plugins;

		$this->set_status( 'success' );
		$this->set_message( $total_plugins . ' Total | ' . $total_active_plugins . ' Active | ' . $inactive_plugins . ' Inactive | ' . $mu_plugins . ' Must-use | ' . $dropin_plugins . ' Dropins.' );
	}
}

/**
 * Checks for Theme stats.
 */
class WPMUDEV_Doctor_Theme_Stats extends runcommand\Doctor\Checks\Check {

	public function run() {
		$cmd_options = array(
			'return'     => true,
			'parse'      => 'json',
			'launch'     => false,
			'exit_error' => true,
		);

		$total_themes = WP_CLI::runcommand( 'theme list --format=count', $cmd_options );

		$this->set_status( 'success' );
		$this->set_message( $total_themes . ' Total.' );
	}
}

/**
 * Checks for User stats.
 */
class WPMUDEV_Doctor_User_Stats extends runcommand\Doctor\Checks\Check {

	public function run() {
		$cmd_options = array(
			'return'     => true,
			'parse'      => 'json',
			'launch'     => false,
			'exit_error' => true,
		);

		$total_users = WP_CLI::runcommand( 'user list --format=count', $cmd_options );
		$roles       = WP_CLI::runcommand( 'role list --format=json', $cmd_options );
		$role_list   = array();

		if ( $roles ) {
			$this->set_status( 'success' );
			foreach ( $roles as $role ) {
				array_push( $role_list, $role['name'] . '(' . $role['role'] . ')' );
			}

			$role_result = 'Roles: ' . implode( ', ', $role_list ) . '.';
		} else {
			$this->set_status( 'warning' );
			$role_result = 'No roles found.';
		}

		$this->set_message( $total_users . ' Total Users | ' . $role_result );
	}
}

/**
 * Checks for Posts stats.
 */
class WPMUDEV_Doctor_Posts_Stats extends runcommand\Doctor\Checks\Check {

	public function run() {
		$cmd_options = array(
			'return'     => true,
			'parse'      => 'json',
			'launch'     => false,
			'exit_error' => true,
		);

		$posts       = WP_CLI::runcommand( 'post list --post_type=post --format=count', $cmd_options );
		$pages       = WP_CLI::runcommand( 'post list --post_type=page --format=count', $cmd_options );
		$posts_count = ( $posts ) ? $posts : 0;
		$pages_count = ( $pages ) ? $pages : 0;

		$this->set_status( 'success' );
		$this->set_message( $posts_count . ' Posts | ' . $pages_count . ' Pages.' );
	}
}

/**
 * Checks for Autoload options to not be over 900kb.
 */
class WPMUDEV_Doctor_Autoload_Report extends runcommand\Doctor\Checks\Check {

	private static $threshold_bytes = 900 * 1024;

	public function run() {
		$cmd_options = array(
			'return'     => true,
			'parse'      => 'json',
			'launch'     => false,
			'exit_error' => true,
		);

		$total_bytes = WP_CLI::runcommand( 'option list --autoload=on --format=total_bytes', $cmd_options );

		$human_threshold = self::format_bytes( self::$threshold_bytes );
		$human_total     = self::format_bytes( $total_bytes );

		if ( self::$threshold_bytes < $total_bytes ) {
			$data = WP_CLI::runcommand( 'option list --fields=option_name,size_bytes --autoload=on --format=json', $cmd_options );

			usort(
				$data,
				function( $a, $b ) {
					return $a['size_bytes'] < $b['size_bytes'];
				}
			);

			$data = array_slice( $data, 0, 3 );

			$final_data = array();

			foreach ( $data as $key => $value ) {
				array_push( $final_data, $data[ $key ]['option_name'] . '(' . self::format_bytes( $data[ $key ]['size_bytes'] ) . ')' );
			}

			$this->set_status( 'warning' );
			$this->set_message( "Autoload options size is {$human_total} (limit {$human_threshold}). | 3 biggest options: " . implode( ', ', $final_data ) ) . '.';
		} else {
			$this->set_status( 'success' );
			$this->set_message( "Autoload options size is {$human_total} (limit {$human_threshold})." );
		}
	}

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
			$this->set_message( 'Time to first byte (TTFB): ' . $curl_info['starttransfer_time'] . ' seconds.' );
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

		if ( $found_headers ) {
			$this->set_status( 'success' );
			$this->set_message( 'Cache Headers found: ' . implode( ', ', $found_headers ) ) . '.';
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

		$this->set_message( $cron_count . ' Total cron jobs (limit ' . self::$threshold_count . ').' . $dup_msg );

	}
}
