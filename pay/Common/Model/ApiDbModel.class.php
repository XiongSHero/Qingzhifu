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
 * @date 2015年2月9日
 */
/**
 * 数据表操作Model类，用于处理数据表操作相关操作
 */

namespace Common\Model;

use Think\Model;

class ApiDbModel extends Model {

    protected $errors = array(); //添加错误描述
    protected $autoCheckFields = false; //不自动检测数据表字段信息

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
     * 重写框架core/model/save方法，加入错误日志记录
     * @author fengxing
     */
    public function save($data = '', $options = array()) {
        $result = parent::save($data, $options);
        $this->addSqlErrorLog($result);
        return $result;
    }

    /**
     * 重写框架core/model/delete方法，加入错误日志记录
     * @author fengxing
     */
    public function delete($options = array()) {
        $result = parent::delete($options);
        $this->addSqlErrorLog($result);
        return $result;
    }

    /**
     * 重写框架core/model/add方法，加入错误日志记录
     * @author fengxing
     */
    public function add($data = '', $options = array(), $replace = false) {
        $result = parent::add($data, $options, $replace);
        $this->addSqlErrorLog($result);
        return $result;
    }

    /**
     * 重写框架core/model/addAll方法，加入错误日志记录
     * @author fengxing
     */
    public function addAll($dataList, $options = array(), $replace = false) {
        $result = parent::addAll($dataList, $options, $replace);
        $this->addSqlErrorLog($result);
        return $result;
    }

    /**
     * 重写框架core/model/select，加入错误日志记录
     * @author fengxing
     */
    public function select($options = array()) {
        $result = parent::select($options);
        $this->addSqlErrorLog($result);
        return $result;
    }

    /**
     * 重写框架core/model/execute方法，加入错误日志记录
     * @author fengxing
     */
    public function execute($sql, $parse = false) {
        $result = parent::execute($sql, $parse);
        $this->addSqlErrorLog($result);
        return $result;
    }

    /**
     * 设置错误信息
     * @param array $params 错误参数
     * @return void
     * @author fengxing
     */
    public function addErrorLog($params) {
        $error = new \Common\Model\LogErrorModel();
        $error->setLine($params);
    }

    /**
     * 包含sql语句设置错误信息
     * @param string|array $errors 错误参数
     * @return $this 当前对象
     * @author fengxing
     */
    public function setErrors($errors) {
        if (is_array($errors)) {
            $this->errors = $errors;
        } else {
            $this->errors['description'] = $errors;
        }
        return $this;
    }

    /**
     * 加入sql错误日志记录
     * @param bool $result sql语句运行是否正确
     * @author fengxing
     */
    public function addSqlErrorLog($result) {
        $sql = $this->getLastSql();
        if ($sql)
            setSqlTrace($sql);
        if ($result === false) {
            $errorStr = $this->getDbError();
            $sqlError = getSqlTrace();
            $this->errors['sql'] = $sqlError . ($sqlError == '' ? '' : '<br/>') . $errorStr;
            $this->addErrorLog($this->errors);
            $showError = C('SHOW_SQL_ERROR'); //取值 0 1 2
            if ($showError == 1) {
                exit('数据操作错误！请联系技术支持人员解决。');
            } elseif ($showError == 2) {
                echo '<meta charset="UTF-8">';
                exit($this->errors['sql']);
            }
        }
    }

    /*     * ****************************************************
     * 通用查询语句
     * **************************************************** */

    /**
     * 插入数据；
     * @param string $table 数据表名称
     * @param array $data 插入数据表字段数组
     * @return bool
     * @author fengxing
     */
    public function insertData($table, $data) {
        return $this->table($this->formatTable($table))
                        ->data($data)
                        ->add();
    }

    /**
     * 批量插入数据；
     * @param string $table 数据表名称
     * @param array $data 插入数据表字段数组
     * @return bool
     * @author fengxing
     */
    public function addAllData($table, $data) {
        return $this->table($this->formatTable($table))->addAll($data);
    }

    /**
     * 更新数据；
     * @param string $table 数据表名称
     * @param array $data 更新数据表字段数组
     * @param array $where 更新条件
     * @return bool
     * @author fengxing
     */
    public function updateData($table, $data, $where) {
        if (empty($data)) {
            $this->addSqlErrorLog(false); //人为添加SQL
            return false;
        }
        return $this->table($this->formatTable($table))
                        ->data($data)
                        ->where($where)
                        ->save();
    }

    /**
     * 更新自加数据
     * @param string $table 数据表名称
     * @param string $value 更改字段
     * @param string $where 关联条件
     * @return array 标签内容
     * @author fengxing
     */
    public function conAddData($table, $value, $where, $field) {
        if (!$value || !$where || empty($field))
            return false;

        $pergPrev = array();
        if (!is_array($field))
            $field = array(
                $field);
        foreach ($field as $fieldn) {
            $pergPrev[] = '/' . $fieldn . '=' . $fieldn . '/';
        }
        $pergNext = array_fill(0, count($pergPrev), '');
        $result = preg_replace($pergPrev, $pergNext, $value);
        if (strlen($result) == strlen($value))
            return false;

        return $this->execute('UPDATE ' . $this->formatTable($table) . ' SET ' . $value . ' WHERE ' . $where);
    }

    /**
     * 删除数据；
     * @param string $table 数据表名称
     * @param array $where 删除条件
     * @return bool
     * @author fengxing
     */
    public function deleteData($table, $where) {
        if (empty($where))
            return false;
        return $this->table($this->formatTable($table))
                        ->where($where)
                        ->delete();
    }

    /**
     * 按条件查询数据；
     * @param string $table 数据表名称
     * @param string $field 查询的字段
     * @param string $where 查询的条件
     * @param string $order 查询的排序
     * @param string $limit 查询的数量
     * @return array
     * @author fengxing
     */
    public function selectData($table, $field, $where, $order = '', $limit = '') {
        return $this->table($this->formatTable($table))
                        ->field($field)
                        ->where($where)
                        ->order($order)
                        ->limit($limit)
                        ->select();
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
    public function groupData($table, $field, $where, $group = '', $order = '', $limit = '') {
        return $this->table($this->formatTable($table))
                        ->field($field)
                        ->where($where)
                        ->group($group)
                        ->order($order)
                        ->limit($limit)
                        ->select();
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
    public function findData($table, $field, $where, $order) {
        return $this->table($this->formatTable($table))
                        ->field($field)
                        ->where($where)
                        ->order($order)
                        ->find();
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
    public function selectCount($table, $where, $field = '', $rename = '') {
        return $this->table($this->formatTable($table) . ' ' . $rename)
                        ->where($where)
                        ->count($field);
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
    public function pageData($table, $field, $where, $order, $page) {
        return $this->table($this->formatTable($table))
                        ->field($field)
                        ->where($where)
                        ->order($order)
                        ->page($page)
                        ->select();
    }

    /**
     * 对单一字段进行唯一查询
     * @param string $table 数据表名称
     * @param string $field 所需字段
     * @param string $where 关联条件
     * @return array 标签内容
     * @author fengxing
     */
    public function distinctData($table, $field, $where = '', $order = '', $limit = '') {
        return $this->table($this->formatTable($table))
                        ->Distinct(true)
                        ->field($field)
                        ->where($where)
                        ->order($order)
                        ->limit($limit)
                        ->select();
    }

    /**
     * 关联插入数据库
     * @param string $table 数据表1名称
     * @param string $value='' 数据表1插入字段
     * @param string $field 表2所需字段
     * @param string $table2 数据表2名称
     * @param string $testID 试题ID
     * @return array
     * @author fengxing
     */
    public function insertSelect($table, $value = '', $field, $table2, $testID) {
        if ($value) {
            $insert = '(' . $value . ')';
        }
        return $this->execute('INSERT INTO ' . $this->formatTable($table) . ' ' . $insert . ' SELECT ' . $field . ' FROM ' . $this->formatTable($table2) . ' WHERE TestID in (' . $testID . ')');
    }

    /**
     * 获取最大的数据
     * @param string $table 数据表名称
     * @param string $field 查询的字段
     * @return int|bool
     * @author fengxing
     */
    public function maxData($table, $field) {
        return $this->table($this->formatTable($table))->max($field); //得到最大数值
    }

    /**
     * 查询字段总数；
     * @param string $table 数据表名称
     * @param string $field 查询的字段
     * @param string $where 查询条件
     * @return int|bool
     * @author fengxing
     */
    public function sumData($table, $field, $where) {
        return $this->table($this->formatTable($table))->where($where)->sum($field);
    }

    /**
     * 根据学科获取平均数
     * @param string $table 数据表名称
     * @param string $where 查询条件
     * @param string $field 查询平均字段
     * @return int
     * @author fengxing
     */
    public function avgData($table, $field, $where) {
        //排除时间和分数是0的
        return $this->table($this->formatTable($table))->where($where)->avg($field);
    }

}
