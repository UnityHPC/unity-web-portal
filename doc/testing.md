UNIT TESTS
---
* UnityConfig
  * getConfig
    * precedence: `def_config_loc` < `deploy_loc` < override
    * `{def_config_loc}/config.ini.default` file exists?
    * `{def_config_loc}/config.ini.default` file is valid ini?
    * `{deploy_loc}/config/config.ini` file exists?
    * `{deploy_loc}/config/config.ini` file is valid ini?
    * `{deploy_loc}/overrides/{_SERVER["HTTP_HOST"]}/` is a directory?
    * `{deploy_loc}/overrides/{_SERVER["HTTP_HOST"]}/config/config.ini` file exists?
    * `{deploy_loc}/overrides/{_SERVER["HTTP_HOST"]}/config/config.ini` is valid ini?
    * path traversal attack using `HTTP_HOST`?

* UnityGroup
  * equals
    * different type?
    * pi uids equal?
  * getPIUID
  * ~~getPIUID()~~
    * don't test getters and setters
  * ~~exists~~
    * tests on LDAPEntry should cover this
  * requestGroup
    * group exists?
    * owner user exists?
    * account deletion request exists for owner user?
    * `send_mail`?
      * send mail to admins?
    * added to audit log?
  * approveGroup
    * group exists?
    * owner user exists?
    * account deletion request exists for owner user?
    * operator is null?
    * `send_mail`?
    * added to audit log?
  * denyGroup
    * group exists?
    * owner user exists?
    * account deletion request exists for owner user?
    * operator is null?
    * `send_mail`?
    * added to audit log?
  * removeGroup
    * group exists?
    * `ldapPiGroupEntry` exists?
    * `send_mail`?
    * added to audit log?
  * approveUser
    * user exists?
    * request exists?
    * `send_mail`?
  * denyUser
    * user exists?
    * request exists?
    * `send_mail`?
  * removeUser
    * user exists?
    * request exists?
    * `send_mail`?
  * newUserRequest
    * user exists?
    * request exists?
    * `send_mail`?
  * getRequests
  * getGroupMembers
    * `ignorecache`?
    * `is_null(cached_val)`?
    * `isset(members)`?
    * `!ignorecache && updatecache`?
  * getGroupMemberUIDs
  * requestExists
    * `len(requesters) > 0`?
    * `user` in `getRequests()`?
  * init
    * ldap pi group entry exists?
  * addUserToGroup
  * userExists
    * `user` in `getGroupMemberUIDs()`?
  * addRequest
  * removeRequest
  * getOwner
  * getLDAPPiGroup
  * getPIUIDfromUID
  * getUIDfromPIUID

* UnityLDAP
  * ~~getUserOU~~
  * ~~getGroupOU~~
  * ~~getPIGroupOU~~
  * ~~GetOrgGroupOU~~
  * ~~getAdminGroup~~
  * ~~getDefUserShell~~
  * getNextUIDNumber
  * getNextPIGIDNumber
  * getNextOrgGIDNumber
  * UIDNumInUse
    * is linux reserved?
  * GIDNumInUse
    * is linux reserved?
  * getUnassignedID
    * csv file in custom mappings path?
      * uid match in csv file?
        * uid num for match already in use?
        * gid num for match already in use?
  * getAllUsers
    * ignorecache?
      * is_null(users)?
  * gettAllPIGroups
    * ignorecache?
      * is_null(users)?
  * gettAllOrgGroups
    * ignorecache?
      * is_null(users)?
  * getUserEntry
  * getGroupEntry
  * getPIGroupEntry
  * getOrgGroupEntry

* UnityMailer
  * sendMail
    * isset(template)?
      * `file_exists({override_template_dir}/{template_filename})`?
      * `file_exists({template_dir}/{template_filename})`?
      * `file_exists({override_template_dir}/footer.php)`?
      * `file_exists({template_dir}/footer.php)`?
      * recipients == "admin"?
      * recipients == "pi_approve"?
      * is_array(recipients)?

* UnityOrg
  * init
    * org_group exists?
  * ~~exists~~
    * tests on LDAPEntry should cover this
  * getLDAPOrgGroup
  * GetOrgID
  * inOrg
  * getOrgMembers
    * `ignorecache`?
      * `is_nul(cached_val)`?
    * isset(members)?
    * `!ignorecache && updatecache`?
  * addUser
  * removeUser

* UnityRedis
  * setCache
    * enabled?
    * empty(key)?
  * getCache
    * enabled?
    * empty(key)?
  * appendCacheArray
    * enabled?
    * empty(key)?
    * `is_null(cached_val)`?
    * `is_array(cached_val)`?
  * removeCacheArray
    * enabled?
    * empty(key)?
    * `is_null(cached_val)`?
    * `is_array(cached_val)`?

* UnitySQL
  * getConn
  * addRequest
    * requestExists?
  * removeRequest
    * requestExists?
  * removeRequests
  * requestExists
  * getRequests
  * getRequestsByUser
  * deleteRequestsByUser
  * addNotice
  * editNotice
  * deleteNotice
  * getNotice
  * getNotices
  * getPages
  * getPage
  * editPage
  * addLog
  * addAccountDeletionRequest
  * accDeletionRequestExists
  * getSiteVar
  * updateSiteVar
  * getRole
  * hasPerm
  * getPriority
  * roleAvailableInGroup

* UnitySSO
  * eppnToUID
  * eppnToOrg
  * getSSO
    * isset(_SERVER["REMOTE_USER"])?

* UnitySite
  * redirect
  * removeTrailingWhitespace
  * getGithubKeys
  * testValidSSHKey

* UnityUser
  * equals
    * different type?
    * pi uids equal?
  * init
    * ldapGroupEntry exists?
    * ldapUserEntry exists?
    * orgEntry exists?
    * `send_mail`?
  * getLDAPUser
  * getLDAPGroup
  * ~~exists~~
    * tests on LDAPEntry should cover this
  * ~~getUID~~
  * setOrg
  * getOrg
    * ignorecache?
      * `is_null(cached_val)`?
    * exists?
      * ignorecache?
  * setFirstname
  * getFirstname
    * ignorecache?
      * `is_null(cached_val)`?
    * exists?
      * ignorecache?
  * setLastname
  * getLastname
    * ignorecache?
      * `is_null(cached_val)`?
    * exists?
      * ignorecache?
  * ~~getFullname~~
    * covered by getFirstname and getLastname
  * setMail
  * getMail
    * ignorecache?
      * `is_null(cached_val)`?
    * exists?
      * ignorecache?
  * setSSHKeys
    * `is_null(operator)`?
    * ldapUser exists?
    * `send_mail`?
  * getSSHKeys
    * ignorecache?
      * `is_null(cached_val)`?
    * exists?
      * ignorecache?
  * setLoginShell
    * ldapUser exists?
    * `is_null(operator)`?
  * getLoginShell
    * ignorecache?
      * `is_null(cached_val)`?
    * exists?
      * ignorecache?
  * setHomeDir
    * ldapUser exists?
      * `is_null(operator)`?
  * getHomeDir
    * ignorecache?
      * `is_null(cached_val)`?
    * exists?
      * ignorecache?
  * isAdmin
    * uid in admins?
  * isPI
    * `getPIGroup()` exists?
  * ~~getPIGroup~~
  * ~~getOrgGroup~~
  * getGroups
    * ignorecache?
      * `is_null(cached_val)`
    * ignorecache?
  * requestAccountDeletion
  * hasRequestedAccountDeletion
    * `accDeletionRequestExists(uid)`?

* UnityWebhook
  * htmlToMarkdown
  * sendWebhook
    * `file_exists({override_template_dir}/{template_filename})`?
    * `file_exists({template_dir}/{template_filename})`?


INTEGRATION TESTS
---
* creating new accounts
* adding SSH keys (all methods)
* removing SSH keys
* requesting PI groups
* accepting PI group requests
* denying PI group requests
* adding new users to PI groups
* removing users from PI groups
* deleting users
* deleting PI groups
* changing login shell

API
---
```
header.php: form_name: clearView
header.php: isset(viewUser)

content.php: pageSel

notices.php: form_type: newNotice, editNotice, delNotice
notices.php(form_type==newNotice): title, date, content: ...
notices.php(form_type==editNotice): id, title, date, content: ...
notices.php(form_type==delNotice): id: ...

pi-mgmt.php: uid: ...
pi-mgmt.php: form_name: req, reqChild, remUserChild, remGroup
pi-mgmt.php(form_name==req,reqChild): action: Approve, Deny
pi-mgmt.php(form_name==remUserChild): pi: ...
pi-mgmt.php(form_name==remGroup): ...

user-mgmt.php: form_name: ==viewAsUser?

account.php: form_type: addKey, delKey, loginshell, pi_request, account_deletion_request
account.php(form_type==addKey): add_type: paste, import, generate, github
account.php(form_type==delKey): delIndex: ...
account.php(form_type==loginshell): shell, shellSelect: ...
account.php(form_type==pi_request): ...
account.php(form_type==account_deletion_request): ...

groups.php: form_name: addPIform, removePIform
groups.php(form_name isset): pi: ...
groups.php(form_name==addPIform): ...
groups.php(removePIform): ...

new_account.php: eula, agree
new_account.php: new_user_sel: pi, not_pi
new_account.php(new_user_sel isset): pi

pi.php: uid: ...
pi.php: form_name: userReq, remUser, disband
pi.php(form_name==userReq): action: Approve, Deny
pi.php(form_name==remUser): ...
pi.php(form_name==disband): ...
```
