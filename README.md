# Brevo pour SymSvel

Module **optionnel** de transport d’e-mails transactionnels par l’API Brevo.

Il s’intègre aux modules **Mails** (choix du transport et identité expéditrice) et **Settings** (chiffrement de la clé API). L’envoi réel s’appuie sur le bridge officiel `symfony/brevo-mailer` : aucune requête HTTP métier n’est réimplémentée dans SymSvel.

## Installation

Depuis un projet SymSvel en développement, via la CLI unique :

```bash
symsvel require symsvel-framework/module-brevo --dev
```

Configurer ensuite dans le BO :

1. **Paramètres → Brevo** : `brevo.api_key`, clé API transactionnelle v3 ;
2. **Paramètres → Mails** : expéditeur `mails.sender_email` et éventuellement `mails.sender_name` ;
3. **Paramètres → Mails** : transport `mails.transport = brevo`.

Une fois la recette du module appliquée, Brevo apparaît parmi les choix de transport. La présence du choix ne valide ni la clé ni la connexion à Brevo. Si l'installation n'a pas de clé API, l'envoi est refusé explicitement. Aucun mécanisme de repli vers Mailpit ou SMTP n'est déclenché automatiquement.

Le module ne possède **aucune table, migration, écran dédié ou endpoint de diagnostic**. Les réglages sont affichés par le BO Settings existant. L'adresse et le nom d'expéditeur restent déclarés par Mails, et les templates sont gérés par les modules émetteurs.

## État

Implémentation initiale sur `dev`, validation CI et crash-test local du fournisseur restant nécessaires. Aucun appel réel à Brevo n'est réalisé par les tests.
