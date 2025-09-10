<?php

use RobinIngelbrecht\PHPUnitCoverageTools\MinCoverage\MinCoverageRule;

return [
    new MinCoverageRule(
        pattern: '*',
        minCoverage: 64,
        exitOnLowCoverage: true
    ),
];
