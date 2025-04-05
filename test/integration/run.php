<?php

namespace UnityWebPortal\lib;

use Mockery;
use Exception;

require_once "panel-bootstrap.php";
require_once "panel-account-ssh-keys.php";

// user with 0 keys
switch_to_user("web_admin@unityhpc.test", "Web", "Admin", "web_admin@unityhpc.test");
PanelAccountSSHKeyTest::test_add_ssh_keys_all_methods_all_inputs();
PanelAccountSSHKeyTest::test_delete_ssh_keys_all_inputs();

// user with > 0 keys and > 0 key slots open
switch_to_user("user0110@org22.edu", "Foo", "Bar", "user0110@org22.edu");
PanelAccountSSHKeyTest::test_add_ssh_keys_all_methods_all_inputs();
PanelAccountSSHKeyTest::test_delete_ssh_keys_all_inputs();

// user with no empty key slots
switch_to_user("user0151@org18.edu", "Foo", "Bar", "user0151@org18.edu");
PanelAccountSSHKeyTest::test_add_ssh_keys_all_methods_all_inputs();
PanelAccountSSHKeyTest::test_delete_ssh_keys_all_inputs();
