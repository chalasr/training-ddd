# Étape 2 : l'agrégat Order (solution du TP 7)

## Ce qui a changé

- `src/Ordering/Domain/Model/Order.php` : la racine d'agrégat. Aucun setter, des méthodes métier : `draft()`, `addLine()`, `removeLine()`, `place()`, `accept()`, `reject()`.
- `OrderLine` : entité interne. Son identité (le plat) n'a de sens qu'à l'intérieur d'une commande.
- Les invariants et leurs exceptions : R1 `OrderMustConcernASingleRestaurant`, R2 `OrderIsBelowMinimumAmount`, R3 `DeliverySlotIsOutsideOpeningHours`, R4 `OrderCannotBeModified`, R5 `AnswerDeadlineHasPassed`.
- Événements enregistrés par l'agrégat (`OrderPlaced`, `OrderAccepted`, `OrderRejected`) grâce à `Shared/Domain/AggregateRoot` (fourni à l'étape 1). Personne ne les publie encore.
- `place()` reçoit le service du domaine `DeliveryFeeCalculator` (fourni à l'étape 1) : la politique tarifaire combine le panier et le créneau, et n'appartient à aucun des deux.
- Les petits value objects fournis à l'étape 1 : `OrderId` (UUID v7 généré par l'application), `CustomerId`, `RestaurantId` (des références opaques vers d'autres contextes), `Dish`, `OpeningHours`, `RestaurantTerms`.

## Choix à discuter

- **`Dish` existe aussi dans le catalogue**, avec d'autres attributs. Pour la Prise de commande, un plat, c'est un nom, un prix et un restaurant au moment de la commande. Même mot, autre modèle.
- **L'agrégat garantit R1 à R5, pas R6** : l'annulation reste dans `OrderService` (TP 10).
- **R5 n'est tenue qu'à moitié** : l'agrégat refuse une acceptation tardive, mais personne n'annule la commande à l'échéance. C'est l'objet du TP 9.
- `OrderLine` est modifiable via `increaseBy()`, marquée `@internal` : PHP n'a pas d'équivalent au `internal` de C#.

## Toujours là

L'agrégat n'est encore branché nulle part : l'application tourne toujours sur `OrderService` et `App\Entity\Order`. On a construit le modèle en isolation, sous tests.

Suite : [TP 8](docs/tp/tp8-repositories.md).
