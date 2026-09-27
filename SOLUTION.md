# Étape 1 : value objects (solution du TP 6)

## Ce qui a changé

- `src/Ordering/Domain/ValueObject/Money.php` : centimes + devise, immuable. `add()` et `multiply()` renvoient une **nouvelle** instance. Additionner deux devises différentes est refusé.
- `Quantity` : strictement positive.
- `DeliverySlot` : porte R3. `startingAt($start, $now)` refuse un créneau hors quart d'heure (`DeliverySlotMustStartOnAQuarterHour`) ou trop proche (`DeliverySlotIsTooSoon`). La durée de 15 minutes est une constante du domaine.
- `OrderService` utilise déjà ces value objects : le refactoring est progressif, l'application n'a jamais cessé de fonctionner.

## Choix à discuter

- **L'heure courante est un paramètre** (`$now`) : le value object reste une fonction pure, testable sans horloge ni mock.
- **Les horaires d'ouverture ne sont pas dans `DeliverySlot`** : ils dépendent du restaurant. Le créneau porte ce qui est vrai pour tous les restaurants ; la vérification des horaires viendra avec l'agrégat.
- **`Money` refuse les montants négatifs** : ici, un montant est un prix. Un remboursement serait un autre concept.
- Validation de format avec `Webmozart\Assert` ; règle métier violée : exception nommée.

## Toujours là

La validation dupliquée de `src/Command/OrderPlaceCommand.php`, avec son `<=` qui diffère du `<` du service. Les value objects ne suffisent pas : il manque un endroit unique qui garantit les règles.

## Fourni pour le TP 7

Pour que le TP 7 porte sur l'agrégat lui-même, cette branche contient déjà les petites briques dont il a besoin (avec leurs tests) :

- `src/Shared/Domain/AggregateRoot.php` (`recordThat()`, `releaseEvents()`), `Event/DomainEvent.php`, `ValueObject/AggregateRootId.php` (trait, UUID v7) ;
- `src/Ordering/Domain/ValueObject/` : `OrderId`, `CustomerId`, `RestaurantId`, `Dish`, `OpeningHours`, `RestaurantTerms` ;
- `src/Ordering/Domain/Service/DeliveryFeeCalculator.php` : la politique tarifaire, extraite d'`OrderService`.

Suite : [TP 7](docs/tp/tp7-agregat.md).
