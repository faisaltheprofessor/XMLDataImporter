<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    use CreatesApplication;

    protected function setUp(): void
    {
        $this->createSqliteTestingDatabase();
        parent::setUp();
    }

    protected function tearDown(): void
    {
        $this->removeSqliteTestingDatabase();
        parent::tearDown();
    }

    protected function createSqliteTestingDatabase(): void
    {
        $filePath = __DIR__.'/Files/testing_db.sqlite';

        if (!file_exists($filePath)) {
            touch($filePath);
        }
    }

    protected function removeSqliteTestingDatabase(): void
    {
        $filePath = __DIR__.'/Files/testing_db.sqlite';

        if (file_exists($filePath)) {
            unlink($filePath);
        }
    }
}
