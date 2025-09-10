<?php

use RobinIngelbrecht\PHPUnitCoverageTools\MinCoverage\MinCoverageRule;

return [
    new MinCoverageRule(
        pattern: '*',
        minCoverage: 65,
        exitOnLowCoverage: true
    ),
];
