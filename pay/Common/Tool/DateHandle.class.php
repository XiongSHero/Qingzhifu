<?php
/**
 * 日期相关处理类
 * @date 2015年10月16日
 * @author fengxing
 */
class DateHandle {
    /**
     * @将星期转换为中文
     * @param int $val 时间戳
     * @return string
     * @author fengxing
     */
    public function toChinese($val){
        $arr = array(
            '日','一','二','三','四','五','六'
        );
        $date = getdate($val);
        return $arr[(int)$date['wday']];
    }

    /**
     * 返回一个月中的天数
     * @param int $month 指定月
     * @return int
     * @author fengxing
     */
    public function getDays($month){
        $days = 31;
        if($month == 4 || $month == 6 || $month == 9 || $month == 11){
            $days = 30;
        }else if($month == 2)
            $days = 28;
        return $days;
    }

    /**
     * 将固定的日期转换成时间戳
     * @param $dateDiff string 需要转换的时间
     * @return int
     * @author fengxing
     */
    public function conversionTime($dateDiff){
        $dataTime = 0;//默认时间为零
        $dataTime2 = 0;
        switch($dateDiff){//将日期转化成时间戳
            case 'today':
                $dataTime=strtotime(date("Y-m-d",time()));
                break;
            case 'yestoday':
                $dataTime=strtotime(date("Y-m-d",time()-24*3600));
                $dataTime2 = strtotime(date("Y-m-d",time()));
                break;
            case 'curweek':
                $dataTime=strtotime("-1 week");
                break;
            case 'curmonth':
                $dataTime=strtotime(date('Y-m-1'));
                break;
            case 'all':
                $dataTime=0;
                break;
        }
        return array($dataTime,$dataTime2);
    }

}
