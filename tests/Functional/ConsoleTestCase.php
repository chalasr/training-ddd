<?php

declare(strict_types=1);

namespace App\Tests\Functional;

use App\Tests\Support\DatabaseTestCase;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Clock\ClockInterface;
use Symfony\Component\Console\Tester\ExecutionResult;

/**
 * Exerce l'application comme un utilisateur : par ses commandes console
 * (self::runCommand() est fourni par Symfony).
 */
abstract class ConsoleTestCase extends DatabaseTestCase
{
    /**
     * En vrai, chaque commande console est un processus séparé : on vide l'EntityManager
     * entre deux commandes pour ne pas relire un objet resté en mémoire.
     *
     * @param array<string, mixed>            $input
     * @param list<string>                    $interactiveInputs
     * @param array<\Closure(string): string> $normalizers
     */
    public static function runCommand(string $name, array $input = [], array $interactiveInputs = [], ?bool $interactive = null, ?bool $decorated = null, ?int $verbosity = null, array $normalizers = []): ExecutionResult
    {
        $result = parent::runCommand($name, $input, $interactiveInputs, $interactive, $decorated, $verbosity, $normalizers);
        $entityManager = self::getContainer()->get(EntityManagerInterface::class);
        \assert($entityManager instanceof EntityManagerInterface);
        $entityManager->clear();

        return $result;
    }

    /**
     * Un créneau toujours valide : demain, en plein service. « Demain » selon l'horloge de l'application,
     * figée dans les tests (config/services.yaml) : les tests ne dépendent pas du jour où on les lance.
     */
    protected static function tomorrowAt(string $time): string
    {
        return self::clock()->now()->modify('+1 day')->format('Y-m-d').' '.$time;
    }

    protected static function clock(): ClockInterface
    {
        $clock = self::getContainer()->get('clock');
        \assert($clock instanceof ClockInterface);

        return $clock;
    }

    protected static function orderIdIn(string $display): string
    {
        self::assertSame(1, preg_match('/Commande (\S+) validée/', $display, $matches), 'Aucun identifiant de commande dans la sortie.');

        return $matches[1];
    }
}
