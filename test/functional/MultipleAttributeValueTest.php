<?php

namespace UnityWebPortal\lib;

use PHPUnit\Framework\TestCase;

class MultipleAttributeValueTest extends TestCase
{
    public function test()
    {
        global $USER;
        switchUser(
            eppn: "user1@org1.test",
            given_name: "foo;foo",
            sn: "bar;bar",
            mail: "user1@org1.test;user1@org1.test"
        );
        $this->assertEquals($USER->getFirstname(), "foo");
        $this->assertEquals($USER->getLastname(), "bar");
        $this->assertEquals($USER->getMail(), "user1@org1.test");
    }
}
