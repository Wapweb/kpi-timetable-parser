<?php
/**
 * Created by PhpStorm.
 * User: Шаповал
 * Date: 20.09.14
 * Time: 22:16
 */

class ParserKpiLessonRoomType implements ParserKpiLessonStrategy {

    /**
     * @param $string
     * @return array
     */
    public function parse($string) {

        //TODO-me надо как бы переписать

        $lesson_room = "";
        $lesson_type = "";
        $rate = 1;

        $string = trim($string);
        if(empty($string)) {
            return array('lesson_room'=>$lesson_room,'lesson_type'=>$lesson_type,'rate' => $rate);
        }

        if(substr_count($string,"0,5") > 0 || substr_count($string,"0.5") > 0)
        {
            $rate = 0.5;
            $string = str_replace("0,5","",$string);
            $string = str_replace("0.5","",$string);
        }

        if(substr_count($string,"1.5") > 0 || substr_count($string,"1.5") > 0)
        {
            $rate = 1.5;
            $string = str_replace("1,5","",$string);
            $string = str_replace("1.5","",$string);
        }
        $string = preg_replace('/\s+/u', ' ',$string);

        if(substr_count($string,",") > 0)
        {
            $chunksNext = explode(",",$string);
            $chunksNext1 = explode(" ",trim($chunksNext[0]));
            $chunksNext2 = explode(" ",trim($chunksNext[1]));


            $lesson_room = isset($chunksNext1[0]) ? trim($chunksNext1[0].",".$chunksNext2[0]) : "";
            if(count($chunksNext) > 2)
            {
                $chunksNext3 = explode(" ",trim($chunksNext[2]));
                $lesson_room.=",".trim($chunksNext3[0]);
            }
            $lesson_type = isset($chunksNext1[1]) ? trim($chunksNext1[1]) : "";
        }
        else
        {
            if(substr_count($string," ") == 1)
            {
                $chunksNext = explode(" ",$string);
                $lesson_room = isset($chunksNext[0]) ? trim($chunksNext[0]) : "";
                $lesson_type = isset($chunksNext[1]) ? trim($chunksNext[1]) : "";
            }
            else
            {
                if(substr_count($string,"Лек") > 0 )
                {
                    $lesson_type = "Лек";
                    $lesson_room  = trim(str_replace($lesson_type,"",$string));
                }

                if(substr_count($string,"Лаб") > 0 )
                {
                    $lesson_type = "Лаб";
                    $lesson_room  = trim(str_replace($lesson_type,"",$string));
                }

                if(substr_count($string,"Прак") > 0 )
                {
                    $lesson_type = "Прак";
                    $lesson_room  = trim(str_replace($lesson_type,"",$string));
                }

                if(!$lesson_type)
                {
                    $lesson_room = trim($string);
                }
            }
        }

        return array('lesson_room'=>$lesson_room,'lesson_type'=>$lesson_type,'rate' => $rate);
    }
} 