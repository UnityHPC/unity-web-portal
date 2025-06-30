# 1.2.2 (upcoming)
* [Bug] Fix `uidnumber`/`gidnumber` conflicts by @simonLeary42 in #248
* [Internal] Refactored `UnitySQL` by @simonLeary42 in #252
* [Internal] Refactored custom user mappings by @simonLeary42 in #253
* [Internal] Style by @simonLeary42 in #254

# 1.2.1
* [Feature] Added cancellation of PI become / PI member requests for existing users by @bryank-cs and @simonLeary42 in #250
* [Internal] Improved error messages by @simonLeary42 in https://github.com/UnityHPC/unity-web-portal/commit/98fb39559aa6668d86ac4aa04b9e9d2c6b8f7f02
* [Internal] Fixed bad variable name by @simonLeary42 in #242 https://github.com/UnityHPC/unity-web-portal/commit/2e03c1187c39ac91c3a8f74cf62ac57d29146d97
* [Internal] Fixed undefined variable by @simonLeary42 in https://github.com/UnityHPC/unity-web-portal/commit/5c2cee50097017fea763ebef56b5ef6c096161bc
* [Internal] Removed `panel/index.php` by @simonLeary42 in #251
* [Internal] Added `getOrgMemberUIDs` by @simonLeary42 in https://github.com/UnityHPC/unity-web-portal/commit/46490b5743bfbd5df72f8a105db1f8f405e812ce
* [Internal] Added `getAllRequests` by @simonLeary42 in https://github.com/UnityHPC/unity-web-portal/commit/3ac98f83aee497a894f5d59760ec805bc8ca5872
* [Internal] Added warning on `null` input to redis `setCache` by @simonLeary42 in https://github.com/UnityHPC/unity-web-portal/commit/969c0ad50df6609b66123c275f3ba5695f600af0
* [Internal] Required attributes to be specified in LDAP search by @simonLeary42 in https://github.com/UnityHPC/unity-web-portal/commit/37f691b1e1048be4650fab0b2c7a30e0627b36ca https://github.com/hakasapl/phpopenldaper/pull/16
* [Bug] Fix missing emails after redis flush by @simonLeary42 in #244

# 1.2.0
* [Feature] Added slack webhooks by @sheldor1510 in #65
* [Feature] Added audit log by @sheldor1510 in #69 #72 #106
* [Feature] Added account deletion requests by @sheldor1510 in #82
* [Feature] Added reminder emails for PI membership requests @bryank-cs in #101
* [Feature] Added sorting and filtering to user / PI mgmt pages by @sheldor1510 in #87
* [Frontend] Improved visuals on account page by @simonLeary42 in #201 #204 #205 #206
* [Frontend] Improved visuals on user / PI mgmt pages by @simonLeary42 in #205
* [Frontend] Removed custom shell by @simonLeary42 in #203
* [Frontend] Removed disband PI by @simonLeary42 in #207
* [Frontend] Addded ToS confirmation by @bryank-cs in #223
* [Frontend] Addded cancellation of PI requests / PI member requests for users awaiting request approval @bryank-cs in #223
* [Frontend] Improved SSH key email contents by @sheldor1510 in #66
* [Frontend] Added note to account page by @sheldor1510 in #91
* [Dev] Updated SQL bootstrap by @sheldor1510 in #91
* [Dev] Added automated testing by @simonLeary42 in #179 #186 #188 #190 #191 #192 #193 #194 #198 #212 #232 #238
* [Dev] Removed policy page / `priv.php`, allow external link instead by @simonLeary42 in #182
* [Dev] Changed admin group from `sudo` to `web_admins`, same as production by @simonLeary42 in #173
* [Dev] Sorted `htpasswd` by @simonLeary42 in #220
* [Bug] Fixed garbage SSH keys from empty github username by @Shaswat975 in #102
* [Bug] Fixed bug when removing old requests by @bryank-cs in #104
* [Bug] Fixed generate SSH key button by @sheldor1510 in #91
* [Bug] Fixed trailing whitespace in PI request by @simonLeary42 in #122
* [Bug] Fixed missing argument by @simonLeary42 in #123
* [Bug] Fixed github SSH key API handling by @simonLeary42 in #162
* [Bug] Fixed `uidnumber`/`gidnumber` collisions by @simonLeary42 in #154
* [API] Switched to JSON Schema for cluster notices @Shaswat975 in #103
* [Internal] Added `UnityPerms` Class by @sheldor1510 in #92
* [Internal] Made REDIS Server Optional by @Shaswat975 in #105
* [Internal] Reduced size of data types in sql by @simonLeary42 in #148
* [Internal] Removed old tables from SQL by @simonLeary42 in #159
* [Internal] Added `gecos` field by @simonLeary42 in #216 #221 #222
* [Internal] Added user group as well as user OU by @simonLeary42 in #218
* [Internal] Added failure if request does not exist by @simonLeary42 in #230
* [Internal] Rewrote `update-ldap-cache.php` by @simonLeary42 in #157 #239 #241
* [Internal] Added caching of max `uidnumber`/`gidnumber` @sheldor1510 in #84
* [Internal] Made emailing admins for PI requests optional by @sheldor1510 in #73
* [Internal] Style by @simonLeary42 in #166 #189 #202 #227
* [Internal] Fixed absolute require by @simonLeary42 in #189
* [Internal] Fixed confusing constant by @simonLeary42 in #158
* [Internal] Removed unused variables by @simonLeary42 in #155
* [Internal] Added LDAP sanitization by @simonLeary42 in #167
* [Internal] Fixed dubious redirect by @bryank-cs in #223
* [Internal] Fixed function name by @simonLeary42 in #228
* [Internal] Refactored `getGroupMemberUIDs`/`getGroupMembers` by @simonLeary42 in #236
* [Internal] Added redirect after HTTP POST by @simonLeary42 in #233
* [Internal] Added more SSH key validation by @simonLeary42 in #187
* [Internal] Refactored `get_group_members.php` @simonLeary42 in #235
* [Internal] Refactored HTTP POST handling in account page by @simonLeary42 in #214
* [Internal] Fix missing import by @simonLeary42 in #231 #237
* [Internal] Switched all uses of `form_name` to `form_type` by @simonLeary42 in #239
* [Internal] Simplified usage of LDAP wrapper functions by @simonLeary42 in #213
* [Internal] Muted warnings by @simonLeary42 in https://github.com/hakasapl/phpopenldaper/pull/3
* [Internal] Fixed deprecation warning by @simonLeary42 in https://github.com/hakasapl/phpopenldaper/pull/5
* [Internal] Ensured that all attributes are returned as arrays by @simonLeary42 in https://github.com/hakasapl/phpopenldaper/pull/14 https://github.com/hakasapl/phpopenldaper/pull/6
* [Internal] Fixed duplicate out-of-sync `LDAPEntry` objects by @simonLeary42 in https://github.com/hakasapl/phpopenldaper/pull/7
* [Internal] Refactored `LDAPEntry` by @simonLeary42 in https://github.com/hakasapl/phpopenldaper/pull/8
* [Internal] Added better error messages by @simonLeary42 in https://github.com/hakasapl/phpopenldaper/pull/11
* [Internal] Added assertions to `LDAPEntry` by @simonLeary42 in https://github.com/hakasapl/phpopenldaper/pull/12

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
