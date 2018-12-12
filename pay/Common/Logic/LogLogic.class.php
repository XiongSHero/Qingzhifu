<?php
// +----------------------------------------------------------------------
// | easy pay [ pay to easy ]
// +----------------------------------------------------------------------
// | Copyright (c) 2016-2017 All rights reserved.
// +----------------------------------------------------------------------
// | Author: fengxing <QQ:51125330>
// +----------------------------------------------------------------------
namespace Common\Logic;

class LogLogic extends BaseLogic {

    protected $moduleName = '日志';

    /**
     * 列表
     */
    public function index($request) {
        $perpage = C('FX_PERPAGE'); //每页行数
        $map = array();
        $data = ' 1=1 ';
        //高级查询
        if ($request['userid']) {
            $map['userid'] = $request['userid'];
            $data .= ' AND userid = "' . $request['userid'] . '" ';
        }
        if ($request['content']) {
            $map['content'] = $request['content'];
            $data .= ' AND content like "%' . $request['content'] . '%" ';
        }
        if ($request['module']) {
            $map['module'] = $request['module'];
            $data .= ' AND module ="' . $request['module'] . '" ';
        }
        if (is_numeric($request['ifadmin'])) {
            $map['ifadmin'] = $request['ifadmin'];
            $data .= ' AND ifadmin ="' . $request['ifadmin'] . '" ';
        }
        $start = $request['start'];
        if (strstr($start, '-')) {
            $start = strtotime($start);
        }
        $end = $request['end'];
        if (strstr($end, '-')) {
            $end = strtotime($end);
        }
        if ($start) {
            if (empty($end))
                $end = time();
            $map['start'] = $start;
            $map['end'] = $end;
            $request['start'] = date('Y-m-d', $start);
            $request['end'] = date('Y-m-d', $end);
            $data .= ' AND addtime between ' . ($start) . ' and ' . ($end) . ' ';
        }
        $log = SM('Rizhi');
        $count = $log->selectCount(
                $data, 'id'); // 查询满足要求的总记录
        // 进行分页数据查询 注意limit方法的参数要使用Page类的属性
        $page = page($count, $request['p'], $perpage) . ',' . $perpage;
        $list = $log->pageData(
                '*', $data, 'id desc', $page);

        foreach($list as $i=>$iList){
            if($list[$i]['ip']=='0.0.0.0') $list[$i]['ip']='本地';
            $list[$i]['addtime']=  stringChange('formatDateTime',$iList['addtime']);
        }

        $pageList =$this->pageList($count, $perpage, $map);

        /* 载入模板标签 */
        $params = array(
            'list' => $list,
            'page' => $pageList,
            'pageName' => $this->moduleName . '管理'
        );
        return [1,$params];
    }

    /**
     * 删除
     */
    public function delete($request) {
        $logID = $request['id']; //获取数据标识
        if (!$logID) {
            return [0,'数据标识不能为空！',  __URL__];
        }
        if(!is_array($logID)) $logID=explode(',',$logID);

        $log = SM('Rizhi');
        $logArray = $log->selectData(
                '*', 'id in (' . implode(',',$logID) . ') and addtime between ' . (time() - 7 * 24 * 3600) . ' and ' . time() . '');
        if ($logArray) {
            return [0,'删除失败，请不要删除最近7天内的日志！'];
        }

        if ($log->deleteData('id in (' . implode(',',$logID) . ')') === false) {
            return [0,'删除失败'];
        } else {
            //写入日志
            $this->adminLog($this->moduleName, '删除日志记录LogID:' . implode(',',$logID) . '');
            return [1,'删除成功！', __URL__];
        }
    }

}
