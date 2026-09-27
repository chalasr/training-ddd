# TP 8 : repositories (démonstration commentée)

**Durée** : 20 min, questions comprises · **Support** : `git diff origin/etape-2 origin/etape-3`

Pas de code à écrire : le formateur parcourt la branche `etape-3` avec vous. Cette étape touche surtout à l'infrastructure (persistance, configuration), qui n'est pas le sujet de la formation. On regarde ce qui relève du DDD.

## Ce qu'on regarde ensemble

1. **Le port** `src/Ordering/Domain/Repository/OrderRepository.php` : une collection de commandes vue du domaine (`save()`, `ofId()`, `nextIdentity()`). Aucun type technique, et pas de pagination : un repository n'est pas un outil de lecture.
2. **Deux adaptateurs pour un même port** : `Infrastructure/InMemory/InMemoryOrderRepository` (pour tester un cas d'usage sans base, voir `tests/Unit/Ordering/Application/`) et `Infrastructure/Doctrine/DoctrineOrderRepository`.
3. **Les cas d'usage** `Application/Command/*Handler` : charger, appeler l'agrégat, enregistrer, publier les événements. Ils orchestrent, ils ne décident rien.
4. **Le contexte Catalogue**, en CRUD assumé, lu par la Prise de commande à travers une **anti-corruption layer** : `Application/Port/RestaurantCatalog` et `Infrastructure/Catalog/RestaurantCatalogAntiCorruptionLayer` ([ADR 0002](../adr/0002-catalogue-crud-et-anti-corruption-layer.md), visible sur `etape-3`).
5. **Le test d'architecture** `tests/Architecture/DependencyRulesTest.php` : les règles de dépendance, vérifiées à chaque lancement des tests.
6. **Le filet de sécurité** : `tests/Functional/OrderJourneyTest.php`, écrit contre l'ancien code, passe toujours sans modification.

## Questions à se poser

- Pourquoi `nextIdentity()` est-il sur le repository, et pas un identifiant auto-incrémenté par la base ?
- Pourquoi un repository **par agrégat**, et pas un pour `OrderLine` ?
- Une commande encore en brouillon doit-elle être enregistrée ? (Dans Popote, le panier vit côté client jusqu'à la validation.)
- Pourquoi est-ce à la Prise de commande de traduire le modèle du Catalogue, et pas au Catalogue de s'adapter ?

## Pour pratiquer après la formation

Depuis `etape-2`, écrivez le port `OrderRepository`, l'adaptateur en mémoire et un test de cas d'usage qui l'utilise. Comparez ensuite avec `etape-3`.
