# custom-wp-doctor

## Customized `wp doctor` commands & checks.

![Tests](https://github.com/mrxkon/custom-wp-doctor/workflows/Tests/badge.svg)
[![PHP Compatibility 7.0+](https://img.shields.io/badge/PHP%20Compatibility-7.0+-8892BF)](https://github.com/PHPCompatibility/PHPCompatibility)
[![WordPress Coding Standards](https://img.shields.io/badge/WordPress%20Coding%20Standards-latest-blue)](https://github.com/WordPress/WordPress-Coding-Standards)

[![Quality Gate Status](https://sonarcloud.io/api/project_badges/measure?project=mrxkon_custom-wp-doctor&metric=alert_status)](https://sonarcloud.io/dashboard?id=mrxkon_custom-wp-doctor) [![Security Rating](https://sonarcloud.io/api/project_badges/measure?project=mrxkon_custom-wp-doctor&metric=security_rating)](https://sonarcloud.io/dashboard?id=mrxkon_custom-wp-doctor)
 [![Maintainability Rating](https://sonarcloud.io/api/project_badges/measure?project=mrxkon_custom-wp-doctor&metric=sqale_rating)](https://sonarcloud.io/dashboard?id=mrxkon_custom-wp-doctor) [![Reliability Rating](https://sonarcloud.io/api/project_badges/measure?project=mrxkon_custom-wp-doctor&metric=reliability_rating)](https://sonarcloud.io/dashboard?id=mrxkon_custom-wp-doctor)

[![My Website](https://img.shields.io/badge/My-Website-orange.svg)](https://xkon.gr)  [![WordPress Profile](https://img.shields.io/badge/WordPress-Profile-blue.svg)](https://profiles.wordpress.org/xkon)

[![Built for WordPress](https://img.shields.io/badge/built%20for-WordPress-blue)](https://wordpress.org) [![Built for WP-CLI](https://img.shields.io/badge/built%20for-WP--CLI-3d681d)](https://wp-cli.org/) [![Built for WPMU DEV](https://img.shields.io/badge/built%20for-WPMU%20DEV-blue)](https://premium.wpmudev.org/)
[![License: GPL v2](https://img.shields.io/badge/License-GPL%20v2+-red)](http://www.gnu.org/licenses/gpl-2.0.html)

---

### Pull requests are welcome!

---

### Props to all contributors of [wp-cli/doctor-command](https://github.com/wp-cli/doctor-command) as some parts of the code have been used on the modified commands.

This was created to fill a "gap" that I felt existed for easier usage, friendlier messages & to display as much information as possible in a more compact way when running only a `wp doctor` command instead of having to always run `wp doctor check --all --config=some/super/long/path`.

## How to install

1. Make sure to have `WP-CLI` & [wp-cli/doctor-command](https://github.com/wp-cli/doctor-command) installed.
2. Download the `master` branch.
3. Upload the `custom-wp-doctor` folder & the `class-custom-wp-doctor.php` into your `mu-plugins` directory.

If everything is done correctly you can now run `wp doctor` and you should get an output like this:

```
$ wp doctor
Running checks  100% [===================================================================================] 0:05 / 0:04
+-----------------------+---------+--------------------------------------------------------------------------------+
| name                  | status  | message                                                                        |
+-----------------------+---------+--------------------------------------------------------------------------------+
| file-eval             | success | All 'php' files passed check for 'eval\(.*base64_decode\(.*'.                  |
| cache-flush           | warning | Use of wp_cache_flush() detected.                                              |
| core-stats            | success | WordPress is at the latest version. Single Site, Public.                       |
| language-update       | success | Languages are up to date.                                                      |
| plugin-stats          | success | 10 Total, 4 Active (limit 80), 2 Inactive (limit 40%), 2 Must-use, 2 Dropins.  |
| wpmudev-plugins       | success | WPMU DEV Dashboard, Hummingbird, Defender & Smush are installed and activated. |
| theme-stats           | warning | 6 Total. 2 updates available for: twentyeleven, twentytwelve.                  |
| user-stats            | success | 1 Total.                                                                       |
| role-stats            | success | 1 administrator, 0 editor, 0 author, 0 contributor, 0 subscriber.              |
| posts-stats           | success | 1 Posts, 2 Pages, 0 Attachments.                                               |
| autoload-stats        | success | 19.1kb Total (limit 900kb).                                                    |
| ttfb                  | success | 0.020024s Time to first byte (TTFB).                                           |
| cache-headers         | success | Hummingbird.                                                                   |
| verify-core-checksums | success | WordPress verifies against its checksums.                                      |
| cron-stats            | success | 15 Total (limit 50).                                                           |
| constants             | success | All constants are ok.                                                          |
| log-scan              | warning | file.txt (100mb), error_log (100mb).                                           |
| php-in-upload         | warning | PHP files detected in the Uploads folder.                                      |
+-----------------------+---------+--------------------------------------------------------------------------------+
```
