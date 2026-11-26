# Brevo pour SymSvel

<!-- symsvel-brand:start -->
<p align="center">
  <a href="https://symsvel.com/"><img src="https://raw.githubusercontent.com/Symsvel-Framework/symsvel-tooling/07264829c404bcaecf1d8ce25c6a71e5beab2ae9/resources/documentation/theme/mark.png" alt="SymSvel Framework" width="48"></a>
  &nbsp;&nbsp;
  <a href="https://doc.symsvel.com/"><img src="https://raw.githubusercontent.com/Symsvel-Framework/symsvel-tooling/07264829c404bcaecf1d8ce25c6a71e5beab2ae9/resources/documentation/theme/zeko.webp" alt="Zeko, le gecko SymSvel" width="88"></a>
</p>
<p align="center"><a href="https://symsvel.com/">Site officiel</a> · <a href="https://doc.symsvel.com/">Documentation développeur</a></p>
<!-- symsvel-brand:end -->

Module **optionnel** de transport d’e-mails transactionnels par l’API Brevo.

Il s’intègre aux modules **Mails** (choix du transport et identité expéditrice) et **Settings** (chiffrement de la clé API). L’envoi réel s’appuie sur le bridge officiel `symfony/brevo-mailer` : aucune requête HTTP métier n’est réimplémentée dans SymSvel.

## Installation

Depuis un projet SymSvel **créé avec `symsvel create-project --dev`**, via la CLI unique :

```bash
symsvel require symsvel-framework/module-brevo
```

Le canal `dev` est déjà enregistré dans le projet : la CLI choisit automatiquement `dev-dev` et le dépôt GitHub du module. Ne pas passer `--dev` à `symsvel require`. Cette installation de développement n'exige pas que Brevo soit publié sur Satis. Les dépendances Mails et Settings doivent toutefois être installées dans leurs versions de développement compatibles.

**Canal stable :** une installation via Satis exige une release fonctionnelle de Brevo **et** des releases compatibles de Mails et Settings. Leurs tags `0.1.0` actuels sont des squelettes initiaux qui ne fournissent pas encore les contrats nécessaires. Il serait incorrect de publier Brevo `1.0.0` avec ses exigences `dev-dev` tant que cette chaîne n'est pas stabilisée.

Configurer ensuite dans le BO :

1. **Paramètres → Brevo** : `brevo.api_key`, clé API transactionnelle v3 ;
2. **Paramètres → Mails** : expéditeur `mails.sender_email` et éventuellement `mails.sender_name` ;
3. **Paramètres → Mails** : transport `mails.transport = brevo`.

Une fois la recette du module appliquée, Brevo apparaît parmi les choix de transport. La présence du choix ne valide ni la clé ni la connexion à Brevo. Si l'installation n'a pas de clé API, l'envoi est refusé explicitement. Aucun mécanisme de repli vers Mailpit ou SMTP n'est déclenché automatiquement.

Le module ne possède **aucune table, migration, écran dédié ou endpoint de diagnostic**. Les réglages sont affichés par le BO Settings existant. L'adresse et le nom d'expéditeur restent déclarés par Mails, et les templates sont gérés par les modules émetteurs.

## État

Implémentation du transport sur `dev`, avec CI automatisée verte et tests de requêtes HTTP simulées. Crash-tests locaux d'installation Composer/Flex/Satis et envoi avec clé Brevo réelle encore nécessaires. La PR draft vers `main` ne doit pas être mergée et aucune release `1.0.0` ne doit être créée avant validation de la chaîne Mails/Settings/Recette.

## Intégration continue GitHub-hosted (22/11/2026)

Le workflow public `.github/workflows/qa.yml` utilise désormais **GitHub-hosted Ubuntu 24.04** au lieu des runners Docker du mainteneur. Les tests Composer et PHP 8.4 restent actifs. Les tests d'interface installent Chromium et ses dépendances système lorsque nécessaire.

Les runners auto-hébergés sont réservés à `symsvel-packages` et `symsvel-recipes`, qui restent privés. Une CI verte ne dispense pas des crash-tests locaux.

## Licence MIT et contributions

Ce dépôt est distribué sous [licence MIT](LICENSE). Le code peut être utilisé, modifié et redistribué, y compris dans des projets commerciaux ou propriétaires, dans les conditions de cette licence (notamment la conservation de la notice de copyright et de licence).

**Contribuer :** créer un fork, puis proposer une pull request vers `dev` lorsque cette branche existe, sinon vers la branche par défaut. Expliquer la modification et fournir les tests et la documentation concernés. Les mainteneurs conservent la maîtrise des revues, des validations (CI et crash-tests), de l'intégration et des releases officielles. Une PR ne garantit pas son acceptation.

**Versions officielles :** seules les versions diffusées par l'organisation [Symsvel-Framework](https://github.com/Symsvel-Framework) sont officielles. Les forks sont libres sous MIT, mais ne doivent pas être présentés comme officiellement approuvés par les mainteneurs. Cette distinction n'ajoute aucune restriction aux droits accordés par la licence MIT sur le code.
