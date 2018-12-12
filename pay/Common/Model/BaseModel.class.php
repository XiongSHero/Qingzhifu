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
 * @date 2015年1月6日
 */
/**
 * 基础模型类，用于处理基础数据相关操作
 */

namespace Common\Model;

use Think\Model;

class BaseModel {

    protected $autoCheckFields = false;    //不自动检测数据表字段信息
    protected $dbConn; //数据库连接
    protected $modelName = __CLASS__;

    /**
     * 初始化模型
     * @author fengxing
     */
    function __construct() {
        //parent::__construct();
        //声明数据库连接
        $this->dbConn = D('ApiDb');
        $this->modelName = get_class($this);
        $tmp = explode('\\', $this->modelName);
        $this->modelName = str_replace(C('DEFAULT_M_LAYER'), '', $tmp[count($tmp) - 1]);
    }

    /**
     * 开启事务；
     * @author fengxing
     */
    public function dbStartTrans() {
        $this->dbConn->startTrans();
    }
    /**
     * 回滚事务；
     * @author fengxing
     */
    public function dbRollback() {
        $this->dbConn->rollback();
    }
    /**
     * 提交事务；
     * @author fengxing
     */
    public function dbCommit() {
        $this->dbConn->commit();
    }


    /**
     * 格式化数据表名称；
     * @param string $table 数据表名称 无需前缀大驼峰
     * @return string
     * @author fengxing
     */
    public function formatTable($table) {
        $table = preg_replace('/[A-Z]/', '_\\0', $table);
        $table = substr(strtolower($table), 1);
        return C('DB_PREFIX') . $table;
    }

    /**
     * 利用__call方法实现一些特殊的Model方法（改写thinkphp方法）
     * @access public
     * @param string $method 方法名称
     * @param array $args 调用参数
     * @return mixed
     */
    public function __call($method, $args) {
        if (strtolower(substr($method, 0, 6)) == 'getapi') {
            return getApi($method, $args);
        }
        if (in_array(strtolower($method), $this->methods, true)) {
            // 连贯操作的实现
            $this->dbConn->options[strtolower($method)] = $args[0];
            return $this->dbConn;
        } elseif (in_array(strtolower($method), array(
                    'count',
                    'sum',
                    'min',
                    'max',
                    'avg'), true)) {
            // 统计查询的实现
            $field = isset($args[0]) ? $args[0] : '*';
            return $this->dbConn->getField(strtoupper($method) . '(' . $field . ') AS tp_' . $method);
        } elseif (strtolower(substr($method, 0, 5)) == 'getby') {
            // 根据某个字段获取记录
            $field = parse_name(substr($method, 5));
            $where[$field] = $args[0];
            return $this->dbConn->where($where)->find();
        } elseif (strtolower(substr($method, 0, 10)) == 'getfieldby') {
            // 根据某个字段获取记录的某个值
            $name = parse_name(substr($method, 10));
            $where[$name] = $args[0];
            return $this->dbConn->where($where)->getField($args[1]);
        } else {
            E(__CLASS__ . ':' . $method . L('_METHOD_NOT_EXIST_'));
            return;
        }
    }

    /**
     * 设置默认模块名称
     * @author fengxing
     */
    public function setModelName($modelName) {
        $this->modelName = $modelName;
    }

    /**
     * 调用模型或方法
     * @param string $modelName 模型名称
     * @param string $functionName 方法名称
     * @param string 更多参数
     * @return mixed
     * @author fengxing
     */
    protected function getModel($modelName, $functionName = '') {
        static $_loadedModels = [];
        if (!isset($_loadedModels[$modelName])) {


            // $newModelName = getMap($modelName);
            // //判断文件夹下是否有对应文件
            // $filePath=APP_PATH.str_replace('/','/Model/',$newModelName).'Model.class.php';
            // if(!file_exists($filePath)){
            //     $newModelName='Base';
            // }
            // $_loadedModels[$modelName] = D($newModelName);
            // if($newModelName=='Base') $_loadedModels[$modelName]->setModelName($modelName);
            //$newModelName=getMap($modelName);
            $newModelName = 'Common/' . $modelName;
            $newModelName = explode('/', $newModelName);
            $newModelName = '\\' . $newModelName[0] . '\\Model\\' . $newModelName[1] . 'Model';
            //判断文件夹下是否有对应文件
            $filePath = APP_PATH . str_replace('/', '/Model/', $newModelName) . 'Model.class.php';
            if (!class_exists($newModelName)) {
                $_loadedModels[$modelName] = new \Common\Model\BaseModel();
                $_loadedModels[$modelName]->setModelName($modelName);
            } else {
                $_loadedModels[$modelName] = new $newModelName();
            }
        }

        if (empty($functionName))
            return $_loadedModels[$modelName];
        $param = func_get_args();
        $param = array_slice($param, 2);

        return call_user_func_array(array(
            $_loadedModels[$modelName],
            $functionName), $param);
    }

    /**
     * 查询数据；
     * @param string $field 字段
     * @param string $where 条件
     * @param string $order 查询
     * @return array 查询后的数据集 或 false
     * @author fengxing
     */
    public function selectData($field, $where, $order = '', $limit = '', $modelName = '') {
        if (!$modelName)
            $modelName = $this->modelName;
        return $this->dbConn->selectData(
                        $modelName, $field, $where, $order, $limit
        );
    }

    /**
     * 插入数据；
     * @param string $where 条件
     * @return array 插入后的行数 或 false
     * @author fengxing
     */
    public function insertData($data, $modelName = '') {
        if (!$modelName)
            $modelName = $this->modelName;
        return $this->dbConn->insertData(
                        $modelName, $data
        );
    }

    /**
     * 更新数据；
     * @param string $data 字段
     * @param string $where 条件
     * @return array 更新后的行数 或 false
     * @author fengxing
     */
    public function updateData($data, $where, $modelName = '') {
        if (!$modelName)
            $modelName = $this->modelName;
        return $this->dbConn->updateData(
                        $modelName, $data, $where
        );
    }

    /**
     * 删除数据；
     * @param string $where 条件
     * @return array 删除后的行数 或 false
     * @author fengxing
     */
    public function deleteData($where, $modelName = '') {
        if (!$modelName)
            $modelName = $this->modelName;
        return $this->dbConn->deleteData(
                        $modelName, $where
        );
    }

    /**
     * 批量插入数据；
     * @param string $table 数据表名称
     * @param array $data 插入数据表字段数组
     * @return bool
     * @author fengxing
     */
    public function addAllData($data, $modelName = '') {
        if (!$modelName)
            $modelName = $this->modelName;
        return $this->dbConn->addAllData(
                        $modelName, $data
        );
    }

    /**
     * 更新自加数据
     * @param string $table 数据表名称
     * @param string $value 更改字段
     * @param string $where 关联条件
     * @return array 标签内容
     * @author fengxing
     */
    public function conAddData($value, $where, $field, $modelName = '') {
        if (!$modelName)
            $modelName = $this->modelName;
        return $this->dbConn->conAddData(
                        $modelName, $value, $where, $field
        );
    }

    /**
     * 按条件查询数据；
     * @param string $table 数据表名称
     * @param string $field 查询的字段
     * @param string $where 查询的条件
     * @param string $group 分组条件
     * @param string $order 查询的排序
     * @param string $limit 查询的数量
     * @return array
     * @author fengxing
     */
    public function groupData($field, $where, $group = '', $order = '', $limit = '', $modelName = '') {
        if (!$modelName)
            $modelName = $this->modelName;
        return $this->dbConn->groupData($modelName, $field, $where, $group, $order, $limit);
    }

    /**
     * 按条件精准查询数据(只查询一条数据)；
     * @param string $table 数据表名称
     * @param string $field 查询的字段
     * @param string $where 查询的条件
     * @param string $order 排序规则
     * @return array
     * @author fengxing
     */
    public function findData($field, $where, $order = '', $modelName = '') {
        if (!$modelName)
            $modelName = $this->modelName;
        return $this->dbConn->findData($modelName, $field, $where, $order);
    }

    /**
     * 按条件查询总数；
     * @param string $table 数据表名称
     * @param string $where 查询的条件
     * @param string $field 聚合字段
     * @param string $rename 数据表重命名
     * @return int 数量
     * @author fengxing
     */
    public function selectCount($where, $field = '', $rename = '', $modelName = '') {
        if (!$modelName)
            $modelName = $this->modelName;
        return $this->dbConn->selectCount($modelName, $where, $field, $rename);
    }

    /**
     * 按条件分页列表
     * @param string $table 数据表名称
     * @param string $field 所需字段
     * @param string $where 关联条件
     * @param string $order 排序
     * @param int $page  当前页码,每页个数
     * @return array 查询内容
     * @author fengxing
     */
    public function pageData($field, $where, $order, $page, $modelName = '') {
        if (!$modelName)
            $modelName = $this->modelName;
        return $this->dbConn->pageData($modelName, $field, $where, $order, $page);
    }

    /**
     * 对单一字段进行唯一查询
     * @param string $table 数据表名称
     * @param string $field 所需字段
     * @param string $where 关联条件
     * @return array 标签内容
     * @author fengxing
     */
    public function distinctData($field, $where = '', $order = '', $limit = '', $modelName = '') {
        if (!$modelName)
            $modelName = $this->modelName;
        return $this->dbConn->distinctData($modelName, $field, $where, $order, $limit);
    }

    /**
     * 关联插入数据库
     * @param string $value='' 数据表1插入字段
     * @param string $field 表2所需字段
     * @param string $table2 数据表2名称
     * @param string $testID 试题ID
     * @param string $modelName 数据表1名称
     * @return array
     * @author fengxing
     */
    public function insertSelect($value = '', $field, $table2, $testID, $modelName = '') {
        if (!$modelName)
            $modelName = $this->modelName;
        return $this->dbConn->insertSelect($modelName, $value, $field, $table2, $testID);
    }

    /**
     * 获取最大的数据
     * @param string $table 数据表名称
     * @param string $field 查询的字段
     * @return int|bool
     * @author fengxing
     */
    public function maxData($field, $modelName = '') {
        if (!$modelName)
            $modelName = $this->modelName;
        return $this->dbConn->maxData($modelName, $field);
    }

    /**
     * 查询字段总数；
     * @param string $table 数据表名称
     * @param string $field 查询的字段
     * @param string $where 查询条件
     * @return int|bool
     * @author fengxing
     */
    public function sumData($field, $where, $modelName = '') {
        if (!$modelName)
            $modelName = $this->modelName;
        return $this->dbConn->sumData($modelName, $field, $where);
    }

    /**
     * 根据学科获取平均数
     * @param string $table 数据表名称
     * @param string $where 查询条件
     * @param string $field 查询平均字段
     * @return int
     * @author fengxing
     */
    public function avgData($field, $where, $modelName = '') {
        if (!$modelName)
            $modelName = $this->modelName;
        //排除时间和分数是0的
        return $this->dbConn->avgData($modelName, $field, $where);
    }

    /**
     * 获取dbConn数据层中的方法
     * @param string $function 方法名
     * @param string param1,param2,param3,param4 可先参数4个
     * @return array
     * @author fengxing
     */
    public function unionSelect($function) {
        $param = func_get_args();
        $function = array_shift($param);
        return call_user_func_array([$this->dbConn,
            $function], $param);
    }

    /**
     * 查询多个单表数据到数组
     * @param array $param $param['model'] 模型 $param['field'] 字段 $param['where'] 条件 $param['order'] 排序
     * @return array
     * @author fengxing
     */
    public function getMoreDataByID($param) {
        $data = array();
        if (!$param)
            return $data;

        foreach ($param as $i => $iParam) {
            $data[$iParam['model']] = $this->dbConn->selectData(
                    $iParam['model'], $iParam['field'], $iParam['where'], $iParam['order']
            );
        }

        return $data;
    }

    /**
     * 设置错误信息
     * @param array $params 错误参数
     * @return void
     * @author fengxing
     */
    public function addErrorLog($params) {
        $this->dbConn->addErrorLog($params);
    }

}
