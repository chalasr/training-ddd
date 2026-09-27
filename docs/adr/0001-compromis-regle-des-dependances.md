# ADR 0001 : ce que le Domain a le droit de connaître

## Contexte

Règle des dépendances : le Domain ne dépend ni de l'Application, ni de l'Infrastructure, ni d'un framework. Appliquée à la lettre, elle oblige à décrire la persistance ailleurs (fichiers de mapping séparés), à réécrire des assertions, et à générer les identifiants à la main.

## Décision

On tolère dans le Domain des bibliothèques qui **décrivent** sans rien **exécuter** d'infrastructure (même compromis que le projet de référence [mtarld/apip-ddd](https://github.com/mtarld/apip-ddd), cf. [Violating the dependency rule](https://matthiasnoback.nl/2020/09/violating-the-dependency-rule/)) :

- `webmozart/assert` : assertions de format ;
- `symfony/uid` : génération des UUID ;
- `Doctrine\ORM\Mapping` : attributs de mapping (`#[ORM\Entity]`, `#[ORM\Embeddable]`...) ;
- `doctrine/collections` : collection des lignes de commande.

Restent interdits : `EntityManager`, requêtes, services du framework. `tests/Architecture/DependencyRulesTest` le vérifie.

## Conséquences

- Moins de code et de fichiers ; le modèle et sa persistance se lisent au même endroit.
- Le domaine reste testable sans base ni framework : les attributs sont inertes hors de Doctrine.
- Changer d'ORM obligerait à retoucher les classes du domaine. On l'accepte : ce n'est pas un risque réaliste ici.
