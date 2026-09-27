# Étape 0 : le point de départ

Pas de solution ici : c'est l'application telle qu'on la trouve en arrivant. Elle fonctionne, et le test `tests/Functional/OrderJourneyTest.php` le prouve.

Pour la visite du code, ouvrez dans cet ordre :

1. `src/Entity/Order.php` : que peut-on faire à une commande ? Qui l'empêche de passer de `cancelled` à `accepted` ?
2. `src/Service/OrderService.php` : où sont R1 à R6 ? Combien de fois lit-on l'heure courante ?
3. `src/Command/OrderPlaceCommand.php` : comparez ses vérifications à celles du service.
4. `src/Entity/Restaurant.php` : que charge-t-on pour afficher un restaurant ?
5. `src/Command/OrderExpireUnansweredCommand.php` : comment R5 est-elle tenue ?

Suite : [TP 6](docs/tp/tp6-value-objects.md).
