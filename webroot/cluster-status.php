<?php
require "../resources/autoload.php";

require_once config::PATHS["libraries"] . "/unity-shell.php";
require_once config::PATHS["templates"] . "/header.php";

$excluded_nodes = array("web");  // Comma separated list of any nodes that should not be listed (their prefix)
$gpu_nodes = array("gpu");  // Nodes starting with this prefix will show GPUs allocated instead of CPUs
$nodes = shell::getNodeArray();

?>

<h1><?php echo unity_locale::CLUSTER_HEADER_MAIN; ?></h1>

<?php

// Get Totals
$totalCPUs = 0;
$allocCPUs = 0;
foreach ($nodes as $node => $data) {
    if (!in_array($node, $excluded_nodes)) {
        $totalCPUs += intval($data["CPUTot"]);
        $allocCPUs += intval($data["CPUAlloc"]);
    }
}

echo "<p><b>" . unity_locale::CLUSTER_LABEL_USAGE . "</b> $allocCPUs / $totalCPUs (" . intval(doubleval($allocCPUs) / doubleval($totalCPUs) * 100) . "%)</p>";

//echo "<pre>";
//die(print_r($nodes));
foreach ($nodes as $node => $data) {
    if (!in_array(substr($node, 0, strcspn($node, "0123456789")), $excluded_nodes)) {  // Get the string up to the first number of the node name, compare with prefix array
        echo "<div class='node-view'>";
        echo "<div style='width: " . doubleval($data["CPUAlloc"]) / doubleval($data["CPUTot"]) * 100 . "%;' class='node-load-bar'></div>";

        echo "<span class='usage vertical-align'>" . $data["CPUAlloc"] . " / " . $data["CPUTot"] . "</span>";
        echo "<div class='info vertical-align'>";
        echo "<span style='margin-right: 15px;'>$node</span>";
        if (strpos($data["State"], "IDLE") !== false || strpos($data["State"], "MIXED") !== false) {
            echo "<span class='message-success'>" . unity_locale::CLUSTER_LABEL_UP . "</span>";
        } else {
            echo "<span class='message-failure'>" . unity_locale::CLUSTER_LABEL_DOWN . "</span>";
        }
        echo "</div>";

        echo "</div>";
    }
}

require_once config::PATHS["templates"] . "/footer.php";
?>

<style>
    div.node-view {
        width: 100%;
        height: 50px;
        background: var(--color-base-3);
        margin: 5px 0 5px 0;
        position: relative;
    }

    div.node-view .info {
        text-transform: uppercase;
        left: 20px;
        z-index: 1;
    }

    div.node-view span {
        display: inline-block;
    }

    div.node-view .node-load-bar {
        position: absolute;
        top: 0;
        bottom: 0;
        left: 0;
        background: #e6e6e6;
    }

    div.node-view .usage {
        right: 20px;
        z-index: 1;
    }
</style>