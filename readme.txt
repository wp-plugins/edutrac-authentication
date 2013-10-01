=== eduTrac Authentication ===
Contributors: parkerj
Donate link: none
Tags: authentication, login, eduTrac, RESTful, REST API, API, SIS
Requires at least: 3.6
Tested up to: 3.6.1
Stable tag: 1.0.2
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

A plugin that can be used to tap into an eduTrac install's RESTful API for authentication into a WordPress powered site.

== Description ==

<a href="http://www.7mediaws.org/">eduTrac</a> is an open source student information system (SIS) that can be used by secondary and post-secondary educational institutions to help manage 
faculty, staff, students, courses, course registration, NSLC transmissions, and more. If you are using a WordPress site along with an eduTrac install, you can use this plugin to bypass the WordPress registration by 
using the eduTrac RESTful API.

If the user exists in eduTrac but does not exist in WordPress, the user will be created. Also, if users already exist in WordPress, they will still be able to login, but their 
user details will might be blank. User details are ported from eduTrac to WordPress, so any changes made to a user's profile will be overwritten the next time the user logs in.

== Installation ==

* Make sure that the administrator account which exists on your WordPress install, also exists as a super administrator on your eduTrac install.
* Upload the `edutrac-authentication` folder to the `/wp-content/plugins/` directory
* Activate the plugin through the 'Plugins' menu in WordPress
* Enter your eduTrac install settings in Settings -> eduTrac API settings

== Frequently Asked Questions ==

= Where do I go to get an authenication token? =

You can visit the eduTrac <a href="http://community.7mediaws.org/projects/edutrac/wiki/RESTful_API">community site</a> for instructions on retrieving an authentication token.

= My administrator account for WordPress doesn't work anymore! =

Since you are authenticating against eduTrac's RESTful API, make sure the super administrator username in your eduTrac install, matches the administrator username in WordPress. Once it's in there, you'll be able to log in as the administrator.  If you are unable to log in, delete the plugin and access should be restored back to the WordPress administrator.

= Can I still create accounts within WordPress? =

No, this is disabled. The only way to create accounts is through your eduTrac installation.

= Can I update user information within WordPress? =

No, what ever information is entered on the WordPress profile page will be overwritten or destroyed on the next login. It is best to redirect the user back to eduTrac.

= I'm locked out! =

FTP into your server, navigate to the plugins folder and rename the plugin; if it's a database connection related error.

== Screenshots ==

1. eduTrac Auth Settings
2. Login message seen on a user's profile.
3. Example login warning message upon access to wp-login.php
4. Example "Lost my password" retrieval attempt

== Changelog ==

= 1.0.2 (2013.10.01) =

* Added instructions on where to go to get an authentication token.
* Added internationalization

= 1.0.1 (2013.09.12) =

* Initial release
