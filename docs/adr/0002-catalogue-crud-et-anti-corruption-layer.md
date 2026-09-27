# ADR 0002 : un Catalogue en CRUD, lu à travers une anti-corruption layer

## Contexte

La Prise de commande a besoin du Catalogue : le prix des plats, le montant minimum et les horaires du restaurant. Le Catalogue est un sous-domaine **de support** : de la saisie de données, sans règle métier complexe.

## Décision

- **Le Catalogue reste en CRUD** (`src/Catalog` : entités à getters et setters, mapping direct). Il ne mérite pas de modèle riche ; plusieurs styles d'architecture cohabitent dans le même projet.
- **La Prise de commande se protège** : elle définit ce dont elle a besoin dans son propre langage (le port `Ordering/Application/Port/RestaurantCatalog`, qui renvoie `Dish` et `RestaurantTerms`) et une **anti-corruption layer** (`Ordering/Infrastructure/Catalog/RestaurantCatalogAntiCorruptionLayer`) traduit le modèle du Catalogue.
- C'est le seul endroit d'Ordering qui connaît une classe du Catalogue (vérifié par `DependencyRulesTest`).

## Alternatives écartées

- **Conformist** (Ordering utilise directement les entités du Catalogue) : le modèle CRUD, avec ses `int` en centimes, ses horaires en chaînes et ses flags, s'infiltrerait dans le cœur du métier.
- **Published Language côté Catalogue** : il faudrait que le Catalogue fasse l'effort de publier un contrat. Pour un contexte de support qu'on veut garder simple, c'est au consommateur de traduire.

## Conséquences

- Le Catalogue peut évoluer (renommer une colonne, changer de format d'horaires) : seule l'ACL change.
- En test, un `InMemoryRestaurantCatalog` remplace le Catalogue.
