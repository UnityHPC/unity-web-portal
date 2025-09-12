<?php

use RobinIngelbrecht\PHPUnitCoverageTools\MinCoverage\MinCoverageRule;

return [
    new MinCoverageRule(
        pattern: '*',
        minCoverage: 63,
        exitOnLowCoverage: true
    ),
];
