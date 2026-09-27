# Étape 3 : repositories (support de la démonstration du TP 8)

## Les repositories

- Le port `src/Ordering/Domain/Repository/OrderRepository.php` : `save()`, `ofId()`, `nextIdentity()`. Aucun type technique.
- Deux adaptateurs : `Infrastructure/InMemory/InMemoryOrderRepository` (pour tester les cas d'usage sans base) et `Infrastructure/Doctrine/DoctrineOrderRepository`.
- Les attributs de mapping sur les classes du domaine : un compromis assumé ([ADR 0001](docs/adr/0001-compromis-regle-des-dependances.md)).
- `tests/Integration/.../DoctrineOrderRepositoryTest` : la commande relue depuis la base est identique à celle enregistrée.

## Autour des repositories

- **Les cas d'usage** : `Application/Command/{PlaceOrder,AcceptOrder,RejectOrder}CommandHandler`, `Application/Query/FindOrderQueryHandler`. Ils orchestrent (charger, appeler l'agrégat, enregistrer) et ne décident rien.
- **Des bus** de commandes, de requêtes et d'événements (`Shared/Application`, adaptés par Symfony Messenger dans `Shared/Infrastructure`).
- **La publication des événements** : après `save()`, chaque handler publie `$order->releaseEvents()`. Les événements sont traités après la fin de la commande. Personne ne les écoute encore : c'est le point de départ du TP 9.
- **Les commandes console réécrites** dans `Ordering/Infrastructure/Console` : elles traduisent la ligne de commande en commande applicative. Plus aucune règle métier dans la console, et plus de validation dupliquée.
- **Le contexte `Catalog`** : restaurants et plats, en CRUD assumé. La Prise de commande le lit à travers une anti-corruption layer ([ADR 0002](docs/adr/0002-catalogue-crud-et-anti-corruption-layer.md)). L'ancien `Restaurant` ne porte plus les commandes.
- **`tests/Architecture/DependencyRulesTest`** : les règles de dépendance vérifiées à chaque lancement des tests. Celles qui isolent la Livraison sont déjà là : elles serviront au TP 9.
- **Le squelette du contexte Livraison** (`src/Delivery/Domain/ValueObject/RunId` et `OrderReference`), point de départ du TP 9.
- `tests/Unit/Ordering/Application/.../PlaceOrderCommandHandlerTest` : un cas d'usage testé avec les adaptateurs en mémoire.

## Le temps, découplé

Toute l'application lit l'heure via `Psr\Clock\ClockInterface` (PSR-20) : `Clock` de Symfony en production, `MockClock` en test. Le Domain la reçoit en paramètre (`$now`). Les dates lues depuis une chaîne passent par `DatePoint::createFromFormat()`. Conséquence visible : dans les tests fonctionnels, le temps est figé au 1er octobre 2026 à 11h00. Ils ne dépendent plus du jour où on les lance, alors qu'aux étapes 0 à 2 l'ancien code lisait l'heure système et les tests devaient ruser (« demain »).

## Choix à discuter

- **Une commande n'est enregistrée qu'une fois validée.** Le panier est un brouillon, en mémoire, jusqu'à `place()`. La Prise de commande ne connaît une commande qu'à partir du moment où le client s'engage.
- **L'identité est générée par l'application** (`nextIdentity()`, UUID v7) et non par la base : l'agrégat a son identité dès sa création.
- Le test fonctionnel de l'étape 0 passe toujours, sans modification : c'est lui qui a rendu ce remaniement sûr.

## Toujours là

`src/Service/OrderService.php` : l'annulation (R6) et l'expiration (R5) écrivent directement dans la table, en contournant l'agrégat.

Suite : [TP 9](docs/tp/tp9-evenements.md).
