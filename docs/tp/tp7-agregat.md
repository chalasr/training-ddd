# TP 7 : l'agrégat Order

**Durée** : 15 min (7a) + 45 min (7b, de part et d'autre du déjeuner) · **Partir de** `etape-1` · **Solution** `etape-2`

```
git switch -c tp7-binome-a origin/etape-1
```

Remplacez `binome-a` par le nom de votre binôme.

## 7a. Au tableau (15 min, sans code)

1. Reprenez les règles R1 à R6 (`docs/regles-metier.md`). Pour chacune : l'agrégat `Order` peut-il la garantir **seul**, avec ce qu'il contient **ou avec ce qu'on lui passe au moment de l'opération** (par exemple les conditions du restaurant) ? Sinon, qui la garantit ? Attention à R5 : que se passe-t-il si le restaurant ne répond jamais ?
2. Ouvrez `src/Entity/Restaurant.php` : cet « agrégat » contient la carte, les commandes et les avis. Quels problèmes cela pose-t-il (chargement, concurrence, cohérence) ? Découpez-le. Qui référence qui, et par quoi ?

## 7b. Coder l'agrégat (30 min avant le déjeuner, 15 min après)

**Déjà fourni sur `etape-1`** (lisez-le, ne le recodez pas) :

- `src/Shared/Domain/AggregateRoot.php` : `recordThat()` pour enregistrer un événement, `releaseEvents()` pour les récupérer ;
- les petits value objects `OrderId`, `CustomerId`, `RestaurantId`, `Dish` (le plat tel que la commande le voit : id, nom, restaurant, prix), `OpeningHours`, `RestaurantTerms` (montant minimum + horaires) ;
- le service du domaine `Domain/Service/DeliveryFeeCalculator` (les frais de livraison, extraits d'`OrderService`).

**À vous**, dans `src/Ordering/Domain/` : `Model/Order` (racine), `Model/OrderLine` (entité interne), `Model/OrderStatus` (enum : `Draft`, `Placed`, `Accepted`, `Rejected`, `Cancelled`), les événements `Event/OrderPlaced`, `OrderAccepted`, `OrderRejected`, et les exceptions.

### L'API attendue

Orientée cas d'usage, **sans aucun setter** :

```php
// Comportement
Order::draft(OrderId $id, CustomerId $customerId, RestaurantId $restaurantId): Order
$order->addLine(Dish $dish, Quantity $quantity): void    // R1, R4. Le même plat deux fois : les quantités s'additionnent
$order->removeLine(string $dishId): void                 // R4. Plat absent : sans effet
$order->place(DeliverySlot $slot, RestaurantTerms $terms,
              DeliveryFeeCalculator $deliveryFees, \DateTimeImmutable $now): void
                                                         // Panier non vide, R2 (hors frais), R3 (horaires)
$order->accept(\DateTimeImmutable $now): void            // R5 : au plus 5 minutes après la validation (5 min pile : accepté)
$order->reject(\DateTimeImmutable $now): void            // Pas de délai pour refuser : un refus tardif a le même effet qu'une expiration

// Lecture (pas de setter, pas de collection modifiable)
$order->id(), status(), lines() /* list<OrderLine> */, subtotal(), deliveryFee(), total(), deliverySlot()
```

Précisions :

- Dans `place()`, `$now` date la validation : c'est le point de départ des 5 minutes de R5. Le délai de 30 minutes du créneau est déjà vérifié par `DeliverySlot::startingAt()`.
- `place()` calcule et retient les frais de livraison : `total()` = articles + frais.
- La ligne de commande copie le nom et le prix du plat au moment de l'ajout. Son identité dans la commande, c'est l'identifiant du plat.
- Une exception nommée d'après la règle ou l'état violé : `OrderMustConcernASingleRestaurant` (R1), `OrderIsBelowMinimumAmount` (R2), `DeliverySlotIsOutsideOpeningHours` (R3), `OrderCannotBeModified` (R4), `AnswerDeadlineHasPassed` (R5), `EmptyOrderCannotBePlaced`, et `OrderIsNotAwaitingAnswer` (accepter ou refuser une commande qui n'attend pas de réponse). L'IA peut les générer, relisez juste leur nom.
- Événements : `OrderPlaced` (avec l'échéance de réponse), `OrderAccepted`, `OrderRejected`.

**L'annulation client (R6) n'est pas au programme : elle reste dans `OrderService` jusqu'au TP 10.**

### Les tests

Point de départ, à copier dans `tests/Unit/Ordering/Domain/Model/OrderTest.php` :

```php
<?php

declare(strict_types=1);

namespace App\Tests\Unit\Ordering\Domain\Model;

use App\Ordering\Domain\Model\Order;
use App\Ordering\Domain\Service\DeliveryFeeCalculator;
use App\Ordering\Domain\ValueObject\CustomerId;
use App\Ordering\Domain\ValueObject\DeliverySlot;
use App\Ordering\Domain\ValueObject\Dish;
use App\Ordering\Domain\ValueObject\Money;
use App\Ordering\Domain\ValueObject\OpeningHours;
use App\Ordering\Domain\ValueObject\OrderId;
use App\Ordering\Domain\ValueObject\Quantity;
use App\Ordering\Domain\ValueObject\RestaurantId;
use App\Ordering\Domain\ValueObject\RestaurantTerms;
use PHPUnit\Framework\TestCase;

final class OrderTest extends TestCase
{
    public function test_an_order_only_concerns_one_restaurant(): void {}
    public function test_adding_the_same_dish_twice_adds_up_the_quantities(): void {}
    public function test_an_empty_order_cannot_be_placed(): void {}
    public function test_an_order_below_the_restaurant_minimum_cannot_be_placed(): void {}
    public function test_the_minimum_amount_does_not_include_the_delivery_fee(): void {}
    public function test_the_delivery_slot_must_fall_within_opening_hours(): void {}
    public function test_a_placed_order_cannot_be_modified(): void {}
    public function test_placing_an_order_records_that_it_was_placed(): void {}
    public function test_the_restaurant_cannot_accept_once_the_five_minutes_have_passed(): void {}
    public function test_an_order_cannot_be_accepted_twice(): void {}

    // Il est 11h00 le 1er octobre. Chez Ginette : minimum 15 €, ouvert de 11h30 à 22h30.

    private function draftAtGinette(): Order
    {
        return Order::draft(OrderId::generate(), CustomerId::fromString('alice'), RestaurantId::fromString('chez-ginette'));
    }

    private function placedOrder(): Order
    {
        $order = $this->draftAtGinette();
        $order->addLine($this->blanquette(), Quantity::of(1));
        $order->place($this->slotAt('12:30'), $this->ginetteTerms(), new DeliveryFeeCalculator(), $this->now());

        return $order;
    }

    private function blanquette(): Dish
    {
        return Dish::of('blanquette', 'Blanquette de veau', RestaurantId::fromString('chez-ginette'), Money::ofCents(1650));
    }

    private function tarteTatin(): Dish
    {
        return Dish::of('tarte-tatin', 'Tarte Tatin', RestaurantId::fromString('chez-ginette'), Money::ofCents(600));
    }

    private function ginetteTerms(): RestaurantTerms
    {
        return RestaurantTerms::of(Money::ofCents(1500), OpeningHours::between('11:30', '22:30'));
    }

    private function slotAt(string $time): DeliverySlot
    {
        return DeliverySlot::startingAt(new \DateTimeImmutable('2026-10-01 '.$time), $this->now());
    }

    private function now(): \DateTimeImmutable
    {
        return new \DateTimeImmutable('2026-10-01 11:00');
    }
}
```

Quelques données utiles : deux tartes Tatin font 12 € (sous le minimum de 15 €), une blanquette 16,50 € (au-dessus). Un créneau à 22h30 finit à 22h45, après la fermeture.

## Travailler avec l'IA

- Donnez à l'IA votre résultat du 7a : la liste des invariants que l'agrégat garantit. C'est votre décision, pas la sienne.
- Refusez tout setter, toute propriété publique modifiable, tout `getLines()` qui renvoie la collection modifiable.
- Vérifiez que chaque règle violée lève une exception **nommée** d'après la règle.

## Pour le débrief

Une `OrderLine` modifiable est-elle un risque, sachant que `$order->lines()` la rend accessible ? Quelles options (et que ferait-on en C# avec `internal`) ?
