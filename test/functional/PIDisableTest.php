<?php
use UnityWebPortal\lib\UserFlag;

class PIDisableTest extends UnityWebPortalTestCase
{
    public function testDisableGroupByAdmin()
    {
        global $USER, $LDAP;
        $this->switchUser("EmptyPIGroupOwner");
        $pi_group = $USER->getPIGroup();
        $memberuids_before = $pi_group->getMemberUIDs();
        $this->assertFalse($pi_group->getIsDisabled());
        $this->assertNotEmpty($pi_group->getMemberUIDs());
        $this->assertTrue($pi_group->getOwner()->getFlag(UserFlag::QUALIFIED));
        try {
            $this->switchUser("Admin");
            http_post(__DIR__ . "/../../webroot/admin/pi-mgmt.php", [
                "form_type" => "disable",
                "pi" => $pi_group->gid,
            ]);
            $this->assertFalse($pi_group->getOwner()->isPI());
            $this->assertTrue($pi_group->getIsDisabled());
            $this->assertEmpty($pi_group->getMemberUIDs());
            $this->assertFalse($pi_group->getOwner()->getFlag(UserFlag::QUALIFIED));
        } finally {
            $entry = $LDAP->getPIGroupEntry($pi_group->gid);
            $entry->setAttribute("memberuid", $memberuids_before);
            $entry->setAttribute("isDisabled", "FALSE");
            $pi_group->getOwner()->setFlag(UserFlag::QUALIFIED, true);
        }
    }

    public function testDisableGroupByOwner()
    {
        global $USER, $LDAP;
        $this->switchUser("EmptyPIGroupOwner");
        $pi_group = $USER->getPIGroup();
        $memberuids_before = $pi_group->getMemberUIDs();
        $this->assertFalse($pi_group->getIsDisabled());
        $this->assertNotEmpty($pi_group->getMemberUIDs());
        $this->assertTrue($pi_group->getOwner()->getFlag(UserFlag::QUALIFIED));
        try {
            http_post(__DIR__ . "/../../webroot/panel/pi.php", ["form_type" => "disable"]);
            $this->assertFalse($pi_group->getOwner()->isPI());
            $this->assertTrue($pi_group->getIsDisabled());
            $this->assertEmpty($pi_group->getMemberUIDs());
            $this->assertFalse($pi_group->getOwner()->getFlag(UserFlag::QUALIFIED));
        } finally {
            $entry = $LDAP->getPIGroupEntry($pi_group->gid);
            $entry->setAttribute("memberuid", $memberuids_before);
            $entry->setAttribute("isDisabled", "FALSE");
            $pi_group->getOwner()->setFlag(UserFlag::QUALIFIED, true);
        }
    }

    public function testMemberBecomesUnqualified()
    {
        global $USER, $LDAP;
        $this->switchUser("Blank");
        $new_user = $USER;
        $this->assertFalse($new_user->getFlag(UserFlag::QUALIFIED));
        $this->switchUser("EmptyPIGroupOwner");
        $pi_group = $USER->getPIGroup();
        $memberuids_before = $pi_group->getMemberUIDs();
        try {
            $pi_group->newUserRequest($new_user);
            $pi_group->approveUser($new_user);
            $this->assertTrue($new_user->getFlag(UserFlag::QUALIFIED));
            http_post(__DIR__ . "/../../webroot/panel/pi.php", ["form_type" => "disable"]);
            $this->assertFalse($new_user->getFlag(UserFlag::QUALIFIED));
        } finally {
            $entry = $LDAP->getPIGroupEntry($pi_group->gid);
            $entry->setAttribute("memberuid", $memberuids_before);
            $entry->setAttribute("isDisabled", "FALSE");
            $pi_group->getOwner()->setFlag(UserFlag::QUALIFIED, true);
            $new_user->setFlag(UserFlag::QUALIFIED, false);
        }
    }

    public function testGetDisabledSetDisabled()
    {
        global $USER, $LDAP;
        $this->switchUser("NormalPI");
        $pi_group = $USER->getPIGroup();
        $entry = $LDAP->getPIGroupEntry($pi_group->gid);
        $this->assertEquals([], $entry->getAttribute("isDisabled"));
        try {
            $pi_group->setIsDisabled(false);
            $this->assertFalse($pi_group->getIsDisabled());
            $pi_group->setIsDisabled(true);
            $this->assertTrue($pi_group->getIsDisabled());
            $entry->removeAttribute("isDisabled");
            $this->assertFalse($pi_group->getIsDisabled());
        } finally {
            if ($entry->hasAttribute("isDisabled")) {
                $entry->removeAttribute("isDisabled");
            }
        }
    }

    public function testPIMgmtShowsBothGroupsWithDisabledAttributeSetFalseAndUnset()
    {
        global $USER, $LDAP;
        $this->switchUser("NormalPI");
        $pi_group = $USER->getPIGroup();
        $entry = $LDAP->getPIGroupEntry($pi_group->gid);
        $this->assertEquals([], $entry->getAttribute("isDisabled"));
        $this->switchUser("Admin");
        $this->assertStringContainsString(
            $pi_group->gid,
            http_get(__DIR__ . "/../../webroot/admin/pi-mgmt.php"),
        );
        try {
            $pi_group->setIsDisabled(false);
            $this->assertEquals(["FALSE"], $entry->getAttribute("isDisabled"));
            $this->assertStringContainsString(
                $pi_group->gid,
                http_get(__DIR__ . "/../../webroot/admin/pi-mgmt.php"),
            );
        } finally {
            if ($entry->hasAttribute("isDisabled")) {
                $entry->removeAttribute("isDisabled");
            }
        }
    }
}
