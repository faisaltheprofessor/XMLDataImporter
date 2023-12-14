# XML Data Importer

This readme file provides instructions specific to this Laravel application.

## PHP 8.2 Requirement
Please note that this XML Data Importer requires PHP 8.2. Make sure you have PHP 8.2 or higher installed on your system before proceeding with the setup and execution of the application.


## Getting Started
1. Please note that these instructions assume a Laravel environment and that you have all the necessary dependencies installed. Make sure to adjust the database connection variables in the ```.env``` file according to your specific database setup. 
Configure the database connection with the desired credentials in your ```.env``` file.
    ```
    DB_CONNECTION=mysql
    DB_HOST=127.0.0.1
    DB_PORT=3306
    DB_DATABASE=testdb
    DB_USERNAME=root
    DB_PASSWORD=root
    ```
2. After setting up the database, run the following command to import XML data and store it into the database. The process is interactive and will guide you throughout:
``` shell 
    php artisan app:import-xml-to-db
 ```
## Logging
To view logs, you can use the Pail package. Run the following command to open the log viewer:
``` shell
    php artisan pail
```
Alternatively you can check the logs in ```storage/logs/dataimportlog.log```

## Testing
To run the tests for the application, use the following command:
``` shell 
    php artisan test
```
Or alternatively
``` shell 
    vendor/bin/phpunit
```
The following commands could prove useful in case you encounter issues when running tests.
``` shell 
    php artisan cache:clear 
    php artisan config:clear
    composer dump-autoload
```

## Disclaimer

This project contains the solution to the trial task (Process and Load data from an XML file to a database). The provided solution fulfills the basic requirements outlined in the task. Although there may be/are bugs, potential areas for improvement or exceptional cases that have not been fully addressed.

