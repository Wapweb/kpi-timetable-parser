<?php
/**
 * Created by PhpStorm.
 * User: Шаповал
 * Date: 20.09.14
 * Time: 18:48
 */

class ParserKpiGroups {

    private $_ukrAlphabet = array(
        'а','б','в','г','д','е','є','ж','з','и','і',
        'ї','й','к','л','м','н','о','п','р','с','т',
        'у','ф','ч','ц','ч','ш','щ','ю','я','ь'
    );
    private $_engAlphabet = array(
        'a','b','c','d','e','f','g','h','i','j','k',
        'l','m','n','o','p','q','r','s','t','u','v',
        'w','x','y','z'
    );

    private  $_replace = array(
        "а"=>"a",
        "б"=>"b",
        "в"=>"v",
        "г"=>"g",
        "д"=>"d",
        "е"=>"e",
        "ж"=>"zh",
        "з"=>"z",
        "й"=>"y",
        "к"=>"k",
        "л"=>"l",
        "м"=>"m",
        "н"=>"n",
        "о"=>"o",
        "п"=>"p",
        "р"=>"r",
        "с"=>"s",
        "т"=>"t",
        "у"=>"u",
        "ф"=>"f",
        "х"=>"kh",
        "ц"=>"ts",
        "ч"=>"ch",
        "ш"=>"sh",
        "щ"=>"shch",
        "і"=>"i",
        "ю"=>"yu",
        "я"=>"ya",
    );


    private $_targetGroupUrl = "http://rozklad.kpi.ua/Schedules/ScheduleGroupSelection.aspx";
    private $_targetUrl = "http://rozklad.kpi.ua/Schedules/ScheduleGroupSelection.aspx/GetGroups";

    private $_gruopUrlPosKey;

    /** @var  PDO $_db */
    private $_db;

    public function __construct($url = ""){
        /** @var PDO _db */
        $this->_db = Registry::get("db");
        $this->_targetUrl = !empty($url) ? $url : $this->_targetUrl;
    }



    public function parseGroups(){

        $this->initializeGroupUrlPostData();
        foreach($this->_ukrAlphabet as $letter) {
            $content = $this->getResponseWithJson($this->_targetUrl,$letter);
            $res = json_decode($content);
            $sum = count($res->d);

            $res = json_decode($content);
            $count = count($res->d);
            if($count > 0){
                foreach($res->d as $group_full_name)
                {
                    $data = $this->processGroupString($group_full_name);
                    $dataUrl = $this->getGroupUrl($group_full_name);

                    if($dataUrl['anomaly']) {
                        foreach($dataUrl['anomaly_groups'] as $anomalyGroup) {
                            $data['group_anomaly'] = 1;
                            $data['group_full_name'] = $anomalyGroup["group_full_name"];
                            $data["group_url"] = $anomalyGroup["group_url"];
                            $this->saveGroup($data);
                        }
                    } else {
                        $data['group_anomaly'] = 0;
                        $data["group_url"] = $dataUrl['url'];
                        $this->saveGroup($data);
                    }

                }
            }
        }

    }

    public function parseEnglishGroups() {
        $this->initializeGroupUrlPostData();
        foreach($this->_engAlphabet as $letter) {
            $content = $this->getResponseWithJson($this->_targetUrl,$letter);
            $res = json_decode($content);
            $sum = count($res->d);

            $res = json_decode($content);
            $count = count($res->d);
            if($count > 0){
                foreach($res->d as $group_full_name)
                {
                    $group_full_name = str_replace(array_values($this->_replace), array_keys($this->_replace),$group_full_name);
                    $data = $this->processGroupString($group_full_name);
                    $dataUrl = $this->getGroupUrl($group_full_name);

                    if($dataUrl['anomaly']) {
                        foreach($dataUrl['anomaly_groups'] as $anomalyGroup) {
                            $data['group_anomaly'] = 1;
                            $data['group_full_name'] = $anomalyGroup["group_full_name"];
                            $data["group_url"] = $anomalyGroup["group_url"];
                            $this->saveGroup($data);
                        }
                    } else {
                        $data['group_anomaly'] = 0;
                        $data["group_url"] = $dataUrl['url'];
                        $this->saveGroup($data);
                    }

                }
            }
        }
    }

    private function getResponseWithJson($url,$letter) {
        $ch = curl_init();
        $data = array("prefixText"=>$letter,"count"=>10);
        $jsonData = json_encode($data);
        curl_setopt($ch, CURLOPT_URL,$url);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json; charset=utf-8'));
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $jsonData);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $content = curl_exec($ch);
        curl_close($ch);
        return $content;
    }

    private function initializeGroupUrlPostData(){
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL,$this->_targetGroupUrl);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        $content = curl_exec($ch);
        preg_match('|<input type="hidden" name="__EVENTVALIDATION" id="__EVENTVALIDATION" value="(.*?)" />|',$content,$out);
        $key = urlencode(trim($out[1]));
        $this->_gruopUrlPosKey = $key;
    }

    private function getResponse($group_full_name) {
        $gruopUrlPostData = "__EVENTTARGET=&__EVENTARGUMENT=&ctl00%24MainContent%24ctl00%24txtboxGroup=$group_full_name&ctl00%24MainContent%24ctl00%24btnShowSchedule=%D0%A0%D0%BE%D0%B7%D0%BA%D0%BB%D0%B0%D0%B4+%D0%B7%D0%B0%D0%BD%D1%8F%D1%82%D1%8C&__EVENTVALIDATION=".$this->_gruopUrlPosKey;
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL,$this->_targetGroupUrl);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/x-www-form-urlencoded'));
        curl_setopt($ch, CURLOPT_POSTFIELDS, $gruopUrlPostData);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        $content = curl_exec($ch);
        $info = curl_getinfo($ch);
        curl_close($ch);
        return array('response'=>$content,'info'=>$info);
    }

    private function getGroupUrl($group_full_name){

        $res = $this->getResponse($group_full_name);
        $content = $res["response"];
        $info = $res["info"];

        if(mb_substr_count($content,"недоступна","utf-8") > 0)
        {
            $this->initializeGroupUrlPostData();

            $res = $this->getResponse($group_full_name);
            $content = $res["response"];
            $info = $res["info"];
            if(mb_substr_count($content,"недоступна","utf-8") > 0) {
                throw new Exception("Invalid key");
            }
        }

        preg_match('|<table (.*?)>(.*?)</table>|su',$content,$out);
        $dom = new DOMDocument();
        $dom->loadHTML('<?xml encoding="utf-8" ?>'.$out[0]);
        $res = $dom->getElementsByTagName("tr");

        $links = $dom->getElementsByTagName('a');
        $count = count($links);

        $anomalyGroups = array();
        $anomaly = 0;

        if($links->length > 0) {
            $anomaly = 1;
            /*** loop over the links ***/
            foreach ($links as $tag)
            {
                $group_url = $tag->getAttribute('href');
                $group_url = "http://rozklad.kpi.ua/Schedules/".$group_url;
                $group_full_name = $tag->childNodes->item(0)->nodeValue;
                $group_full_name = mb_strtolower($group_full_name,"UTF-8");
                $anomalyGroups[] = array('group_url'=>$group_url,'group_full_name'=>$group_full_name);
            }
        }

        return array('url'=>$info['url'],'anomaly'=>$anomaly,'anomaly_groups'=>$anomalyGroups);
    }

    private function processGroupString($group_full_name) {
        $group_full_name = mb_strtolower($group_full_name,"UTF-8");
        preg_match("|(.+?)\-([^0-9]{0,3})(\d{1,3})([^0-9]{0,3})|u",$group_full_name,$out);

        $group_prefix = trim($out['1']);
        $group_okr = 'bachelor';

        $okr = trim($out['4']);
        if($okr == 'м')
            $group_okr = 'magister';

        if($okr == 'с')
            $group_okr = 'specialist';

        $group_type = 'daily';

        $type = trim($out['2']);
        if(substr_count($type,'з') > 0)
        {
            $group_type = 'extramural';
        }

        return array(
            'group_full_name'=>$group_full_name,
            'group_prefix'=> $group_prefix,
            'group_type'=>$group_type,
            'group_okr'=>$group_okr
        );
    }

    private function saveGroup($groupData)
    {
        $sth = $this->_db->prepare("
				INSERT INTO `group` SET
				group_full_name = :group_full_name,
				group_prefix = :group_prefix,
				group_okr = :group_okr,
				group_type = :group_type,
				group_anomaly = :group_anomaly,
				group_url =:group_url
			");

        $sth->bindParam(":group_full_name",$groupData['group_full_name']);
        $sth->bindParam(":group_prefix",$groupData['group_prefix']);
        $sth->bindParam(":group_okr",$groupData['group_okr']);
        $sth->bindParam(":group_type",$groupData['group_type']);
        $sth->bindParam(":group_anomaly",$groupData['group_anomaly']);
        $sth->bindParam(":group_url",$groupData['group_url']);
        $sth->execute();
    }
} 