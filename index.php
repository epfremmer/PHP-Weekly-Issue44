#!/usr/bin/env php
<?php

//declare(strict_types=1);

/**
 * File index.php
 *
 * @note why does Jansen hate us?
 * @author Edward Pfremmer <epfremme@nerdery.com>
 */

require_once 'vendor/autoload.php';

use PHPWeekly\Issue44\Manager;

if (!isset($argv[1]) || !is_numeric($argv[1])) {
    throw new \InvalidArgumentException("Missing or invalid sequence argument provided");
}

if (!isset($argv[2]) || !is_numeric($argv[2])) {
    throw new \InvalidArgumentException("Missing or invalid iterations argument provided");
}

$start = microtime(true);

$manager = new Manager((int) $argv[2]);
$manager->start($argv[1]);

echo sprintf('total: %s', microtime(true) - $start) . PHP_EOL;
