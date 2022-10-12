<?php

$config->custom->appearance['timezone'] = 'America/New_York';
$config->custom->appearance['hide_template_warning'] = true;
$config->custom->appearance['friendly_attrs'] = array();

$servers = new Datastore();
$servers->newServer('ldap_pla');
$servers->setValue('server', 'name', 'unity-web-portal-dev');
$servers->setValue('server', 'host', 'identity');
$servers->setValue('login', 'auth_type', 'config');
$servers->setValue('login', 'bind_id', 'cn=admin,dc=example,dc=com');
$servers->setValue('login', 'bind_pass', 'password');
$servers->setValue('server', 'base', array('dc=example,dc=com'));
$servers->setValue('auto_number', 'min', array('uidNumber' => 30000,'gidNumber' => 30000));
