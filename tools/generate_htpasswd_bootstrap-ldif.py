"""
generates an htpasswd and a bootstrap.ldif for the web and identity docker containers, respectively
outputs shell instructions to set env vars to use tempfiles during docker build
remember to clean up tempfiles after use
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
            "posixGroup",
            "top",
        ],
        {
            "cn": "admin",
            "userPassword": "{SSHA}d6WBSm5wjlNpMwil1KQY+Uo4o/vc6PnR",  # password is "password"
            "description": "for LDAP server administration purposes only",
        },
    ],
    [f"ou=groups,{ROOT_DN}", OU_OBJECT_CLASSES, {"ou": "groups"}],
    [f"ou=org_groups,{ROOT_DN}", OU_OBJECT_CLASSES, {"ou": "org_groups"}],
    [f"ou=pi_groups,{ROOT_DN}", OU_OBJECT_CLASSES, {"ou": "pi_groups"}],
    [f"ou=users,{ROOT_DN}", OU_OBJECT_CLASSES, {"ou": "users"}],
]

SHELL_CHOICES = ["/bin/bash", "/bin/tcsh", "/bin/zsh"]
PUBKEY_CHOICES = [
    "ssh-ed25519 AAAAC3NzaC1lZDI1NTE5AAAAIDWG37i3uTdnanD8SCY2UCUcuqYEszvb/eebyqfUHiRn foobar",
    "ssh-ed25519 e31ZTITy1nCE5AH3v3ACINzAfqAAANzD/iYbSDYsCnAudUuGU7Eecl2WnbiqR8AUCaaD foobar",
    "ssh-ed25519 ZineuqDqInAAI1fAu3cUC3b3DezsNTAnSUlCW/YA7z1aU2HC5RyYAvdN8iCaEbADGAET foobar",
]
NUM_USERS = 100  # TODO increase to 4000
ID_ZFILL = len(str(NUM_USERS))
NUM_PIS = 10


def user_num2id(user_num: int) -> int:
    return user_num + 10000


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
        {"cn": uid, "gidnumber": user_num2id(user_num)},
    )


def main():
    random.seed(1)

    org_group_membership = {k: [] for k in [f"org{x}" for x in [1, 2, 3]]}
    pi_group_membership = {}
    user_groups = {}
    users = {}

    pi_user_nums = random.sample(range(0, NUM_USERS), k=NUM_PIS)
    for user_num in range(NUM_USERS):
        org = random.choice(list(org_group_membership.keys()))
        uid, user_attributes, user_group_attributes = make_random_user(user_num, org)
        users[uid] = user_attributes
        user_groups[uid] = user_group_attributes
        org_group_membership.setdefault(org, []).append(uid)
        if user_num in pi_user_nums:
            pi_group_membership[uid] = []

    for uid in users:
        num_pis = random.randint(0, 3)
        for pi in random.sample(list(pi_group_membership.keys()), k=num_pis):
            pi_group_membership[pi].append(uid)

    with tempfile.NamedTemporaryFile(mode="w+", delete=False) as ldif_tempfile:
        with ldap3.Connection(server=None, client_strategy=ldap3.LDIF) as ldap_conn:
            ldap_conn.stream = ldif_tempfile
            for args in LDAP_EXTRA_ENTRIES:
                ldap_conn.add(*args)
            for group_cn, member_uids in org_group_membership.items():
                ldap_conn.add(
                    f"cn={group_cn},{ORG_GROUPS_OU_DN}",
                    GROUP_OBJECT_CLASSES,
                    {"cn": group_cn, "memberuid": member_uids},
                )
            for group_cn, member_uids in pi_group_membership.items():
                ldap_conn.add(
                    f"cn={group_cn},{PI_GROUPS_OU_DN}",
                    GROUP_OBJECT_CLASSES,
                    {"cn": group_cn, "memberuid": member_uids},
                )
            for uid, attributes in users.items():
                ldap_conn.add(f"cn={uid},{USERS_OU_DN}", USER_OBJECT_CLASSES, attributes)
            for uid, attributes in user_groups.items():
                ldap_conn.add(f"cn={uid},{USER_GROUPS_OU_DN}", GROUP_OBJECT_CLASSES, attributes)
        print(f"export LDAP_BOOTSTRAP_LDIF_PATH={ldif_tempfile.name}")


if __name__ == "__main__":
    main()
