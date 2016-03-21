#!/usr/bin/env php
<?php

declare(strict_types=1);

/**
 * File index.php
 *
 * @author Edward Pfremmer <epfremme@nerdery.com>
 */

require_once 'vendor/autoload.php';

use PHPWeekly\Issue44\Manager;

if (in_array('--print', $argv)) {
    array_splice($argv, array_search('--print', $argv), 1);
    $print = true;
}

if (!isset($argv[1]) || !is_numeric($argv[1])) {
    throw new \InvalidArgumentException("Missing or invalid sequence argument provided");
}

if (!isset($argv[2]) || !is_numeric($argv[2])) {
    throw new \InvalidArgumentException("Missing or invalid iterations argument provided");
}

$start = microtime(true);

$manager = new Manager((int) $argv[2]);
$manager->start($argv[1]);

echo PHP_EOL;

if (isset($print)) {
    echo sprintf('Result: %s', $manager->getResult());
}

echo sprintf('Processed: %s sequences', $argv[2]) . PHP_EOL;
echo sprintf('Runtime: %s seconds', microtime(true) - $start) . PHP_EOL;
echo sprintf('Memory: %s bytes', number_format(memory_get_peak_usage(true))) . PHP_EOL;
