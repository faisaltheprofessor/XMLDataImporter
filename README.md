# XML Data Importer

This readme file provides instructions specific to your Laravel application.

## Getting Started
1. Configure the database connection with the desired credentials. Update the following variables in your ```.env``` file according to your database setup:
    ```
    DB_CONNECTION=mysql
    DB_HOST=127.0.0.1
    DB_PORT=3306
    DB_DATABASE=testdb
    DB_USERNAME=root
    DB_PASSWORD=root
    ```
2. After setting up the database, run the following command to import XML data and store it into the database. The process is interactive and will guide you throughout:
``` bash
artisan app:import-xml-to-db
 ```
## Logging
To view logs, you can use the Pail package. Run the following command to open the log viewer:
```bash
    php artisan pail
```
Alternatively you can check the logs in ```storage/logs/dataimportlog.log```

## Testing
To run the tests for your application, use the following command:
```bash 
    php artisan test
```
Please note that these instructions assume a Laravel environment and that you have all the necessary dependencies installed. Make sure to adjust the database connection variables in the ```.env``` file according to your specific database setup.



