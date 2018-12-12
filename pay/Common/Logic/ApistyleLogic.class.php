<?php

// +----------------------------------------------------------------------
// | easy pay [ pay to easy ]
// +----------------------------------------------------------------------
// | Copyright (c) 2016-2017 All rights reserved.
// +----------------------------------------------------------------------
// | Author: fengxing <QQ:51125330>
// +----------------------------------------------------------------------

namespace Common\Logic;

class ApistyleLogic extends BaseLogic {

    protected $moduleName = '上游类型';

    /**
     * 列表
     */
    public function index($request) {
        $map = array();
        $data = ' 1=1 ';

        $jiekou = SM('Jiekoustyle');
        $list = $jiekou->selectData(
                '*', $data, 'id ASC'
        );

        foreach ($list as $i => $iList) {
            $tmp = unserialize($iList['params']);
            $str = array();
            foreach ($tmp as $j => $jTmp) {
                $str[$j] = '';
                foreach ($jTmp as $k => $kTmp) {
                    $str[$j].='【' . $k . '：' . $kTmp . '】';
                }
            }
            $list[$i]['params'] = implode('<br/>', $str);
            $list[$i]['addtime'] = stringChange('formatDateTime', $iList['addtime']);
        }

        $params = array(
            'list' => $list,
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

        $Jiekou = SM('Jiekoustyle');
        $row = $Jiekou->findData('*', 'id=' . $request['id']);
        $list = unserialize($row['params']);

        $params = array(
            'edit' => $row,
            'list' => $list,
            'act' => 'edit',
            'pageName' => '修改' . $this->moduleName
        );
        return [1,
            $params,
            'Apistyle/add'];
    }

    /**
     * 保存
     */
    public function save($request) {
        $jiekouID = $request['id']; //获取数据标识
        $act = $request['act']; //获取模板标识
        //判断数据标识
        if (empty($jiekouID) && $act == 'edit') {
            return [0,
                '数据标识不能为空！'];
        }
        if (empty($act)) {
            return [0,
                '模板标识不能为空！'];
        }
        $title = $request['paramstitle'];
        $en = $request['paramsen'];
        $value = $request['paramsvalue'];
        $input = $request['paramsinput'];
        $paramsArray = array();
        foreach ($title as $i => $iTitle) {
            $paramsArray[] = array(
                'title' => $iTitle,
                'en' => $en[$i],
                'input' => $input[$i],
                'value' => $value[$i]
            );
        }

        $jiekou = SM('Jiekoustyle');
        $data = array();
        $data['stylename'] = $request['stylename'];
        $data['en'] = $request['en'];
        $data['params'] = serialize($paramsArray);

        if ($act == 'add') {
            $data['addtime'] = time();
            //检查名称重复
            $buffer = $jiekou->selectData(
                    'id', 'stylename="' . $data['stylename'] . '"');
            if ($buffer) {
                return [0,
                    '名称重复请更换'];
            }
            $buffer = $jiekou->selectData(
                    'id', 'en="' . $data['en'] . '"');
            if ($buffer) {
                return [0,
                    '英文标识重复请更换'];
            }
            if ($jiekou->insertData($data) === false) {
                return [0,
                    '添加失败'];
            } else {
                //写入日志
                $this->adminLog($this->moduleName, '添加上游类型【' . $data['stylename'] . '】');
                return [1,
                    '添加成功！',
                    U('Apistyle/index')];
            }
        } elseif ($act == 'edit') {
            $data['id'] = $jiekouID;
            $buffer = $jiekou->selectData(
                    'id,stylename', 'id="' . $data['id'] . '"');
            if (!$buffer) {
                return [0,
                    '类型不存在'];
            }
            $buffer = $jiekou->selectData(
                    'id,stylename', 'stylename="' . $data['stylename'] . '" && id!="' . $data['id'] . '"');
            if ($buffer) {
                return [0,
                    '类型名称重复'];
            }
            $buffer = $jiekou->selectData(
                    'id,stylename', 'en="' . $data['en'] . '" && id!="' . $data['id'] . '"');
            if ($buffer) {
                return [0,
                    '接口类型英文标识重复'];
            }
            if ($jiekou->updateData(
                            $data, 'id=' . $data['id']) === false) {
                return [0,
                    '修改失败'];
            } else {
                //写入日志
                $this->adminLog($this->moduleName, '修改上游类型id为【' . $jiekouID . '】的数据');
                return [1,
                    '修改成功！',
                    U('Apistyle/index')];
            }
        }
    }

    /**
     * 删除
     */
    public function delete($request) {
        $jiekouID = $request['id']; //获取数据标识
        $idArray = explode(',', $jiekouID);
        if (!$jiekouID) {
            return [0,
                '数据标识不能为空',
                __URL__];
        }
        if (SM('Jiekoustyle')->deleteData(
                        'id in (' . implode(',', $idArray) . ')') === false) {
            return [0,
                '删除失败'];
        } else {
            //写入日志
            $this->adminLog($this->moduleName, '删除上游类型ID为【' . implode(',', $idArray) . '】的数据');
            return [1,
                '删除成功',
                __URL__];
        }
    }

}
