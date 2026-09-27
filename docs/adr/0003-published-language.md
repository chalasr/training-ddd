# ADR 0003 : la Prise de commande publie un contrat (Published Language)

## Contexte

R7 : la Livraison doit proposer une course dès qu'un restaurant accepte une commande. Elle doit donc apprendre l'acceptation, qui se produit dans la Prise de commande. La Prise de commande est upstream, la Livraison downstream. D'autres contextes suivront (Notifications, Facturation, Litiges).

## Décision

**Open Host Service + Published Language** : la Prise de commande publie un contrat stable, `App\Ordering\PublishedLanguage\OrderAcceptedIntegrationEvent`.

- Il **appartient à l'upstream** (comme un package `Ordering.Contracts` en .NET), d'où son emplacement sous `src/Ordering`.
- Il ne contient **que des scalaires** (identifiants, dates ISO 8601) : sérialisable, versionnable, aucun type interne d'Ordering.
- La Livraison ne connaît d'Ordering **que ce namespace** (vérifié par `DependencyRulesTest`).

C'est ce que l'on fait avec Kafka et un Schema Registry, un package de contrats partagé (MassTransit, NServiceBus) ou les `IntegrationEvent` d'eShopOnContainers.

## Alternatives écartées

- **Conformist** : la Livraison écoute directement `OrderAccepted`. Elle dépendrait alors du modèle interne d'Ordering, et chaque refactoring de l'agrégat la casserait.
- **Shared Kernel** : mettre l'événement dans `src/Shared`. La propriété devient partagée, et les deux équipes doivent se mettre d'accord sur chaque changement. À réserver à un petit modèle, stable et réellement co-possédé.
- **Anti-corruption layer côté Livraison** : utile quand l'upstream ne fait aucun effort pour ses consommateurs (legacy, prestataire externe). Ordering est notre Core et aura plusieurs consommateurs : c'est à lui de publier un contrat, une fois pour tous.

## Conséquences

- Ordering peut refactorer `Order` et `OrderAccepted` librement tant que le contrat est respecté.
- Un changement du contrat est un acte délibéré (nouvelle version, période de compatibilité).
- Coût : une classe de contrat et un listener de traduction.
