<?php
namespace UnityWebPortal\lib;

use phpseclib3\Crypt\PublicKeyLoader;
use Exception;

class GithubUserNotFoundOrNoKeysException extends Exception {}
class BadRequestException extends Exception {}
class ForbiddenException extends Exception {}

class UnitySite
{
    public function redirect($destination)
    {
        if ($_SERVER["PHP_SELF"] != $destination) {
            header("Location: $destination");
            echo("Redirect failed, click <a href='$destination'>here</a> to continue.");
            throw new RedirectFailedException();
        }
    }

    public function array_get_or_bad_request(mixed $key, array $array){
        if (!array_key_exists($key, $array)){
            $this->bad_request("missing $key");
        }
        return $array[$key];
    }

    public function bad_request(string $msg): void {
        header("HTTP/1.1 400 Bad Request");
        $full_msg = "<pre>ERROR: bad request. Please contact Unity support.\n$msg</pre>";
        // this clogs up phpunit output
        // error_log($full_msg);
        // error_log((new Exception())->getTraceAsString());
        echo $full_msg;
        throw new BadRequestException;
    }

    // FIXME move this function somewhere with these globals defined?
    public function forbidden(UnitySQL $SQL, UnityUser $USER): void{
        header("HTTP/1.1 403 Forbidden");
        echo "<pre>Access denied. This incident has been recorded.</pre>";
        // error_log("user-mgmt.php: access denied to '" . $USER->getUID() . "'");
        // error_log((new Exception())->getTraceAsString());
        $SQL->addLog(
            $USER->getUID(),
            $_SERVER['REMOTE_ADDR'],
            "access_denied",
            $USER->getUID()
        );
        throw new ForbiddenException();
    }

    public function alert(string $msg): void{
        echo "<script type='text/javascript'>alert(" . json_encode($msg) . ");</script>";
    }

    public function getGithubKeys($username)
    {
        $url = "https://api.github.com/users/$username/keys";
        $headers = array(
        "User-Agent: Unity Cluster User Portal"
        );

        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
        $keys = json_decode(curl_exec($curl), false);
        curl_close($curl);

        if ((!is_array($keys)) || (count($keys) == 0)) {
            throw new GithubUserNotFoundOrNoKeysException();
        }
        return array_map(function($x){return $x->key;}, $keys);
    }

    public function testValidSSHKey(string $key_str)
    {
        $key_str = trim($key_str);
        if ($key_str == ""){
            return false;
        }
        try {
            PublicKeyLoader::load($key_str);
            return true;
        } catch (Exception $e) {
            return false;
        }
    }
}
