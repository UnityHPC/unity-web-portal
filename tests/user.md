# User Tests

The following tests will perform every possible user/group function on the web portal:

1. Sign in as the user `user7@domain.edu`
    1. Verify sso_log in mysql is filled in
1. Request an account as a PI
    1. Verify mysql requests table has the correct row
    1. Verify emails:
        1. One going to admins
        1. One going to the user
1. Approve the request with the user `admin1@domain.edu`
    1. Verify 3x LDAP objects were created and that they have correct attributes:
        1. `cn=user7_domain_edu,ou=users,dc=unity,dc=rc,dc=umass,dc=edu`
        1. `cn=user7_domain_edu,ou=groups,dc=unity,dc=rc,dc=umass,dc=edu`
        1. `cn=pi_user7_domain_edu,ou=pi_groups,dc=unity,dc=rc,dc=umass,dc=edu`
    1. Verify that the user was added to an ORG group in LDAP
    1. Verify email goes to the new PI
    1. Verify the request is no longer in the sql table
1. Sign in as the user `user8@domain.edu`
    1. Verify sso_log in mysql is filled in
1. 
