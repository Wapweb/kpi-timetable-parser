<?php
/**
 * Created by PhpStorm.
 * User: Шаповал
 * Date: 20.09.14
 * Time: 22:39
 */

class ParserKpiLessonContext {
    /** @var  ParserKpiLessonStrategy $_strategy */
    private $_strategy;

    public function __construct(ParserKpiLessonStrategy $strategy)
    {
        $this->_strategy = $strategy;
    }

    public function parse($string) {
        return $this->_strategy->parse($string);
    }
} 