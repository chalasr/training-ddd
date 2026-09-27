# TP 10 : refactoring final (démonstration en direct)

**Durée** : 20 min, questions comprises · **Départ** `etape-4` · **Arrivée** `etape-5`

Le formateur fait ce refactoring en direct, avec son assistant IA, test d'abord. Suivez, questionnez, proposez : c'est vous qui décidez de la modélisation. Si vous voulez le refaire vous-mêmes après la formation, tout est ci-dessous.

## Le point de départ

Il reste un morceau de l'ancien code : `src/Service/OrderService.php`, avec l'annulation client (R6). Elle écrit directement dans la table en **contournant l'agrégat** : aucune règle vérifiée par le domaine, aucun événement publié. On la migre dans l'agrégat, puis on supprime `OrderService`.

## Les étapes, test d'abord

1. Écrire les tests dans `tests/Unit/Ordering/Domain/Model/OrderTest.php`, et les regarder échouer :
   - `test_the_customer_cancels_an_order_the_restaurant_has_not_accepted_yet` (et l'événement `OrderCancelled` est enregistré)
   - `test_an_accepted_order_cannot_be_cancelled`
   - `test_a_rejected_order_cannot_be_cancelled`
2. Implémenter `Order::cancel(\DateTimeImmutable $now)`.
3. Créer le cas d'usage `CancelOrderCommand` et son handler, sur le modèle d'`AcceptOrderCommandHandler`.
4. Remplacer la commande console `src/Command/OrderCancelCommand.php` par `src/Ordering/Infrastructure/Console/CancelOrderConsoleCommand.php`.
5. Supprimer `OrderService`.

**Le filet de sécurité** : `tests/Functional/OrderJourneyTest.php` contient deux tests d'annulation, écrits contre l'ancien code. Ils doivent rester verts sans être modifiés.

## Questions à se poser

- Quelle règle existante de l'agrégat peut-on réutiliser pour « tant que le restaurant n'a pas répondu » ?
- `OrderCancelled` existe déjà (R5). Comment distinguer les deux causes d'annulation ?
- Qu'est-ce qui a rendu cette migration sûre ? Et qu'aurait-il fallu si ces tests fonctionnels n'existaient pas ?
- Qu'est-ce que l'IA a fait vite, et qu'est-ce qu'il a fallu décider pour elle ?
