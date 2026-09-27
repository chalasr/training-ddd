# Règles métier de Popote

Ce que l'expert métier nous a dit. Le formateur joue l'expert : posez-lui vos questions, certaines règles ne sont données que si on les demande.

| | Règle |
|---|---|
| R1 | Une commande ne concerne qu'un seul restaurant. |
| R2 | Chaque restaurant fixe un montant minimum de commande (hors frais de livraison). |
| R3 | Le client choisit un créneau de livraison de 15 minutes, au plus tôt 30 minutes après la validation, pendant les horaires d'ouverture du restaurant. |
| R4 | Tant que la commande n'est pas validée, le client modifie librement son panier. Après validation, le contenu est figé. |
| R5 | Le restaurant accepte ou refuse dans les 5 minutes. Sans réponse, la commande est annulée et le client remboursé. |
| R6 | Le client annule sans frais tant que le restaurant n'a pas accepté. Ensuite, l'annulation n'est plus possible. |
| R7 | Une course est proposée à un coursier dès l'acceptation par le restaurant. Un coursier transporte au plus deux commandes à la fois. |
| R8 | Un litige s'ouvre au plus tard 24 h après la livraison. Le remboursement ne dépasse pas le montant des articles concernés. |

## Frais de livraison

2,90 €, plus 1 € sur les créneaux de rush (12h00 à 13h30 et 19h00 à 20h30). Offerts à partir de 30 € d'articles.

## Données de démonstration (`app:catalog:seed`)

| Restaurant | Id | Horaires | Minimum | Plats |
|---|---|---|---|---|
| Chez Ginette | `chez-ginette` | 11:30 à 22:30 | 15,00 € | `blanquette` 16,50 €, `salade-lyonnaise` 9,50 €, `tarte-tatin` 6,00 € |
| Sushi Kaze | `sushi-kaze` | 12:00 à 22:00 | 20,00 € | `plateau-sushi` 22,90 €, `maki-saumon` 6,50 €, `mochi` 4,00 € |
| Pizzeria Nonna | `pizzeria-nonna` | 11:00 à 23:00 | 12,00 € | `margherita` 11,00 €, `tiramisu` 5,50 € |
