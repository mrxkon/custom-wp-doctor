# wpmudev-doctor

## Customized `wp doctor` command & checks for WPMU DEV Hosting.

### Props to all contributors of [wp-cli/doctor-command](https://github.com/wp-cli/doctor-command) as some parts of the code have been used on the modified commands.

This was created to fill a "gap" that I felt existed for easier usage & to display as much information as possible in a more compact way when running only a `wp doctor` command instead of having to always run `wp doctor check --all --config=PATH`

![Tests](https://github.com/mrxkon/wpmudev-doctor/workflows/Tests/badge.svg)
![PHP Compatibility 7.0+](https://img.shields.io/badge/PHP%20Compatibility-7.0+-8892BF) ![WordPress Coding Standards](https://img.shields.io/badge/WordPress%20Coding%20Standards-latest-blue)

[![Quality Gate Status](https://sonarcloud.io/api/project_badges/measure?project=mrxkon_wpmudev-doctor&metric=alert_status)](https://sonarcloud.io/dashboard?id=mrxkon_wpmudev-doctor) [![Security Rating](https://sonarcloud.io/api/project_badges/measure?project=mrxkon_wpmudev-doctor&metric=security_rating)](https://sonarcloud.io/dashboard?id=mrxkon_wpmudev-doctor)
 [![Maintainability Rating](https://sonarcloud.io/api/project_badges/measure?project=mrxkon_wpmudev-doctor&metric=sqale_rating)](https://sonarcloud.io/dashboard?id=mrxkon_wpmudev-doctor) [![Reliability Rating](https://sonarcloud.io/api/project_badges/measure?project=mrxkon_wpmudev-doctor&metric=reliability_rating)](https://sonarcloud.io/dashboard?id=mrxkon_wpmudev-doctor)


[![My Website](https://img.shields.io/badge/My-Website-orange.svg)](https://xkon.gr)  [![WordPress Profile](https://img.shields.io/badge/WordPress-Profile-blue.svg)](https://profiles.wordpress.org/xkon)

[![Built for WordPress](https://img.shields.io/badge/built%20for-WordPress-blue)](https://wordpress.org) [![Built for WP-CLI](https://img.shields.io/badge/built%20for-WP--CLI-3d681d)](https://wp-cli.org/) [![Built for WPMU DEV](https://img.shields.io/badge/built%20for-WPMU%20DEV-blue)](https://premium.wpmudev.org/)
[![License: GPL v2](https://img.shields.io/badge/License-GPL%20v2+-red)](http://www.gnu.org/licenses/gpl-2.0.html)

---
### Pull requests are welcome!
---

## How to install

1. Make sure to have `WP-CLI` & [wp-cli/doctor-command](https://github.com/wp-cli/doctor-command) installed.
2. Download the `master` branch.
3. Upload the `wpmudev-doctor` folder & the `class-wpmudev-hosting-doctor.php` into your `mu-plugins` directory.

If everything is done correctly you can now run `wp doctor` and you should get an output like this:

```
Running checks  100% [============================================================================================] 0:03 / 0:05
+-------------------------------+---------+--------------------------------------------------------------------------------+
| name                          | status  | message                                                                        |
+-------------------------------+---------+--------------------------------------------------------------------------------+
| file-eval                     | success | All 'php' files passed check for 'eval\(.*base64_decode\(.*'.                  |
| cache-flush                   | warning | Use of wp_cache_flush() detected.                                              |
| wpmudev-core-stats            | success | WordPress is at the latest version. Single Site, Public.                       |
| language-update               | success | Languages are up to date.                                                      |
| wpmudev-plugin-stats          | success | 10 Total, 4 Active (limit 80), 2 Inactive (limit 40%), 2 Must-use, 2 Dropins.  |
| wpmudev-plugin-check          | success | WPMU DEV Dashboard, Hummingbird, Defender & Smush are installed and activated. |
| wpmudev-theme-stats           | warning | 4 Total. 1 update available for: twentytwenty.                                 |
| wpmudev-user-stats            | success | 1 Total.                                                                       |
| wpmudev-roles-stats           | success | 1 administrator, 0 editor, 0 author, 0 contributor, 0 subscriber.              |
| wpmudev-posts-stats           | success | 1 Posts, 2 Pages, 0 Attachments.                                               |
| wpmudev-autoload-report       | success | 19.12kb Total (limit 900kb).                                                   |
| wpmudev-ttfb                  | success | 0.041726s Time to first byte (TTFB).                                           |
| wpmudev-cache-headers         | success | Hummingbird.                                                                   |
| wpmudev-verify-core-checksums | success | WordPress verifies against its checksums.                                      |
| wpmudev-cron-stats            | success | 15 Total (limit 50).                                                           |
| wpmudev-constants             | warning | Defined: WP_DEBUG.                                                             |
| wpmudev-log-scan              | warning | whatever.txt (100mb).                                                          |
| php-in-upload                 | warning | PHP files detected in the Uploads folder.                                      |
+-------------------------------+---------+--------------------------------------------------------------------------------+
```
