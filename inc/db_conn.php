<?php
$db = new PDO("mysql:dbname=DATABASE_NAME;host=HOST_ADDRESS","USER_NAME","USER_PASSWORD");
$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
$db->query("SET NAMES utf8");
