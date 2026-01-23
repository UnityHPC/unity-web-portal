<?php

use PHPUnit\Framework\Attributes\DataProvider;
use UnityWebPortal\lib\exceptions\NoDieException;
use TRegx\PhpUnit\DataProviders\DataProvider as TRegxDataProvider;

class PageLoadTest extends UnityWebPortalTestCase
{
    public static function findPHPFiles($path)
    {
        // if I do this recursively I get the ajax and modal files, which aren't appropriate
        // for these tests, so instead I just list the directory
        // $directory_iterator = new RecursiveDirectoryIterator($path);
        // $iterator_iterator = new RecursiveIteratorIterator($directory_iterator);
        // $regex_iterator = new RegexIterator(
        //     $iterator_iterator,
        //     '/^.+\.php$/i',
        //     RecursiveRegexIterator::GET_MATCH
        // );
        // return array_keys(iterator_to_array($regex_iterator)));
        $files = [];
        foreach (new DirectoryIterator($path) as $file) {
            if (str_ends_with($file->getFilename(), ".php")) {
                array_push($files, join("/", [$path, $file->getFilename()]));
            }
        }
        return $files;
    }

    public static function providerAdmin()
    {
        return TRegxDataProvider::list(...self::findPHPFiles(__DIR__ . "/../../webroot/admin"));
    }

    public static function providerNonexistentUser()
    {
        $dir = __DIR__ . "/../../webroot/panel";
        $excludePages = array_map(fn($x) => "$dir/$x.php", [
            "pi", // requires user to be a PI
            "new_account", // this is the one page that does not redirect to new_account
        ]);
        $panelPages = self::findPHPFiles($dir);
        $output = array_diff($panelPages, $excludePages);
        return TRegxDataProvider::list(...$output);
    }

    public static function providerMisc()
    {
        return [
            // normal page load
            ["Admin", "admin/pi-mgmt.php", "/PI Management/"],
            ["Admin", "admin/user-mgmt.php", "/User Management/"],
            ["NonExistent", "panel/new_account.php", "/Register New Account/"],
            ["Blank", "panel/account.php", "/Account Settings/"],
            ["Blank", "panel/groups.php", "/My Principal Investigators/"],
            ["EmptyPIGroupOwner", "panel/pi.php", "/My Users/"],
            // new_account.php should redirect to account.php if account already exists
            ["Blank", "panel/new_account.php", "/panel\/account\.php/", true],
            // non-PI can't access pi.php
            ["Blank", "panel/pi.php", "/You are not a PI./", true],
            // disabled account technically exists but new_account.php should not redirect
            ["Disabled", "panel/new_account.php", "/Register New Account/", true],
        ];
    }

    #[DataProvider("providerMisc")]
    public function testLoadPage($nickname, $path, $regex, $ignore_die = false)
    {
        $this->switchUser($nickname);
        $output = http_get(__DIR__ . "/../../webroot/" . $path, ignore_die: $ignore_die);
        $this->assertMatchesRegularExpression($regex, $output);
    }

    #[DataProvider("providerAdmin")]
    public function testLoadAdminPageNotAnAdmin($path)
    {
        $this->switchUser("Blank");
        $output = http_get($path, ignore_die: true);
        $this->assertMatchesRegularExpression("/You are not an admin\./", $output);
    }

    #[DataProvider("providerNonexistentUser")]
    public function testLoadPageNonexistentUser($path)
    {
        $this->switchUser("NonExistent");
        $output = http_get($path, ignore_die: true);
        $this->assertMatchesRegularExpression("/panel\/new_account\.php/", $output);
    }

    public function testLoadPageLockedUser()
    {
        ob_start();
        try {
            $this->switchUser("Locked");
        } catch (NoDieException) {
            // ignore
        }
        $output = _ob_get_clean();
        $this->assertMatchesRegularExpression("/Your account is locked\./", $output);
    }

    public function testLoadPIPageForAnotherGroup()
    {
        global $LDAP, $USER;
        $this->switchUser("Manager");
        $gids = $LDAP->getPIGroupGIDSWithManager($USER->uid);
        $this->assertTrue(count($gids) > 0);
        $output = http_get(__DIR__ . "/../../webroot/panel/pi.php", [
            "gid" => $gids[0],
        ]);
        $this->assertMatchesRegularExpression("/My Users/", $output);
    }

    public function testLoadPIPageForAnotherGroupForbidden()
    {
        global $USER;
        $this->switchUser("EmptyPIGroupOwner");
        $gid = $USER->getPIGroup()->gid;
        $this->switchUser("Blank");
        $output = http_get(
            __DIR__ . "/../../webroot/panel/pi.php",
            ["gid" => $gid],
            ignore_die: true,
        );
        $this->assertMatchesRegularExpression("/not allowed/", $output);
    }

    public function testLoadPIPageForNonexistentGroup()
    {
        $this->switchUser("Blank");
        $output = http_get(
            __DIR__ . "/../../webroot/panel/pi.php",
            ["gid" => "foobar"],
            ignore_die: true,
        );
        $this->assertMatchesRegularExpression("/This group does not exist/", $output);
    }

    public function testDisplayManagedGroups()
    {
        global $USER, $LDAP;
        $this->switchUser("Manager");
        $gids = $LDAP->getPIGroupGIDSWithManager($USER->uid);
        $this->assertTrue(count($gids) > 0);
        $output = http_get(__DIR__ . "/../../webroot/index.php");
        foreach ($gids as $gid) {
            $this->assertMatchesRegularExpression("/pi\.php\?gid=$gid/", $output);
        }
    }
}
