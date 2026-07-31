find . -name "._*" -delete
docker-compose up -d --build
docker-compose exec -T web php /var/www/html/attapp/database/createtable.php
docker-compose down
Username: anika
Password: 123
http://localhost:8081
Web Portal (App): http://localhost:8080/attapp/login/index.php (User: anika / Pass: 123)
phpMyAdmin (DB Panel): http://localhost:8081
Faculty Attendance App Portal: 👉 http://localhost:8080/attapp/login/index.php 