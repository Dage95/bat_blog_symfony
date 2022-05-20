# Projet Le Blog de Batman

## Installation

```
git clone https://github.com/Dage95/bat_blog_symfony.git
```

### Modifier les paramètres d'environnement dans le fichier .env pour les faire correspondre à votre environnement (accès BDD, clés Google Recaptcha ...)

```
# Accès BDD à modifier
DATABASE_URL="mysql://root:@127.0.0.1:3306/leblogdebatman?serverVersion=5.7&charset=utf8mb4"

# Clés Google Recaptcha à modifier
GOOGLE_RECAPTCHA_SITE_KEY=xxxxxxxxxxxxxxxxxxxxxxxxxx
GOOGLE_RECAPTCHA_PRIVATE_KEY=xxxxxxxxxxxxxxxxxxxxxxxxxx
```

### Déplacer le terminal dans le dossier cloné du projet
```
cd leblogdebatman
```

### Taper les commandes suivantes :
```
composer install
symfony console doctrine:database:create
symfony console make:migration
symfony console doctrine:migration:migrate
symfony console doctrine:fixtures:load
symfony console assets:install public
```

Les fixtures crééeront :
* Un compte admin (email: a@a.a,  mdp: aaaaaaaaA11)
* 10 comptes utilisateurs (email aléatoire, mdp du compte admin)
* 200 articles
* Entre 0 et 10 commentaires par article

### Démarrer le serveur Symfony
```
symfony serve
```
