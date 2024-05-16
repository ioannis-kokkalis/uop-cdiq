# Guide to Run

Edit the values of the placeholders at ```.env``` file.

Move your **certificate** and **private key** inside ```server/``` named ```cert``` and ```pkey``` respectively.

Execute the following commands inside base directory of repository:

````
docker compose --build -d
````

````
docker exec -it <server-container> bash
````

````
php .private/_admin/dbms/create.php
````

```
php .private/_admin/operators.php
```

# Bla bla bla

...
