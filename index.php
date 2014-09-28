<?php
$root = realpath($_SERVER["DOCUMENT_ROOT"]);

require_once $root.DIRECTORY_SEPARATOR.'inc'.DIRECTORY_SEPARATOR.'db_conn.php';

spl_autoload_register(function ($class) {
    require_once 'classes'.DIRECTORY_SEPARATOR.$class.".php";
});

Registry::set("db",$db);


// parse groups
$ParserKpiGroups = new ParserKpiGroups();
$ParserKpiGroups->parseGroups();
$ParserKpiGroups->parseEnglishGroups();

// parse lessons
$ParserKpiLessons = new ParserKpiLesson();
$ParserKpiLessons->parseLessonsFromDb();