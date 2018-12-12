<?php
// +----------------------------------------------------------------------
// | easy pay [ pay to easy ]
// +----------------------------------------------------------------------
// | Copyright (c) 2016-2017 All rights reserved.
// +----------------------------------------------------------------------
// | Author: fengxing <QQ:51125330>
// +----------------------------------------------------------------------
/**
 * @author fengxing
 * @date 2015年2月6日
 */
/**
 * 错误处理分析
 */
namespace Common\Model;
class LogErrorModel extends BaseModel{
    /**
     * 错误记录
     * @para array $data操作信息数组
     * @author fengxing
     */
    public function setLine($para){
        $action = MODULE_NAME.'/'.CONTROLLER_NAME.'/'.ACTION_NAME;
        if($para['msg']){
            $param = is_array($para['msg']) ? serialize($para['msg']) : $para['msg'];
            $param .= "<br>".serialize($_COOKIE);
        }else
            $param = serialize($_GET).'##@##'.serialize($_POST).'##@##'.serialize($_COOKIE);
        $param=str_replace("\r\n","\\r\\n",$param);
        $sql = '无';
        if($para['sql']){
            $sql = $para['sql'];
        }
        $sql=str_replace("\r\n",'\\r\\n',$sql);
        $description = '无';
        if($para['description']){
            $description = $para['description'];
        }
        $description=str_replace("\r\n",'\\r\\n',$description);
        $data = array(
            'Url' => $action,
            'Params' => $param,
            'SqlContent' => $sql,
            'AddTime' => time(),
            'Description' => $description
        );
        $this->insertData($data);
    }
    /**
     * 获取列表
     * @param array $params查询参数
     * @return array
     * @author fengxing
     */
    public function getList($params, $page, $prepage=30){
        $preset = array(
            'tableName' => '',
            'start' => 0,
            'end' => time()
        );
        $keys = array_keys($preset);
        foreach($params as $key=>$value){
            if(!in_array($key, $keys) || empty($value)){
                unset($params[$key]);
            }
        }
        $params = $params + $preset;
        $where[] = "AddTime BETWEEN {$params['start']} AND {$params['end']}";
        if($params['tableName']){
            $where[] = "SqlContent LIKE '%{$params['tableName']}%'";
        }
        $where = implode(' AND ', $where);
        $count = $this->selectCount($where, 'ErrorID');
        $limit = (($page-1)*$prepage).','.$prepage;
        $list = $this->selectData('*', $where, 'ErrorID DESC', $limit);
        return array(
            'data' => $list,
            'total' => $count
        );
    }

    public function delData($day=7){
        if($day < 7){
            $day = 7;
        }
        $time = strtotime(($day*-1).' day');
        $this->deleteData('AddTime < '.$time);
    }
}