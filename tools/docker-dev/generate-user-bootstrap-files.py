"""
generates bootstrap files for the web, identity, sql docker containers
outputs shell instructions to set LDAP_BOOTSTRAP_LDIF_PATH HTPASSWD_PATH SQL_BOOTSTRAP_USERS_PATH
"""

import sys
import json
import string
import random
import tempfile

NUM_USERS = 10000
NUM_PIS = 1000  # should be >= (the number of PIs created by test cases + MAX_PIS_MEMBER_OF)
NUM_ORGS = 25
# zfill: get the last number in the range from 0 to max, check how many digits of string it is
ID_ZFILL = len(str(range(NUM_USERS - 1, NUM_USERS)[-1]))
ORG_ZFILL = len(str(range(NUM_ORGS - 1, NUM_ORGS)[-1]))
# the user number range starts at this number and ends at NUM_USERS
NUM_RESERVED_USER_NUMBERS = 100

ROOT_DN = "dc=unityhpc,dc=test"
USERS_OU_DN = f"ou=users,{ROOT_DN}"
USER_GROUPS_OU_DN = f"ou=groups,{ROOT_DN}"
PI_GROUPS_OU_DN = f"ou=pi_groups,{ROOT_DN}"
ORG_GROUPS_OU_DN = f"ou=org_groups,{ROOT_DN}"
USER_OBJECT_CLASSES = ["inetOrgPerson", "posixAccount", "top", "ldapPublicKey"]
GROUP_OBJECT_CLASSES = ["posixGroup", "top"]
OU_OBJECT_CLASSES = ["organizationalUnit", "top"]
LDAP_EXTRA_ENTRIES = [
    {
        "dn": ROOT_DN,
        "objectClass": ["organization", "dcObject", "top"],
        "structuralObjectClass": "organization",
        "o": "unityhpc",
    },
    {
        "dn": f"cn=admin,{ROOT_DN}",
        "cn": "admin",
        "userPassword": "{SSHA}d6WBSm5wjlNpMwil1KQY+Uo4o/vc6PnR",  # password is "password"
        "description": "for LDAP server administration purposes only",
        "objectClass": [
            "simpleSecurityObject",
            "organizationalRole",
            "top",
        ],
    },
    {"dn": USER_GROUPS_OU_DN, "objectClass": OU_OBJECT_CLASSES, "ou": "groups"},
    {"dn": ORG_GROUPS_OU_DN, "objectClass": OU_OBJECT_CLASSES, "ou": "org_groups"},
    {"dn": PI_GROUPS_OU_DN, "objectClass": OU_OBJECT_CLASSES, "ou": "pi_groups"},
    {"dn": USERS_OU_DN, "objectClass": OU_OBJECT_CLASSES, "ou": "users"},
]
WEB_ADMIN_USER = {
    "dn": f"cn=web_admin_unityhpc_test,{USERS_OU_DN}",
    "objectclass": USER_OBJECT_CLASSES,
    "cn": "web_admin_unityhpc_test",
    "uid": "web_admin_unityhpc_test",
    "mail": "web_admin@unityhpc.test",
    "o": "unityhpc_test",
    "homedirectory": "/home/web_admin_unityhpc_test",
    "loginshell": "/bin/bash",
    "uidnumber": 501,
    "gidnumber": 501,
    "givenname": "Web",
    "sn": "Admin",
}
WEB_ADMIN_USER_GROUP = {
    "dn": f"cn=web_admin_unityhpc_test,{USER_GROUPS_OU_DN}",
    "objectclass": GROUP_OBJECT_CLASSES,
    "cn": "web_admin_unityhpc_test",
    "gidnumber": 501,
}
WEB_ADMINS_GROUP_GID = 500
LOCKED_GROUP_GID = 502
SHELL_CHOICES = ["/bin/bash", "/bin/zsh", "foobar"]
MAX_PUBKEYS = 100  # TODO integrate with UnityConfig
MAX_PIS_MEMBER_OF = 100  # TODO integrate with UnityConfig
SQL_ACCT_DEL_REQ_TABLE = "account_deletion_requests"  # FIXME integrate somehow
SQL_REQUESTS_TABLE = "requests"  # FIXME integrate somehow
SQL_PI_PROMOTION = "admin"  # FIXME integrate UnitySQL::REQUEST_PI_PROMOTION

with open("example-ssh-public-keys.json", "r", encoding="utf8") as public_keys_file:
    pubkeys = json.load(public_keys_file)
    PUBKEY_CHOICES = pubkeys["small_keys"] + pubkeys["big_keys"]
    assert len(PUBKEY_CHOICES) >= MAX_PUBKEYS, "not enough pubkeys in public-keys.json!"


def user_num2id(user_num: int) -> int:
    assert user_num < 1000000
    return user_num + 1000000


def pi_num2gid(pi_num: int) -> int:
    assert pi_num < 1000000
    return pi_num + 2000000


def org_num2gid(org_num: int) -> int:
    assert org_num < 1000000
    return org_num + 3000000


def make_random_user(
    user_num: int, org: str, num_pubkeys: int
) -> tuple[str, dict[str, object], dict[str, object]]:
    """
    returns uid, ldap posixaccount attributes, ldap posixgroup attributes
    does not generate pi group membership because this is used to make the PIs (chicken/egg problem)
    """
    # eppn must be {user}@{org}.edu: https://www.educause.edu/fidm/attributes
    # this form is also a valid email address, and 99% of the time it should be their email
    eppn = f"user{str(user_num).zfill(ID_ZFILL)}@{org}.edu"
    uid = eppn.replace("@", "_").replace(".", "_")
    if num_pubkeys == 1:
        pubkey_or_pubkeys = random.choice(PUBKEY_CHOICES)
    else:
        pubkey_or_pubkeys = random.sample(PUBKEY_CHOICES, k=num_pubkeys)
    uidnumber_gidnumber = user_num2id(user_num)
    return (
        uid,
        {
            "dn": f"cn={uid},{USERS_OU_DN}",
            "objectclass": USER_OBJECT_CLASSES,
            "cn": uid,
            "uid": uid,
            "mail": eppn,
            "o": org,
            "uidnumber": uidnumber_gidnumber,
            "gidnumber": uidnumber_gidnumber,
            "givenname": f"Givenname{user_num}",
            "sn": f"Surname{user_num}",
            "homedirectory": f"/home/{uid}",
            "loginshell": random.choice(SHELL_CHOICES),
            "sshpublickey": pubkey_or_pubkeys,
        },
        # user group does not have memberuids, rely on user gidnumber instead
        {
            "cn": uid,
            "gidnumber": uidnumber_gidnumber,
            "dn": f"cn={uid},{USER_GROUPS_OU_DN}",
            "objectclass": GROUP_OBJECT_CLASSES,
        },
    )


def dict2ldif(x: dict) -> str:
    output = ""
    for k, v in x.items():
        if isinstance(v, (list, set)):
            if len(v) == 0:
                continue  # no empty values allowed
            for e in v:
                output += f"{k}: {e}\n"  # lists are represented as duplicate keys
        else:
            output += f"{k}: {v}\n"
    output += "\n"
    return output


def main():
    random.seed(1)

    org_group_membership = {
        k: set() for k in [f"org{str(x).zfill(ORG_ZFILL)}" for x in range(NUM_ORGS)]
    }
    pi_group_membership = {"pi_web_admin_unityhpc_test": set()}
    user_pi_group_membership = {}
    user_groups = [WEB_ADMIN_USER_GROUP]
    users = [WEB_ADMIN_USER]
    filler_user_names = []
    filler_pi_names = []
    users_requested_deletion = []
    users_requested_pi_promotion = []
    locked_users = []
    web_admins_group_members = [WEB_ADMIN_USER["cn"]]
    users_need_pis = {}
    pis_need_users = {}
    ALL_FILLER_USERS = -1
    pis_need_all_filler_users = []
    users_num_pi_requests = {}

    # test case users
    # start counting at the end of the reserved user number range
    cur_user_num = NUM_RESERVED_USER_NUMBERS
    for is_admin in [True, False]:
        for has_requested_deletion in [True, False]:
            for has_requested_pi_promotion in [True, False]:
                for is_locked in [True, False]:
                    for is_pi in [True, False]:
                        for num_pi_group_members in [0, 1, ALL_FILLER_USERS]:
                            for num_pubkeys in [0, 1, MAX_PUBKEYS]:
                                for num_pi_group_member_of in [0, 1, MAX_PIS_MEMBER_OF]:
                                    for num_pi_group_requests in [0, 1, MAX_PIS_MEMBER_OF]:
                                        # PIs are not allowed to request account deletion
                                        if has_requested_deletion and is_pi:
                                            continue
                                        if not is_pi and num_pi_group_members > 0:
                                            continue
                                        if is_pi and has_requested_pi_promotion:
                                            continue
                                        # skipped just to reduce number of test cases
                                        if has_requested_deletion and is_admin:
                                            continue
                                        # skipped just to reduce number of test cases
                                        if is_locked and is_admin:
                                            continue
                                        org = random.choice(list(org_group_membership.keys()))
                                        uid, user_attributes, user_group_attributes = (
                                            make_random_user(cur_user_num, org, num_pubkeys)
                                        )
                                        org_group_membership[org].add(uid)
                                        users.append(user_attributes)
                                        user_pi_group_membership[uid] = set()
                                        user_groups.append(user_group_attributes)
                                        if is_admin:
                                            web_admins_group_members.append(uid)
                                        if has_requested_deletion:
                                            users_requested_deletion.append(uid)
                                        if has_requested_pi_promotion:
                                            users_requested_pi_promotion.append(uid)
                                        if is_locked:
                                            locked_users.append(uid)
                                        if is_pi:
                                            pi_group_membership[f"pi_{uid}"] = set()
                                            if num_pi_group_members == ALL_FILLER_USERS:
                                                pis_need_all_filler_users.append(f"pi_{uid}")
                                            elif num_pi_group_members > 0:
                                                pis_need_users[f"pi_{uid}"] = num_pi_group_members
                                        if num_pi_group_member_of > 0:
                                            users_need_pis[uid] = num_pi_group_member_of
                                        # reduce number of PI requests to stay under limit
                                        if num_pi_group_requests == MAX_PIS_MEMBER_OF:
                                            if is_pi:
                                                num_pi_group_requests -= 1
                                            num_pi_group_requests -= num_pi_group_member_of
                                        users_num_pi_requests[uid] = num_pi_group_requests
                                        cur_user_num += 1

    print(
        f"test cases made {cur_user_num} users and {len(pi_group_membership)} PIs", file=sys.stderr
    )

    assert (
        len(pis_need_all_filler_users) < MAX_PIS_MEMBER_OF
    ), f"no user can be a member of more than {MAX_PIS_MEMBER_OF} PIs, but there are {len(pis_need_all_filler_users)} test case PIs which need to have all filler users as members! this would exceed MAX_PIS_MEMBER_OF for all filler users."

    # after covering all cases, generate random users to meet NUM_USERS, and make some of them PIs
    # to meet NUM_PIS
    assert (
        NUM_USERS >= cur_user_num
    ), f"test cases have made {cur_user_num} users, which already exceeds  NUM_USERS=={NUM_USERS}!"
    assert NUM_PIS >= len(
        pi_group_membership
    ), f"test cases have made {len(pi_group_membership)} PIs, which already exceeds NUM_PIS=={NUM_PIS}!"
    filler_user_nums = range(cur_user_num, NUM_USERS)
    filler_pi_user_nums = random.sample(filler_user_nums, k=(NUM_PIS - len(pi_group_membership)))

    print(
        f"adding in {len(filler_user_nums)} filler users, {len(filler_pi_user_nums)} of which are filler PIs",
        file=sys.stderr,
    )
    for user_num in filler_user_nums:
        org = random.choice(list(org_group_membership.keys()))
        num_pubkeys = random.randint(0, 3)
        uid, user_attributes, user_group_attributes = make_random_user(user_num, org, num_pubkeys)
        users.append(user_attributes)
        user_pi_group_membership[uid] = set()
        user_groups.append(user_group_attributes)
        org_group_membership[org].add(uid)
        if user_num in filler_pi_user_nums:
            pi_group_membership[f"pi_{uid}"] = set()
            filler_pi_names.append(f"pi_{uid}")
        filler_user_names.append(user_attributes["cn"])

    # use filler users and filler PIs to meet num_pi_group_members, num_pi_group_member_of,
    # and users_num_pi_requests
    for pi, num_needed in pis_need_users.items():
        assert (
            len(filler_user_names) >= num_needed
        ), f"there aren't enough filler users ({len(filler_user_names)}) to meet num_pi_group_members ({num_needed})!"
        pi_group_membership[pi] = set(random.sample(filler_user_names, k=num_needed))
        for uid in pi_group_membership[pi]:
            user_pi_group_membership[uid].add(pi)
    for uid, num_needed in users_need_pis.items():
        assert (
            len(filler_pi_names) >= num_needed
        ), f"there aren't enough filler PIs ({len(filler_pi_names)}) to meet num_pi_group_member_of ({num_needed})!"
        for selected_pi in random.sample(filler_pi_names, k=num_needed):
            pi_group_membership[selected_pi].add(uid)
            user_pi_group_membership[uid].add(selected_pi)
    for pi in pis_need_all_filler_users:
        pi_group_membership[pi] |= set(filler_user_names)
        for uid in filler_user_names:
            user_pi_group_membership[uid].add(pi)

    with tempfile.NamedTemporaryFile(mode="w+", delete=False) as sql_tempfile:
        sql_tempfile.write(
            "insert into %s (uid) values %s;\n"
            % (SQL_ACCT_DEL_REQ_TABLE, ",".join([f"('{x}')" for x in users_requested_deletion]))
        )

        sql_tempfile.write(
            "insert into %s (uid, request_for) values %s;\n"
            % (
                SQL_REQUESTS_TABLE,
                ",".join([f"('{x}', '{SQL_PI_PROMOTION}')" for x in users_requested_pi_promotion]),
            )
        )

        for uid, num_requests in users_num_pi_requests.items():
            if num_requests > 0:
                pis_not_already_member_of = set(filler_pi_names) - user_pi_group_membership[uid]
                assert (
                    len(pis_not_already_member_of) > num_requests
                ), "cannot make requests because there aren't enough filler PIs that I'm not already a member of!"
                pis_to_request = random.sample(list(pis_not_already_member_of), k=num_requests)
                for pi in pis_to_request:
                    sql_tempfile.write(
                        f"insert into {SQL_REQUESTS_TABLE} (uid, request_for) values ('{uid}', '{pi}');\n"
                    )
    print(f"export SQL_BOOTSTRAP_USERS_PATH={sql_tempfile.name}")

    with tempfile.NamedTemporaryFile(mode="w+", delete=False) as ldif_tempfile:
        for entry in LDAP_EXTRA_ENTRIES:
            ldif_tempfile.write(dict2ldif(entry))
        ldif_tempfile.write(
            dict2ldif(
                {
                    "dn": f"cn=web_admins,{ROOT_DN}",
                    "objectclass": GROUP_OBJECT_CLASSES,
                    "cn": "web_admins",
                    "memberuid": web_admins_group_members,
                    "gidnumber": WEB_ADMINS_GROUP_GID,
                },
            )
        )
        ldif_tempfile.write(
            dict2ldif(
                {
                    "dn": f"cn=locked,{ROOT_DN}",
                    "objectclass": GROUP_OBJECT_CLASSES,
                    "cn": "locked",
                    "memberuid": locked_users,
                    "gidnumber": LOCKED_GROUP_GID,
                },
            )
        )
        for i, (org, member_uids) in enumerate(org_group_membership.items()):
            ldif_tempfile.write(
                dict2ldif(
                    {
                        "dn": f"cn={org}_edu,{ORG_GROUPS_OU_DN}",
                        "objectclass": GROUP_OBJECT_CLASSES,
                        "cn": f"{org}_edu",
                        "memberuid": member_uids,
                        "gidnumber": org_num2gid(i),
                    },
                )
            )
        for i, (group_cn, member_uids) in enumerate(pi_group_membership.items()):
            ldif_tempfile.write(
                dict2ldif(
                    {
                        "dn": f"cn={group_cn},{PI_GROUPS_OU_DN}",
                        "objectclass": GROUP_OBJECT_CLASSES,
                        "cn": group_cn,
                        "memberuid": member_uids,
                        "gidnumber": pi_num2gid(i),
                    },
                )
            )
        for attributes in users:
            ldif_tempfile.write(dict2ldif(attributes))
        for attributes in user_groups:
            ldif_tempfile.write(dict2ldif(attributes))
    print(f"export LDAP_BOOTSTRAP_LDIF_PATH={ldif_tempfile.name}")

    with tempfile.NamedTemporaryFile(mode="w+", delete=False) as htpasswd_tempfile:
        for user in users:
            # password is "password"
            htpasswd_tempfile.write(f"{user["mail"]}:$apr1$Rgrex74Z$rgJx6sCnGQN9UVMmhVG2R1\n")
    print(f"export HTPASSWD_PATH={htpasswd_tempfile.name}")


if __name__ == "__main__":
    # import cProfile
    # cProfile.run("main()")
    main()
