<?php

// +----------------------------------------------------------------------
// | easy pay [ pay to easy ]
// +----------------------------------------------------------------------
// | Copyright (c) 2016-2017 All rights reserved.
// +----------------------------------------------------------------------
// | Author: fengxing <QQ:51125330>
// +----------------------------------------------------------------------

namespace Common\Logic;

class BankLogic extends BaseLogic {

    protected $moduleName = '银行类型';

    /**
     * 列表
     */
    public function index($request) {
        $map = array();
        $data = ' 1=1 ';
        //高级查询
        if ($request['bankcode']) {
            $map['bankcode'] = $request['bankcode'];
            $data.=' AND bankcode ="' . $request['bankcode'] . '" ';
        }
        if ($request['bankname']) {
            $map['bankname'] = $request['bankname'];
            $data.=' AND bankname ="' . $request['bankname'] . '" ';
        }
        if (is_numeric($request['status'])) {
            $map['status'] = $request['status'];
            $data.=' AND status ="' . $request['status'] . '" ';
        }
        $perpage = C('FX_PERPAGE'); //每页行数
        $zijian = SM('Bank');
        $count = $zijian->selectCount(
                $data, 'id'
        ); // 查询满足要求的总记录数
        // 进行分页数据查询 注意limit方法的参数要使用Page类的属性
        $page = page($count, $request['p'], $perpage) . ',' . $perpage;

        $list = $zijian->pageData(
                '*', $data, 'id DESC', $page
        );


        $payother = SM('Jiekoupeizhi')->selectData('*', '1=1', 'pzid desc');
        $payotherKey = stringChange('arrayKey', $payother, 'pzid');

        foreach ($list as $i => $iList) {
            if (empty($iList['pzid']))
                $list[$i]['pzname'] = '系统默认';
            else
                $list[$i]['pzname'] = $payotherKey[$iList['pzid']]['pzname'];
            $list[$i]['addtime'] = stringChange('formatDateTime', $iList['addtime']);
            $list[$i]['statusname'] = $iList['status'] == 1 ? '取消' : '正常';
        }

        $pageList = $this->pageList($count, $perpage, $map);

        $params = array(
            'list' => $list,
            'page' => $pageList,
            'pageName' => $this->moduleName . '管理'
        );
        return [1,
            $params];
    }

    /**
     * 添加
     */
    public function add($request) {
        //获取有银行配置的账户
        $pzBuffer = $this->getUsePz();

        $params = array(
            'act' => 'add',
            'pzBuffer' => $pzBuffer,
            'pageName' => '添加' . $this->moduleName
        );
        return [1,
            $params];
    }

    //获取能用的银行接口账户
    private function getUsePz() {

        $jkBuffer = SM('Jiekou')->selectData('*', "jkstyle like 'bank%' and ifopen=1");
        if (empty($jkBuffer)) {
            return;
        }

        $jkArr = array();
        foreach ($jkBuffer as $i => $iJkBuffer) {
            $jkArr[] = $iJkBuffer['jkid'];
        }
        if (empty($jkArr))
            return;

        $jkzjBuffer = SM('Jiekouzj')->selectData('*', "jkid in (" . implode(',', $jkArr) . ") and ifopen=1");
        if (empty($jkzjBuffer))
            return;

        $pzArr = array();
        foreach ($jkzjBuffer as $i => $iJkzjBuffer) {
            $pzArr[] = $iJkzjBuffer['pzid'];
        }
        if (empty($pzArr))
            return;
        $buffer = SM('Jiekoupeizhi')->selectData('*', "pzid in (" . implode(',', $pzArr) . ")");
        return $buffer;
    }

    /**
     * 修改
     */
    public function edit($request) {
        if (!$request['id']) {
            return [0,
                '参数错误！'];
        }
        $zijianModel = SM('Bank');
        $row = $zijianModel->findData('*', 'id=' . $request['id']);

        //获取有银行配置的账户
        $pzBuffer = $this->getUsePz();

        $params = array(
            'edit' => $row,
            'act' => 'edit',
            'pzBuffer' => $pzBuffer,
            'pageName' => '修改' . $this->moduleName
        );
        return [1,
            $params,
            'Bank/add'];
    }

    /**
     * 保存
     */
    public function save($request) {
        $zjid = $request['id']; //获取数据标识
        $act = $request['act']; //获取模板标识
        //判断数据标识
        if (empty($zjid) && $act == 'edit') {
            return [0,
                '数据标识不能为空！'];
        }
        if (empty($act)) {
            return [0,
                '模板标识不能为空！'];
        }
        $zijian = SM('Bank');
        $data = array();
        $data['bankname'] = $request['bankname'];
        $data['bankcode'] = $request['bankcode'];
        $data['icon'] = $request['icon'];
        $data['orderid'] = $request['orderid'];
        $data['status'] = $request['status'];
        $data['pzid'] = $request['pzid'];

        //logo上传
        if ($_FILES['file']['size']) {
            $path = SL('Upload')->uploadImage();
            if (!strstr($path, 'Uploads')) {
                return [0,
                    $path];
            }
            $data['icon'] = $path;
        }

        if ($act == 'add') {
            $buffer = $zijian->findData(
                    'bankname,bankcode', 'bankcode="' . $data['bankcode'] . '"');
            if ($buffer) {
                return [0,
                    '银行编码已存在'];
            }
            $data['addtime'] = time();
            if ($zijian->insertData($data) === false) {
                return [0,
                    '添加失败'];
            } else {
                //写入日志
                $this->adminLog($this->moduleName, '添加银行类型【' . $data['userid'] . '】');
                return [1,
                    '添加成功！',
                    __URL__];
            }
        } elseif ($act == 'edit') {
            $data['id'] = $zjid;
            $buffer = $zijian->findData(
                    'bankname,bankcode', 'id="' . $data['id'] . '"');
            if (!$buffer) {
                return [0,
                    '数据标识不存在'];
            }
            $buffer = $zijian->findData(
                    'bankname,bankcode', 'bankcode="' . $data['bankcode'] . '" and id!="' . $data['id'] . '"');
            if ($buffer) {
                return [0,
                    '银行编码已存在'];
            }

            if ($zijian->updateData(
                            $data, 'id=' . $data['id']) === false) {
                return [0,
                    '修改失败'];
            } else {
                //写入日志
                $this->adminLog($this->moduleName, '修改银行类型ID为【' . $data['id'] . '】的数据');
                return [1,
                    '修改成功！',
                    __URL__];
            }
        }
    }

    /**
     * 删除
     */
    public function delete($request) {
        $zijianID = $request['id']; //获取数据标识
        $idArray = explode(',', $zijianID);

        if (!$zijianID) {
            return [0,
                '数据标识不能为空',
                __URL__];
        }
        if (SM('Bank')->deleteData(
                        'id in (' . implode(',', $idArray) . ')') === false) {
            return [0,
                '删除失败'];
        } else {
            //写入日志
            $this->adminLog($this->moduleName, '删除银行类型ID为【' . implode(',', $idArray) . '】的数据');
            return [1,
                '删除成功',
                __URL__];
        }
    }

}
