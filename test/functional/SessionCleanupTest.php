<?php

class SessionCleanupTest extends UnityWebPortalTestCase
{
    public function testSessionCleanup()
    {
        global $_SESSION;
        $this->switchUser("Normal");
        $first_session_id = session_id();
        $_SESSION["csrf_tokens"] = ["foobar"];
        // set last login timestamp to 1970-00-00 00:00
        // assume duration from epoch until now is greater than config session_cleanup_idle_seconds
        $_SESSION["LAST_ACTIVITY"] = 0;
        $this->switchUser("Normal");
        $this->assertEquals($first_session_id, session_id());
        $this->assertEmpty($_SESSION["csrf_tokens"]);
    }

    public function testSessionNotCleanedUp()
    {
        global $_SESSION;
        $this->switchUser("Normal");
        $first_session_id = session_id();
        $_SESSION["csrf_tokens"] = ["foobar"];
        // set last login timestamp to a future timestamp
        // assume negative time delta is less than config session_cleanup_idle_seconds
        $_SESSION["LAST_ACTIVITY"] = time() + 999;
        $this->switchUser("Normal");
        $this->assertEquals($first_session_id, session_id());
        $this->assertEqualsCanonicalizing(["foobar"], $_SESSION["csrf_tokens"]);
    }
}
