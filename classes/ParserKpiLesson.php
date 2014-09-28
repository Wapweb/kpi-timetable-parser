<?php
/**
 * Created by PhpStorm.
 * User: Шаповал
 * Date: 20.09.14
 * Time: 22:17
 */

class ParserKpiLesson {

    /** @var  PDO $_db */
    private $_db;

    private $_time =  array(
        1 => array('time_start'=>'08:30','time_end'=>'10:05'),
        2 => array('time_start'=>'10:25','time_end'=>'12:00'),
        3 => array('time_start'=>'12:20','time_end'=>'13:55'),
        4 => array('time_start'=>'14:15','time_end'=>'15:50'),
        5 => array('time_start'=>'16:10','time_end'=>'17:45'),
    );

    private  $_days = array(
        'Понеділок',
        'Вівторок',
        'Середа',
        'Четвер',
        'П’ятниця',
        'Субота'
    );

    public function __construct() {
        /** @var PDO _db */
        $this->_db = Registry::get("db");
    }

    public function parseLessonFromString($string,$delimiter="@"){
        $splits = explode($delimiter,$string);

        $parserLessonName = new ParserKpiLessonContext(new ParserKpiLessonName());
        $lessonName = $parserLessonName->parse($splits[0]);

        $parserLessonTeacher = new ParserKpiLessonContext(new ParserKpiLessonTeacher());
        $lessonTeacher = $parserLessonTeacher->parse($splits[1]);

        $parserLessonRoomType = new ParserKpiLessonContext(new ParserKpiLessonRoomType());
        $lessonRoomType = $parserLessonRoomType->parse($splits[2]);

        return array(
            'lesson_name'=>$lessonName['lesson_name'],
            'teacher_name'=>$lessonTeacher['teacher_name'],
            'rate'=>$lessonRoomType['rate'],
            'lesson_type'=>$lessonRoomType['lesson_type'],
            'lesson_room'=>$lessonRoomType['lesson_room'],
            'lesson_room_type'=>$splits[2]
        );
    }

    public function parseLessonsFromDb()
    {
        $this->_db->query("TRUNCATE TABLE `lesson`");
        $query = $this->_db->query("SELECT * FROM `group`");
        while($m = $query->fetch(PDO::FETCH_ASSOC)) {

            if($m['group_anomaly']) $m["group_url"] = "http://rozklad.kpi.ua/Schedules/".$m["group_url"];
            $content = $this->getResponse($m["group_url"]);
            $htmlTables = $this->getHtmlTables($content);
            $this->convertHtmlTableToArrayAndSave($htmlTables[0],$m['group_id'],1);
            $this->convertHtmlTableToArrayAndSave($htmlTables[1],$m['group_id'],2);

        }
    }

    private function convertHtmlTableToArrayAndSave($htmlTable,$group_id,$lesson_week)
    {
        $dom = new DOMDocument();
        $dom->loadHTML('<?xml encoding="utf-8" ?>'.$htmlTable);
        $res = $dom->getElementsByTagName("tr");
        $count = $res->length;
        for($i=0;$i<$count;$i++)
        {
            $tr = $res->item($i);
            $tds = $tr->getElementsByTagName('td');
            $countB = $tds->length;
            /** @var  DOMNode $td*/
            for($b=0;$b<$countB;$b++)
            {

                $lesson_number = $i;
                $day_number = $b;

                if($lesson_number < 1 || $lesson_number > 5) continue;
                if($day_number < 1 || $day_number > 6) continue;

                $cell = $tds->item($b);

                if(!empty($cell->nodeValue)) {
                    $string = $cell->textContent;
                    $data = $this->parseLessonFromString($string);
                    $data['time_start'] = $this->_time[$lesson_number]['time_start'];
                    $data['time_end'] = $this->_time[$lesson_number]['time_end'];
                    $data['day_number'] = $day_number;
                    $data['lesson_number'] = $lesson_number;
                    $data['day_name'] = $this->_days[$day_number-1];
                    $data['group_id'] = $group_id;

                    $sth = $this->_db->prepare("
						INSERT INTO `lesson` SET
						group_id = :group_id,
						day_number = :day_number,
						day_name = :day_name,
						lesson_number = :lesson_number,lesson_room = :lesson_room,
						lesson_name = :lesson_name,
						lesson_type = :lesson_type,
						teacher_name = :teacher_name,
						lesson_week = :lesson_week,
						time_start = :time_start,
						time_end = :time_end,
						rate = :rate,
						lesson_room_type = :lesson_room_type
					");

                    $sth->bindParam(":group_id",$group_id);
                    $sth->bindParam(":day_number",$data['day_number']);
                    $sth->bindParam(":day_name",$data['day_name']);
                    $sth->bindParam(":lesson_number",$data['lesson_number']);
                    $sth->bindParam(":lesson_name",$data['lesson_name']);
                    $sth->bindParam(":lesson_room",$data['lesson_room']);
                    $sth->bindParam(":lesson_type",$data['lesson_type']);
                    $sth->bindParam(":teacher_name",$data['teacher_name']);
                    $sth->bindParam(":lesson_week",$lesson_week);
                    $sth->bindParam(":time_start",$data['time_start']);
                    $sth->bindParam(":time_end",$data['time_end']);
                    $sth->bindParam(":rate",$data['rate']);
                    $sth->bindParam(":lesson_room_type",$data['lesson_room_type']);

                    $sth->execute();
                }
            }
        }
    }


    private function getResponse($url) {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL,$url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        $content = curl_exec($ch);
        curl_close($ch);
        $content = mb_convert_encoding($content, 'utf-8', mb_detect_encoding($content));
        return $content;
    }

    private function getHtmlTables($response,$delimiter="@") {
        preg_match_all('|<table (.*?)>(.*?)</table>|su',$response,$out);
        $firstTable = str_replace(array("<br>","<br/>"),$delimiter,$out[0][0]);
        $secondTable = str_replace(array("<br>","<br/>"),$delimiter,$out[0][1]);
        return array($firstTable,$secondTable);
    }

}