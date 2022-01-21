# 
# This file is to run the migrations on docker containers
# 
DOCKER="docker"
CONTAINER_NAME="canonizer_api"
MIGRATE="php artisan migrate"
DB_SEED="--seed"

echo "Migrating Seeding the database"
`$DOCKER exec -it $CONTAINER_NAME $MIGRATE $DB_SEED`
echo "Seeding the database completed."

echo "$DOCKER exec -it $CONTAINER_NAME $MIGRATE $DB_SEED"

`/usr/local/bin/docker exec -it canonizer_api /usr/local/bin/php /opt/canonizer/artisan migrate --seed`