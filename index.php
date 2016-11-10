<?php
require "crontab.php";
$project = 'jubaopen-log';                  // 上面步骤创建的项目名称
$logstore = 'secure_log';                // 上面步骤创建的日志库名称


$c = new CrontabLogs($project, $logstore);
//$c->_setNum(1);
//$res = $c->getLogs(time()-3600*4,time());
//echo json_encode($res);
//var_dump($res);
//$key2 = explode(',',$_POST['keys']);
$key2 = array('time_db_get_t_user','time_db_get_t_ucloud','TotalCost');
$time = $_POST['time'];
$num  = $_POST['num'];
$time = 60;
$num  = 10;
$i    = 1;
if(empty($time) || empty($num))
    $res = array('test'=>array(1,2,3,4));
else
    $res = $c->checkKeyAverage(time()-$time*60, time(), $key2, $num);
while($i<=$num){
    $res2[] = $i++;
}
//$key2 = array('time_db_get_t_user',"TotalCost");
//$res = $c->checkKey(time()-3600*4,time(),$key2);
?>


<!doctype html>
<html>
<head>
    <meta charset="utf-8">
    <style>
        /* css 代码  */
    </style>
    <script src="./js/jquery-1.10.2.js"></script>
    <script src="./js/highcharts.js"></script>
</head>
<body>
<form>
    查询时间:<select name="time">
        <option <?php echo $num == 10 ? "": "selected";?> value="10">10分钟</option>
        <option <?php echo $num == 20 ? "": "selected";?> value="20">20分钟</option>
        <option <?php echo $num == 30 ? "": "selected";?> value="30">30分钟</option>
        <option <?php echo $num == 60 ? "": "selected";?> value="60">60分钟</option>
        <option <?php echo $num == 120 ? "": "selected";?> value="120">120分钟</option>
        <option <?php echo $num == 240 ? "": "selected";?> value="240">240分钟</option>
    </select>
    分成几次:<input type="text" value="<?php echo empty($time)? "": $time;?>" name="num">
    <input type="submit" value="提交">
</form>
<div id="container" style="min-width:400px;height:400px"></div>
<script>
    $(function () {
        $('#container').highcharts({
            chart: {
                type: 'line'
            },
            title: {
                text: '脚本时间统计'
            },
            subtitle: {
                text: '根据字段匹配'
            },
            xAxis: {
                categories: <?php echo json_encode($res2)?>
            },
            yAxis: {
                title: {
                    text: 'Time (ms)'
                }
            },
            plotOptions: {
                line: {
                    dataLabels: {
                        enabled: true
                    },
                    enableMouseTracking: false
                }
            },
            series: [
                <?php
                $str = "";
                foreach($res as $k=>$v){
                    $str .="{name: '$k',data: ".json_encode($v)."},";
                }
                echo rtrim($str,',');
                ?>
            ]
        });
    });
</script>
</body>
</html>