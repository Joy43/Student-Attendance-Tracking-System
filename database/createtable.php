<?php
// Include the database connection
$path = isset($_SERVER['DOCUMENT_ROOT']) && $_SERVER['DOCUMENT_ROOT'] !== '' ? $_SERVER['DOCUMENT_ROOT'] : dirname(__DIR__, 2);
require_once $path . "/attapp/database/database.php";

// Turn off strict exception reporting for graceful duplicate handling
mysqli_report(MYSQLI_REPORT_OFF);

// ✅ Helper: Create table safely
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

// ===========================================================
// 2️⃣ INSERT DEFAULT DATA
// ===========================================================

insertData($conn, "
INSERT INTO student_details (id, roll_no, name) VALUES
(1,'CSB21001','nafeem'),
(2,'CSB21002','sobuj'),
(3,'CSB21003','Shehab'),
(4,'CSB21004','mostafa'),
(5,'CSB21005','nidhi'),
(6,'CSB21006','ridhi'),
(7,'CSB21007','khandakar'),
(8,'CSB21008','shifat'),
(9,'CSB21009','ibad'),
(10,'CSB21010','James Jones')
", "student_details");

insertData($conn, "
INSERT INTO faculty_details (id, user_name, password, name) VALUES
(1,'anika','123','Anika Akter'),
(2,'kawsir','123','kawser'),
(3,'najma','123','Najma Akter'),
(4,'saima','123','Saima Akter'),
(5,'shanchayan','123','sanchayan battacharjje'),
(6,'manooj','123','Manooj Hazarika')
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
// ✅ FINISH
// ===========================================================
echo "<br><br>🎉 All tables created and data inserted successfully.";

mysqli_close($conn);
?>
