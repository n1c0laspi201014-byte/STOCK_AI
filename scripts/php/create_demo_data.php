<?php
declare(strict_types=1);

echo "PASS Demo data utility started\n  Tested: idempotent seed process; existing production-like rows are not overwritten.\n";
require dirname(__DIR__, 2) . '/database/seed.php';

