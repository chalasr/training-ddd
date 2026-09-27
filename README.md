# Popote : projet de travaux pratiques, formation Domain-Driven Design

Popote est une plateforme de livraison de repas dans une ville. Elle met en relation des clients, des restaurants partenaires et des coursiers indépendants. Son argument commercial : **le créneau de livraison choisi par le client est tenu**.

Ce dépôt est le code des travaux pratiques du jour 2. Il part d'une application au **modèle anémique** (branche `etape-0`) et la fait évoluer, TP après TP, vers un modèle riche organisé par bounded context.

- Les règles métier : [docs/regles-metier.md](docs/regles-metier.md)
- Les énoncés des TP : [docs/tp/](docs/tp/)
- PHP en une page pour les développeurs C# : [docs/rappel-php.md](docs/rappel-php.md)

## Prérequis

- **Git** et **Docker Desktop installé et démarré** (sous Windows : backend WSL 2, proposé par défaut). C'est tout : pas besoin de PHP ni de base de données sur votre machine.
- Vérification : `git --version` et `docker run --rm hello-world`
- Un éditeur : VS Code (extension **PHP Intelephense**, qui fonctionne sans PHP installé) ou PhpStorm.
- Votre assistant IA habituel (Claude Code, Copilot...). Il lira [AGENTS.md](AGENTS.md), qui décrit les conventions du projet.

## Installation (une seule fois)

```
git clone https://github.com/chalasr/training-ddd.git popote
cd popote
docker compose build
docker compose run --rm php composer install
docker compose run --rm php bin/console doctrine:schema:create
docker compose run --rm php bin/console app:catalog:seed
```

Pour ne pas retaper `docker compose run --rm php` devant chaque commande, ouvrez un shell dans le conteneur :

```
docker compose run --rm php bash
```

Toutes les commandes ci-dessous se lancent ensuite telles quelles dans ce shell.

## Utiliser l'application

Il n'y a pas d'interface web : une commande console par cas d'usage.

```
bin/console app:catalog:list
bin/console app:order:place alice chez-ginette "2027-01-15 12:30" blanquette tarte-tatin:2
bin/console app:order:show <id-de-la-commande>
bin/console app:order:accept <id-de-la-commande>
bin/console app:order:reject <id-de-la-commande>
bin/console app:order:cancel <id-de-la-commande>
```

Remplacez `<id-de-la-commande>` par l'identifiant affiché par `app:order:place` (sans les chevrons). Le créneau doit commencer au moins 30 minutes plus tard : la date doit être à venir.

## Vérifier son travail

```
vendor/bin/phpunit                  # tous les tests
vendor/bin/phpunit --testdox        # les tests, lus comme des phrases métier
vendor/bin/phpstan analyse          # analyse statique (niveau 8)
```

## Déroulé des TP

Chaque étape corrigée a sa branche. **Chaque TP démarre de la branche de l'étape précédente**, dans une branche à vous (le préfixe `origin/` est nécessaire : après le clone, seule `etape-0` existe en local) :

```
git switch -c tp6-binome-a origin/etape-0
```

| TP | Sujet | Partir de | Solution |
|---|---|---|---|
| [TP 6](docs/tp/tp6-value-objects.md) | Value objects | `etape-0` | `etape-1` |
| [TP 7](docs/tp/tp7-agregat.md) | Agrégat Order et invariants | `etape-1` | `etape-2` |
| [TP 8](docs/tp/tp8-repositories.md) | Repositories et anti-corruption layer (démonstration commentée) | `etape-2` | `etape-3` |
| [TP 9](docs/tp/tp9-evenements.md) | Domain events entre contextes | `etape-3` | `etape-4` |
| [TP 10](docs/tp/tp10-refactoring-final.md) | Refactoring final (démonstration en direct) | `etape-4` | `etape-5` |

En retard ou bloqué ? Repartez de la solution : `git switch -c tp8-binome-a origin/etape-2`. Chaque branche contient un `SOLUTION.md` qui explique ses choix. Pour voir exactement ce qu'une étape a changé : `git diff origin/etape-1 origin/etape-2`.

**Après chaque changement de branche**, repartez d'une base de dev neuve : `docker compose run --rm php composer reset-db` (ou `composer reset-db` dans le shell du conteneur). La structure de la base change d'une étape à l'autre ; les données de dev sont perdues, sans conséquence (les tests utilisent leur propre base).

## En cas de problème

- **`docker: command not found` ou erreur de connexion** : Docker Desktop n'est pas démarré. Lancez-le et attendez que l'icône soit verte.
- **Lenteur sous Windows** : clonez le dépôt dans le système de fichiers WSL (`~/` sous Ubuntu) plutôt que dans `C:\Users\...`.
- **Fins de ligne** : le dépôt force LF via `.gitattributes`. Si Git convertit quand même, vérifiez `git config core.autocrlf` (mettez `input` ou `false`).
- **Proxy ou réseau d'entreprise qui bloque Docker Hub ou Packagist** : prévenez le formateur.
