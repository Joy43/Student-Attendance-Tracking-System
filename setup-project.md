# 📘 Complete Manual Run Guide
## Student Attendance Tracking System — `attapp`

> **Tech Stack:** PHP 8.2 · Apache · MySQL 8.0 · Docker  
> **Platform:** macOS  
> **Project Path:** `/Volumes/ss joy/dcc/attapp`

---

## 📁 1. Full Project Structure (Every File Explained)

```
attapp/
│
├── 📄 Dockerfile                  ← Builds PHP 8.2 + Apache image with mysqli
├── 📄 docker-compose.yml          ← Runs all 4 services together
├── 📄 run.sh                      ← One-click automated startup script
├── 📄 openapi.yaml                ← Swagger API specification (REST docs)
├── 📄 logout.php                  ← Destroys session → redirects to login
├── 📄 .dockerignore               ← Files excluded from Docker build
├── 📄 .gitignore                  ← Files excluded from Git
│
├── 📁 login/
│   └── index.php                  ← Login form + auth logic (entry point)
│
├── 📁 dashboard/
│   ├── header.php                 ← Auth guard + sidebar + top navbar
│   ├── footer.php                 ← Closing HTML + Chart.js scripts + JS helpers
│   ├── style.css                  ← Full CSS design system (dark/light mode)
│   ├── dashboard.php              ← Stats cards + attendance charts
│   ├── take_attendance.php        ← Mark students Present/Absent/Late/Leave
│   ├── view_attendance.php        ← Filter and view attendance records
│   ├── manage_students.php        ← Add / Edit / Delete students (CRUD)
│   ├── notifications.php          ← AJAX endpoint: returns JSON notifications
│   └── notifications_page.php    ← Full notifications history page
│
└── 📁 database/
    ├── database.php               ← DB connection (reads env vars or defaults)
    └── createtable.php            ← Creates 6 tables + inserts seed data
```

---

## 🗄️ 2. Database Schema (All 6 Tables)

### Table 1 — `student_details`
| Column   | Type         | Notes              |
|----------|--------------|--------------------|
| id       | INT PK AUTO  |                    |
| roll_no  | VARCHAR(20)  | Unique             |
| name     | VARCHAR(50)  |                    |

**Seeded Data (10 Students):**
| Roll No   | Name         |
|-----------|--------------|
| CSB21001  | nafeem       |
| CSB21002  | sobuj        |
| CSB21003  | Shehab       |
| CSB21004  | mostafa      |
| CSB21005  | nidhi        |
| CSB21006  | ridhi        |
| CSB21007  | khandakar    |
| CSB21008  | shifat       |
| CSB21009  | ibad         |
| CSB21010  | James Jones  |

---

### Table 2 — `faculty_details`
| Column    | Type         | Notes              |
|-----------|--------------|--------------------|
| id        | INT PK AUTO  |                    |
| user_name | VARCHAR(20)  | Unique (login)     |
| name      | VARCHAR(100) | Full display name  |
| password  | VARCHAR(50)  | Plain text         |

**All Login Accounts (6 Faculty):**
| Username    | Password | Full Name              |
|-------------|----------|------------------------|
| `anika`     | `123`    | Anika Akter            |
| `kawsir`    | `123`    | kawser                 |
| `najma`     | `123`    | Najma Akter            |
| `saima`     | `123`    | Saima Akter            |
| `shanchayan`| `123`    | sanchayan battacharjje |
| `manooj`    | `123`    | Manooj Hazarika        |

---

### Table 3 — `course_details`
| Column | Type        | Notes           |
|--------|-------------|-----------------|
| id     | INT PK AUTO |                 |
| code   | VARCHAR(20) | Unique          |
| title  | VARCHAR(50) |                 |
| credit | INT         |                 |

**Seeded Courses (6):**
| Code | Title                        | Credits |
|------|------------------------------|---------|
| CS1  | software engineering         | 2       |
| CS2  | Embedded management system   | 3       |
| CS3  | Computer networking          | 3       |
| CS4  | Artificial Intelligence      | 4       |
| Cs5  | Theory of Computation        | 3       |
| CS6  | Demystifying Networking      | 1       |

---

### Table 4 — `session_details`
| Column | Type        | Notes                  |
|--------|-------------|------------------------|
| id     | INT PK AUTO |                        |
| year   | INT         |                        |
| term   | VARCHAR(50) | e.g. "SPRING SEMESTER" |

**Seeded Sessions (2):**
| Year | Term            |
|------|-----------------|
| 2023 | SPRING SEMESTER |
| 2023 | AUTUMN SEMESTER |

---

### Table 5 — `attendance_details`
| Column     | Type       | Notes                             |
|------------|------------|-----------------------------------|
| faculty_id | INT        | FK → faculty_details.id           |
| course_id  | INT        | FK → course_details.id            |
| session_id | INT        | FK → session_details.id           |
| student_id | INT        | FK → student_details.id           |
| on_date    | DATE       | Format: YYYY-MM-DD                |
| status     | VARCHAR(10)| Present / Absent / Late / Leave   |
| PRIMARY KEY| composite  | (faculty, course, session, student, date) |

---

### Table 6 — `notifications`
| Column      | Type                           | Notes                  |
|-------------|--------------------------------|------------------------|
| id          | INT PK AUTO                    |                        |
| faculty_user| VARCHAR(50)                    | Linked to session user |
| type        | ENUM('create','update','delete')| Action type           |
| title       | VARCHAR(120)                   |                        |
| message     | TEXT                           |                        |
| is_read     | TINYINT(1)                     | 0=unread, 1=read       |
| created_at  | DATETIME                       | Auto timestamp         |

---

## 🔌 3. Database Connection Configuration

File: [`database/database.php`](file:///Volumes/ss%20joy/dcc/attapp/database/database.php)

```php
$servername = getenv('DB_HOST')     ?: "127.0.0.1";
$username   = getenv('DB_USER')     ?: "root";
$password   = getenv('DB_PASSWORD') ?: "";
$dbname     = getenv('DB_NAME')     ?: "attapp_db";
$port       = getenv('DB_PORT')     ?: 3307;
```

| ENV Variable  | Default      | Docker Value      |
|---------------|--------------|-------------------|
| `DB_HOST`     | `127.0.0.1`  | `db`              |
| `DB_USER`     | `root`       | `root`            |
| `DB_PASSWORD` | *(empty)*    | `attapp_password` |
| `DB_NAME`     | `attapp_db`  | `attapp_db`       |
| `DB_PORT`     | `3307`       | `3306`            |

> ⚠️ **Note:** The default port is `3307` (not the MySQL standard `3306`) to avoid conflicts with a locally-running MySQL instance.

---

## 🐳 METHOD A — Docker (Recommended · Works Right Now)

> ✅ Docker v29 is already installed and running on your Mac. Use this method!

### Step 1 — Navigate to the project folder

```bash
cd "/Volumes/ss joy/dcc/attapp"
```

### Step 2 — Clean macOS hidden metadata files (prevents build errors)

```bash
find . -name "._*" -delete
```

### Step 3 — Build the PHP web server Docker image

```bash
docker build -t attapp-web:latest - < Dockerfile
```

> **What this does:** Pulls `php:8.2-apache`, installs the `mysqli` extension, tags it as `attapp-web:latest`

### Step 4 — Launch all 4 services

```bash
docker-compose up -d
```

> **What starts:**
> - `attapp_web` → Apache + PHP on port `8080`
> - `attapp_db` → MySQL 8.0 on port `3307`
> - `attapp_phpmyadmin` → DB admin panel on port `8081`
> - `attapp_swagger` → Swagger UI on port `8082`

### Step 5 — Verify all containers are running

```bash
docker ps
```

You should see 4 containers with status `Up`:

```
CONTAINER ID   IMAGE                    STATUS        PORTS
xxxx           attapp-web:latest        Up 30s        0.0.0.0:8080->80/tcp
xxxx           mysql:8.0                Up 30s        0.0.0.0:3307->3306/tcp
xxxx           phpmyadmin:latest        Up 30s        0.0.0.0:8081->80/tcp
xxxx           swaggerapi/swagger-ui    Up 30s        0.0.0.0:8082->8080/tcp
```

### Step 6 — Wait for MySQL to finish initializing

```bash
until docker-compose exec -T db mysqladmin ping -h localhost -u root --password=attapp_password --silent 2>/dev/null; do
  echo -n "."
  sleep 2
done
echo " MySQL is ready!"
```

> MySQL takes about **10–20 seconds** on first start. Do NOT skip this step.

### Step 7 — Create database tables and insert seed data

```bash
docker-compose exec -T web php /var/www/html/attapp/database/createtable.php
```

**Expected output:**
```
✅ Table 'student_details' created.
✅ Table 'course_details' created.
✅ Table 'faculty_details' created.
✅ Table 'session_details' created.
✅ Table 'attendance_details' created.
✅ Table 'notifications' created.
✅ Data inserted into 'student_details'.
✅ Data inserted into 'faculty_details'.
✅ Data inserted into 'session_details'.
✅ Data inserted into 'course_details'.
🎉 All tables created and data inserted successfully.
```

### Step 8 — Open the app

| Service          | URL                                              |
|------------------|--------------------------------------------------|
| 🌐 Faculty App   | http://localhost:8080/attapp/login/index.php     |
| 🗄️ phpMyAdmin   | http://localhost:8081                            |
| 📄 Swagger Docs  | http://localhost:8082                            |

### Step 9 — Login

```
Username: anika
Password: 123
```

### Step 10 — Stop the app

```bash
# Stop but KEEP database data
docker-compose down

# Stop AND DELETE all database data (full reset)
docker-compose down -v
```

---

## ⚡ ONE-COMMAND Startup (Shortcut)

The project includes `run.sh` which does ALL of the above automatically:

```bash
cd "/Volumes/ss joy/dcc/attapp"
bash run.sh
```

> This runs: clean → build → start → wait for DB → init tables → print URLs

---

## 🖥️ METHOD B — Local (Without Docker) via Homebrew

> ⚠️ PHP and MySQL are **NOT currently installed** on your Mac. Follow these steps first.

### Step B-1 — Install Homebrew

```bash
/bin/bash -c "$(curl -fsSL https://raw.githubusercontent.com/Homebrew/install/HEAD/install.sh)"
```

After install, follow the instructions in Terminal to add Homebrew to your PATH.

### Step B-2 — Install PHP 8.2

```bash
brew install php@8.2
```

Add to PATH:
```bash
echo 'export PATH="/opt/homebrew/opt/php@8.2/bin:$PATH"' >> ~/.zshrc
echo 'export PATH="/opt/homebrew/opt/php@8.2/sbin:$PATH"' >> ~/.zshrc
source ~/.zshrc
```

Verify:
```bash
php --version
# PHP 8.2.x (cli)
```

### Step B-3 — Install MySQL 8.0

```bash
brew install mysql@8.0
echo 'export PATH="/opt/homebrew/opt/mysql@8.0/bin:$PATH"' >> ~/.zshrc
source ~/.zshrc
```

### Step B-4 — Start MySQL service

```bash
brew services start mysql@8.0
```

### Step B-5 — Secure MySQL and create the database

```bash
# Set root password and create database
mysql -u root -e "
ALTER USER 'root'@'localhost' IDENTIFIED WITH mysql_native_password BY 'attapp_password';
CREATE DATABASE IF NOT EXISTS attapp_db;
FLUSH PRIVILEGES;
"
```

### Step B-6 — Set environment variables

```bash
export DB_HOST=127.0.0.1
export DB_USER=root
export DB_PASSWORD=attapp_password
export DB_NAME=attapp_db
export DB_PORT=3306
```

> 💡 Add these to `~/.zshrc` to make them permanent.

### Step B-7 — Serve the project using PHP built-in server

The app uses `/attapp/` as the URL prefix. Serve from **one level above** the project:

```bash
cd "/Volumes/ss joy/dcc"
php -S localhost:8080
```

### Step B-8 — Initialize the database

Open a new Terminal tab and run:

```bash
export DB_HOST=127.0.0.1
export DB_USER=root
export DB_PASSWORD=attapp_password
export DB_NAME=attapp_db
export DB_PORT=3306

php "/Volumes/ss joy/dcc/attapp/database/createtable.php"
```

### Step B-9 — Open the app

```
http://localhost:8080/attapp/login/index.php
```

---

## 📦 METHOD C — MAMP / XAMPP (GUI Approach)

1. **Download & Install** [MAMP](https://www.mamp.info) (free) or [XAMPP](https://www.apachefriends.org)
2. Set the **Document Root** (Web Root) to: `/Volumes/ss joy/dcc`
   - In MAMP: Preferences → Web Server → Document Root
   - In XAMPP: Edit `httpd.conf` → change `DocumentRoot`
3. **Start Apache and MySQL** from the MAMP/XAMPP control panel
4. Open **phpMyAdmin** → Create database named `attapp_db`
5. Open: `http://localhost:8888/attapp/login/index.php` (MAMP) or `http://localhost/attapp/login/index.php` (XAMPP)
6. Navigate to: `http://localhost:8888/attapp/database/createtable.php` to initialize tables

---

## 🗺️ 4. App Navigation Map (All Pages)

```
📌 Entry Point
http://localhost:8080/attapp/login/index.php
         │
         ▼ (Login with username + password)
┌──────────────────────────────────────────────┐
│  SIDEBAR NAVIGATION (visible on all pages)   │
├──────────────────────────────────────────────┤
│  📊 Dashboard     → /dashboard/dashboard.php │
│  📋 Attendance    → /dashboard/take_attendance.php │
│  👥 Students      → /dashboard/manage_students.php │
│  📈 Reports       → /dashboard/view_attendance.php │
│  🔔 Notifications → /dashboard/notifications_page.php │
│  🚪 Sign Out      → /logout.php              │
└──────────────────────────────────────────────┘
```

---

## 📱 5. Feature Walkthrough (What Each Page Does)

### 📊 Dashboard (`dashboard.php`)
- Shows **6 stat cards**: Total Students, Present Today, Absent Today, Late Today, Leave Requests, Attendance %
- **Line chart**: 7-day attendance trend (Present vs Absent)
- **Doughnut chart**: Today's overview breakdown
- **Recent activity**: Last 5 attendance records
- **Quick action buttons**: Take Attendance, Manage Students, Generate Reports

### 📋 Take Attendance (`take_attendance.php`)
- Select **Course**, **Session/Term**, and **Date**
- See all 10 students in a table
- Click chip to mark each student: ✅ Present | ❌ Absent | ⏰ Late | 📝 Leave
- **Bulk buttons**: Mark All Present / Mark All Absent
- **Search bar**: Filter students by name or roll number
- Click **Save Attendance Records** to submit

### 📈 View Attendance Reports (`view_attendance.php`)
- Filter by Course, Session, Date
- Shows a table with student name, roll number, and status
- Color-coded status badges

### 👥 Manage Students (`manage_students.php`)
- **Add new student**: Enter name + roll number
- **Edit existing student**: Click edit icon → form pre-fills
- **Delete student**: Click delete icon → confirms and removes
- Lists all students in a searchable table

### 🔔 Notifications (`notifications_page.php`)
- Shows all system notifications for the logged-in faculty
- Notifications created when attendance is submitted
- Can mark individual or all as read
- Bell icon in top nav shows unread count badge

---

## 🐳 6. Docker Service Details

### `docker-compose.yml` breakdown

| Service     | Image                   | Container           | Port Mapping    | Volume                       |
|-------------|-------------------------|---------------------|-----------------|------------------------------|
| `web`       | `attapp-web:latest`     | `attapp_web`        | `8080 → 80`     | Project folder → `/var/www/html/attapp` |
| `db`        | `mysql:8.0`             | `attapp_db`         | `3307 → 3306`   | `db_data` volume              |
| `phpmyadmin`| `phpmyadmin:latest`     | `attapp_phpmyadmin` | `8081 → 80`     | —                            |
| `swagger`   | `swaggerapi/swagger-ui` | `attapp_swagger`    | `8082 → 8080`   | `openapi.yaml` → `/openapi.yaml` |

### Environment Variables (Docker)
```yaml
web:
  DB_HOST=db           # MySQL service name (internal DNS)
  DB_USER=root
  DB_PASSWORD=attapp_password
  DB_NAME=attapp_db
  DB_PORT=3306

db:
  MYSQL_ROOT_PASSWORD=attapp_password
  MYSQL_DATABASE=attapp_db

phpmyadmin:
  PMA_HOST=db
  PMA_PORT=3306
  PMA_USER=root
  PMA_PASSWORD=attapp_password
```

---

## 🔧 7. Useful Docker Commands Reference

```bash
# ── BASIC ──────────────────────────────────────────
# Start everything (background)
docker-compose up -d

# Start everything and see logs
docker-compose up

# Stop everything (keep DB data)
docker-compose down

# Full reset (stop + delete DB volume)
docker-compose down -v

# ── MONITORING ─────────────────────────────────────
# See all running containers
docker ps

# See all containers (including stopped)
docker ps -a

# Watch live logs of web server
docker-compose logs -f web

# Watch live logs of database
docker-compose logs -f db

# See logs of all services
docker-compose logs -f

# ── INTERACT ───────────────────────────────────────
# Open a shell inside the PHP container
docker-compose exec web bash

# Connect to MySQL inside the container
docker-compose exec db mysql -u root -pattapp_password attapp_db

# Run a PHP file inside container
docker-compose exec web php /var/www/html/attapp/database/createtable.php

# ── REBUILD ────────────────────────────────────────
# Rebuild image after Dockerfile changes
docker-compose up -d --build

# Force rebuild from scratch (no cache)
docker-compose build --no-cache
docker-compose up -d

# ── CLEANUP ────────────────────────────────────────
# Remove stopped containers
docker container prune

# Remove unused images
docker image prune

# See disk usage
docker system df
```

---

## ❗ 8. Troubleshooting Guide

| Problem | Cause | Fix |
|---------|-------|-----|
| `Port 8080 already in use` | Another app using port 8080 | Run `lsof -i :8080` → kill that process, OR change `8080:80` in `docker-compose.yml` |
| `Port 8081 already in use` | Another app on 8081 | Change `8081:80` to `8083:80` in `docker-compose.yml` |
| `Connection failed: attapp_db` | MySQL not ready yet | Wait 15–20s then re-run the createtable step |
| `No such container: attapp_web` | Containers not started | Run `docker-compose up -d` first |
| Login shows "Invalid credentials" | Tables not created | Run the `createtable.php` step again |
| Blank white page | PHP fatal error | Run `docker-compose logs web` to see error |
| DB data gone after restart | `down -v` was used | Re-run `createtable.php` to reseed data |
| `Cannot connect to Docker` | Docker Desktop not running | Open Docker Desktop app first |
| `attapp-web:latest` not found | Image not built | Run `docker build -t attapp-web:latest - < Dockerfile` |
| phpMyAdmin shows error | DB not running | Run `docker ps` to check `attapp_db` is running |
| `._DS_Store` or `._*` errors | macOS metadata files | Run `find . -name "._*" -delete` |
| CSS / styles not loading | Wrong URL or Apache config | Ensure URL has `/attapp/` prefix: `localhost:8080/attapp/login/index.php` |

---

## 🔐 9. Security Notes (For Production Use)

> ⚠️ This project is a **development/academic app**. If deploying publicly:

- [ ] Passwords are stored as **plain text** — use `password_hash()` / `password_verify()`
- [ ] SQL queries use **string interpolation** — switch to **prepared statements**
- [ ] No CSRF protection on forms
- [ ] No HTTPS/SSL configured
- [ ] Default credentials are weak (`123`)

---

## ✅ Quick Reference Card

```
┌─────────────────────────────────────────────────────┐
│           ATTAPP — QUICK START                      │
├─────────────────────────────────────────────────────┤
│ 1. cd "/Volumes/ss joy/dcc/attapp"                  │
│ 2. bash run.sh          ← does everything!          │
│                                                     │
│ OR manually:                                        │
│ 1. docker build -t attapp-web:latest - < Dockerfile │
│ 2. docker-compose up -d                             │
│ 3. (wait 15 seconds for MySQL)                      │
│ 4. docker-compose exec -T web php \                 │
│    /var/www/html/attapp/database/createtable.php    │
│                                                     │
│ OPEN:  http://localhost:8080/attapp/login/index.php  │
│ LOGIN: anika / 123                                  │
│                                                     │
│ STOP:  docker-compose down                          │
└─────────────────────────────────────────────────────┘
```
