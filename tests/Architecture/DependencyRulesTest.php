<?php

declare(strict_types=1);

namespace App\Tests\Architecture;

use PHPUnit\Framework\TestCase;

/**
 * Les règles de dépendance, vérifiées à chaque lancement des tests (équivalent de NetArchTest / ArchUnit).
 * Principe : on relève les classes dont dépend chaque fichier PHP (ses "use" et les noms complets
 * écrits dans le code, comme \App\Ordering\...) et on liste celles qui sont interdites.
 */
final class DependencyRulesTest extends TestCase
{
    /**
     * Compromis assumés (cf. mtarld/apip-ddd) : des bibliothèques tolérées dans le Domain parce qu'elles
     * ne font que le décrire (validation, identifiants, métadonnées de persistance), sans rien exécuter
     * d'infrastructure. En revanche : pas d'EntityManager, pas de requête, pas de framework.
     */
    private const array LIBRARIES_ALLOWED_IN_DOMAIN = [
        'Webmozart\Assert\\',
        'Symfony\Component\Uid\\',
        'Doctrine\ORM\Mapping',
        'Doctrine\Common\Collections\\',
    ];

    public function test_the_domain_only_depends_on_itself(): void
    {
        $violations = [];

        foreach (self::phpFilesIn('src/*/Domain') as $file) {
            $context = explode('/', $file)[1];
            $allowed = ['App\\'.$context.'\Domain\\', 'App\Shared\Domain\\', ...self::LIBRARIES_ALLOWED_IN_DOMAIN];

            foreach (self::dependenciesOf($file) as $dependency) {
                if (!self::startsWithAny($dependency, $allowed)) {
                    $violations[] = $file.' -> '.$dependency;
                }
            }
        }

        self::assertSame([], $violations, 'Le Domain ne dépend ni de l\'Application, ni de l\'Infrastructure, ni d\'un framework.');
    }

    public function test_ordering_only_knows_the_catalog_through_its_anti_corruption_layer(): void
    {
        $violations = self::dependenciesMatching('src/Ordering', 'App\Catalog\\', exceptIn: 'src/Ordering/Infrastructure/Catalog/');

        self::assertSame([], $violations, 'Seule l\'anti-corruption layer de la Prise de commande connaît le Catalogue.');
    }

    public function test_delivery_only_knows_ordering_through_its_published_language(): void
    {
        $violations = self::dependenciesMatching('src/Delivery', 'App\Ordering\\', exceptTo: 'App\Ordering\PublishedLanguage\\');

        self::assertSame([], $violations, 'La Livraison ne connaît de la Prise de commande que son contrat public (Published Language).');
    }

    public function test_ordering_does_not_know_delivery(): void
    {
        self::assertSame([], self::dependenciesMatching('src/Ordering', 'App\Delivery\\'), 'La Prise de commande (upstream) ignore qui consomme ses événements.');
    }

    public function test_the_published_language_does_not_leak_the_ordering_model(): void
    {
        self::assertSame([], self::dependenciesMatching('src/Ordering/PublishedLanguage', 'App\\'), 'Le contrat public ne contient que des scalaires, aucun type interne.');
    }

    /**
     * @param string      $forbidden préfixe des dépendances interdites
     * @param string|null $exceptIn  dossier dont les fichiers ont le droit (ex. l'anti-corruption layer)
     * @param string|null $exceptTo  préfixe de dépendances tout de même autorisées (ex. le Published Language)
     *
     * @return list<string> les "fichier -> dépendance" interdits
     */
    private static function dependenciesMatching(string $directory, string $forbidden, ?string $exceptIn = null, ?string $exceptTo = null): array
    {
        $violations = [];

        foreach (self::phpFilesIn($directory) as $file) {
            if (null !== $exceptIn && str_starts_with($file, $exceptIn)) {
                continue;
            }

            foreach (self::dependenciesOf($file) as $dependency) {
                if (str_starts_with($dependency, $forbidden) && (null === $exceptTo || !str_starts_with($dependency, $exceptTo))) {
                    $violations[] = $file.' -> '.$dependency;
                }
            }
        }

        return $violations;
    }

    /**
     * @return list<string> chemins relatifs à la racine du projet
     */
    private static function phpFilesIn(string $pattern): array
    {
        $root = \dirname(__DIR__, 2);
        $files = [];

        foreach (glob($root.'/'.$pattern, \GLOB_ONLYDIR) ?: [] as $directory) {
            foreach (new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($directory, \FilesystemIterator::SKIP_DOTS)) as $file) {
                if ($file instanceof \SplFileInfo && 'php' === $file->getExtension()) {
                    $files[] = substr($file->getPathname(), \strlen($root) + 1);
                }
            }
        }
        sort($files);

        return $files;
    }

    /**
     * Les classes dont dépend un fichier : ses "use", et les noms complets écrits dans le code (\App\...).
     *
     * @return list<string>
     */
    private static function dependenciesOf(string $file): array
    {
        $code = (string) file_get_contents(\dirname(__DIR__, 2).'/'.$file);

        preg_match_all('/^use\s+([\w\\\\]+)/m', $code, $imports);
        preg_match_all('/(?<![\w\\\\])\\\\([A-Z]\w*(?:\\\\\w+)+)/', $code, $fullyQualifiedNames);

        return array_values(array_unique([...$imports[1], ...$fullyQualifiedNames[1]]));
    }

    /**
     * @param list<string> $prefixes
     */
    private static function startsWithAny(string $dependency, array $prefixes): bool
    {
        foreach ($prefixes as $prefix) {
            if (str_starts_with($dependency, $prefix)) {
                return true;
            }
        }

        return false;
    }
}
