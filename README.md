Terravision API Microservice
===============
For setting up a local development environment, please install Docker first.

Build the docker image
----------------------
```
docker-compose build
```

Start the containers
--------------------
```
docker-compose up -d
```

Access PHP container as a `dev` user
------------------------------------
```
docker exec -it -u dev tvapi_php bash
```

Don't forget to install vendor libraries
----------------------------------------
```
composer install
```
