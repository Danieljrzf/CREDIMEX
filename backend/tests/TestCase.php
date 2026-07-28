<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Tests\Support\PostgreSqlTestSafetyGuard;

abstract class TestCase extends BaseTestCase
{
    /**
     * Boot the testing helper traits after the CREDIMEX fail-closed guard.
     *
     * @return array
     */
    protected function setUpTraits()
    {
        $uses = $this->traitsUsedByTest
            ?? class_uses_recursive(static::class);

        PostgreSqlTestSafetyGuard::assertForbiddenDatabaseTraitsNotPresent($uses);

        PostgreSqlTestSafetyGuard::enforceUsingApplication($this->app);

        return parent::setUpTraits();
    }
}
