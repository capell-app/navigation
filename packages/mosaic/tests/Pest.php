<?php

declare(strict_types=1);

/*
|--------------------------------------------------------------------------
| Test Case
|--------------------------------------------------------------------------
|
| The closure you provide to your test functions is always bound to a specific PHPUnit test
| case class. By default, that will be the PHPUnit\Framework\TestCase class. Of course,
| you may need to bind a different test case.
|
| Similarly, if you wish to share setup or teardown logic between all tests of a given
| group, you may use group hooks. See the PHPUnit documentation for more information:
| https://phpunit.de/manual/current/en/fixtures.html#fixtures.global-state
|
*/

use PHPUnit\Framework\TestCase;

uses(TestCase::class)->in('Feature');

/*
|--------------------------------------------------------------------------
| Expectations
|--------------------------------------------------------------------------
|
| When you're writing tests, you often need to check that values meet certain conditions. The
| "expect()" function gives you access to a set of "expectations" methods that you can call
| on your value. By default, we have a few custom expectations already setup for you. However,
| you can also extend the Expectations API by adding your own custom expectations.
|
*/

expect()->extend('toBeString', function () {
    return $this->toBeString();
});

/*
|--------------------------------------------------------------------------
| Functions
|--------------------------------------------------------------------------
|
| While Pest is very powerful out-of-the-box, you may have some testing code specific to your
| project that you don't want to repeat in every file. Here you can also expose helpers as
| global functions to keep your tests clean and organized.
|
*/

function something()
{
    // ..
}
