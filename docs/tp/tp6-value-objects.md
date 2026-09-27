# TP 6 : identifier puis coder les value objects

**Durée** : 50 min · **Partir de** `etape-0` · **Solution** `etape-1`

```
git switch -c tp6-binome-a origin/etape-0
```

Remplacez `binome-a` par le nom de votre binôme.

## Objectif

Sortir du code anémique les concepts qui n'ont pas d'identité propre, et leur donner du comportement. Dans `src/Service/OrderService.php`, les montants sont des `int` qu'on additionne à la main, et la règle du créneau (R3) est réécrite deux fois, dont une fois différemment dans `src/Command/OrderPlaceCommand.php`.

## 1. Classer (10 min, sans code)

Pour chaque concept du glossaire, décidez : **entité** (a une identité qui persiste dans le temps) ou **value object** (défini uniquement par sa valeur) ?

> Commande, ligne de commande, restaurant, plat, prix, montant minimum, quantité, créneau de livraison, client, course, coursier, adresse.

Justifiez en une phrase : « deux X avec les mêmes valeurs sont-ils le même X ? ».

## 2. Coder (40 min)

Dans `src/Ordering/Domain/ValueObject/` :

| Value object | Construction | Comportement |
|---|---|---|
| `Money` | `Money::ofCents(1650)`, `Money::zero()` | `add()`, `multiply(int)`, `isGreaterThanOrEqual()`, `equals()`. Montant en centimes + devise. Jamais négatif. |
| `Quantity` | `Quantity::of(2)` | Strictement positive. `add()`. |
| `DeliverySlot` | `DeliverySlot::startingAt(\DateTimeImmutable $start, \DateTimeImmutable $now)` | **Porte R3** : commence sur un quart d'heure, au moins 30 min après `$now`. Dure 15 min : `end()`. |

Une exception nommée par règle violée, dans `src/Ordering/Domain/Exception/` (par exemple `DeliverySlotIsTooSoon`).

### Tests à faire passer

Dans `tests/Unit/Ordering/Domain/ValueObject/`, au minimum :

- `test_adding_amounts_returns_a_new_amount` (et l'original n'a pas changé)
- `test_amounts_in_different_currencies_cannot_be_added`
- `test_an_amount_cannot_be_negative`
- `test_a_quantity_is_strictly_positive`
- `test_a_delivery_slot_lasts_fifteen_minutes`
- `test_a_delivery_slot_cannot_start_between_two_quarter_hours`
- `test_a_delivery_slot_cannot_start_less_than_thirty_minutes_after_the_order`

```
docker compose run --rm php vendor/bin/phpunit --testdox tests/Unit
```

## Contraintes

- `final readonly class`, constructeur privé, constructeur nommé.
- Pas de `new \DateTimeImmutable()` dans le value object : l'heure courante est un paramètre (`$now`). Pourquoi ?

## Travailler avec l'IA

- Décidez vous-mêmes de la liste des règles de chaque value object **avant** de prompter, et donnez-la à l'IA.
- Demandez les tests d'abord, relisez leurs noms : se lisent-ils comme des phrases de l'expert métier ?
- Vérifiez que l'IA n'a pas ajouté de setter ou de propriété modifiable.

## Pour aller plus loin

Utilisez vos value objects dans `OrderService` : le calcul du sous-total avec `Money`, la vérification du créneau avec `DeliverySlot`. Le test fonctionnel `tests/Functional/OrderJourneyTest.php` doit rester vert.
