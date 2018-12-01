<?php

namespace Breyta\Test\Migrations;

use Breyta\FileHelper;
use Breyta\Migrations;
use Breyta\Test\TestCase;
use Mockery as m;

class CreateMigrationTest extends TestCase
{

    protected $path = '/tmp/breyta-test';

    protected function setUp()
    {
        if (file_exists($this->path)) {
            exec('rm -Rf ' . $this->path);
        }
        mkdir($this->path);
        $this->migrations = new Migrations($this->mockPdo(), $this->path);
    }

    /** @test */
    public function createsAFileWithTimestamp()
    {
        $this->migrations->createMigration('JustAName');

        self::assertNotNull(FileHelper::getTimeFromFileName($this->getCreatedFileName()));
    }

    /** @test */
    public function addsNameToFileName()
    {
        $this->migrations->createMigration('JustAName');

        self::assertContains('JustAName', $this->getCreatedFileName());
    }

    /** @test */
    public function createsAClassWithNamespace()
    {
        $this->migrations->createMigration('JustAName');

        self::assertSame('Breyta\Migration\JustAName', FileHelper::getClassFromFile($this->getCreatedFileName()));
    }

    /** @test */
    public function appendsFoldersToNamespace()
    {
        $this->migrations->createMigration('News/CreateArticleTable');

        self::assertSame(
            'Breyta\Migration\News\CreateArticleTable',
            FileHelper::getClassFromFile($this->getCreatedFileName())
        );
    }

    /** @test */
    public function containsACommentWithMigrationName()
    {
        $this->migrations->createMigration('News/CreateArticleTable');

        $status = $this->migrations->getStatus();

        self::assertContains('// ' . $status->migrations[1]->file, file_get_contents($this->getCreatedFileName()));
    }

    protected function getCreatedFileName()
    {
        $files = [];
        exec('find ' . $this->path . ' -type f', $files);
        self::assertCount(1, $files);
        return $files[0];
    }
}
