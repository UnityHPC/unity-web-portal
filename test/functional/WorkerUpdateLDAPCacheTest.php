<?php

use PHPUnit\Framework\TestCase;
use UnityWebPortal\lib\UnityUser;
use UnityWebPortal\lib\UnityLDAP;

class WorkerUpdateLDAPCacheTest extends TestCase
{
    public function testFlushAndThenUpdate()
    {
        global $USER, $REDIS;
        switchUser(...getUserIsPIHasAtLeastOneMember());
        $initial_value = $USER->getPIGroup()->getGroupMemberUIDs(false);
        [$_, $output_lines] = executeWorker("update-ldap-cache.php", "-f");
        error_log(implode("\n", $output_lines));
        // switchUser(...getUserIsPIHasAtLeastOneMember()); // refresh $REDIS
        $after_flush_value = $REDIS->getCache($USER->getPIGroup()->gid, "members");
        $this->assertEqualsCanonicalizing([], $after_flush_value);
        [$_, $output_lines] = executeWorker("update-ldap-cache.php");
        error_log(implode("\n", $output_lines));
        // switchUser(...getUserIsPIHasAtLeastOneMember()); // refresh $REDIS
        $after_update_value = $REDIS->getCache($USER->getPIGroup()->gid, "members");
        $this->assertEqualsCanonicalizing($initial_value, $after_update_value);
    }
}
