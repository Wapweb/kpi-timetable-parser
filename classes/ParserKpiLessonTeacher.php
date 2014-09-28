<?php
/**
 * Created by PhpStorm.
 * User: Шаповал
 * Date: 20.09.14
 * Time: 22:16
 */

class ParserKpiLessonTeacher implements ParserKpiLessonStrategy {

    /**
     * @param $string
     * @return array
     */
    public function parse($string) {
        return array('teacher_name' => trim($string));
    }
} 