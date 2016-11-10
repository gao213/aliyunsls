<?php
require "crontab.php";
$project = 'jubaopen-test';                  // 上面步骤创建的项目名称
$logstore = 'secure_test';                // 上面步骤创建的日志库名称
$check   =  array(
    array('/2016/','>','1'),//检测规则  ==>  正则 比较符 阀值
    array('/SELECT/','>','1'),
);

$c = new CrontabLogs($project, $logstore, $check);
$res = $c->getCheck(time()-600, time());
$str = '';
if(!empty($res)){
    foreach($res as $k=>$v){
        $str .= "===============================================<br><br>匹配正则: ".$k."  匹配次数: ".count($v) ."<br>";
        foreach($v as $o){
            $str .= $o."<br>";
        }
    }
//    echo $str;
    echo $c->smtp_mail("56707892@qq.com","邮件提示",$str),"\n";
}else{
    echo "is null\n";
}
//var_dump($res);