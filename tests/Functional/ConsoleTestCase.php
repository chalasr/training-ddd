<?php

declare(strict_types=1);

namespace App\Tests\Functional;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Tools\SchemaTool;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

/**
 * Exerce l'application comme un utilisateur : par ses commandes console
 * (self::runCommand() est fourni par Symfony).
 * Base SQLite de test recréée avant chaque test.
 */
abstract class ConsoleTestCase extends KernelTestCase
{
    protected function setUp(): void
    {
        self::bootKernel();

        $em = self::getContainer()->get(EntityManagerInterface::class);
        \assert($em instanceof EntityManagerInterface);
        $schemaTool = new SchemaTool($em);
        $metadata = $em->getMetadataFactory()->getAllMetadata();
        $schemaTool->dropSchema($metadata);
        $schemaTool->createSchema($metadata);
    }

    /**
     * Un créneau toujours valide : demain à 12h30, en plein service.
     */
    protected static function tomorrowAt(string $time): string
    {
        return (new \DateTimeImmutable('tomorrow'))->format('Y-m-d').' '.$time;
    }

    protected static function orderIdIn(string $display): string
    {
        self::assertSame(1, preg_match('/Commande (\S+) validée/', $display, $matches), 'Aucun identifiant de commande dans la sortie.');

        return $matches[1];
    }
}
