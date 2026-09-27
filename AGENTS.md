# AGENTS.md : consignes pour les assistants IA

Tu assistes des développeurs **en formation Domain-Driven Design**. Ils connaissent bien C# (.NET) ou Python, mais pas PHP. Le sujet de la formation est le DDD, pas PHP ni Symfony.

## Comment aider

- **Ne fais que ce que demande le TP en cours** (énoncés dans `docs/tp/`). Ne code pas l'étape suivante, ne « termine » pas le projet.
- Quand une **décision de modélisation** se présente (entité ou value object ? quel invariant dans quel agrégat ? où placer une règle ?), expose les options et laisse les participants trancher avant d'écrire le code.
- Explique ce que tu écris en termes DDD, et fais le parallèle avec C# quand c'est utile (`readonly class` ≈ `record`, `enum` ≈ `enum`, etc.).
- Écris le test d'abord quand l'énoncé le demande.
- N'affaiblis jamais une règle de `tests/Architecture/` ni un test existant pour le faire passer : corrige le code. Tu peux ajouter une règle ou un test quand le TP le demande.
- Reste bref sur les détails du framework et de l'ORM : ce n'est pas le sujet.

## Lancer les commandes

Tout passe par Docker (PHP n'est pas installé sur la machine) :

```
docker compose run --rm php vendor/bin/phpunit
docker compose run --rm php vendor/bin/phpstan analyse
docker compose run --rm php bin/console <commande>
```

## Le métier

Popote, livraison de repas. Règles R1 à R8 : `docs/regles-metier.md`. Utilise le vocabulaire du glossaire dans le code (en anglais) :

| Métier | Code |
|---|---|
| Commande | `Order` |
| Ligne de commande | `OrderLine` |
| Créneau de livraison | `DeliverySlot` |
| Valider une commande | `place()` |
| Course | `Run` |
| Coursier | `CourierId` (la Livraison ne connaît le coursier que par son identifiant) |

## Architecture cible

Un dossier par bounded context, trois couches :

```
src/<Contexte>/Domain/          modèle métier : Model, ValueObject, Event, Exception, Repository (interfaces), Service
src/<Contexte>/Application/     cas d'usage : Command, Query (+ leurs handlers), EventListener, Port
src/<Contexte>/Infrastructure/  adaptateurs : Doctrine, InMemory, Console
src/Ordering/PublishedLanguage/ contrat public de la Prise de commande pour les autres contextes (scalaires uniquement)
src/Shared/                     briques communes (AggregateRoot, bus)
src/Catalog/                    contexte CRUD assumé (entités Doctrine à getters/setters)
```

Règle des dépendances : le Domain ne dépend que de lui-même. Compromis acceptés dans le Domain : `webmozart/assert`, `symfony/uid`, les attributs de mapping `Doctrine\ORM\Mapping` et `doctrine/collections`. Jamais d'`EntityManager`, de requête ou de service Symfony dans le Domain.

Le code dans `src/Entity`, `src/Service` et `src/Command` (s'il existe sur ta branche) est **l'ancien code** qu'on refactore : ne l'imite pas.

## Conventions de code

- `declare(strict_types=1);` en tête de fichier. Classes `final`. Value objects `final readonly`.
- Constructeurs privés et constructeurs nommés : `Money::ofCents(1650)`, `DeliverySlot::startingAt($start, $now)`, `Order::draft(...)`.
- Pas de setters sur le modèle : des méthodes métier nommées d'après le langage du domaine (`place()`, `accept()`, `cancel()`).
- Une exception nommée d'après la règle ou l'état violé, dans `Domain/Exception`, qui étend `\DomainException` : `OrderIsBelowMinimumAmount`, `OrderIsNotAwaitingAnswer`, pas `InvalidOrderException`.
- Validation technique (format, non vide) : `Webmozart\Assert\Assert`.
- Commandes console : classes invocables (Symfony 7.3+), `#[AsCommand]` et une méthode `__invoke()` dont les paramètres `#[Argument]` déclarent les arguments. Pas d'héritage de `Command`.
- Un repository est une collection d'agrégats (`save()`, `ofId()`, `nextIdentity()`...) : **pas de pagination** (ni page, ni limite, ni paginator) dans un repository. Un besoin de liste paginée est un besoin de lecture : il passe par une Query dédiée dans l'Application.
- **Le temps est une dépendance, comme une base de données.** L'heure courante vient toujours de `Psr\Clock\ClockInterface` (PSR-20, l'équivalent du `TimeProvider` de .NET 8), implémentée par Symfony : `Clock` en production, `MockClock` en test. Jamais `new \DateTime()`, `new \DateTimeImmutable()` sans date explicite, `time()` ni `date()`.
  - Le Domain ne lit pas l'heure : il la reçoit en paramètre (`$now`). C'est l'Application qui injecte `ClockInterface` et appelle `$this->clock->now()`.
  - Lire une date depuis une chaîne (saisie, contrat publié) : `Symfony\Component\Clock\DatePoint::createFromFormat()`, qui lève une exception si le format est faux.
  - Test unitaire : `new MockClock(new \DateTimeImmutable('2026-10-01 11:00'))`. Attention, `new MockClock('2026-10-01 11:00')` interprète la chaîne en UTC.
  - Test fonctionnel (à partir de l'étape 3) : l'horloge de l'application est figée au 1er octobre 2026 à 11h00 (`config/services.yaml`) ; `$clock->sleep(300)` la fait avancer de 5 minutes.
- Montants en centimes (`int`), jamais de `float`.
- Tests nommés comme des phrases métier : `test_a_placed_order_cannot_be_modified()`. Structure Arrange, Act, Assert. Tests du domaine sans base de données ni framework (`PHPUnit\Framework\TestCase`).
