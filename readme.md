# As Soon As Possible

Make sure to edit the placeholders at ```.env``` file.

# Docker Compose

````
docker compose --build -d
````

# Create the Database

After both the server and database containers finish initializing, run:
````
docker exec -it <server-container> bash
````
and now (from inside the server container) run:
````
php .private/_admin/dbms/create.php
````