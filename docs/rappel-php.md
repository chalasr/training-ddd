# PHP 8.4 en une page, pour développeurs C#

| Concept | C# | PHP |
|---|---|---|
| Variable | `var total = 0;` | `$total = 0;` |
| Appel de méthode / propriété | `order.Place()` / `order.Id` | `$order->place()` / `$order->id` |
| Membre statique | `Money.Zero()` | `Money::zero()` |
| Espace de noms | `namespace App.Ordering;` + `using` | `namespace App\Ordering;` + `use` |
| Typage strict | par défaut | `declare(strict_types=1);` en tête de fichier |
| Classe scellée | `sealed class` | `final class` |
| Value object | `record` / `readonly record struct` | `final readonly class` |
| Propriétés en lecture seule publiques, écriture privée | `{ get; private set; }` | `public private(set) int $x;` |
| Constructeur primaire | `class Money(int cents)` | promotion : `__construct(public int $cents)` |
| Énumération | `enum OrderStatus { Draft }` | `enum OrderStatus: string { case Draft = 'draft'; }` |
| `switch` expression | `x switch { ... }` | `match ($x) { ... }` |
| Null-conditionnel | `slot?.Start` | `$slot?->start` |
| Coalescence | `a ?? b` | `$a ?? $b`, et `?? throw new ...` |
| Lambda | `x => x * 2` | `fn ($x) => $x * 2` (ou `static fn`) |
| Liste | `List<Order>` | `array`, typé en commentaire : `/** @var list<Order> */` |
| Dictionnaire | `Dictionary<string, int>` | `array`, typé : `/** @var array<string, int> */` |
| Génériques | `Collection<T>` | seulement en commentaire (`@template`), vérifié par PHPStan |
| Visibilité `internal` | `internal` | n'existe pas : convention `/** @internal */` |
| Attribut | `[Required]` | `#[ORM\Column]` |
| Égalité de valeur | `record` : automatique | à écrire : méthode `equals()` |
| Heure courante, remplaçable en test | `TimeProvider` (.NET 8), `FakeTimeProvider` | `Psr\Clock\ClockInterface` : `$clock->now()` ; en test `MockClock` |

Quelques repères :

- `$this` est obligatoire pour accéder à un membre : `$this->status`, jamais `status` seul.
- `self` désigne la classe courante : `new self(...)`, `self::MINIMUM`.
- Une interface se nomme souvent `...Interface` dans l'écosystème PHP (`CommandBusInterface`).
- Le point d'entrée d'une classe « appelable » est `__invoke()` : les handlers s'appellent comme des fonctions, `$handler($command)`.
- `\DateTimeImmutable` ≈ `DateTimeOffset` immuable ; `$date->modify('+30 minutes')` renvoie une nouvelle instance. On ne l'obtient jamais par `new \DateTimeImmutable()` (l'heure système) : l'heure courante vient de l'horloge injectée.
