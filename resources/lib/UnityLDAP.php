<?php

namespace UnityWebPortal\lib;

use RuntimeException;
use UnityWebPortal\lib\exceptions\ArrayKeyException;
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
 * @phpstan-type attributes array<string, array<int|string>>
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
    private int $offset_user_uidnumber = CONFIG["ldap"]["offset_user_uidnumber"];
    private int $offset_user_gidnumber = CONFIG["ldap"]["offset_user_gidnumber"];
    private int $offset_pi_gidnumber = CONFIG["ldap"]["offset_pi_gidnumber"];
    private int $offset_org_gidnumber = CONFIG["ldap"]["offset_org_gidnumber"];

    // Instance vars for various ldapEntry objects
    private LDAPEntry $baseOU;
    private LDAPEntry $userOU;
    private LDAPEntry $groupOU; /** @phpstan-ignore property.onlyWritten */
    private LDAPEntry $pi_groupOU;
    private LDAPEntry $org_groupOU; /** @phpstan-ignore property.onlyWritten */

    /** @var array<string, PosixGroup> */
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

    public function getDefUserShell(): string
    {
        return $this->def_user_shell;
    }

    public function getNextUserUIDNumber(string $uid): int
    {
        $IDNumsInUse = $this->getAllUIDNumbersInUse();
        $customIDMappings = $this->getCustomIDMappings();
        if (array_key_exists($uid, $customIDMappings)) {
            $customMappedID = $customIDMappings[$uid][0];
            if (in_array($customMappedID, $IDNumsInUse)) {
                UnityHTTPD::errorLog(
                    "warning",
                    sprintf(
                        "user '%s' has a custom mapped GIDNumber %s but it's already in use!",
                        $uid,
                        $customMappedID,
                    ),
                );
            } else {
                return $customMappedID;
            }
        }
        $customMappedUIDNumbers = array_map(fn($x) => $x[0], $this->getCustomIDMappings());
        $IDNumbersToSkip = array_merge($this->getAllUIDNumbersInUse(), $customMappedUIDNumbers);
        return $this->getNextIDNumber($this->offset_user_uidnumber, $IDNumbersToSkip);
    }

    public function getNextUserGIDNumber(string $uid): int
    {
        $IDNumsInUse = $this->getAllGIDNumbersInUse();
        $customIDMappings = $this->getCustomIDMappings();
        if (array_key_exists($uid, $customIDMappings)) {
            $customMappedID = $customIDMappings[$uid][1];
            if (in_array($customMappedID, $IDNumsInUse)) {
                UnityHTTPD::errorLog(
                    "warning",
                    sprintf(
                        "user '%s' has a custom mapped GIDNumber %s but it's already in use!",
                        $uid,
                        $customMappedID,
                    ),
                );
            } else {
                return $customMappedID;
            }
        }
        $customMappedGIDNumbers = array_map(fn($x) => $x[1], $this->getCustomIDMappings());
        $IDNumbersToSkip = array_merge($this->getAllGIDNumbersInUse(), $customMappedGIDNumbers);
        return $this->getNextIDNumber($this->offset_user_gidnumber, $IDNumbersToSkip);
    }

    public function getNextPIGIDNumber(): int
    {
        $customMappedGIDNumbers = array_map(fn($x) => $x[1], $this->getCustomIDMappings());
        $IDNumbersToSkip = array_merge($this->getAllGIDNumbersInUse(), $customMappedGIDNumbers);
        return $this->getNextIDNumber($this->offset_pi_gidnumber, $IDNumbersToSkip);
    }

    public function getNextOrgGIDNumber(): int
    {
        $customMappedGIDNumbers = array_map(fn($x) => $x[1], $this->getCustomIDMappings());
        $IDNumbersToSkip = array_merge($this->getAllGIDNumbersInUse(), $customMappedGIDNumbers);
        return $this->getNextIDNumber($this->offset_org_gidnumber, $IDNumbersToSkip);
    }

    private function isIDNumberForbidden(int $id): bool
    {
        // 0-99 are probably going to be used for local system accounts instead of LDAP accounts
        // 100-999, 60000-64999 are reserved for debian packages
        return $id <= 999 || ($id >= 60000 && $id <= 64999);
    }

    /** @param int[] $IDsToSkip */
    private function getNextIDNumber(int $start, array $IDsToSkip): int
    {
        $new_id = $start;
        while ($this->isIDNumberForbidden($new_id) || in_array($new_id, $IDsToSkip)) {
            $new_id++;
        }
        return $new_id;
    }

    /** @return array<string, int> */
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
                $handle = _fopen($fileinfo->getPathname(), "r");
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
        foreach ($output as [$uid, $uidNumber_str, $gidNumber_str]) {
            $output_map[$uid] = [digits2int($uidNumber_str), digits2int($gidNumber_str)];
        }
        return $output_map;
    }

    /** @return int[] */
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

    /** @return int[] */
    private function getAllGIDNumbersInUse(): array
    {
        // use baseOU for awareness of externally managed entries
        return array_map(
            fn($x) => $x["gidnumber"][0],
            $this->baseOU->getChildrenArrayStrict(["gidNumber"], true, "(objectClass=posixGroup)"),
        );
    }

    /**
     * @param string[] $attributes
     * @param attributes $default_values
     * @return attributes[]
     */
    public function getAllNativeUsersAttributes(
        array $attributes,
        array $default_values = [],
    ): array {
        return $this->userOU->getChildrenArrayStrict(
            $attributes,
            true, // recursive
            "(objectClass=posixAccount)",
            $default_values,
        );
    }

    /** @return UnityGroup[] */
    public function getAllPIGroups(
        UnitySQL $UnitySQL,
        UnityMailer $UnityMailer,
        UnityWebhook $UnityWebhook,
    ) {
        $out = [];
        $pi_groups_attributes = $this->pi_groupOU->getChildrenArrayStrict(
            attributes: ["cn"],
            recursive: false,
        );
        foreach ($pi_groups_attributes as $attributes) {
            array_push(
                $out,
                new UnityGroup($attributes["cn"][0], $this, $UnitySQL, $UnityMailer, $UnityWebhook),
            );
        }
        return $out;
    }

    /**
     * @param string[] $attributes
     * @param attributes $default_values
     * @return attributes[]
     */
    public function getAllPIGroupsAttributes(array $attributes, array $default_values = []): array
    {
        return $this->pi_groupOU->getChildrenArrayStrict(
            $attributes,
            false, // non-recursive
            "objectClass=posixGroup",
            $default_values,
        );
    }

    /** @return string[] */
    public function getPIGroupGIDsWithMemberUID(string $uid): array
    {
        return array_map(
            fn($x) => $x["cn"][0],
            $this->pi_groupOU->getChildrenArrayStrict(
                ["cn"],
                false,
                "(memberuid=" . ldap_escape($uid, flags: LDAP_ESCAPE_FILTER) . ")",
            ),
        );
    }

    /** @return string[] */
    public function getAllPIGroupOwnerUIDs(): array
    {
        return array_map(
            fn($x) => UnityGroup::GID2OwnerUID($x["cn"][0]),
            $this->pi_groupOU->getChildrenArrayStrict(["cn"]),
        );
    }

    /**
     * @param string[] $attributes
     * @param attributes $default_values
     * @return attributes[]
     */
    public function getPIGroupAttributesWithMemberUID(
        string $uid,
        array $attributes,
        array $default_values = [],
    ) {
        return $this->pi_groupOU->getChildrenArrayStrict(
            $attributes,
            recursive: false,
            filter: sprintf("(memberuid=%s)", ldap_escape($uid, "", LDAP_ESCAPE_FILTER)),
            default_values: $default_values,
        );
    }

    /**
     * Returns an associative array where keys are UIDs and values are arrays of PI GIDs
     * @return array<string, string[]>
     */
    public function getUID2PIGIDs(): array
    {
        $uid2pigids = [];
        // for each PI group, append that GID to the member list for each of its member UIDs
        $pi_groups_attributes = $this->getAllPIGroupsAttributes(
            ["cn", "memberuid"],
            default_values: ["memberuid" => []],
        );
        foreach ($pi_groups_attributes as $array) {
            $gid = (string) $array["cn"][0];
            foreach ($array["memberuid"] as $uid) {
                $uid = (string) $uid;
                if (!array_key_exists($uid, $uid2pigids)) {
                    $uid2pigids[$uid] = [];
                }
                array_push($uid2pigids[$uid], $gid);
            }
        }
        return $uid2pigids;
    }

    public function getUserEntry(string $uid): LDAPEntry
    {
        $uid = ldap_escape($uid, flags: LDAP_ESCAPE_DN);
        return $this->getEntry(UnityLDAP::RDN . "=$uid," . CONFIG["ldap"]["user_ou"]);
    }

    public function getGroupEntry(string $gid): LDAPEntry
    {
        $gid = ldap_escape($gid, flags: LDAP_ESCAPE_DN);
        return $this->getEntry(UnityLDAP::RDN . "=$gid," . CONFIG["ldap"]["group_ou"]);
    }

    public function getPIGroupEntry(string $gid): LDAPEntry
    {
        $gid = ldap_escape($gid, flags: LDAP_ESCAPE_DN);
        return $this->getEntry(UnityLDAP::RDN . "=$gid," . CONFIG["ldap"]["pigroup_ou"]);
    }

    public function getOrgGroupEntry(string $gid): LDAPEntry
    {
        $gid = ldap_escape($gid, flags: LDAP_ESCAPE_DN);
        return $this->getEntry(UnityLDAP::RDN . "=$gid," . CONFIG["ldap"]["orggroup_ou"]);
    }

    /**
     * returns an array with each UID as an array key
     * @param string[] $uids
     * @param string[] $attributes
     * @param attributes $default_values
     * @return attributes[]
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
        $uids_escaped = array_map(fn($x) => ldap_escape($x, flags: LDAP_ESCAPE_FILTER), $uids);
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
            throw new EntryNotFoundException(_json_encode($uids_not_found));
        }
        ksort($output);
        return $output;
    }
}
