#!/usr/bin/env bash

working_directory=$(pwd)
app_directory=$working_directory"/mnt/app"
public_directory=$app_directory"/public"
MYSQL_ROOT="root"
MYSQL_PASS="mysql"

cd $app_directory

echo "Please select what You want to do"
echo "Update project - 1; Recreate database with demo data - 2; Break update - any other key"

update_project()
{
  echo "Pulling changes from repository"
  git pull

  echo "Update dependencies"
  i=1
  sp="/-\|"
  echo -n ' '
  while docker-compose exec app composer update
  do
      printf "\b${sp:i++%${#sp}:1}"
      if [ $i == 2 ]; then
          break
      fi
  done

  echo "Update database"
  docker-compose exec app bin/console doctrine:schema:update --force

  echo "Update node_modules"
  docker-compose exec app npm install && docker-compose exec app npm run build
  cd $public_directory/assets || exit
  ln -s ../../node_modules node_modules

  echo "Update was finished successfully"
  cd $working_directory || exit
}

recreate_db_with_demo_data()
{
  echo "Dropping existing database"
  docker-compose exec app bin/console doctrine:database:drop --force

  echo "Recreating database"
  docker-compose exec app bin/console doctrine:database:create
  docker-compose exec app bin/console doctrine:schema:update --force

  echo "Clear user images folder"
  docker-compose exec app  bash -c "rm -rf /var/www/html/public/uploads/images/news";
  docker-compose exec app  bash -c "rm -rf /var/www/html/public/uploads/images/teaser";

  echo "Load fixtures"
  docker-compose exec app bin/console doctrine:fixtures:load --append

  echo "Load translate city"
  docker-compose exec db bash -c "mysql -u $MYSQL_ROOT -p$MYSQL_PASS web_app_db < /var/dev/geo.sql 2>/dev/null";

  echo "Load data to other filters for traffic analysis"
  docker-compose exec app bin/console app:other-filters-data:generate

  echo "Recreating database was finished successfully"
  cd $working_directory || exit
}

while :
do
  read UPDATE_OPTION
  case $UPDATE_OPTION in
	1)
		update_project
		break
		;;
	2)
		recreate_db_with_demo_data
		break
		;;
	*)
	  echo "Breaking update"
	  cd $working_directory || exit
		break
		;;
  esac
done
