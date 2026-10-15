<?php

declare(strict_types=1);

if (getenv('PRIZM_DB_DRIVER') === false || getenv('PRIZM_DB_DRIVER') === '') {
    putenv('PRIZM_DB_DRIVER=sqlite');
    putenv('PRIZM_DB_PATH=:memory:');
}
if (getenv('PRIZM_SEED') === false || getenv('PRIZM_SEED') === '') {
    putenv('PRIZM_SEED=0');
}

require dirname(__DIR__) . '/util.php';
