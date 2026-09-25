<?php
/*
|--------------------------------------------------------------------------
| EDUCATION WEBSITE - ONE FILE VERSION
|--------------------------------------------------------------------------
| File: index.php
|
| Features:
| Class
| Subject
| Chapter
| Lesson
| Free / Paid
| Lesson Content
| Question
| Answer
| Admin Login
| Admin Dashboard
| ShukhiMart Paid Lesson
|--------------------------------------------------------------------------
*/

session_start();

/* =========================
   DATABASE CONFIG
========================= */

$dbHost = "localhost";
$dbName = "education_site";
$dbUser = "root";
$dbPass = "";

/* =========================
   SHUKHIMART
========================= */

$shukhimartBook =
    "https://shukhimart.com.bd/product/online-book-UDzKcg";

/* =========================
   DATABASE CONNECT
========================= */

try {

    $pdo = new PDO(
        "mysql:host=$dbHost;charset=utf8mb4",
        $dbUser,
        $dbPass,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
        ]
    );

    $pdo->exec("
        CREATE DATABASE IF NOT EXISTS `$dbName`
        CHARACTER SET utf8mb4
        COLLATE utf8mb4_unicode_ci
    ");

    $pdo->exec("USE `$dbName`");

} catch (Exception $e) {

    die("Database connection error: " . $e->getMessage());

}

/* =========================
   CREATE TABLES
========================= */

$pdo->exec("
CREATE TABLE IF NOT EXISTS classes (

    id INT AUTO_INCREMENT PRIMARY KEY,

    name VARCHAR(255) NOT NULL,

    status TINYINT(1) DEFAULT 1,

    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP

) ENGINE=InnoDB
");

$pdo->exec("
CREATE TABLE IF NOT EXISTS subjects (

    id INT AUTO_INCREMENT PRIMARY KEY,

    class_id INT NOT NULL,

    name VARCHAR(255) NOT NULL,

    status TINYINT(1) DEFAULT 1,

    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP

) ENGINE=InnoDB
");

$pdo->exec("
CREATE TABLE IF NOT EXISTS chapters (

    id INT AUTO_INCREMENT PRIMARY KEY,

    subject_id INT NOT NULL,

    chapter_number INT NOT NULL,

    name VARCHAR(255) NOT NULL,

    status TINYINT(1) DEFAULT 1,

    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP

) ENGINE=InnoDB
");

$pdo->exec("
CREATE TABLE IF NOT EXISTS lessons (

    id INT AUTO_INCREMENT PRIMARY KEY,

    chapter_id INT NOT NULL,

    lesson_number INT NOT NULL,

    name VARCHAR(255) NOT NULL,

    is_free TINYINT(1) DEFAULT 0,

    status TINYINT(1) DEFAULT 1,

    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP

) ENGINE=InnoDB
");

$pdo->exec("
CREATE TABLE IF NOT EXISTS contents (

    id INT AUTO_INCREMENT PRIMARY KEY,

    lesson_id INT NOT NULL,

    title VARCHAR(255) NOT NULL,

    content LONGTEXT NOT NULL,

    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP

) ENGINE=InnoDB
");

$pdo->exec("
CREATE TABLE IF NOT EXISTS questions (

    id INT AUTO_INCREMENT PRIMARY KEY,

    lesson_id INT NOT NULL,

    question TEXT NOT NULL,

    marks INT DEFAULT NULL,

    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP

) ENGINE=InnoDB
");

$pdo->exec("
CREATE TABLE IF NOT EXISTS answers (

    id INT AUTO_INCREMENT PRIMARY KEY,

    question_id INT NOT NULL,

    answer LONGTEXT NOT NULL,

    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP

) ENGINE=InnoDB
");

/* =========================
   DEFAULT ADMIN
========================= */

$pdo->exec("
CREATE TABLE IF NOT EXISTS admins (

    id INT AUTO_INCREMENT PRIMARY KEY,

    email VARCHAR(255) UNIQUE NOT NULL,

    password VARCHAR(255) NOT NULL

) ENGINE=InnoDB
");

$adminExists = $pdo
    ->query("SELECT COUNT(*) FROM admins")
    ->fetchColumn();

if (!$adminExists) {

    $password = password_hash(
        "admin123",
        PASSWORD_DEFAULT
    );

    $stmt = $pdo->prepare("
        INSERT INTO admins(email,password)
        VALUES(?,?)
    ");

    $stmt->execute([
        "admin@example.com",
        $password
    ]);
}

/* =========================
   HELPER
========================= */

function e($value)
{
    return htmlspecialchars(
        $value ?? "",
        ENT_QUOTES,
        "UTF-8"
    );
}

function admin()
{
    return isset($_SESSION["admin_id"]);
}

/* =========================
   ADMIN LOGIN
========================= */

if (
    isset($_POST["login"])
) {

    $email = $_POST["email"] ?? "";
    $password = $_POST["password"] ?? "";

    $stmt = $GLOBALS["pdo"]->prepare("
        SELECT *
        FROM admins
        WHERE email = ?
        LIMIT 1
    ");

    $stmt->execute([$email]);

    $user = $stmt->fetch();

    if (
        $user &&
        password_verify(
            $password,
            $user["password"]
        )
    ) {

        $_SESSION["admin_id"] = $user["id"];

        header(
            "Location: ?page=admin"
        );

        exit;

    }

    $loginError = "Email অথবা Password ভুল।";
}

/* =========================
   LOGOUT
========================= */

if (
    isset($_GET["logout"])
) {

    session_destroy();

    header(
        "Location: ?page=login"
    );

    exit;
}

/* =========================
   ADMIN ACTIONS
========================= */

if (
    admin() &&
    isset($_POST["action"])
) {

    $action = $_POST["action"];

    /* CLASS */

    if ($action == "add_class") {

        $stmt = $pdo->prepare("
            INSERT INTO classes(name)
            VALUES(?)
        ");

        $stmt->execute([
            $_POST["name"]
        ]);

    }

    if ($action == "delete_class") {

        $stmt = $pdo->prepare("
            DELETE FROM classes
            WHERE id=?
        ");

        $stmt->execute([
            $_POST["id"]
        ]);
    }

    /* SUBJECT */

    if ($action == "add_subject") {

        $stmt = $pdo->prepare("
            INSERT INTO subjects
            (class_id,name)
            VALUES(?,?)
        ");

        $stmt->execute([
            $_POST["class_id"],
            $_POST["name"]
        ]);
    }

    if ($action == "delete_subject") {

        $stmt = $pdo->prepare("
            DELETE FROM subjects
            WHERE id=?
        ");

        $stmt->execute([
            $_POST["id"]
        ]);
    }

    /* CHAPTER */

    if ($action == "add_chapter") {

        $stmt = $pdo->prepare("
            INSERT INTO chapters
            (subject_id,chapter_number,name)
            VALUES(?,?,?)
        ");

        $stmt->execute([
            $_POST["subject_id"],
            $_POST["chapter_number"],
            $_POST["name"]
        ]);
    }

    if ($action == "delete_chapter") {

        $stmt = $pdo->prepare("
            DELETE FROM chapters
            WHERE id=?
        ");

        $stmt->execute([
            $_POST["id"]
        ]);
    }

    /* LESSON */

    if ($action == "add_lesson") {

        $stmt = $pdo->prepare("
            INSERT INTO lessons
            (
                chapter_id,
                lesson_number,
                name,
                is_free
            )
            VALUES(?,?,?,?)
        ");

        $stmt->execute([
            $_POST["chapter_id"],
            $_POST["lesson_number"],
            $_POST["name"],
            isset($_POST["is_free"]) ? 1 : 0
        ]);
    }

    if ($action == "delete_lesson") {

        $stmt = $pdo->prepare("
            DELETE FROM lessons
            WHERE id=?
        ");

        $stmt->execute([
            $_POST["id"]
        ]);
    }

    /* CONTENT */

    if ($action == "add_content") {

        $stmt = $pdo->prepare("
            INSERT INTO contents
            (
                lesson_id,
                title,
                content
            )
            VALUES(?,?,?)
        ");

        $stmt->execute([
            $_POST["lesson_id"],
            $_POST["title"],
            $_POST["content"]
        ]);
    }

    /* QUESTION */

    if ($action == "add_question") {

        $stmt = $pdo->prepare("
            INSERT INTO questions
            (
                lesson_id,
                question,
                marks
            )
            VALUES(?,?,?)
        ");

        $stmt->execute([
            $_POST["lesson_id"],
            $_POST["question"],
            $_POST["marks"] ?: null
        ]);
    }

    /* ANSWER */

    if ($action == "add_answer") {

        $stmt = $pdo->prepare("
            INSERT INTO answers
            (
                question_id,
                answer
            )
            VALUES(?,?)
        ");

        $stmt->execute([
            $_POST["question_id"],
            $_POST["answer"]
        ]);
    }

    header(
        "Location: ?page=admin"
    );

    exit;
}

/* =========================
   LOGIN PAGE
========================= */

if (
    ($_GET["page"] ?? "") == "login"
    &&
    !admin()
) {

?>

<!DOCTYPE html>

<html lang="bn">

<head>

<meta charset="UTF-8">

<meta name="viewport"
content="width=device-width,initial-scale=1">

<title>Admin Login</title>

<style>

body{
    margin:0;
    background:#f4f6fb;
    font-family:Arial,sans-serif;
}

.login{
    width:360px;
    max-width:90%;
    margin:100px auto;
    background:white;
    padding:30px;
    border-radius:15px;
    box-shadow:0 5px 25px #ddd;
}

input,select,textarea,button{
    width:100%;
    padding:12px;
    margin:7px 0;
    box-sizing:border-box;
}

button{
    background:#673ab7;
    color:white;
    border:0;
    border-radius:7px;
    cursor:pointer;
}

.error{
    color:red;
}

</style>

</head>

<body>

<div class="login">

<h2>🔐 Admin Login</h2>

<?php

if(isset($loginError)){

echo "<p class='error'>"
    .e($loginError).
    "</p>";

}

?>

<form method="post">

<input
type="email"
name="email"
placeholder="Admin Email"
required
>

<input
type="password"
name="password"
placeholder="Password"
required
>

<button name="login">
Login
</button>

</form>

</div>

</body>

</html>

<?php

exit;

}

/* =========================
   WEBSITE DATA
========================= */

$classes = $pdo
    ->query("
        SELECT *
        FROM classes
        WHERE status=1
        ORDER BY id
    ")
    ->fetchAll();

$selectedClass =
    intval($_GET["class"] ?? 0);

$selectedSubject =
    intval($_GET["subject"] ?? 0);

$selectedChapter =
    intval($_GET["chapter"] ?? 0);

$selectedLesson =
    intval($_GET["lesson"] ?? 0);

$subjects = [];

$chapters = [];

$lessons = [];

$lessonData = null;

if ($selectedClass) {

    $stmt = $pdo->prepare("
        SELECT *
        FROM subjects
        WHERE class_id=?
        AND status=1
        ORDER BY id
    ");

    $stmt->execute([
        $selectedClass
    ]);

    $subjects = $stmt->fetchAll();
}

if ($selectedSubject) {

    $stmt = $pdo->prepare("
        SELECT *
        FROM chapters
        WHERE subject_id=?
        AND status=1
        ORDER BY chapter_number
    ");

    $stmt->execute([
        $selectedSubject
    ]);

    $chapters = $stmt->fetchAll();
}

if ($selectedChapter) {

    $stmt = $pdo->prepare("
        SELECT *
        FROM lessons
        WHERE chapter_id=?
        AND status=1
        ORDER BY lesson_number
    ");

    $stmt->execute([
        $selectedChapter
    ]);

    $lessons = $stmt->fetchAll();
}

if ($selectedLesson) {

    $stmt = $pdo->prepare("
        SELECT *
        FROM lessons
        WHERE id=?
        LIMIT 1
    ");

    $stmt->execute([
        $selectedLesson
    ]);

    $lessonData = $stmt->fetch();

    if (
        $lessonData &&
        !$lessonData["is_free"]
    ) {

        header(
            "Location: ".$shukhimartBook
        );

        exit;
    }
}

/* =========================
   ADMIN PAGE
========================= */

if (
    ($_GET["page"] ?? "") == "admin"
    &&
    admin()
) {

$allClasses =
    $pdo->query("
        SELECT *
        FROM classes
        ORDER BY id DESC
    ")->fetchAll();

$allSubjects =
    $pdo->query("
        SELECT
            subjects.*,
            classes.name AS class_name
        FROM subjects

        LEFT JOIN classes
        ON classes.id=subjects.class_id

        ORDER BY subjects.id DESC

    ")->fetchAll();

$allChapters =
    $pdo->query("
        SELECT
            chapters.*,
            subjects.name AS subject_name
        FROM chapters

        LEFT JOIN subjects
        ON subjects.id=chapters.subject_id

        ORDER BY chapters.id DESC

    ")->fetchAll();

$allLessons =
    $pdo->query("
        SELECT
            lessons.*,
            chapters.name AS chapter_name
        FROM lessons

        LEFT JOIN chapters
        ON chapters.id=lessons.chapter_id

        ORDER BY lessons.id DESC

    ")->fetchAll();

$allContents =
    $pdo->query("
        SELECT
            contents.*,
            lessons.name AS lesson_name
        FROM contents

        LEFT JOIN lessons
        ON lessons.id=contents.lesson_id

        ORDER BY contents.id DESC

    ")->fetchAll();

$allQuestions =
    $pdo->query("
        SELECT
            questions.*,
            lessons.name AS lesson_name
        FROM questions

        LEFT JOIN lessons
        ON lessons.id=questions.lesson_id

        ORDER BY questions.id DESC

    ")->fetchAll();

$allQuestionsForAnswer =
    $pdo->query("
        SELECT *
        FROM questions
        ORDER BY id DESC
    ")->fetchAll();

?>

<!DOCTYPE html>

<html lang="bn">

<head>

<meta charset="UTF-8">

<meta name="viewport"
content="width=device-width,initial-scale=1">

<title>Education Admin</title>

<style>

*{
    box-sizing:border-box;
}

body{
    margin:0;
    background:#f5f6fa;
    font-family:Arial,"Noto Sans Bengali",sans-serif;
}

header{
    background:linear-gradient(
        135deg,
        #673ab7,
        #ff7a00
    );

    color:white;
    padding:25px;
}

.container{
    width:95%;
    max-width:1200px;
    margin:auto;
}

nav{
    margin-top:15px;
}

nav a{
    color:white;
    text-decoration:none;
    margin-right:20px;
}

.card{
    background:white;
    padding:20px;
    margin:20px 0;
    border-radius:15px;
    box-shadow:0 3px 15px #ddd;
}

input,select,textarea,button{
    padding:11px;
    margin:5px 0;
    width:100%;
}

textarea{
    min-height:120px;
}

button{
    background:#673ab7;
    color:white;
    border:0;
    border-radius:7px;
}

table{
    width:100%;
    border-collapse:collapse;
}

th,td{
    padding:10px;
    border-bottom:1px solid #ddd;
    text-align:left;
}

.free{
    color:green;
    font-weight:bold;
}

.paid{
    color:#d97706;
    font-weight:bold;
}

</style>

</head>

<body>

<header>

<div class="container">

<h1>📚 Education Admin Panel</h1>

<nav>

<a href="?page=admin">
Dashboard
</a>

<a href="?logout=1">
Logout
</a>

</nav>

</div>

</header>

<div class="container">

<!-- =====================
     CLASS
===================== -->

<div class="card">

<h2>📚 Class</h2>

<form method="post">

<input
type="hidden"
name="action"
value="add_class"
>

<input
name="name"
placeholder="যেমন: একাদশ / দ্বাদশ"
required
>

<button>
+ Add Class
</button>

</form>

<table>

<tr>
<th>ID</th>
<th>Name</th>
</tr>

<?php foreach(
$allClasses as $item
): ?>

<tr>

<td>
<?=e($item["id"])?>
</td>

<td>
<?=e($item["name"])?>
</td>

</tr>

<?php endforeach; ?>

</table>

</div>


<!-- =====================
     SUBJECT
===================== -->

<div class="card">

<h2>📖 Subject</h2>

<form method="post">

<input
type="hidden"
name="action"
value="add_subject"
>

<select
name="class_id"
required
>

<option value="">
Class নির্বাচন করুন
</option>

<?php foreach(
$allClasses as $class
): ?>

<option
value="<?=$class["id"]?>"
>

<?=e($class["name"])?>

</option>

<?php endforeach; ?>

</select>

<input
name="name"
placeholder="যেমন: ইসলাম শিক্ষা"
required
>

<button>
+ Add Subject
</button>

</form>

</div>


<!-- =====================
     CHAPTER
===================== -->

<div class="card">

<h2>📑 Chapter</h2>

<form method="post">

<input
type="hidden"
name="action"
value="add_chapter"
>

<select
name="subject_id"
required
>

<option value="">
Subject নির্বাচন করুন
</option>

<?php foreach(
$allSubjects as $subject
): ?>

<option
value="<?=$subject["id"]?>"
>

<?=e(
$subject["class_name"]
)?>
 -
<?=e(
$subject["name"]
)?>

</option>

<?php endforeach; ?>

</select>

<input
type="number"
name="chapter_number"
placeholder="Chapter Number"
required
>

<input
name="name"
placeholder="অধ্যায় ১"
required
>

<button>
+ Add Chapter
</button>

</form>

</div>


<!-- =====================
     LESSON
===================== -->

<div class="card">

<h2>📝 Lesson</h2>

<form method="post">

<input
type="hidden"
name="action"
value="add_lesson"
>

<select
name="chapter_id"
required
>

<option value="">
Chapter নির্বাচন করুন
</option>

<?php foreach(
$allChapters as $chapter
): ?>

<option
value="<?=$chapter["id"]?>"
>

<?=e(
$chapter["name"]
)?>

</option>

<?php endforeach; ?>

</select>

<input
type="number"
name="lesson_number"
placeholder="Lesson Number"
required
>

<input
name="name"
placeholder="পাঠ ১"
required
>

<label>

<input
type="checkbox"
name="is_free"
value="1"
style="width:auto"
>

🟢 Free Lesson

</label>

<button>
+ Add Lesson
</button>

</form>


<h3>বর্তমান Lesson</h3>

<table>

<tr>

<th>Lesson</th>

<th>Status</th>

</tr>

<?php foreach(
$allLessons as $lesson
): ?>

<tr>

<td>

পাঠ
<?=e(
$lesson["lesson_number"]
)?>
:
<?=e(
$lesson["name"]
)?>

</td>

<td>

<?php if(
$lesson["is_free"]
): ?>

<span class="free">
🟢 FREE
</span>

<?php else: ?>

<span class="paid">
🔒 PAID
</span>

<?php endif; ?>

</td>

</tr>

<?php endforeach; ?>

</table>

</div>


<!-- =====================
     CONTENT
===================== -->

<div class="card">

<h2>📄 Lesson Content</h2>

<form method="post">

<input
type="hidden"
name="action"
value="add_content"
>

<select
name="lesson_id"
required
>

<option value="">
Lesson নির্বাচন করুন
</option>

<?php foreach(
$allLessons as $lesson
): ?>

<option
value="<?=$lesson["id"]?>"
>

<?=e(
$lesson["name"]
)?>

</option>

<?php endforeach; ?>

</select>

<input
name="title"
placeholder="Content Title"
required
>

<textarea
name="content"
placeholder="এখানে সম্পূর্ণ Lesson লিখুন..."
required
></textarea>

<button>
+ Add Content
</button>

</form>

</div>


<!-- =====================
     QUESTION
===================== -->

<div class="card">

<h2>❓ Question</h2>

<form method="post">

<input
type="hidden"
name="action"
value="add_question"
>

<select
name="lesson_id"
required
>

<option value="">
Lesson নির্বাচন করুন
</option>

<?php foreach(
$allLessons as $lesson
): ?>

<option
value="<?=$lesson["id"]?>"
>

<?=e(
$lesson["name"]
)?>

</option>

<?php endforeach; ?>

</select>

<textarea
name="question"
placeholder="প্রশ্ন লিখুন..."
required
></textarea>

<input
type="number"
name="marks"
placeholder="Marks"
>

<button>
+ Add Question
</button>

</form>

</div>


<!-- =====================
     ANSWER
===================== -->

<div class="card">

<h2>✅ Answer</h2>

<form method="post">

<input
type="hidden"
name="action"
value="add_answer"
>

<select
name="question_id"
required
>

<option value="">
Question নির্বাচন করুন
</option>

<?php foreach(
$allQuestionsForAnswer
as $question
): ?>

<option
value="<?=$question["id"]?>"
>

<?=e(
mb_substr(
$question["question"],
0,
80
)
)?>

</option>

<?php endforeach; ?>

</select>

<textarea
name="answer"
placeholder="উত্তর লিখুন..."
required
></textarea>

<button>
+ Add Answer
</button>

</form>

</div>

</div>

</body>

</html>

<?php

exit;

}

/* =========================
   PUBLIC WEBSITE
========================= */

?>

<!DOCTYPE html>

<html lang="bn">

<head>

<meta charset="UTF-8">

<meta name="viewport"
content="width=device-width,initial-scale=1">

<title>
Education Website
</title>

<style>

*{
    box-sizing:border-box;
}

body{

    margin:0;

    background:#f5f7fb;

    font-family:
    Arial,
    "Noto Sans Bengali",
    sans-serif;

    color:#222;
}

.header{

    background:
    linear-gradient(
        135deg,
        #673ab7,
        #ff7a00
    );

    color:white;

    padding:35px 0;

}

.container{

    width:94%;

    max-width:1100px;

    margin:auto;

}

.header h1{

    margin:0;

    font-size:32px;

}

.header p{

    margin-top:8px;

}

.card{

    background:white;

    padding:22px;

    margin:20px 0;

    border-radius:15px;

    box-shadow:
    0 4px 15px
    rgba(0,0,0,.07);

}

.grid{

    display:grid;

    grid-template-columns:
    repeat(
        auto-fit,
        minmax(200px,1fr)
    );

    gap:12px;

}

.item{

    display:block;

    background:white;

    border:1px solid #ddd;

    padding:16px;

    border-radius:12px;

    text-decoration:none;

    color:#222;

}

.item:hover{

    border-color:#673ab7;

}

.lesson{

    display:flex;

    justify-content:space-between;

    align-items:center;

    gap:15px;

    padding:15px;

    margin:8px 0;

    border:1px solid #ddd;

    border-radius:12px;

}

.lesson a{

    background:#673ab7;

    color:white;

    padding:10px 15px;

    border-radius:8px;

    text-decoration:none;

}

.free{

    color:#198754;

    font-weight:bold;

}

.paid{

    color:#d97706;

    font-weight:bold;

}

.content{

    line-height:1.8;

}

.question{

    background:#f7f8fa;

    padding:15px;

    margin:10px 0;

    border-radius:10px;

}

@media(max-width:600px){

    .lesson{

        flex-direction:column;

        align-items:flex-start;

    }

}

</style>

</head>

<body>

<header class="header">

<div class="container">

<h1>
📚 শিক্ষা প্ল্যাটফর্ম
</h1>

<p>
সহজে পড়ুন • শিখুন • প্রস্তুতি নিন
</p>

</div>

</header>


<main class="container">


<!-- CLASS -->

<div class="card">

<h2>
১. শ্রেণি
</h2>

<div class="grid">

<?php foreach(
$classes as $class
): ?>

<a
class="item"
href="?class=<?=$class["id"]?>"
>

📚
<?=e(
$class["name"]
)?>

</a>

<?php endforeach; ?>

</div>

</div>


<!-- SUBJECT -->

<?php if(
$selectedClass
): ?>

<div class="card">

<h2>
২. বিষয়
</h2>

<div class="grid">

<?php foreach(
$subjects as $subject
): ?>

<a
class="item"

href="?class=<?=$selectedClass?>&subject=<?=$subject["id"]?>"
>

📖
<?=e(
$subject["name"]
)?>

</a>

<?php endforeach; ?>

</div>

</div>

<?php endif; ?>


<!-- CHAPTER -->

<?php if(
$selectedSubject
): ?>

<div class="card">

<h2>
৩. অধ্যায়
</h2>

<div class="grid">

<?php foreach(
$chapters as $chapter
): ?>

<a
class="item"

href="?class=<?=$selectedClass?>&subject=<?=$selectedSubject?>&chapter=<?=$chapter["id"]?>"
>

📑 অধ্যায়
<?=e(
$chapter["chapter_number"]
)?>
:
<?=e(
$chapter["name"]
)?>

</a>

<?php endforeach; ?>

</div>

</div>

<?php endif; ?>


<!-- LESSON -->

<?php if(
$selectedChapter
): ?>

<div class="card">

<h2>
৪. পাঠ
</h2>

<?php foreach(
$lessons as $lesson
): ?>

<div class="lesson">

<div>

<strong>

পাঠ
<?=e(
$lesson["lesson_number"]
)?>
:
<?=e(
$lesson["name"]
)?>

</strong>

<?php if(
$lesson["is_free"]
): ?>

<span class="free">
🟢 FREE
</span>

<?php else: ?>

<span class="paid">
🔒 PAID
</span>

<?php endif; ?>

</div>


<a href="
?class=<?=$selectedClass?>
&subject=<?=$selectedSubject?>
&chapter=<?=$selectedChapter?>
&lesson=<?=$lesson["id"]?>
">

<?php if(
$lesson["is_free"]
): ?>

পড়ুন

<?php else: ?>

কিনুন / Paid Version

<?php endif; ?>

</a>

</div>

<?php endforeach; ?>

</div>

<?php endif; ?>


<!-- LESSON CONTENT -->

<?php if(
$lessonData &&
$lessonData["is_free"]
): ?>

<div class="card content">

<h2>

<?=e(
$lessonData["name"]
)?>

</h2>

<?php

$stmt = $pdo->prepare("
    SELECT *
    FROM contents
    WHERE lesson_id=?
    ORDER BY id
");

$stmt->execute([
    $lessonData["id"]
]);

$contents =
$stmt->fetchAll();

?>

<?php foreach(
$contents as $content
): ?>

<h3>

<?=e(
$content["title"]
)?>

</h3>

<div>

<?=nl2br(
e($content["content"])
)?>

</div>

<?php endforeach; ?>


<h2>
❓ প্রশ্ন ও উত্তর
</h2>

<?php

$stmt = $pdo->prepare("
    SELECT *
    FROM questions
    WHERE lesson_id=?
    ORDER BY id
");

$stmt->execute([
    $lessonData["id"]
]);

$questions =
$stmt->fetchAll();

?>

<?php foreach(
$questions as $question
): ?>

<div class="question">

<strong>
প্রশ্ন:
</strong>

<?=e(
$question["question"]
)?>

<?php

$stmt2 = $pdo->prepare("
    SELECT *
    FROM answers
    WHERE question_id=?
");

$stmt2->execute([
    $question["id"]
]);

$answers =
$stmt2->fetchAll();

?>

<?php foreach(
$answers as $answer
): ?>

<p>

<strong>
উত্তর:
</strong>

<?=nl2br(
e($answer["answer"])
)?>

</p>

<?php endforeach; ?>

</div>

<?php endforeach; ?>

</div>

<?php endif; ?>


</main>

</body>

</html>