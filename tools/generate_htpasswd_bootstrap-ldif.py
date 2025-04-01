"""
generates an htpasswd and a bootstrap.ldif for the web and identity docker containers, respectively
outputs shell instructions to set LDAP_BOOTSTRAP_LDIF_PATH and HTPASSWD_PATH to tempfiles
"""

import string
import random
import tempfile

import ldap3
from beartype import beartype

ROOT_DN = "dc=unityhpc,dc=test"
USERS_OU_DN = f"ou=users,{ROOT_DN}"
USER_GROUPS_OU_DN = f"ou=groups,{ROOT_DN}"
PI_GROUPS_OU_DN = f"ou=pi_groups,{ROOT_DN}"
ORG_GROUPS_OU_DN = f"ou=org_groups,{ROOT_DN}"
USER_OBJECT_CLASSES = ["inetOrgPerson", "posixAccount", "top", "ldapPublicKey"]
GROUP_OBJECT_CLASSES = ["posixGroup", "top"]
OU_OBJECT_CLASSES = ["organizationalUnit", "top"]
LDAP_EXTRA_ENTRIES = [
    [
        ROOT_DN,
        ["organization", "dcObject", "top"],
        {"structuralObjectClass": "organization", "o": "unityhpc"},
    ],
    [
        f"cn=admin,{ROOT_DN}",
        [
            "simpleSecurityObject",
            "organizationalRole",
            "top",
        ],
        {
            "cn": "admin",
            "userPassword": "{SSHA}d6WBSm5wjlNpMwil1KQY+Uo4o/vc6PnR",  # password is "password"
            "description": "for LDAP server administration purposes only",
        },
    ],
    [
        f"cn=web_admins,{ROOT_DN}",
        GROUP_OBJECT_CLASSES,
        {"cn": "web_admins", "gidnumber": 500, "memberuid": "web_admin_unityhpc_test"},
    ],
    [f"ou=groups,{ROOT_DN}", OU_OBJECT_CLASSES, {"ou": "groups"}],
    [f"ou=org_groups,{ROOT_DN}", OU_OBJECT_CLASSES, {"ou": "org_groups"}],
    [f"ou=pi_groups,{ROOT_DN}", OU_OBJECT_CLASSES, {"ou": "pi_groups"}],
    [f"ou=users,{ROOT_DN}", OU_OBJECT_CLASSES, {"ou": "users"}],
]
WEB_ADMIN = {
    "cn": "web_admin_unityhpc_test",
    "uid": "web_admin_unityhpc_test",
    "mail": "web_admin@unityhpc.test",
    "o": "unityhpc_test",
    "homedirectory": "/home/web_admin_unityhpc_test",
    "loginshell": "/bin/bash",
    "uidnumber": 500,
    "gidnumber": 500,
    "givenname": "Web",
    "sn": "Admin",
}
SHELL_CHOICES = ["/bin/bash", "/bin/tcsh", "/bin/zsh"]
PUBKEY_CHOICES = [
    "ssh-ed25519 AAAAC3NzaC1lZDI1NTE5AAAAIDWG37i3uTdnanD8SCY2UCUcuqYEszvb/eebyqfUHiRn foobar",
    "ecdsa-sha2-nistp256 AAAAE2VjZHNhLXNoYTItbmlzdHAyNTYAAAAIbmlzdHAyNTYAAABBBIL0GJOPT94cHG/vbgBtCdTxJNY3BTBxmKNJqb2cMdootEJr5Yt/mWoPDxW1FOazv+nhCwT5wfz/rCayAv6wptU= foobar",
    "ssh-rsa AAAAB3NzaC1yc2EAAAADAQABAAABgQDBnvfnEmpXoyEygsTNdi4fhKiLAY9aUQ1ktMIY1GkegKkHds73wMlUsbf24I3OtV27gVIPTl8Q8VB9zfIC8cGR0lvF1XbcRXhumSM+efSICmgkFj5YlkBjfePH4Wgy4zU5I4UXo1fDsb6REl2XD/OU74hU1j0vkXS04LsLK4V11KTf7nWfQpFmR+ratK0jShP/jtz0W+jFNpdEG8AtBFt5MQ6xmQL4zVGatNM0cvbH3bJGepz4+8EX7kyVU+1+lGjbx3EURA+OLjY3VfRMsNb4FnIr1nYNDz1Jwr0dv22RqW2+7I7xiO9/Hs6vqTpepCPtePDtjg9U6vl+2koQ6mlx0ghxWjOug/fePZXk09wW1ylkGH1z+pKDYHsHYNAmtdZ/rgyq+U+lo00fE7kIu1twZTJsf0MCPXw0NKGroJWEYfFCdt2dArfPpiIfZFyS6nj+8CoBKjIE2aVIINDTBH29iUmYL9ms1QXhjNEztc6dvYts6oLRlZbbmg6y7Hq5Iz0= foobar",
]
NUM_USERS = 10000
NUM_PIS = 100
NUM_ORGS = 10
# zfill: get the last number in the range from 0 to max, check how many digits of string it is
ID_ZFILL = len(str(range(NUM_USERS - 1, NUM_USERS)[-1]))
ORG_ZFILL = len(str(range(NUM_ORGS - 1, NUM_ORGS)[-1]))
# the user number range starts at this number and ends at NUM_USERS
NUM_RESERVED_USER_NUMBERS = 100


def user_num2id(user_num: int) -> int:
    return user_num + 1000000


def pi_num2gid(pi_num: int) -> int:
    return pi_num + 2000000


def org_num2gid(pi_num: int) -> int:
    return pi_num + 3000000


@beartype
def make_random_user(user_num: int, org: str) -> tuple[str, dict[str, object], dict[str, object]]:
    """
    returns uid, ldap posixaccount attributes, ldap posixgroup attributes
    does not generate pi group membership because this is used to make the PIs (chicken/egg problem)
    """
    # eppn must be {user}@{org}.edu: https://www.educause.edu/fidm/attributes
    # this form is also a valid email address, and 99% of the time it should be their email
    eppn = f"user{str(user_num).zfill(ID_ZFILL)}@{org}"
    uid = eppn.replace("@", "_").replace(".", "_")
    num_pubkeys = random.randint(0, 3)
    if num_pubkeys == 1:
        pubkey_or_pubkeys = random.choice(PUBKEY_CHOICES)
    else:
        pubkey_or_pubkeys = random.sample(PUBKEY_CHOICES, k=num_pubkeys)
    len_email_org = random.choice([4, 10, 15])
    email_org = "".join(random.sample(string.ascii_letters + string.digits, k=len_email_org))
    email_tld = "".join(random.sample(string.ascii_letters + string.digits, k=3))
    email = f"{uid}@{email_org}.{email_tld}"  # user_org_edu@asldkjasldkj.xxx
    return (
        uid,
        {
            "cn": uid,
            "uid": uid,
            "mail": email,
            "o": org,
            "uidnumber": user_num2id(user_num),
            "gidnumber": user_num2id(user_num),
            "givenname": f"Givenname{user_num}",
            "sn": f"Surname{user_num}",
            "homedirectory": f"/home/{uid}",
            "loginshell": random.choice(SHELL_CHOICES),
            "sshpublickey": pubkey_or_pubkeys,
        },
        # user group does not have memberuids, rely on user gidnumber instead
        {"cn": uid, "gidnumber": user_num2id(user_num)},
    )


def main():
    random.seed(1)

    org_group_membership = {
        k: [] for k in [f"org{str(x).zfill(ORG_ZFILL)}_edu" for x in range(NUM_ORGS)]
    }
    pi_group_membership = {"pi_web_admin_unityhpc_test": []}
    user_groups = []
    users = [WEB_ADMIN]

    pi_user_nums = random.sample(range(NUM_RESERVED_USER_NUMBERS, NUM_USERS), k=NUM_PIS)
    for user_num in range(NUM_RESERVED_USER_NUMBERS, NUM_USERS):
        org = random.choice(list(org_group_membership.keys()))
        uid, user_attributes, user_group_attributes = make_random_user(user_num, org)
        users.append(user_attributes)
        user_groups.append(user_group_attributes)
        org_group_membership.setdefault(org, []).append(uid)
        if user_num in pi_user_nums:
            pi_group_membership[f"pi_{uid}"] = []

    for attributes in users:
        uid = attributes["uid"]
        num_pis = random.randint(0, 3)
        for pi in random.sample(list(pi_group_membership.keys()), k=num_pis):
            pi_group_membership[pi].append(uid)

    with tempfile.NamedTemporaryFile(mode="w+", delete=False) as ldif_tempfile:
        with ldap3.Connection(server=None, client_strategy=ldap3.LDIF) as ldap_conn:
            ldap_conn.stream = ldif_tempfile
            for args in LDAP_EXTRA_ENTRIES:
                ldap_conn.add(*args)
            for i, (group_cn, member_uids) in enumerate(org_group_membership.items()):
                ldap_conn.add(
                    f"cn={group_cn},{ORG_GROUPS_OU_DN}",
                    GROUP_OBJECT_CLASSES,
                    {"cn": group_cn, "memberuid": member_uids, "gidnumber": org_num2gid(i)},
                )
            for i, (group_cn, member_uids) in enumerate(pi_group_membership.items()):
                ldap_conn.add(
                    f"cn={group_cn},{PI_GROUPS_OU_DN}",
                    GROUP_OBJECT_CLASSES,
                    {"cn": group_cn, "memberuid": member_uids, "gidnumber": pi_num2gid(i)},
                )
            for attributes in users:
                cn = attributes["cn"]
                ldap_conn.add(f"cn={cn},{USERS_OU_DN}", USER_OBJECT_CLASSES, attributes)
            for attributes in user_groups:
                cn = attributes["cn"]
                ldap_conn.add(f"cn={cn},{USER_GROUPS_OU_DN}", GROUP_OBJECT_CLASSES, attributes)
    print(f"export LDAP_BOOTSTRAP_LDIF_PATH={ldif_tempfile.name}")

    with tempfile.NamedTemporaryFile(mode="w+", delete=False) as htpasswd_tempfile:
        for user in users:
            # password is "password"
            htpasswd_tempfile.write(f"{user["mail"]}:$apr1$Rgrex74Z$rgJx6sCnGQN9UVMmhVG2R1\n")
    print(f"export HTPASSWD_PATH={htpasswd_tempfile.name}")


if __name__ == "__main__":
    main()
