<?php
// Include the database connection
require_once dirname(__DIR__) . "/src/database.php";

// Turn off strict exception reporting for graceful duplicate handling
mysqli_report(MYSQLI_REPORT_OFF);

//  Helper: Create table safely
function createTable($conn, $sql, $name)
{
    if (mysqli_query($conn, $sql)) {
        echo "<br>✅ Table '$name' created.";
    } else {
        echo "<br>⚠️ Table '$name' not created (maybe already exists): " . mysqli_error($conn);
    }
}

//  Helper: Insert initial data
function insertData($conn, $sql, $tableName)
{
    if (mysqli_query($conn, $sql)) {
        echo "<br>✅ Data inserted into '$tableName'.";
    } else {
        if (strpos(mysqli_error($conn), 'Duplicate') !== false) {
            echo "<br>⚠️ Data already exists in '$tableName'.";
        } else {
            echo "<br>❌ Error inserting into '$tableName': " . mysqli_error($conn);
        }
    }
}

// ===========================================================
// 1️⃣ CREATE TABLES
// ===========================================================

createTable($conn, "
CREATE TABLE IF NOT EXISTS student_details (
    id INT AUTO_INCREMENT PRIMARY KEY,
    roll_no VARCHAR(20) UNIQUE,
    name VARCHAR(50)
)", "student_details");

createTable($conn, "
CREATE TABLE IF NOT EXISTS course_details (
    id INT AUTO_INCREMENT PRIMARY KEY,
    code VARCHAR(20) UNIQUE,
    title VARCHAR(50),
    credit INT
)", "course_details");

createTable($conn, "
CREATE TABLE IF NOT EXISTS faculty_details (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_name VARCHAR(20) UNIQUE,
    name VARCHAR(100),
    password VARCHAR(50)
)", "faculty_details");

createTable($conn, "
CREATE TABLE IF NOT EXISTS session_details (
    id INT AUTO_INCREMENT PRIMARY KEY,
    year INT,
    term VARCHAR(50),
    UNIQUE (year, term)
)", "session_details");

createTable($conn, "
CREATE TABLE IF NOT EXISTS attendance_details (
    faculty_id INT,
    course_id INT,
    session_id INT,
    student_id INT,
    on_date DATE,
    status VARCHAR(10),
    PRIMARY KEY (faculty_id, course_id, session_id, student_id, on_date)
)", "attendance_details");

createTable($conn, "
CREATE TABLE IF NOT EXISTS notifications (
    id INT AUTO_INCREMENT PRIMARY KEY,
    faculty_user VARCHAR(50) NOT NULL,
    type ENUM('create','update','delete') NOT NULL,
    title VARCHAR(120) NOT NULL,
    message TEXT NOT NULL,
    is_read TINYINT(1) DEFAULT 0,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
)", "notifications");

// ===========================================================
// 2️ INSERT DEFAULT DATA
// ===========================================================

insertData($conn, "
INSERT INTO student_details (id, roll_no, name) VALUES
(1,'CSE-192','ss joy'),
(2,'CSE-192','sani')

", "student_details");


insertData($conn, "
INSERT INTO faculty_details (id, user_name, password, name) VALUES
(1,'anika','123','Anika Akter'),
(2,'kawsir','123','kawser'),
(3,'najma','123','Najma Akter'),
(4,'saima','123','Saima Akter'),
(5,'shanchayan','123','sanchayan battacharjje'),
(6,'manooj','123','Manooj Hazarika'),
(7,'joy','123','ss joy')
", "faculty_details");

insertData($conn, "
INSERT INTO session_details (id, year, term) VALUES
(1,2023,'SPRING SEMESTER'),
(2,2023,'AUTUMN SEMESTER')
", "session_details");

insertData($conn, "
INSERT INTO course_details (id, title, code, credit) VALUES
(1,'software engineering','CS1',2),
(2,'Embedded management system','CS2',3),
(3,'Computer networking','CS3',3),
(4,'Artificial Intelligence','CS4',4),
(5,'Theory of Computation','Cs5',3),
(6,'Demystifying Networking','CS6',1)
", "course_details");

// ===========================================================
//  FINISH
// ===========================================================
echo "<br><br>🎉 All tables created and data inserted successfully.";

mysqli_close($conn);
?>
