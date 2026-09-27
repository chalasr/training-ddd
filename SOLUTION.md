# Étape 5 : refactoring final (solution du TP 10)

## Ce qui a changé

- `Order::cancel($now)` porte R6 : refusé une fois la commande acceptée (`OrderCannotBeCancelledOnceAccepted`), et plus généralement dès que la commande n'attend plus la réponse du restaurant (règle réutilisée : `OrderIsNotAwaitingAnswer`). L'annulation enregistre `OrderCancelled` avec le motif `CustomerRequest`.
- Le cas d'usage `CancelOrderCommand` et sa commande console `Ordering/Infrastructure/Console/CancelOrderConsoleCommand`.
- **`OrderService` est supprimé**, avec la dernière commande console de l'ancien code : `src/Service` et `src/Command` disparaissent.
- Les tests fonctionnels d'annulation écrits contre l'ancien code passent sans modification.

## Bilan du parcours

| | Étape 0 | Étape 5 |
|---|---|---|
| Où sont les règles ? | Dispersées entre service et console, dupliquées | Dans l'agrégat, une exception nommée par règle |
| Qui peut modifier une commande ? | N'importe qui, via les setters | Seulement l'agrégat, via ses méthodes métier |
| L'heure courante | `new \DateTime()` partout | Passée en paramètre, pilotable en test |
| R5 | Un cron qui scanne la table | Une vérification programmée à l'échéance |
| Livraison | `// TODO : prévenir la livraison` | Un contexte qui réagit à un contrat publié |
| Tests | 1 test fonctionnel | Domaine, cas d'usage, intégration, fonctionnel, architecture |

## Pour aller plus loin

- Regrouper deux commandes dans une même course (R7) : quelle politique, dans quel contexte ?
- Rembourser le client : un contexte Paiement derrière une anti-corruption layer, qui écoute `OrderCancelled`.
- R8 et les litiges : un nouveau contexte, un nouveau langage.
