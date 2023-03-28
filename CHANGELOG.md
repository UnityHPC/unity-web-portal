# 1.1.2

2023-3-28

* [Bug] Fixed new users not being added to cache

# 1.1.1

2023-3-15

* [Feature] Added Redis support for caching
* [Feature] Added new deployment folder
* [Feature] Added workers folder with flush cache script
* [Dev] Added many more test users
* [Bug] Fixes admin page styles

# 1.1.0

2023-3-13

* [Feature] Added alternate config email for PI emails (#35)
* [Feature] Pasted and Uploaded SSH Keys are now validated (#17)
* [Feature] Added request date for requests on admin/pi pages (#40)
* [Feature] Added conf options and login selector to user accounts page (#15)
* [Feature] Added PIGroups in User for view for admins (#29)
* [Feature] It is now possible to override mail templates
* [Frontend] Removed borders in between footer logos (#46)
* [Frontend] Added a new content management field for home page (#45)
* [Bug] Admin pages are now sorted alphabetically by UID (#52)
* [Bug] Fixed footer lookup PHP error for sending emails
* [Bug] Fixed changelog format
* [Bug] Fixed styling on content management page (#20)
* [API] Added API call for getting content management text
* [Project] Footers in the project are now generic
* [Project] Default configs do not reference UMA labels anymore

# 1.0.0-RC2

2022-10-03

* [BugFix] Fixed Unity Branding where Array were not being merged
* [Project] Bumped phpopenldaper version from composer

# 1.0.0-RC1

* [Feature] Admins can now access the web portal as a separate user
* [Feature] Added account details to account settings page
* [Feature] Added branding configuration
* [Feature] Added search for admin pages
* [Feature] Added cluster notice management page
* [Feature] Additional menu items are configurable from branding
* [Frontend] Styling changes
* [Frontend] Removed version from header
* [Backend] Slurm accounting is no longer in the scope of the project
* [Backend] Changed entire project folder structure
* [Backend] Content pages are now sql backed instead of hard coded
* [Backend] Removed locale for now
* [Backend] Changed all emails
* [Backend] LDAP objects are now not created until the user is approved for at least one group
* [Backend] Organization is set in ldap objects now
* [Project] Project now adheres to PHP PSR-12 standards


# 0.6.0-BETA

* [Feature] Added cluster notices - hakan
* [Feature] Added development environment features - hakan
* [Feature] Added universal logging support - hakan
* [Feature] Deployed new documentation - hakan
* [Feature] Added custom unity-web-portal ldif file for future - hakan
* [Bug] Disabled PI check for new user - hakan
* [Bug] Fixed issue which caused PI already exists message to not display correctly - calvin
* [UI] Added URI branding - hakan

# 0.5.0-BETA

* [Feature] Added notices for inactive account
* [Locale] New XML based locale for easier dynamic lang switching
* [Feature] Added VAST storage and Truenas Core storage integration for automatic storage creation
* [Feature] Added GitHub SSH key import
* [Performance] Nested groups are loaded via AJAX instead of directly to avoid long loading times
* [Feature] Added support for custom user id mappings
* [Style] General style changes
* [Feature] Added unityfs service, which is a socket service responsible for provisioning storage on Unity

# 0.4.0HF1-BETA

* [Bug] Fixed issue where some users account wasn't activating

# 0.4.0-BETA

* [Feature] Added Multiple PI Support
* [Backend] Users and PI Groups are treated as different entities