<?php

declare(strict_types=1);

namespace App\Tests\Support;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Tools\SchemaTool;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

/**
 * Démarre Symfony et recrée le schéma de la base SQLite de test avant chaque test.
 */
abstract class DatabaseTestCase extends KernelTestCase
{
    protected function setUp(): void
    {
        self::bootKernel();

        $schemaTool = new SchemaTool($this->entityManager());
        $metadata = $this->entityManager()->getMetadataFactory()->getAllMetadata();
        $schemaTool->dropSchema($metadata);
        $schemaTool->createSchema($metadata);
    }

    protected function entityManager(): EntityManagerInterface
    {
        $entityManager = self::getContainer()->get(EntityManagerInterface::class);
        \assert($entityManager instanceof EntityManagerInterface);

        return $entityManager;
    }
}
