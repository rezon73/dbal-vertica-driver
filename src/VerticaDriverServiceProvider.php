<?php

namespace mixartemev\VerticaDriver;

use Doctrine\DBAL\Driver\Vertica\VerticaDriver;
use Illuminate\Database\PostgresConnection;
use Illuminate\Support\ServiceProvider;

class VerticaDriverServiceProvider extends ServiceProvider
{
    /**
     * 
     */
    public function boot()
    {
        $this->app->bind('db.connector.vertica', function () {
            return new VerticaDriver();
        });

        $this->app->make('db')->resolverFor('vertica', function ($connection, $database, $prefix, $config) {
            return new PostgresConnection($connection, $database, $prefix, $config);
        });
    }
}
