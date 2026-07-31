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

==================================================
🚀 Starting Attendance Tracking System Setup...
==================================================
🧹 Cleaning up macOS temporary companion files...
🐳 Building PHP web image and launching containers...
...
⏳ Waiting for MySQL database to initialize...
✅ Database container is fully online.
🗄️  Running database schema setup and seed scripts...

🎉 System is fully online and ready!
--------------------------------------------------
👉 Faculty Portal (Web App): http://localhost:8080/attapp/login/index.php
   Faculty Credentials: Username: anika | Password: 123
--------------------------------------------------
👉 Database Admin Panel (phpMyAdmin): http://localhost:8081
--------------------------------------------------
👉 API Docs (Swagger UI): http://localhost:8082
--------------------------------------------------
