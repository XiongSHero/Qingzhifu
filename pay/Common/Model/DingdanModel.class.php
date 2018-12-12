<?php
// +----------------------------------------------------------------------
// | easy pay [ pay to easy ]
// +----------------------------------------------------------------------
// | Copyright (c) 2016-2017 All rights reserved.
// +----------------------------------------------------------------------
// | Author: fengxing <QQ:51125330>
// +----------------------------------------------------------------------
/**
 * 订单模型
 * @author fengxing
 */
namespace Common\Model;
class DingdanModel extends BaseModel {

    /**
     * 获取代理对应商户的当日交易额
     * @param int $starttime 开始时间
     * @param int $endtime 结束时间
     * @return array
     * @author fengxing
     */
    public function getAgentMoney($starttime,$endtime){
        $result=$this->dbConn->field('a.agent as userid,SUM(totalmoney) as money')
                ->table($this->formatTable('DingdanAgent').' a')
                ->join(' left join '.$this->formatTable('Dingdan').' d on a.ddid=d.ddid')
                ->where('d.status=1 and d.paytime between '.$starttime.' and '.$endtime)
                ->group('a.agent')
                ->order('a.agent asc')
                ->select();

        return $result;
    }
    /**
     * 获取指定代理对应商户的交易额
     * @param int $userid 商户id
     * @param int $starttime 开始时间
     * @param int $endtime 结束时间
     * @return array
     * @author fengxing
     */
    public function getAgentMoneyByAgent($userid,$starttime,$endtime,$field='totalmoney'){
        $result=$this->dbConn->table($this->formatTable('DingdanAgent').' d')
                ->join(' left join '.$this->formatTable('Dingdan').' a on a.ddid=d.ddid')
                ->where('a.status=1 and a.paytime between '.$starttime.' and '.$endtime.' and d.agent='.$userid)
                ->sum('a.'.$field);

        return $result;
    }
    /**
     * 获取指定代理对应商户的订单列表
     * @param int $agent 商户id
     * @param array $where 条件
     * @param string $page 分页条件
     * @return array
     * @author fengxing
     */
    public function getAgentList($agent,$where,$page){
        $result=$this->dbConn->field('d.*,a.agentmoney')
                ->table($this->formatTable('DingdanAgent').' a')
                ->join(' left join '.$this->formatTable('Dingdan').' d on a.ddid=d.ddid')
                ->where($where.' and a.agent='.$agent)
                ->page($page)
                ->order('d.ddid desc')
                ->select();

        return $result;
    }
    /**
     * 获取指定代理对应商户的订单总数
     * @param int $agent 商户id
     * @param array $where 条件
     * @return array
     * @author fengxing
     */
    public function getAgentCount($agent,$where){
        $result=$this->dbConn->table($this->formatTable('DingdanAgent').' a')
                ->join(' left join '.$this->formatTable('Dingdan').' d on a.ddid=d.ddid')
                ->where($where.' and a.agent='.$agent)
                ->count('d.ddid');

        return $result;
    }
    /**
     * 获取指定代理对应订单总额
     * @param int $agent 商户id
     * @param array $where 条件
     * @return array
     * @author fengxing
     */
    public function getAgentSum($agent,$start=0,$end=0,$field="totalmoney"){
        $where='d.status=1 and a.agent='.$agent;
        if($start) $where.=' and d.addtime between '.$start.' and '.$end;
        $result=$this->dbConn
                ->table($this->formatTable('Dingdan').' d')
                ->join(' left join '.$this->formatTable('DingdanAgent').' a on a.ddid=d.ddid')
                ->where($where)
                ->sum('d.'.$field);

        return $result;
    }
    /**
     * 根据zjid找到对应支付账户名称
     * @param array $zjid 配置中间表id
     * @return array
     * @author fengxing
     */
    public function getPayName($zjid){
        if(!is_array($zjid)) $zjid=array($zjid);
        $result=$this->dbConn
                ->field('z.zjid,p.pzid,p.pzname')
                ->table($this->formatTable('Jiekouzj').' z')
                ->join(' left join '.$this->formatTable('Jiekoupeizhi').' p on p.pzid=z.pzid')
                ->where('zjid in ('.implode(',',$zjid).')')
                ->select();

        return $result;
    }
    /**
     * 获取最近30天的流水统计
     * @return array
     * @author fengxing
     */
    public function getGroup30Month(){
        $start=strtotime(date('Y-m-d',strtotime('-30 day')));
        $end=strtotime(date('Y-m-d'));
        $result=$this->dbConn->query('select COUNT(ddid) as `num`,SUM(totalmoney) as totalmoney,SUM(havemoney) as havemoney,SUM(dailimoney) as dailimoney,SUM(syflmoney) as syflmoney,`status`,FROM_UNIXTIME(paytime,"%Y-%m-%d") as paytimes from '.$this->formatTable('Dingdan').' where paytime between '.$start.' and '.$end.' group by `status`,paytimes');
        return $result;
    }
}