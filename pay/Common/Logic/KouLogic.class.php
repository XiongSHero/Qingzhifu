<?php

// +----------------------------------------------------------------------
// | easy pay [ pay to easy ]
// +----------------------------------------------------------------------
// | Copyright (c) 2016-2017 All rights reserved.
// +----------------------------------------------------------------------
// | Author: fengxing <QQ:51125330>
// +----------------------------------------------------------------------

namespace Common\Logic;

class KouLogic extends BaseLogic {

    protected $moduleName = '扣量';

    /**
     * 列表
     */
    public function index($request) {
        $map = array();
        $data = ' 1=1 ';
        //高级查询
        if ($request['userid']) {
            $map['userid'] = $request['userid'];
            $data.=' AND userid ="' . $request['userid'] . '" ';
        }
        $perpage = C('FX_PERPAGE'); //每页行数
        $zijian = SM('Zijian');
        $count = $zijian->selectCount(
                $data, 'zjid'
        ); // 查询满足要求的总记录数
        // 进行分页数据查询 注意limit方法的参数要使用Page类的属性
        $page = page($count, $request['p'], $perpage) . ',' . $perpage;

        $list = $zijian->pageData(
                '*', $data, 'zjid DESC', $page
        );
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
        $params = array(
            'act' => 'add',
            'pageName' => '添加' . $this->moduleName
        );
        return [1,
            $params];
    }

    /**
     * 修改
     */
    public function edit($request) {
        if (!$request['id']) {
            return [0,
                '参数错误！'];
        }
        $zijianModel = SM('Zijian');
        $row = $zijianModel->findData('*', 'zjid=' . $request['id']);
        $params = array(
            'edit' => $row,
            'act' => 'edit',
            'pageName' => '修改' . $this->moduleName
        );
        return [1,
            $params,
            'Kou/add'];
    }

    /**
     * 保存
     */
    public function save($request) {
        $zjid = $request['zjid']; //获取数据标识
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
        $zijian = SM('Zijian');
        $data = array();
        $data['userid'] = $request['userid'];
        $data['zijian'] = $request['zijian'];
        $data['initval'] = $request['initval'];

        $buffer = SM('User')->findData('*', 'userid="' . $data['userid'] . '"');
        if (!$buffer) {
            return [0,
                '用户名id不存在'];
        }

        if ($act == 'add') {
            //检查用户名称重复
            $buffer = $zijian->selectData(
                    'userid', 'userid="' . $data['userid'] . '"');
            if ($buffer) {
                return [0,
                    '用户名重复请更换'];
            }
            if ($zijian->insertData($data) === false) {
                return [0,
                    '添加失败'];
            } else {
                //写入日志
                $this->adminLog($this->moduleName, '添加扣量用户【' . $data['userid'] . '】');
                return [1,
                    '添加成功！',
                    __URL__];
            }
        } elseif ($act == 'edit') {
            $data['zjid'] = $zjid;
            $buffer = $zijian->findData(
                    'userid', 'zjid="' . $data['zjid'] . '"');
            if (!$buffer) {
                return [0,
                    '扣量用户不存在'];
            }

            if ($zijian->updateData(
                            $data, 'zjid=' . $data['zjid']) === false) {
                return [0,
                    '修改失败'];
            } else {
                //写入日志
                $this->adminLog($this->moduleName, '修改扣量用户UserID为【' . $buffer['userid'] . '】的数据');
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
        if (SM('Zijian')->deleteData(
                        'zjid in (' . implode(',', $idArray) . ')') === false) {
            return [0,
                '删除失败'];
        } else {
            //写入日志
            $this->adminLog($this->moduleName, '删除扣量用户zjID为【' . implode(',', $idArray) . '】的数据');
            return [1,
                '删除成功',
                __URL__];
        }
    }

    /**
     * 加入用户扣量
     */
    public function addUser($userid) {
        if (empty($buffer['klvalue']))
            $buffer['klvalue'] = 10;
        if (empty($buffer['klzijian']))
            $buffer['klzijian'] = 10;

        $zjBuffer = SM('Zijian')->findData('*', 'userid="' . $userid . '"');
        $data = array(
            'userid' => $userid,
            'initval' => $buffer['klvalue'],
            'zijian' => $buffer['klzijian']
        );
        if ($zjBuffer) {
            SM('Zijian')->updateData($data, 'userid="' . $userid . '"');
        } else {
            SM('Zijian')->insertData($data);
        }
    }

}
