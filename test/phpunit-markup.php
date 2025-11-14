#!/usr/bin/env php
<?php while ($line = fgets(STDIN)) {
    $line = rtrim($line, "\n");
    if (preg_match('/^(\d+\))?\s*(\/.*):(\d+)$/', $line, $matches)) {
        [$path, $lineNumber] = [$matches[2], intval($matches[3]) - 1];
        $src_line = trim(file($path)[$lineNumber]);
        echo "$line \033[36m$src_line\033[0m\n";
    } else {
        echo "$line\n";
    }
}
