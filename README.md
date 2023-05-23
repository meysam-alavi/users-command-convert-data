

### Description of app execution and command execution

run in host OS in sail-app root
- chmod 0777 -R storage
- ./vendor/bin/sail up -d
- ./vendor/bin/sail php artisan migrate --path="/database/migration/2014_10_12_000000_create_users_table.php"
- ./vendor/bin/sail php artisan user:convert-data [--resume]

Check inserted data in users table in database
- http://localhost/users/list

