## 1.0.0-RC2
* [BugFix] Fixed Unity Branding where Array were not being merged

## 1.0.0-RC1
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


## 0.6.0-BETA
* [Feature] Added cluster notices - hakan
* [Feature] Added development environment features - hakan
* [Feature] Added universal logging support - hakan
* [Feature] Deployed new documentation - hakan
* [Feature] Added custom unity-web-portal ldif file for future - hakan
* [Bug] Disabled PI check for new user - hakan
* [Bug] Fixed issue which caused PI already exists message to not display correctly - calvin
* [UI] Added URI branding - hakan

## 0.5.0-BETA
* [Feature] Added notices for inactive account
* [Locale] New XML based locale for easier dynamic lang switching
* [Feature] Added VAST storage and Truenas Core storage integration for automatic storage creation
* [Feature] Added GitHub SSH key import
* [Performance] Nested groups are loaded via AJAX instead of directly to avoid long loading times
* [Feature] Added support for custom user id mappings
* [Style] General style changes
* [Feature] Added unityfs service, which is a socket service responsible for provisioning storage on Unity

## 0.4.0HF1-BETA
* [Bug] Fixed issue where some users account wasn't activating

## 0.4.0-BETA
* [Feature] Added Multiple PI Support
* [Backend] Users and PI Groups are treated as different entities