<?php declare(strict_types=1);

date_default_timezone_set('UTC');

require __DIR__.'/../vendor/autoload.php';

// B.C. for PSR Log's old inheritance
// see https://github.com/php-fig/log/pull/52
if (!class_exists('\\PHPUnit_Framework_TestCase', true)) {
    class_alias('\\PHPUnit\\Framework\\TestCase', '\\PHPUnit_Framework_TestCase');
}
