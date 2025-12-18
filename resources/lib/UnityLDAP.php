<?php

namespace UnityWebPortal\lib;

use UnityWebPortal\lib\exceptions\EntryNotFoundException;
use PHPOpenLDAPer\LDAPConn;
use PHPOpenLDAPer\LDAPEntry;
use UnityWebPortal\lib\PosixGroup;

enum UserFlag: string
{
    case ADMIN = "admin";
    case GHOST = "ghost";
    case IDLELOCKED = "idlelocked";
    case LOCKED = "locked";
    case QUALIFIED = "qualified";
}

/**
 * An LDAP connection class which extends LDAPConn tailored for the UnityHPC Platform
 */
class UnityLDAP extends LDAPConn
{
    private const string RDN = "cn"; // The defauls RDN for LDAP entries is set to "common name"

    public const array POSIX_ACCOUNT_CLASS = [
        "inetorgperson",
        "posixAccount",
        "top",
        "ldapPublicKey",
    ];

    public const array POSIX_GROUP_CLASS = ["posixGroup", "top"];

    private string $custom_mappings_path =
        __DIR__ . "/../../" . CONFIG["ldap"]["custom_user_mappings_dir"];
    private string $def_user_shell = CONFIG["ldap"]["def_user_shell"];
    private int $offset_UIDGID = CONFIG["ldap"]["offset_UIDGID"];
    private int $offset_PIGID = CONFIG["ldap"]["offset_PIGID"];
    private int $offset_ORGGID = CONFIG["ldap"]["offset_ORGGID"];

    // Instance vars for various ldapEntry objects
    private LDAPEntry $baseOU;
    private LDAPEntry $userOU;
    private LDAPEntry $groupOU;
    private LDAPEntry $pi_groupOU;
    private LDAPEntry $org_groupOU;

    public array $userFlagGroups;

    public function __construct()
    {
        parent::__construct(CONFIG["ldap"]["uri"], CONFIG["ldap"]["user"], CONFIG["ldap"]["pass"]);
        $this->baseOU = $this->getEntry(CONFIG["ldap"]["basedn"]);
        $this->userOU = $this->getEntry(CONFIG["ldap"]["user_ou"]);
        $this->groupOU = $this->getEntry(CONFIG["ldap"]["group_ou"]);
        $this->pi_groupOU = $this->getEntry(CONFIG["ldap"]["pigroup_ou"]);
        $this->org_groupOU = $this->getEntry(CONFIG["ldap"]["orggroup_ou"]);
        $this->userFlagGroups = [];
        foreach (UserFlag::cases() as $flag) {
            $dn = CONFIG["ldap"]["user_flag_groups"][$flag->value];
            $this->userFlagGroups[$flag->value] = new PosixGroup(new LDAPEntry($this->conn, $dn));
        }
    }

    public function getUserOU(): LDAPEntry
    {
        return $this->userOU;
    }

    public function getGroupOU(): LDAPEntry
    {
        return $this->groupOU;
    }

    public function getPIGroupOU(): LDAPEntry
    {
        return $this->pi_groupOU;
    }

    public function getOrgGroupOU(): LDAPEntry
    {
        return $this->org_groupOU;
    }

    public function getDefUserShell(): string
    {
        return $this->def_user_shell;
    }

    public function getNextUIDGIDNumber(string $uid): int
    {
        $IDNumsInUse = array_merge($this->getAllUIDNumbersInUse(), $this->getAllGIDNumbersInUse());
        $customIDMappings = $this->getCustomIDMappings();
        $customMappedID = $customIDMappings[$uid] ?? null;
        if (!is_null($customMappedID) && !in_array($customMappedID, $IDNumsInUse)) {
            return $customMappedID;
        }
        if (!is_null($customMappedID) && in_array($customMappedID, $IDNumsInUse)) {
            UnityHTTPD::errorLog(
                "warning",
                "user '$uid' has a custom mapped IDNumber $customMappedID but it's already in use!",
            );
        }
        return $this->getNextIDNumber(
            $this->offset_UIDGID,
            array_merge($IDNumsInUse, array_values($this->getCustomIDMappings())),
        );
    }

    public function getNextPIGIDNumber(): int
    {
        return $this->getNextIDNumber(
            $this->offset_PIGID,
            array_merge($this->getAllGIDNumbersInUse(), array_values($this->getCustomIDMappings())),
        );
    }

    public function getNextOrgGIDNumber(): int
    {
        return $this->getNextIDNumber(
            $this->offset_ORGGID,
            array_merge($this->getAllGIDNumbersInUse(), array_values($this->getCustomIDMappings())),
        );
    }

    private function isIDNumberForbidden($id): bool
    {
        // 0-99 are probably going to be used for local system accounts instead of LDAP accounts
        // 100-999, 60000-64999 are reserved for debian packages
        return $id <= 999 || ($id >= 60000 && $id <= 64999);
    }

    private function getNextIDNumber($start, $IDsToSkip): int
    {
        $new_id = $start;
        while ($this->isIDNumberForbidden($new_id) || in_array($new_id, $IDsToSkip)) {
            $new_id++;
        }
        return $new_id;
    }

    private function getCustomIDMappings(): array
    {
        $output = [];
        $dir = new \DirectoryIterator($this->custom_mappings_path);
        foreach ($dir as $fileinfo) {
            $filename = $fileinfo->getFilename();
            if ($fileinfo->isDot() || $filename == "README.md") {
                continue;
            }
            if ($fileinfo->getExtension() == "csv") {
                $handle = fopen($fileinfo->getPathname(), "r");
                while (($row = fgetcsv($handle, null, ",")) !== false) {
                    array_push($output, $row);
                }
            } else {
                UnityHTTPD::errorLog(
                    "warning",
                    "custom ID mapping file '$filename' ignored, extension != .csv",
                );
            }
        }
        $output_map = [];
        foreach ($output as [$uid, $uidNumber_str]) {
            $output_map[$uid] = str2int($uidNumber_str);
        }
        return $output_map;
    }

    private function getAllUIDNumbersInUse(): array
    {
        // use baseOU for awareness of externally managed entries
        return array_map(
            fn($x) => $x["uidnumber"][0],
            $this->baseOU->getChildrenArrayStrict(
                ["uidNumber"],
                true,
                "(objectClass=posixAccount)",
            ),
        );
    }

    private function getAllGIDNumbersInUse(): array
    {
        // use baseOU for awareness of externally managed entries
        return array_map(
            fn($x) => $x["gidnumber"][0],
            $this->baseOU->getChildrenArrayStrict(["gidNumber"], true, "(objectClass=posixGroup)"),
        );
    }

    public function getQualifiedUsersAttributes(
        array $attributes,
        array $default_values = [],
    ): array {
        $include_uids = $this->userFlagGroups[UserFlag::QUALIFIED]->getMemberUIDs();
        $user_attributes = $this->baseOU->getChildrenArrayStrict(
            $attributes,
            true, // recursive
            "(objectClass=posixAccount)",
            $default_values,
        );
        foreach ($user_attributes as $i => $attributes) {
            if (!in_array($attributes["uid"][0], $include_uids)) {
                unset($user_attributes[$i]);
            }
        }
        return array_values($user_attributes); // reindex
    }

    public function getAllPIGroups(
        UnitySQL $UnitySQL,
        UnityMailer $UnityMailer,
        UnityWebhook $UnityWebhook,
    ) {
        $out = [];

        $pi_groups = $this->pi_groupOU->getChildren(true);

        foreach ($pi_groups as $pi_group) {
            array_push(
                $out,
                new UnityGroup(
                    $pi_group->getAttribute("cn")[0],
                    $this,
                    $UnitySQL,
                    $UnityMailer,
                    $UnityWebhook,
                ),
            );
        }

        return $out;
    }

    public function getAllPIGroupsAttributes(array $attributes, array $default_values = []): array
    {
        return $this->pi_groupOU->getChildrenArrayStrict(
            $attributes,
            false, // non-recursive
            "objectClass=posixGroup",
            $default_values,
        );
    }

    public function getPIGroupGIDsWithMemberUID(string $uid): array
    {
        return array_map(
            fn($x) => $x["cn"][0],
            $this->pi_groupOU->getChildrenArrayStrict(
                ["cn"],
                false,
                "(memberuid=" . ldap_escape($uid, LDAP_ESCAPE_FILTER) . ")",
            ),
        );
    }

    public function getAllPIGroupOwnerAttributes(
        array $attributes,
        array $default_values = [],
    ): array {
        // get the PI groups, filter for just the GIDs, then map the GIDs to owner UIDs
        $owner_uids = array_map(
            fn($x) => UnityGroup::GID2OwnerUID($x),
            array_map(fn($x) => $x["cn"][0], $this->pi_groupOU->getChildrenArrayStrict(["cn"])),
        );
        $owner_attributes = $this->getQualifiedUsersAttributes($attributes, $default_values);
        foreach ($owner_attributes as $i => $attributes) {
            if (!in_array($attributes["uid"][0], $owner_uids)) {
                unset($owner_attributes[$i]);
            }
        }
        $owner_attributes = array_values($owner_attributes); // reindex
        $owners_not_found = array_diff(
            $owner_uids,
            array_map(fn($x) => $x["uid"][0], $owner_attributes),
        );
        if (count($owners_not_found) > 0) {
            UnityHTTPD::errorLog(
                "warning",
                "PI group owners not found: " . \jsonEncode($owners_not_found) . "\n",
            );
        }
        return $owner_attributes;
    }

    /**
     * Returns an associative array where keys are UIDs and values are arrays of PI GIDs
     */
    public function getQualifiedUID2PIGIDs(): array
    {
        // initialize output so each UID is a key with an empty array as its value
        $uids = $this->userFlagGroups[UserFlag::QUALIFIED]->getMemberUIDs();
        $uid2pigids = array_combine($uids, array_fill(0, count($uids), []));
        // for each PI group, append that GID to the member list for each of its member UIDs
        foreach (
            $this->getAllPIGroupsAttributes(
                ["cn", "memberuid"],
                default_values: ["memberuid" => []],
            )
            as $array
        ) {
            $gid = $array["cn"][0];
            foreach ($array["memberuid"] as $uid) {
                if (array_key_exists($uid, $uid2pigids)) {
                    array_push($uid2pigids[$uid], $gid);
                } else {
                    UnityHTTPD::errorLog(
                        "warning",
                        "user '$uid' is a member of a PI group but is not a qualified user!",
                    );
                }
            }
        }
        return $uid2pigids;
    }

    public function getAllOrgGroups($UnitySQL, $UnityMailer, $UnityWebhook): array
    {
        $out = [];

        $org_groups = $this->org_groupOU->getChildren(true);

        foreach ($org_groups as $org_group) {
            array_push(
                $out,
                new UnityOrg(
                    $org_group->getAttribute("cn")[0],
                    $this,
                    $UnitySQL,
                    $UnityMailer,
                    $UnityWebhook,
                ),
            );
        }

        return $out;
    }

    public function getAllOrgGroupsAttributes(array $attributes, array $default_values = []): array
    {
        return $this->org_groupOU->getChildrenArrayStrict(
            $attributes,
            default_values: $default_values,
        );
    }

    public function getUserEntry(string $uid): LDAPEntry
    {
        $uid = ldap_escape($uid, "", LDAP_ESCAPE_DN);
        return $this->getEntry(UnityLDAP::RDN . "=$uid," . CONFIG["ldap"]["user_ou"]);
    }

    public function getGroupEntry(string $gid): LDAPEntry
    {
        $gid = ldap_escape($gid, "", LDAP_ESCAPE_DN);
        return $this->getEntry(UnityLDAP::RDN . "=$gid," . CONFIG["ldap"]["group_ou"]);
    }

    public function getPIGroupEntry(string $gid): LDAPEntry
    {
        $gid = ldap_escape($gid, "", LDAP_ESCAPE_DN);
        return $this->getEntry(UnityLDAP::RDN . "=$gid," . CONFIG["ldap"]["pigroup_ou"]);
    }

    public function getOrgGroupEntry(string $gid): LDAPEntry
    {
        $gid = ldap_escape($gid, "", LDAP_ESCAPE_DN);
        return $this->getEntry(UnityLDAP::RDN . "=$gid," . CONFIG["ldap"]["orggroup_ou"]);
    }

    /**
     * @throws \UnityWebPortal\lib\exceptions\EntryNotFoundException
     */
    public function getUidFromEmail(string $email): LDAPEntry
    {
        $email = ldap_escape($email, "", LDAP_ESCAPE_FILTER);
        $cn = $this->search("mail=$email", CONFIG["ldap"]["user_ou"], ["cn"]);
        if ($cn && count($cn) == 1) {
            return $cn[0];
        }
        throw new exceptions\EntryNotFoundException($email);
    }

    /**
     * returns an array with each UID as an array key
     * @throws \UnityWebPortal\lib\exceptions\EntryNotFoundException
     */
    public function getUsersAttributes(
        array $uids,
        array $attributes,
        array $default_values = [],
    ): array {
        if (count($uids) === 0) {
            return [];
        }
        $attributes = array_map("strtolower", $attributes);
        if (in_array("uid", $attributes)) {
            $asked_for_uid_attribute = true;
        } else {
            $asked_for_uid_attribute = false;
            array_push($attributes, "uid");
        }
        $uids_escaped = array_map(fn($x) => ldap_escape($x, "", LDAP_ESCAPE_FILTER), $uids);
        $filter =
            "(&(objectClass=posixAccount)(|" .
            implode("", array_map(fn($x) => "(uid=$x)", $uids_escaped)) .
            "))";
        $entries = $this->baseOU->getChildrenArrayStrict(
            $attributes,
            true,
            $filter,
            $default_values,
        );
        $output = [];
        foreach ($entries as $entry) {
            $uid = $entry["uid"][0];
            if (!$asked_for_uid_attribute) {
                unset($entry["uid"]);
            }
            $output[$uid] = $entry;
        }
        $uids_not_found = array_diff($uids, array_keys($output));
        if (count($uids_not_found) > 0) {
            throw new EntryNotFoundException(jsonEncode($uids_not_found));
        }
        ksort($output);
        return $output;
    }
}
