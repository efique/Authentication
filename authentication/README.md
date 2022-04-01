composer install
Créer la base authentication
php artisan migrate
php artisan db:seed

Compte Admin :
email = admin@admin.fr
password = admin

Les champs login de l'api se nomment email.

Le refresh token pour la route refresh-token correspond au accessToken que l'on écrase pour en recréer un neuf.
Laravel Sanctum ne permettant pas (facilement du moins) de créer le système de refresh token.

Groupe PastisOlive composé de Barriol Valentin, Logos Lorenzo et Sourlier Kevin.