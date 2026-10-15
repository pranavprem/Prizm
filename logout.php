<?php

declare(strict_types=1);

require_once __DIR__ . '/util.php';
prizm_logout();
header('Location: index.php', true, 303);
