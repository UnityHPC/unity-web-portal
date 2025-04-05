<?php

namespace UnityWebPortal\lib;

use Mockery;
use Exception;

require_once "panel-bootstrap.php";

function delete_ssh_key(mixed $index): void {
    post(
        "../../webroot/panel/account.php",
        ["form_type" => "delKey", "delIndex" => $index]
    );
}

function add_ssh_key_paste(string $key): void {
    post(
        "../../webroot/panel/account.php",
        [
            "form_type" => "addKey",
            "add_type" => "paste",
            "key" => $key
        ]
    );
}

function add_ssh_key_import(string $key): void {
    $tmp = tmpfile();
    $tmp_path = stream_get_meta_data($tmp)["uri"];
    fwrite($tmp, $key);
    $_FILES["keyfile"] = ["tmp_name" => $tmp_path];
    try {
        post(
            "../../webroot/panel/account.php",
            ["form_type" => "addKey", "add_type" => "import"]
        );
    } finally {
        unlink($tmp_path);
        unset($_FILES["keyfile"]);
    }
}

function add_ssh_key_generated(string $key): void {
    post(
        "../../webroot/panel/account.php",
        [
            "form_type" => "addKey",
            "add_type" => "generate",
            "gen_key" => $key
        ]
    );
}

function add_ssh_keys_github(array $keys): void {
    global $SITE;
    $old_site = $SITE;
    $SITE = Mockery::mock(UnitySite::class)->makePartial();
    $SITE->shouldReceive("getGithubKeys")->with("foobar")->andReturn($keys);
    try {
        post(
            "../../webroot/panel/account.php",
            [
                "form_type" => "addKey",
                "add_type" => "github",
                "gh_user" => "foobar"
            ]
        );
    } finally {
        Mockery::close();
        $SITE = $old_site;
    }
}
