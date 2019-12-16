<?php // phpcs:ignore -- \r\n notice.

/**
 * Plugin Name: Customized "wp doctor"
 * Description: Combined & customized WP-CLI "wp doctor" checks.
 * Version:     1.0
 * Author:      Konstantinos Xenos
 * Author URI:  https://xkon.gr
 * Repo URI:    https://github.com/mrxkon/custom-wp-doctor/
 * License:     GPLv2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 */

/**
 * Copyright (C) 2019 Konstantinos Xenos (https://xkon.gr).
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 2 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see https://www.gnu.org/licenses/.
 */

/**
 * Props to all contributors of https://github.com/wp-cli/doctor-command.
 *
 * For installation instructions visit: https://github.com/mrxkon/custom-wp-doctor/.
 */

// Check that the file is not accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	die( 'We\'re sorry, but you can not directly access this file.' );
}

// Wrapper command for "wp doctor"
if ( defined( 'WP_CLI' ) && WP_CLI ) {
	WP_CLI::add_command( 'doctor', 'Custom_WP_Doctor' );
}

/**
 * Adds a wrapper "wp doctor" function to run our custom config directly.
 */
class Custom_WP_Doctor {
	public function __invoke() {
		WP_CLI::runcommand( 'doctor check --all --config=' . ABSPATH . 'wp-content/mu-plugins/custom-wp-doctor/custom-wp-doctor.yml' );
	}
}
