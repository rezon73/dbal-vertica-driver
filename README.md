DBAL Vertica driver
===================

Doctrine DBAL connector driver for Vertica.
*Ready for use in **Laravel** / Lumen*

Requirements
------------
* php >= 7.0
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
```bash
composer update
```
