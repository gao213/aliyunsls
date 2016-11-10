<?php
/**
 * Created by PhpStorm.
 * User: gaoyu
 * Date: 16/8/11
 * Time: 17:49
 */
require_once realpath('./Log_Autoload.php');
require_once realpath('./phpmailer/class.phpmailer.php');

class CrontabLogs{
    const ENDPOINT = 'cn-beijing.log.aliyuncs.com';    //阿里云接口
    const ACCESSKEYID = 'xxxx';            //阿里云ACCESSKEYID
    const ACCESSKEY = 'xxx';//阿里云ACCESSKEY
    private $times    = 600;//默认获取日志周期 秒
    private $num      = 10;//默认获取日志条数
    private $project;
    private $logstore;
    private $check = array(
        array('正则','比较符号>,<,=,<=,>=,',"阀值") //检测配置
    );

    public function __construct($project, $logstore, $check = ''){
        $this->project  = $project;
        $this->logstore = $logstore;
        $this->check    = $check;
        $this->client   = new Aliyun_Log_Client(self::ENDPOINT, self::ACCESSKEYID, self::ACCESSKEY);
    }

    //获取阿里云sls日志 开始时间,结束时间,机器标签,查询规则,默认条数,页数
    public function getLogs($from, $to, $query = '', $topic = '', $page = 0){
        $res = array();
        $req4 = new Aliyun_Log_Models_GetLogsRequest($this->project, $this->logstore, $from, $to, $topic = '', $query = '',$this->num, $page, False);
        $res4 = $this->client->getLogs($req4);
        $arr = $res4->getLogs();
        if(empty($arr)) return false;
        foreach($arr as $v){
            $res[] =  $v->getContents()['content']."\n";
        }
        return $res;
    }

    //匹配规则与阀值
    public function checkNum($arr){
        if(empty($arr)) return false;
        $res = array();
        foreach($arr as $k=>$v){
            switch($this->check[$k][1]){
                case ">":if(count($v) > $this->check[$k][2]) $res[$this->check[$k][0]] = $v;
                    break;
                case "<":if(count($v) < $this->check[$k][2]) $res[$this->check[$k][0]] = $v;
                    break;
                case "==":if(count($v) == $this->check[$k][2]) $res[$this->check[$k][0]] = $v;
                    break;
                case ">=":if(count($v) >= $this->check[$k][2]) $res[$this->check[$k][0]] = $v;
                    break;
                case "<=":if(count($v) <= $this->check[$k][2]) $res[$this->check[$k][0]] = $v;
                    break;
                default:return false;
            }
        }
        return $res;
    }

    //检测日志正则匹配条数
    public function getCheck($from, $to, $arr = null){
        if($arr == null){
            $arr = $this->getLogs($from, $to);
        }
        if(empty($this->check) || empty($arr)) return false;
        $num = array();
        foreach($this->check as $k=>$v){
            $num[$k] = preg_grep($v[0],$arr);
        }
        if(empty($num)) return false;
        return $this->checkNum($num);
    }

    //根据字段分析log
    public function checkKey($from, $to, $key){
        $arr = $this->getLogs($from, $to);
        if(empty($arr)) return false;
        $keys = $key;
        if(is_array($keys)){
            foreach($keys as &$v){
                $v = "$v\[(.*?)\(ms\)\]";
            }
            $preg = implode('.*?',$keys);
        }else{
            $preg = "$keys\[(.*?)\(ms\)\]";
        }
        preg_match_all("/$preg/",implode("\n",$arr),$res);
        if(empty($res)) return false;
        unset($res[0]);
        return array_combine((array)$key,$res);
    }

    //根据字段分析log---平均用时
    public function checkKeyAverage($from, $to, $key, $average){
        $ave = round(($to-$from)/$average);
        $arr = array();
        while($from < $to){
            $res = $this->checkKey($from,$from+$ave,$key);
            foreach($key as $v){
                if(empty($res))
                    $arr[$v][] = 0;
                else
                    $arr[$v][] = round(array_sum($res[$v])/@count($res[$v]),2);
            }
            $from += $ave;
        }
        return $arr;
    }
    //发送邮件
    public function smtp_mail($sendto_email, $subject, $body, $attachment=''){
        $mail = new PHPMailer();
        $mail->IsSMTP();                  // send via SMTP
        $mail->SMTPAuth = true;           // turn on SMTP authentication


        $mail->Host = "smtp.exmail.qq.com";     // SMTP servers
        $mail->Username = "xx@xxx.com";    // SMTP username  注意：普通邮件认证不需要加 @域名
        $mail->Password = "xxx";  // SMTP password
        $mail->From     = "xx@xxx.com";// 发件人邮箱
        $mail->FromName =  "xxxx";       // 发件人

        $mail->CharSet = "UTF-8";   // 这里指定字符集！
        $mail->Encoding = "base64";
        $mail->AddAddress($sendto_email,"username");  // 收件人邮箱和姓名
        $mail->WordWrap = 50; // set word wrap 换行字数
        if($attachment!=''){
            if(is_array($attachment)){
                foreach($attachment as $v){
                    if(file_exists($v))
                        $mail->AddAttachment($v);        // attachment 附件
                    else
                        return 'file:'.$v.'. Not found!';
                }
            }else{
                $mail->AddAttachment(dirname (__FILE__).'/../../crontab/mail/'.$attachment); // attachment 附件
            }
        }

        $mail->IsHTML(true);  // send as HTML
        // 邮件主题
        $mail->Subject = $subject;
        // 邮件内容
        $mail->Body = $body;
        $mail->AltBody ="text/html";
        if(!$mail->Send())
        {
            return "err: " . $mail->ErrorInfo;
        }
        else {
            return "ok";
        }
    }

    //设置获取日志时间
    public function _setTimes($time){
        $this->times = $time;
    }

    //设置获取条数
    public function _setNum($num){
        $this->num = $num;

    }
}



