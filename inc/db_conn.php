<?php
$db = new PDO("mysql:dbname=rozklad;host=localhost","wapweb","1111");
$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
$db->query("SET NAMES utf8");