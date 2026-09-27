# Étape 4 : domain events entre contextes (solution du TP 9)

## Ce qui a changé

- **Rappel (en place depuis l'étape 3)** : chaque handler publie `$order->releaseEvents()` après `save()`. Les événements sont traités **après la fin de la commande** : un contexte abonné ne voit jamais un fait qui n'a pas été enregistré.
- **Le contrat public** `src/Ordering/PublishedLanguage/OrderAcceptedIntegrationEvent` : que des scalaires. Le listener `Ordering/Application/EventListener/PublishIntegrationEventWhenOrderAccepted` traduit l'événement interne en contrat public ([ADR 0003](docs/adr/0003-published-language.md)).
- **Le contexte Livraison** (`src/Delivery`) : l'agrégat `Run` (au plus deux commandes, R7 ; attribué à un seul coursier), `CreateRunWhenOrderAccepted`, ses repositories et `bin/console app:delivery:runs`.
- **Les règles d'isolation** (présentes depuis l'étape 3) restent vertes : la Livraison ne connaît d'Ordering que `PublishedLanguage` ; Ordering ignore la Livraison ; le contrat ne contient aucun type interne.
- **En plus du TP** : `Run` persisté en base, `assignTo(CourierId)`, la commande `app:delivery:runs`, un test fonctionnel de bout en bout.
- **R5 en asynchrone** : `OrderPlaced` porte l'échéance (`answerDeadline`). `ScheduleExpirationWhenOrderPlaced` programme `ExpireOrderIfNotAnsweredCommand` à cette échéance (transport `async`, en base). Le worker l'exécute, et `Order::expireIfNotAnswered()` décide : sans réponse, la commande est annulée (`OrderCancelled`). Le cron de l'ancien code est supprimé.

Voir la programmation en action : passez une commande, puis lancez le worker. Cinq minutes plus tard, la commande est annulée.

```
bin/console messenger:consume async -vv
```

## Le temps, découplé

Toute l'application lit l'heure via `Psr\Clock\ClockInterface` (PSR-20) : `Clock` de Symfony en production, `MockClock` en test. Le Domain la reçoit en paramètre (`$now`). Les dates lues depuis une chaîne passent par `DatePoint::createFromFormat()`. Conséquence visible : dans les tests fonctionnels, le temps est figé au 1er octobre 2026 à 11h00. Ils ne dépendent plus du jour où on les lance, alors qu'aux étapes 0 à 2 l'ancien code lisait l'heure système et les tests devaient ruser (« demain »).

## Choix à discuter

- **Événement du domaine ou événement d'intégration ?** `OrderAccepted` est interne et libre d'évoluer ; `OrderAcceptedIntegrationEvent` est un contrat, qui change rarement et prudemment.
- **Consistance éventuelle** : la course est créée après l'acceptation, pas dans la même opération. Si sa création échoue, la commande reste acceptée ; il faudra rejouer la réaction (le transport async, avec ses tentatives, est fait pour ça).
- **Le domaine décide, l'infrastructure minute** : la règle des 5 minutes est dans l'agrégat ; le délai d'exécution est un détail technique. `expireIfNotAnswered()` est sans effet si le restaurant a répondu entre-temps.
- `Run` ne regroupe pas encore deux commandes automatiquement : l'agrégat sait le faire (`addOrder()`), la politique de regroupement reste à écrire.

## Toujours là

`OrderService::cancelOrder()`, en SQL direct. Conséquence visible : une annulation ne publie aucun événement.

Suite : [TP 10](docs/tp/tp10-refactoring-final.md).
