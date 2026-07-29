<?php

namespace Tests\Feature\Infrastructure\Database;

use App\Infrastructure\Database\PostgreSqlGrantManager;
use Tests\TestCase;

final class PostgreSqlGrantManagerIntegrationTest extends TestCase
{
    public function test_testing_environment_passes_grant_context_validation_without_granting(): void
    {
        $this->assertSame('testing', config('app.env'));
        $this->assertSame('credimex', config('credimex.database.schema'));
        $this->assertSame('credimex_test_app', config('credimex.database.app_role'));

        $manager = PostgreSqlGrantManager::fromApplication($this->app);
        $context = $manager->assertSafeContext();

        $this->assertSame('testing', $context->environment);
        $this->assertSame('credimex_test_app', $context->configuredAppRole);
        $this->assertSame('credimex_test', $context->appConnection->database);
        $this->assertSame('credimex_test_app', $context->appConnection->user);
        $this->assertSame('credimex', $context->appConnection->schema);
        $this->assertSame('credimex', $context->appConnection->searchPath);
        $this->assertSame('{credimex}', $context->appConnection->schemasText);

        $this->assertSame('credimex_test', $context->ownerConnection->database);
        $this->assertSame('credimex_test_owner', $context->ownerConnection->user);
        $this->assertSame('credimex', $context->ownerConnection->schema);
        $this->assertSame('credimex', $context->ownerConnection->searchPath);
        $this->assertSame('{credimex}', $context->ownerConnection->schemasText);

        $this->assertTrue($context->role->exists);
        $this->assertTrue($context->role->canLogin);
        $this->assertTrue($context->role->hasConnect);
        $this->assertSame('credimex_test_app', $context->role->name);
    }
}
