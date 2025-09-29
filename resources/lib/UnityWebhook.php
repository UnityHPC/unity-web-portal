<?php

namespace UnityWebPortal\lib;

class UnityWebhook
{
    private $template_dir = __DIR__ . "/../mail";
    private $override_template_dir = __DIR__ . "/../../deployment/mail_overrides";
    private $url = CONFIG["webhook"]["url"];
    private $MSG_LINKREF;
    private $Subject; // set by template

    public function __construct()
    {
        $this->MSG_LINKREF = CONFIG["site"]["url"] . CONFIG["site"]["prefix"];
    }
    public function htmlToMarkdown($html)
    {
        // Define regex patterns for each markdown format
        $bold = '/<(b|strong)\b[^>]*>(.*?)<\/(b|strong)>/s';
        $italic = '/<i\b[^>]*>(.*?)<\/i>/s';
        $strikethrough = '/<del\b[^>]*>(.*?)<\/del>/s';
        $link = '/<a\b[^>]*href=["\']?([^"\'\s]*)[^>]*>(.*?)<\/a>/s';

        // Replace each HTML tag with its corresponding markdown format
        $md = preg_replace($bold, '*$2*', $html);
        $md = preg_replace($italic, '_$1_', $md);
        $md = preg_replace($strikethrough, '~$1~', $md);
        $md = preg_replace($link, '$2: $1', $md);

        // Replace any remaining HTML tags with an empty string
        $md = strip_tags($md);

        return $md;
    }

    public function sendWebhook($template = null, $data = null)
    {
        $template_filename = $template . ".php";
        if (file_exists($this->override_template_dir . "/" . $template_filename)) {
            $template_path = $this->override_template_dir . "/" . $template_filename;
        } else {
            $template_path = $this->template_dir . "/" . $template_filename;
        }

        ob_start();
        include $template_path;
        $mes_html = ob_get_clean();

        $message = $this->htmlToMarkdown($mes_html);

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $this->url);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json'));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, \jsonEncode(array('text' => $message)));
        $result = curl_exec($ch);
        curl_close($ch);
        return $result;
    }
}
