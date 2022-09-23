<?php

$config->custom->appearance['timezone'] = 'America/New_York';
$config->custom->appearance['hide_template_warning'] = true;
$config->custom->appearance['friendly_attrs'] = array();

$servers = new Datastore();
$servers->newServer('ldap_pla');
$servers->setValue('server', 'name', 'unity-web-portal-dev');
$servers->setValue('server', 'host', 'identity');
$servers->setValue('login', 'auth_type', 'session');
$servers->setValue('login', 'bind_id', 'cn=admin,dc=unity,dc=rc,dc=umass,dc=edu');
$servers->setValue('server', 'base', array('dc=unity,dc=rc,dc=umass,dc=edu'));
$servers->setValue('auto_number', 'min', array('uidNumber' => 20000,'gidNumber' => 20000));
