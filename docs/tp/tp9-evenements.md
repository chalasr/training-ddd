# TP 9 : domain events entre contextes

**Durée** : 45 min · **Partir de** `etape-3` · **Solution** `etape-4`

```
git switch -c tp9-binome-a origin/etape-3
```

Remplacez `binome-a` par le nom de votre binôme.

## Objectif

R7 : « une course est proposée à un coursier dès l'acceptation par le restaurant ». La Livraison est un autre bounded context : elle doit réagir à l'acceptation **sans connaître le modèle de la Prise de commande**.

## Déjà en place sur `etape-3`

- **La publication des événements.** Après `save()`, les handlers publient les événements de l'agrégat (`$this->eventBus->publish(...$order->releaseEvents())`). Ils sont traités après la fin de la commande. Personne ne les écoute encore.
- **Comment on écoute un événement.** Une classe qui implémente `Shared\Application\Event\EventListenerInterface`, avec une méthode `__invoke(OrderAccepted $event)`. **C'est le type du paramètre de `__invoke` qui abonne le listener** à cet événement (l'équivalent d'un `INotificationHandler<OrderAccepted>` de MediatR). Un listener peut lui-même publier via `EventBusInterface`.
- **Aucune configuration à toucher.** Les listeners, et une interface qui n'a qu'une implémentation (votre `RunRepository` en mémoire), sont découverts automatiquement.
- **Le squelette de la Livraison** : `src/Delivery/Domain/ValueObject/RunId` et `OrderReference` (la commande telle que la Livraison la voit : une simple référence).
- **Les règles d'isolation**, déjà actives dans `tests/Architecture/DependencyRulesTest.php` : la Livraison ne connaît de la Prise de commande que `App\Ordering\PublishedLanguage` ; la Prise de commande ignore la Livraison ; le contrat public ne contient aucun type interne. **Lisez-les avant de coder.**

## À faire

1. **Publier un contrat, pas son modèle.** Créez `src/Ordering/PublishedLanguage/OrderAcceptedIntegrationEvent`, uniquement des scalaires : `orderId`, `restaurantId`, `customerId`, `deliverySlotStart`, `deliverySlotEnd`, `acceptedAt` (dates au format ISO 8601). Le contrat sert tous les consommateurs à venir, pas seulement la Livraison.
   Puis un listener dans la Prise de commande, `Application/EventListener/PublishIntegrationEventWhenOrderAccepted`, qui traduit `OrderAccepted` (interne) en `OrderAcceptedIntegrationEvent` (public) et le publie.

2. **L'agrégat `Run`** (une course), dans `src/Delivery/Domain/Model/` :
   - `Run::propose(RunId $id, OrderReference $order, \DateTimeImmutable $deliverBy)` : `$deliverBy` est la **fin** du créneau de livraison ;
   - `addOrder(OrderReference $order)` : une course transporte **au plus deux** commandes (R7), la troisième lève une exception ;
   - lecture : `id()`, `orders()`, `deliverBy()`.
   On ne regroupe pas encore les commandes : chaque acceptation crée sa propre course. `addOrder()` est testé unitairement.

3. **La réaction de la Livraison** : un `Domain/Repository/RunRepository` (`save()`, `nextIdentity()`), son implémentation `Infrastructure/InMemory/InMemoryRunRepository`, et `Application/EventListener/CreateRunWhenOrderAccepted`, qui écoute `OrderAcceptedIntegrationEvent`.

4. **L'erreur volontaire.** Faites écouter `OrderAccepted` (l'événement interne) directement par la Livraison. Lancez les tests : lequel devient rouge, et que dit-il ? Remettez le contrat public.

### Tests à faire passer

- `tests/Unit/Delivery/Domain/Model/RunTest.php` : `test_a_run_carries_up_to_two_orders`, `test_a_run_cannot_carry_a_third_order`.
- Les tests d'architecture, verts à la fin.
- Bonus : un test fonctionnel qui passe et accepte une commande (`self::runCommand(...)`, comme dans `tests/Functional/OrderJourneyTest.php`), puis vérifie qu'une course existe dans le `RunRepository` (récupéré dans le conteneur). Le repository en mémoire suffit.

## Questions à se poser

- Pendant l'erreur volontaire, le test fonctionnel passait-il ? Qu'est-ce que ça dit de la valeur d'un test d'architecture ?
- Pourquoi ne pas laisser la Livraison écouter directement `OrderAccepted` ? Qu'est-ce qui casserait le jour où on refactore `Order` ?
- Qui est upstream, qui est downstream ? Quel pattern de la Context Map est-ce ? Comparez avec la façon dont la Prise de commande lit le Catalogue.
- Que se passe-t-il si la création de la course échoue ? La commande est-elle quand même acceptée ?

## Travailler avec l'IA

- Montrez à l'IA les règles d'isolation **avant** de lui faire écrire le contexte Livraison : elles lui donnent la règle du jeu.
- Si l'IA importe une classe `App\Ordering\Domain\...` dans `src/Delivery`, c'est exactement l'erreur que le TP veut faire sentir.

## Démonstration du formateur : R5 en asynchrone

« Sans réponse du restaurant dans les 5 minutes, la commande est annulée. » La solution programme une commande `ExpireOrderIfNotAnsweredCommand` à l'échéance, exécutée par un worker (`bin/console messenger:consume async`). Le domaine fixe l'échéance (`OrderPlaced::answerDeadline`) et décide (`Order::expireIfNotAnswered()`) ; l'infrastructure ne fait que porter le minuteur. Le cron de l'ancien code disparaît.
