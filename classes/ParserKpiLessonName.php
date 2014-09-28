<?php
/**
 * Created by PhpStorm.
 * User: Шаповал
 * Date: 20.09.14
 * Time: 22:15
 */

class ParserKpiLessonName implements ParserKpiLessonStrategy {

    /**
     * @param $string
     * @return array
     */
    public function parse($string){
        return array('lesson_name' => trim($string));
    }
} 