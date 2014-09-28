<?php
/**
 * Created by PhpStorm.
 * User: Шаповал
 * Date: 20.09.14
 * Time: 22:14
 */

interface ParserKpiLessonStrategy {

    /**
     * @param $string
     * @return array
     */
    function parse($string);
} 