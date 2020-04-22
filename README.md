DBAL Vertica driver
===================

Doctrine DBAL connector driver for Vertica.
*Ready for use in **Laravel** / Lumen*

Requirements
------------
* php >= 7.2
* php_odbc extension
* Vertica drivers
* Doctrine 2 DBAL

Installation
------------

##### PHP extentions:
```bash
apt-get install php-odbc php-pdo php-json
```

##### Vertica drivers:
Make ODBC and Vertica drivers to work together:
* Download and extract Vertica drivers from official website https://my.vertica.com/vertica-client-drivers/ (it should match your Vertica Db version)
* Extract driver under /opt/vertica/
* create/edit file: /etc/odbc.ini ([example](https://github.com/skatrych/vertica-php-adapter/blob/master/examples/drivers/odbc.ini))
* create/edit file: /etc/odbcinst.ini ([example](https://github.com/skatrych/vertica-php-adapter/blob/master/examples/drivers/odbcinst.ini))
* create/edit file: /etc/vertica.ini ([example](https://github.com/skatrych/vertica-php-adapter/blob/master/examples/drivers/vertica.ini))

##### All the rest:
* Add to your composer.json:
```json
    "repositories": [
        {
            "type": "vcs",
            "url": "https://github.com/rezon73/dbal-vertica-driver.git"
        }
    ]
```
```bash
composer install
```
Integration in Laravel
----------------------

##### .env
```
DB_HOST_VERTICA=127.0.0.1
#DB_PORT_VERTICA=5433 (DONT SET PORT! IT MUST BE EXACTLY INTEGER! GETTING FROM dafaults in )
DB_DATABASE_VERTICA=dbname
DB_USERNAME_VERTICA=username
DB_PASSWORD_VERTICA=password
```

##### config/database.php
```php
<?php
return [
    'connections' => [
        'vertica' => [
            'driver' => 'vertica',
            'host' => env('DB_HOST_VERTICA', '127.0.0.1'),
            'port' => env('DB_PORT_VERTICA', 5433), //EXACTLY DIGITS, NOT STRING
            'database' => env('DB_DATABASE_VERTICA', 'forge'),
            'username' => env('DB_USERNAME_VERTICA', 'forge'),
            'password' => env('DB_PASSWORD_VERTICA', ''),
            'schema' => 'public',
            'sslmode' => 'allow',
            'options' => [
                PDO::ATTR_EMULATE_PREPARES => false,
            ]
        ]
    ];
```

##### app/Providers/AppServiceProvider.php
```php
<?php
class AppServiceProvider extends ServiceProvider
{
    public function boot()
    {
        App::bind('db.connector.vertica', function () {
            return new VerticaDriver;
        });
        DB::resolverFor('vertica', function ($connection, $database, $prefix, $config) {
            return new VerticaConnection($connection, $database, $prefix, $config);
        });
    }
}
```