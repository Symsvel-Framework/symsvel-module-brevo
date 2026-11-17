# Module Brevo : fournisseur Symfony Mailer optionnel

## Architecture validée (17/11/2026)

Ce module reprend le périmètre de `xcore-legacy-module-brevo` sans recopier son client HTTP. XCore gérait lui-même les appels `POST https://api.brevo.com/v3/smtp/email`, les destinataires, HTML/texte et les pièces jointes. Symfony 8.1 fournit désormais le bridge officiel `symfony/brevo-mailer`, qui prend en charge ces opérations ; Brevo n'a pas vocation à en dupliquer l'implémentation.

`BrevoBundle` déclare trois services distincts :
- `BrevoTransportProvider`, tag `symsvel.mails.transport_provider`, ajoute **brevo** aux transports nommés proposés par Mails ;
- `BrevoSettingDefinitionProvider`, tag `symsvel.settings.definition_provider`, fournit `api_key` dans le namespace propriétaire `brevo`, donc `brevo.api_key`, secret, privé, non bloquant à l'installation ;
- `BrevoSettingsTransportFactory`, tag natif `mailer.transport_factory`, reconnaît exclusivement `symsvel-brevo://default`, fabrique un `DeferredBrevoTransport` **sans consulter la clé**. C'est seulement lors de `send()` que ce transport lit `brevo.api_key` via `SettingManager`, construit le DSN `brevo+api://KEY@default` et délègue au `BrevoTransportFactory` natif.

Le bundle ajoute `framework.mailer.transports.brevo = symsvel-brevo://default` dans `prependExtension`, uniquement lorsqu'il est installé. Cette configuration doit être vérifiée par un crash-test Symfony du Back réellement assemblé ; la recette Flex n'ajoute aucun endpoint ni route. Aucune valeur sensible n'est stockée dans le code, `module.json` ou la recette.

## Configuration et responsabilité

Les clés de Settings sont :
- `brevo.api_key` : identifiant de l'API Brevo v3, fourni et géré dans le BO Settings, chiffré au repos par Settings ;
- `mails.transport` : choix `brevo` parmi les providers Mails, toujours facultatif au Setup ;
- `mails.sender_email` et `mails.sender_name` : expéditeur commun géré par Mails. Contrairement à XCore, Brevo ne déclare pas d'expéditeur concurrent et n'ajoute aucun fallback.

Lorsqu'un mail transactionnel est envoyé par `ConfiguredMailSender`, Mails fixe `X-Transport: brevo` et Symfony Mailer choisit le transport nommé. La route Messenger `SendEmailMessage: sync` du Back fait tenter l'envoi dans la requête, conformément à la décision prise le 17/11/2026 ; Brevo n'ajoute ni job Doctrine, ni queue, ni runner. La sélection de Mailpit doit continuer à fonctionner sans clé Brevo : le provider reste optionnel et n'effectue aucun appel réseau au démarrage. L'absence de clé alors que Brevo est choisi déclenche une exception explicite, sans révéler la clé.

Symfony Brevo Mailer gère nativement MIME, pièces jointes, destinataires, transport HTTPS et erreurs d'API ; aucune fonction transactionnelle App/Auth/BO ne réside dans ce module. La clé est relue pour **chaque envoi Brevo**. Même lorsqu'un conteneur Symfony initialise tous les transports nommés, une clé absente n'empêche donc ni Mailpit ni SMTP de fonctionner. Le test `BrevoSettingsTransportFactoryTest` vérifie que la création du transport est possible sans clé et que seule la tentative d'envoi Brevo échoue. Les workers durables devront être testés séparément si utilisés à l'avenir.

## Installation et compatibilité

Le package Composer est `symsvel-framework/module-brevo`, module `Brevo` de schéma 1, dépendant de `mails` et `settings`. Les dépendances de développement utilisent actuellement les branches `dev-dev` de Mails/Settings ; l'ancien squelette `SymsvelModuleBrevo` ne doit pas être publié tel quel. Une recette Flex propre déclare `SymSvel\Module\Brevo\BrevoBundle` sans route inutile. La publication effective sur le dépôt central de recettes et le registry privé doit être vérifiée avant tout `symsvel require` sur CT ; aucune modification de `main` n'est effectuée dans cette passe.

## Validation et tests

`BrevoBundleTest` vérifie l'enregistrement des services tagués, la qualification du secret Settings et la présence de l'option Brevo dans le catalogue Mails sans sondage réseau. `BrevoSettingsTransportFactoryTest` utilise SQLite en mémoire et le vrai `SettingManager` pour vérifier le chiffrement, puis un `MockHttpClient` pour tester le bridge et son payload HTTP sans contacter Brevo ; l'absence de clé et les DSN incorrects sont également testés. CI PHP 8.4 via runners auto-hébergés. Restent obligatoires : CI verte, compilation réelle du conteneur Symfony/recette, synchronisation Settings, test de sélection et envoi avec clé réelle ou simulateur contrôlé, puis crash-tests utilisateur.

Aucune migration Doctrine, modification de Core ou de Back, nouveau paramètre de production obligatoire, ni effet de bord d'installation d'un serveur local n'est ajouté.

## Préparation d'une release stable 1.0.0 (17/11/2026)

L'implémentation Brevo sur `dev` et sa CI automatisée sont présentes, mais le code `composer.json` exige `symsvel-framework/module-mails:dev-dev` et `symsvel-framework/module-settings:dev-dev`. Les seules releases stables Mails/Settings actuellement disponibles (`0.1.0`) sont des squelettes initiaux : elles ne fournissent pas `MailTransportProviderInterface`, `SettingManager` ou `SettingDefinitionProviderInterface`. **Ne pas simplement remplacer les contraintes `dev-dev` par `^0.1`** : cela créerait un package installable par Composer mais non exécutable.

Plan de publication : finaliser et valider les releases stables de Mails et Settings avec ces contrats, mettre à jour les contraintes Composer de Brevo et leurs tests, vérifier la compilation d'un vrai Back, la recette Flex `module-brevo`, l'envoi vers Mailpit sans clé Brevo, puis un envoi réel Brevo avec clé API en CT local. Après validation utilisateur des CT, merger la PR Brevo et publier un nouveau tag `1.0.0` (sans modifier le tag historique `0.1.0` mal nommé). Déployer Satis, vérifier l'absence de l'ancien nom et la résolution installable `symsvel-framework/module-brevo:1.0.0`.

Sur un projet de canal `dev`, utiliser `symsvel require symsvel-framework/module-brevo` **sans option `--dev`** : la CLI reprend la provenance `dev` déjà enregistrée lors de `create-project` et le Tooling configure le dépôt VCS correspondant. Ce chemin permet de commencer les CT avant la publication stable.
