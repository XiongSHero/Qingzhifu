<?php

// +----------------------------------------------------------------------
// | easy pay [ pay to easy ]
// +----------------------------------------------------------------------
// | Copyright (c) 2016-2017 All rights reserved.
// +----------------------------------------------------------------------
// | Author: fengxing <QQ:51125330>
// +----------------------------------------------------------------------

namespace Common\Logic;

class ApiLogic extends BaseLogic {

    protected $moduleName = '接口';
    protected $round = array(
        '关闭',
        '开启');
    protected $jumpname = array(
        0 => '不跳转',
        'gateway' => '跳转银行');

    /**
     * 列表
     */
    public function index($request) {
        $map = array();
        $data = ' 1=1 ';

        $jiekou = SM('Jiekou');

        $list = $jiekou->selectData(
                '*', $data, 'jkid ASC'
        );


        //获取接口域名
        $httpBuffer = SM('Http')->selectData('*', '1=1', 'id asc');
        $httpBuffer = stringChange('arrayKey', $httpBuffer, 'id');

        foreach ($list as $i => $iList) {
            $list[$i]['round'] = $this->round[$list[$i]['ifround']];
            if ($list[$i]['round'] == '开启')
                $list[$i]['round'] = '<font color="red">' . $list[$i]['round'] . '</font>';
            $list[$i]['ifopenname'] = $this->round[$list[$i]['ifopen']];
            $list[$i]['ifuseropenname'] = $this->round[$list[$i]['ifuseropen']];
            if (empty($list[$i]['httpid']))
                $list[$i]['http'] = '默认域名';
            else
                $list[$i]['http'] = $httpBuffer[$list[$i]['httpid']]['http'];
        }

        $params = array(
            'list' => $list,
            'pageName' => $this->moduleName . '类型管理'
        );
        return [1,
            $params];
    }

    /**
     * 轮询配置
     */
    public function round($request) {
        if (!$request['id']) {
            return [0,
                '参数错误！'];
        }

        if (IS_POST) {
            $jkid = $request['id'];
            $zjid = $request['zjid'];
            $list = array();
            if (!empty($zjid)) {
                if (!is_array($zjid))
                    $zjid = array(
                        $zjid);
                foreach ($zjid as $i => $iZjid) {
                    $list[$iZjid] = array(
                        'ifopen' => 1,
                        'power' => $request['power_' . $iZjid]
                    );
                }
            }

            $data = array(
                'ifround' => $request['ifround'],
                'roundhttpid' => $request['roundhttpid'],
                'list' => serialize($list)
            );
            $result = SM('Jiekou')->updateData($data, 'jkid=' . $jkid);
            if ($result === false) {
                return [0,
                    '更新失败'];
            }
            return [1,
                '更新成功',
                __URL__];
        }

        //获取id数据
        $Jiekou = SM('Jiekou');
        $row = $Jiekou->findData('*', 'jkid=' . $request['id']);
        $row['list'] = unserialize($row['list']);

        //获取待轮询的列表
        $zjBuffer = SM('Jiekouzj')->selectData('*', 'jkid=' . $request['id'], 'pzid asc');
        $pzArray = array();
        foreach ($zjBuffer as $i => $iZjBuffer) {
            $pzArray[] = $iZjBuffer['pzid'];
            $zjBuffer[$i]['power'] = $row['list'][$iZjBuffer['zjid']]['power'];
            $zjBuffer[$i]['ifopen'] = $row['list'][$iZjBuffer['zjid']]['ifopen'];
        }

        //获取配置信息
        if ($pzArray) {
            $pzBuffer = SM('Jiekoupeizhi')->selectData('*', 'pzid in (' . implode(',', $pzArray) . ')');
            $pzBuffer = stringChange('arrayKey', $pzBuffer, 'pzid');
        }

        foreach ($zjBuffer as $i => $iZjBuffer) {
            $zjBuffer[$i]['pzname'] = $pzBuffer[$iZjBuffer['pzid']]['pzname'];
        }

        //获取接口域名
        $httpBuffer = SM('Http')->selectData('*', 'locked=0', 'id asc');

        $params = array(
            'edit' => $row,
            'list' => $zjBuffer,
            'act' => 'edit',
            'http' => $httpBuffer,
            'pageName' => '配置' . $this->moduleName . '轮询'
        );
        return [1,
            $params,
            'Api/round'];
    }

    /**
     * 添加
     */
    public function add($request) {
        //获取接口域名
        $httpBuffer = SM('Http')->selectData('*', 'locked=0', 'id asc');

        $params = array(
            'act' => 'add',
            'http' => $httpBuffer,
            'pageName' => '添加' . $this->moduleName . '类型'
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
        $Jiekou = SM('Jiekou');
        $row = $Jiekou->findData('*', 'jkid=' . $request['id']);

        //获取接口域名
        $httpBuffer = SM('Http')->selectData('*', 'locked=0', 'id asc');

        $params = array(
            'edit' => $row,
            'act' => 'edit',
            'http' => $httpBuffer,
            'pageName' => '修改' . $this->moduleName . '类型'
        );
        return [1,
            $params,
            'Api/add'];
    }

    /**
     * 保存
     */
    public function save($request) {
        $jiekouID = $request['jkid']; //获取数据标识
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
        $jiekou = SM('Jiekou');
        $data = array();
        $data['jkname'] = $request['jkname'];
        $data['jkstyle'] = $request['jkstyle'];
        $data['ifopen'] = $request['ifopen'];
        $data['ifuseropen'] = $request['ifuseropen'];
        $data['httpid'] = $request['httpid'];

        if ($act == 'add') {

            //检查名称重复
            $buffer = $jiekou->selectData(
                    'jkid', 'jkname="' . $data['jkname'] . '"');
            if ($buffer) {
                return [0,
                    '名称重复请更换'];
            }
            //检查类型重复
            $buffer = $jiekou->selectData(
                    'jkid', 'jkstyle="' . $data['jkstyle'] . '"');
            if ($buffer) {
                return [0,
                    '类型重复请更换'];
            }
            if ($jiekou->insertData($data) === false) {
                return [0,
                    '添加失败'];
            } else {
                //写入日志
                $this->adminLog($this->moduleName, '添加接口类型【' . $data['jkname'] . '】');
                return [1,
                    '添加成功！',
                    __URL__];
            }
        } elseif ($act == 'edit') {
            $data['jkid'] = $jiekouID;
            $buffer = $jiekou->selectData(
                    'jkid,jkname', 'jkid="' . $data['jkid'] . '"');
            if (!$buffer) {
                return [0,
                    '接口类型不存在'];
            }
            $buffer = $jiekou->selectData(
                    'jkid,jkname', 'jkname="' . $data['jkname'] . '" && jkid!="' . $data['jkid'] . '"');
            if ($buffer) {
                return [0,
                    '接口名称重复'];
            }
            $buffer = $jiekou->selectData(
                    'jkid,jkname', 'jkstyle="' . $data['jkstyle'] . '" && jkid!="' . $data['jkid'] . '"');
            if ($buffer) {
                return [0,
                    '类型名称重复'];
            }
            if ($jiekou->updateData(
                            $data, 'jkid=' . $data['jkid']) === false) {
                return [0,
                    '修改失败'];
            } else {
                //写入日志
                $this->adminLog($this->moduleName, '修改接口类型jkid为【' . $jiekouID . '】的数据');
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
        $jiekouID = $request['id']; //获取数据标识
        $idArray = explode(',', $jiekouID);
        if (!$jiekouID) {
            return [0,
                '数据标识不能为空',
                __URL__];
        }
        if (SM('Jiekou')->deleteData(
                        'jkid in (' . implode(',', $idArray) . ')') === false) {
            return [0,
                '删除失败'];
        } else {
            //写入日志
            $this->adminLog($this->moduleName, '删除接口类型jkID为【' . implode(',', $idArray) . '】的数据');
            return [1,
                '删除成功',
                __URL__];
        }
    }

    /**
     * 接口账户列表
     */
    public function user($request) {
        global $publicData;
        $map = array();
        $data = ' 1=1 ';

        $jiekou = SM('Jiekou');
        $list = $jiekou->selectData(
                '*', $data, 'jkid ASC'
        );
        $jiekouArr = array();
        foreach ($list as $iList) {
            $iList['list'] = unserialize($iList['list']);
            $jiekouArr[$iList['jkid']] = $iList;
        }

        //获取接口域名
        $httpBuffer = SM('Http')->selectData('*', '1=1', 'id asc');
        $httpBuffer = stringChange('arrayKey', $httpBuffer, 'id');

        $jiekouzj = SM('Jiekouzj');
        $list = $jiekouzj->selectData(
                '*', $data, 'zjid ASC'
        );
        $jiekouzjArr = array();
        $today = strtotime(date('Y-m-d'));
        foreach ($list as $iList) {
            $iList['isround'] = $jiekouArr[$iList['jkid']]['list'][$iList['zjid']] ? 1 : 0; //是否在轮询中
            $iList['ifround'] = $jiekouArr[$iList['jkid']]['ifround'];
            $iList['jkname'] = $jiekouArr[$iList['jkid']]['jkname'];
            $iList['jkstyle'] = $jiekouArr[$iList['jkid']]['jkstyle'];
            $iList = stringChange('formatMoneyByArray', $iList, array(
                'fl',
                'je',
                'jemax',
                'jetotal',
                'jetoday'));
            if (empty($iList['jemax']))
                $iList['jemax'] = '不限';
            if (empty($iList['jetotal'])) {
                $iList['jetotal'] = '不限';
            } else {
                if ($today != $iList['today']) {
                    $iList['jetoday'] = 0;
                }
            }

            $iList['ifjumpname'] = $this->jumpname[$iList['ifjump']];
            $iList['ifopen'] = $iList['ifopen'] == 0 ? '关闭' : '<font color="red">开启</font>';
            $iList['ifchoose'] = $iList['ifchoose'] == 0 ? '<a href="javascript:;" class="ifchoose" tid="' . $iList['zjid'] . '">未应用</a>' : '<font color="red">应用</font>';
            $times = round((time() - $iList['changetime']) / 60 + (int) $publicData['peizhi']['changeapitime'], 0);
            $iList['changetime'] = $iList['changetime'] == 0 ? '无' : ($times . '分钟');

            $jiekouzjArr[$iList['pzid']][] = $iList;
        }

        $jiekoupeizhi = SM('Jiekoupeizhi');
        $list = $jiekoupeizhi->selectData(
                '*', $data, 'pzid ASC'
        );
        foreach ($list as $i => $iList) {
            $list[$i]['params'] = (($iList['params']));
            if (empty($list[$i]['httpid']))
                $list[$i]['http'] = '默认域名';
            else
                $list[$i]['http'] = $httpBuffer[$list[$i]['httpid']]['http'];
            $list[$i]['sub'] = $jiekouzjArr[$iList['pzid']];
        }

        $params = array(
            'list' => $list,
            'pageName' => $this->moduleName . '账户管理'
        );
        return [1,
            $params];
    }

    /**
     * 添加
     */
    public function adduser($request) {
        $jiekou = SM('Jiekou');
        $list = $jiekou->selectData(
                '*', $data, 'jkid ASC'
        );

        //获取接口域名
        $httpBuffer = SM('Http')->selectData('*', 'locked=0', 'id asc');

        //获取上游类型
        $jiekoustyle = SM('Jiekoustyle');
        $liststyle = $jiekoustyle->selectData(
                '*', '1=1', 'id ASC'
        );
        foreach ($liststyle as $i => $iListstyle) {
            $liststyle[$i]['params'] = unserialize($liststyle[$i]['params']);
        }
        $newliststyle = stringChange('arrayKey', $liststyle, 'id');
        $newliststyle = json_encode($newliststyle, true);
        if (empty($newliststyle))
            $newliststyle = '';

        $params = array(
            'act' => 'add',
            'list' => $list,
            'http' => $httpBuffer,
            'liststyle' => $liststyle,
            'newliststyle' => $newliststyle,
            'jumpname' => $this->jumpname,
            'pageName' => '添加' . $this->moduleName . '账户'
        );
        return [1,
            $params];
    }

    /**
     * 修改
     */
    public function edituser($request) {
        if (!$request['id']) {
            return [0,
                '参数错误！'];
        }

        $jiekouzj = SM('Jiekouzj');
        $list = $jiekouzj->selectData(
                '*', 'pzid=' . $request['id'], 'zjid ASC'
        );
        $jiekouzjArr = array();
        foreach ($list as $iList) {
            $jiekouzjArr[$iList['jkid']] = $iList;
        }

        $jiekou = SM('Jiekou');
        $list = $jiekou->selectData(
                '*', '1=1', 'jkid ASC'
        );
        $jiekouArr = array();
        foreach ($list as $i => $iList) {
            $list[$i]['pzid'] = $jiekouzjArr[$iList['jkid']]['pzid'];
            $list[$i]['fl'] = $jiekouzjArr[$iList['jkid']]['fl'];
            $list[$i]['je'] = $jiekouzjArr[$iList['jkid']]['je'];
            $list[$i]['jemax'] = $jiekouzjArr[$iList['jkid']]['jemax'];
            $list[$i]['jetotal'] = $jiekouzjArr[$iList['jkid']]['jetotal'];
            $list[$i]['ifjump'] = $jiekouzjArr[$iList['jkid']]['ifjump'];
            $list[$i]['ifopen'] = $jiekouzjArr[$iList['jkid']]['ifopen'];
            $list[$i] = stringChange('formatMoneyByArray', $list[$i], array(
                'fl',
                'syfl',
                'je',
                'jemax',
                'jetotal'));
        }

        $jiekoupeizhi = SM('Jiekoupeizhi');
        $row = $jiekoupeizhi->findData('*', 'pzid=' . $request['id']);
        $row = array_merge($row, unserialize($row['params']));
        $jsonrow = json_encode($row, true);
        if (empty($row))
            $jsonrow = '';

        //获取接口域名
        $httpBuffer = SM('Http')->selectData('*', 'locked=0', 'id asc');

        //获取上游类型
        $jiekoustyle = SM('Jiekoustyle');
        $liststyle = $jiekoustyle->selectData(
                '*', '1=1', 'id ASC'
        );
        foreach ($liststyle as $i => $iListstyle) {
            $liststyle[$i]['params'] = unserialize($liststyle[$i]['params']);
        }
        $newliststyle = stringChange('arrayKey', $liststyle, 'id');
        $newliststyle = json_encode($newliststyle, true);
        if (empty($newliststyle))
            $newliststyle = '';

        $params = array(
            'edit' => $row,
            'editstyle' => $jsonrow,
            'list' => $list,
            'act' => 'edit',
            'http' => $httpBuffer,
            'liststyle' => $liststyle,
            'newliststyle' => $newliststyle,
            'jumpname' => $this->jumpname,
            'pageName' => '修改' . $this->moduleName . '账户'
        );
        return [1,
            $params,
            'Api/adduser'];
    }

    /**
     * 保存
     */
    public function saveuser($request) {
        $pzID = $request['pzid']; //获取数据标识
        $act = $request['act']; //获取模板标识
        //判断数据标识
        if (empty($pzID) && $act == 'edit') {
            return [0,
                '数据标识不能为空！'];
        }
        if (empty($act)) {
            return [0,
                '模板标识不能为空！'];
        }
        $data = array();
        $data['pzname'] = $request['pzname'];
        $data['style'] = $request['style'];
        $data['ifrepay'] = $request['ifrepay'];
        $data['httpid'] = $request['httpid'];

        $styleBuffer = SM('Jiekoustyle')->findData('*', 'en="' . $data['style'] . '"');
        if (empty($styleBuffer)) {
            return [0,
                '上游类型不存在，添加上游类型后，刷新重试。'];
        }
        $styleBufferList = unserialize($styleBuffer['params']);
        $tmparr = array();
        foreach ($styleBufferList as $i => $iStyleBufferList) {
            $tmp = $data['style'] . '_' . $iStyleBufferList['en'];
            $tmparr[$tmp] = $request[$tmp];
        }
        $data['params'] = serialize($tmparr);

        $jkid = $request['jkid'];
        $zjbuffer = array();
        foreach ($jkid as $iJkid) {
            $zjbuffer[] = array(
                'jkid' => $iJkid,
                'fl' => $request['fl_' . $iJkid],
                'syfl' => $request['syfl_' . $iJkid],
                'je' => $request['je_' . $iJkid],
                'jemax' => $request['jemax_' . $iJkid],
                'jetotal' => $request['jetotal_' . $iJkid],
                'ifjump' => $request['ifjump_' . $iJkid],
                'ifopen' => $request['ifopen_' . $iJkid]
            );
        }

        $jiekoupeizhi = SM('Jiekoupeizhi');
        if ($act == 'add') {
            //检查名称重复
            $buffer = $jiekoupeizhi->selectData(
                    'pzid', 'pzname="' . $data['pzname'] . '"');
            if ($buffer) {
                return [0,
                    '名称重复请更换'];
            }
            if (($pzID = $jiekoupeizhi->insertData($data)) === false) {
                return [0,
                    '添加失败'];
            } else {

                if ($zjbuffer) {
                    //写入配置中间
                    $addAllBuffer = array();
                    foreach ($zjbuffer as $iZjbuffer) {
                        $addAllBuffer[] = array(
                            'pzid' => $pzID,
                            'jkid' => $iZjbuffer['jkid'],
                            'fl' => $iZjbuffer['fl'],
                            'syfl' => $iZjbuffer['syfl'],
                            'je' => $iZjbuffer['je'],
                            'jemax' => $iZjbuffer['jemax'],
                            'jetotal' => $iZjbuffer['jetotal'],
                            'ifjump' => $iZjbuffer['ifjump'],
                            'ifopen' => $iZjbuffer['ifopen']
                        );
                    }
                    SM('Jiekouzj')->addAllData($addAllBuffer);
                }

                $this->checkApiChoose(); //接口应用检测
                //写入日志
                $this->adminLog($this->moduleName, '添加接口账户【' . $data['pzname'] . '】');
                return [1,
                    '添加成功！',
                    U('Api/user')];
            }
        } elseif ($act == 'edit') {
            $data['pzid'] = $pzID;
            $buffer = $jiekoupeizhi->selectData(
                    'pzid,pzname', 'pzid="' . $data['pzid'] . '"');
            if (!$buffer) {
                return [0,
                    '接口类型不存在'];
            }
            $buffer = $jiekoupeizhi->selectData(
                    'pzid,pzname', 'pzname="' . $data['pzname'] . '" && pzid!="' . $data['pzid'] . '"');
            if ($buffer) {
                return [0,
                    '接口名称重复'];
            }

            if ($jiekoupeizhi->updateData(
                            $data, 'pzid=' . $data['pzid']) === false) {
                return [0,
                    '修改失败'];
            } else {

                $jiekouzj = SM('Jiekouzj');
                $buffer = $jiekouzj->selectData('*', 'pzid=' . $data['pzid']);
                //写入配置中间
                if ($zjbuffer) {
                    //写入配置中间
                    $addAllBuffer = array();
                    foreach ($zjbuffer as $i => $iZjbuffer) {
                        if ($buffer[$i]) {
                            $jiekouzj->updateData(array(
                                'pzid' => $pzID,
                                'jkid' => $iZjbuffer['jkid'],
                                'fl' => $iZjbuffer['fl'],
                                'syfl' => $iZjbuffer['syfl'],
                                'je' => $iZjbuffer['je'],
                                'jemax' => $iZjbuffer['jemax'],
                                'jetotal' => $iZjbuffer['jetotal'],
                                'ifjump' => $iZjbuffer['ifjump'],
                                'ifopen' => $iZjbuffer['ifopen']
                                    ), 'zjid=' . $buffer[$i]['zjid']);
                        } else {
                            $addAllBuffer[] = array(
                                'pzid' => $pzID,
                                'jkid' => $iZjbuffer['jkid'],
                                'fl' => $iZjbuffer['fl'],
                                'syfl' => $iZjbuffer['syfl'],
                                'je' => $iZjbuffer['je'],
                                'jemax' => $iZjbuffer['jemax'],
                                'jetotal' => $iZjbuffer['jetotal'],
                                'ifjump' => $iZjbuffer['ifjump'],
                                'ifopen' => $iZjbuffer['ifopen']
                            );
                        }
                    }
                    $i++;
                    if ($buffer[$i]) {
                        $zjidArr = array();
                        for (; $i < count($buffer); $i++) {
                            $zjidArr[] = $buffer[$i]['zjid'];
                        }
                        $jiekouzj->deleteData('zjid in (' . implode(',', $zjidArr) . ')');
                    } else {
                        if ($addAllBuffer)
                            $jiekouzj->addAllData($addAllBuffer);
                    }
                }else {
                    $jiekouzj->deleteData('pzid=' . $data['pzid']);
                }

                $this->checkApiChoose(); //接口应用检测
                //写入日志
                $this->adminLog($this->moduleName, '修改接口账户pzid为【' . $pzID . '】的数据');
                return [1,
                    '修改成功！',
                    U('Api/user')];
            }
        }
    }

    /**
     * 删除
     */
    public function deleteuser($request) {
        $pzID = $request['id']; //获取数据标识
        $idArray = explode(',', $pzID);
        if (!$pzID) {
            return [0,
                '数据标识不能为空',
                U('Api/user')];
        }
        if (SM('Jiekoupeizhi')->deleteData(
                        'pzid in (' . implode(',', $idArray) . ')') === false) {
            return [0,
                '删除失败'];
        } else {

            SM('Jiekouzj')->deleteData('pzid in (' . implode(',', $idArray) . ')');
            $this->checkApiChoose(); //接口应用检测
            //写入日志
            $this->adminLog($this->moduleName, '删除接口账户pzID为【' . implode(',', $idArray) . '】的数据');
            return [1,
                '删除成功',
                U('Api/user')];
        }
    }

    /**
     * 切换账户接口开启状态
     */
    public function userchangeopen($request) {
        $zjid = explode(',', $request['zjid']);
        $ifopen = $request['ifopen']; //需要切换成的状态
        $jiekouzj = SM('Jiekouzj');
        $zjBuffer = $jiekouzj->selectData('*', 'zjid in (' . implode(',', $zjid) . ')');
        if (!$zjBuffer) {
            return [1,
                '切换成功'];
        }

        if ($ifopen === '') {
            $ifopen = $zjBuffer[0]['ifopen'] == 1 ? 0 : 1;
            $idArray = array(
                $zjBuffer[0]['zjid']);
        } else {
            $idArray = array();
            foreach ($zjBuffer as $iZjBuffer) {
                if ($iZjBuffer['ifopen'] != $ifopen) {
                    $idArray[] = $iZjBuffer['zjid'];
                }
            }
            if (empty($idArray)) {
                return [1,
                    '切换成功'];
            }
        }
        $statusname = '开启';
        if (empty($ifopen)) {
            $statusname = '关闭';
            $ifopen = 0;
        }

        //更新当前接口
        $result = $jiekouzj->updateData(array(
            'ifopen' => $ifopen,
            'ifchoose' => 0,
            'changetime' => 0), 'zjid in (' . implode(',', $idArray) . ')');
        if ($result === false) {
            return [0,
                '切换失败'];
        } else {
            $this->checkApiChoose(); //接口应用检测

            return [1,
                '切换成功'];
        }
    }

    /**
     * 切换账户接口应用
     */
    public function userchangechoose($request) {
        $zjid = $request['zjid'];
        $jiekouzj = SM('Jiekouzj');
        $zjBuffer = $jiekouzj->findData('*', 'zjid=' . $zjid);
        if (!$zjBuffer || $zjBuffer['ifchoode'] == 1) {
            return [1,
                '切换成功'];
        }

        global $publicData;
        //更新所属接口类型状态为0
        $jiekouzj->updateData(array(
            'ifchoose' => 0,
            'changetime' => 0), 'jkid=' . $zjBuffer['jkid'] . ' and ifchoose=1');
        //更新当前接口为1
        $jiekouzj->updateData(array(
            'ifchoose' => 1,
            'changetime' => (time() + (int) $publicData['peizhi']['changeapitime'] * 60)), 'zjid=' . $zjid);
        return [1,
            '切换成功'];
    }

    /**
     * 判断接口的应用状态
     */
    public function checkApiChoose() {
        global $publicData;

        //获取基础接口
        $jiekou = SM('Jiekou');
        $jiekouzj = SM('Jiekouzj');
        $jiekouBuffer = $jiekou->selectData('*', '1=1');
        $jiekouBuffer = stringChange('arrayKey', $jiekouBuffer, 'jkid');

        //更改已经关闭的数据的应用状态
        $jiekouzj->updateData(array(
            'ifchoose' => 0,
            'changetime' => 0), 'ifopen=0');

        //去掉已经有应用的接口
        $jiekouzjBuffer = $jiekouzj->selectData('*', 'ifchoose=1 and ifopen=1');
        foreach ($jiekouzjBuffer as $iBuffer) {
            unset($jiekouBuffer[$iBuffer['jkid']]);
        }

        //对剩余接口进行轮训
        if ($jiekouBuffer) {
            foreach ($jiekouBuffer as $i => $iBuffer) {
                $jiekouzjBuffer = $jiekouzj->findData('*', 'jkid=' . $i . ' and ifopen=1', 'zjid asc');
                if ($jiekouzjBuffer) {
                    $jiekouzj->updateData(array(
                        'ifchoose' => 1,
                        'changetime' => (time() + (int) $publicData['peizhi']['changeapitime'] * 60)), 'zjid=' . $jiekouzjBuffer['zjid']);
                }
            }
        }
    }

    /**
     * 获取以jkid为键值的数组
     */
    public function getJkArrayKeyID() {
        $jiekou = SM('Jiekou');
        $jiekouBuffer = $jiekou->selectData(
                '*', '1=1', 'jkid ASC'
        );
        return stringChange('arrayKey', $jiekouBuffer, 'jkid');
    }

    /**
     * 获取以pzid为键值的数组
     */
    public function getPzArrayKeyID() {
        $jiekoupz = SM('Jiekoupeizhi');
        $jiekoupzBuffer = $jiekoupz->selectData(
                '*', '1=1', 'pzid ASC'
        );
        return stringChange('arrayKey', $jiekoupzBuffer, 'pzid');
    }

    /**
     * 根据zjid获取接口和配置数组
     */
    public function getJkByZj($zjid) {
        //获取支付账号
        $jkzj = SM('Jiekouzj')->findData('*', 'zjid=' . $zjid);
        if (!$jkzj)
            return;

        $jkpz = SM('Jiekoupeizhi')->findData('*', 'pzid=' . $jkzj['pzid']);
        if (!$jkzj)
            return $jkzj;
        $jkzj['pzname'] = $jkpz['pzname'];
        $jkzj['style'] = $jkpz['style'];
        $jkzj['params'] = $jkpz['params'];
        $jkzj['httpid'] = $jkpz['httpid'];

        $jk = SM('Jiekou')->findData('*', 'jkid=' . $jkzj['jkid']);
        if (!$jkzj)
            return $jkzj;

        $jkzj['jkname'] = $jk['jkname'];
        $jkzj['jkstyle'] = $jk['jkstyle'];
        return $jkzj;
    }

    /**
     * 接口检测
     */
    public function check($request) {

        $jkBuffer = $this->getJkArrayKeyID();
        $jkpzBuffer = $this->getPzArrayKeyID();

        //检测接口返回情况
        if (IS_POST) {
            $zjid = explode(',', $request['id']);
            $jkzjBuffer = SM('Jiekouzj')->selectData('*', 'zjid in (' . implode(',', $zjid) . ')', 'zjid asc');
            $jkzjBuffer = stringChange('arrayKey', $jkzjBuffer, 'zjid');

            $data = array();
            //获取后台配置的收钱账户
            global $publicData;
            $thisuserid = $publicData['peizhi']['bzjuserid'];
            if (!$thisuserid) {
                $thisuserid = '2017100';
            }

            $userBuffer = SM('User')->findData('userid,miyao', 'userid=' . $thisuserid);
            $data['fxid'] = $userBuffer['userid'];
            $data['fxkey'] = $userBuffer['miyao'];
            $data['fxdesc'] = 'testpay';
            $data['fxfee'] = 10;
            $data['fxnotifyurl'] = $publicData['peizhi']['httpstyle'] . "://" . $_SERVER['HTTP_HOST'] . "/Test/notifyUrl";
            $data['fxbackurl'] = $publicData['peizhi']['httpstyle'] . "://" . $_SERVER['HTTP_HOST'] . "/Test/backUrl";
            $data['fxip'] = get_client_ip(0, true);
            $return = array();
            foreach ($zjid as $iZjid) {
                $data['fxddh'] = time() . getDingdanRand();
                $data['fxattch'] = $data['fxddh'];
                $data['fxpay'] = $jkBuffer[$jkzjBuffer[$iZjid]['jkid']]['jkstyle'];
                $data['fxsign'] = md5($data['fxid'] . $data['fxddh'] . $data['fxfee'] . $data['fxnotifyurl'] . $data['fxkey']);
                $buffer = SL('Pay/payApi', $data, $jkzjBuffer[$iZjid]['zjid']);
                $return[] = array(
                    'id' => $iZjid,
                    'status' => $this->changeApiResult($buffer));
            }

            return [1,
                $return];
        }

        //获取所有可用接口列表
        $jkzjBuffer = SM('Jiekouzj')->selectData('*', '1=1', 'zjid asc');

        foreach ($jkzjBuffer as $i => $iJkzjBuffer) {
            $jkzjBuffer[$i]['jkname'] = $jkBuffer[$iJkzjBuffer['jkid']]['jkname'];
            $jkzjBuffer[$i]['jkstyle'] = $jkBuffer[$iJkzjBuffer['jkid']]['jkstyle'];
            $jkzjBuffer[$i]['pzname'] = $jkpzBuffer[$iJkzjBuffer['pzid']]['pzname'];
            $jkzjBuffer[$i]['ifopen'] = $iJkzjBuffer['ifopen'] == 1 ? '开启' : '关闭';
            $jkzjBuffer[$i]['ifopenno'] = $iJkzjBuffer['ifopen'] == 1 ? '关闭' : '开启';
            $jkzjBuffer[$i]['ifchoose'] = $iJkzjBuffer['ifchoose'] == 1 ? '应用' : '未应用';
            $jkzjBuffer[$i] = stringChange('formatMoneyByArray', $jkzjBuffer[$i], array(
                'fl'));
        }

        $params = array(
            'list' => $jkzjBuffer,
            'pageName' => '接口检测'
        );
        return [1,
            $params];
    }

    //获取可用接口
    public function getOpenApi() {
        //判断是否有可用的接口
        $jkBuffer = SM('Jiekou')->selectData('*', '1=1');
        $jkBuffer = stringChange('arrayKey', $jkBuffer, 'jkid');
        $jkzjBuffer = SM('Jiekouzj')->selectData('*', 'ifchoose=1 and ifopen=1');
        $list = array();
        foreach ($jkzjBuffer as $i => $iJkzjBuffer) {
            if ($jkBuffer[$iJkzjBuffer['jkid']]['ifopen'] != 1)
                continue;
            $list[$i] = $iJkzjBuffer;
            $list[$i]['jkname'] = $jkBuffer[$iJkzjBuffer['jkid']]['jkname'];
            $list[$i]['jkstyle'] = $jkBuffer[$iJkzjBuffer['jkid']]['jkstyle'];
        }
        return $list;
    }

    /**
     * 获取用户可用的api及费率
     * @params int $userid 商户id
     * @params int $have 是否仅返回开启类型 默认0返回全部  1返回可用类型
     */
    public function getUserOpenApi($userid, $have = 0) {
        $jiekouBuffer = SM('Jiekou')->selectData('*', '1=1', 'jkid asc');

        //为设定的接口添加用户定义fl和ifopen
        $jiekouUser = SM('JiekouUser');
        $jiekouUserBuffer = $jiekouUser->selectData('*', 'userid=' . $userid);
        if ($jiekouUserBuffer) {
            $jiekouUserBuffer = stringChange('arrayKey', $jiekouUserBuffer, 'jkid');
            foreach ($jiekouBuffer as $i => $iJiekouBuffer) {
                $jiekouBuffer[$i]['fl'] = $jiekouUserBuffer[$iJiekouBuffer['jkid']]['fl'];
                $jiekouBuffer[$i]['fldefault'] = 1; //为1代表用户自定义费率
                $jiekouBuffer[$i]['ifdefaultopen'] = $jiekouUserBuffer[$iJiekouBuffer['jkid']]['ifopen'];
            }
        }

        //接口配置费率 非轮询
        $jiekouzjBuffer = SM('Jiekouzj')->selectData('*', 'ifchoose=1 and ifopen=1');
        if ($jiekouzjBuffer) {
            $jiekouzjBuffer = stringChange('arrayKey', $jiekouzjBuffer, 'jkid');
            foreach ($jiekouBuffer as $i => $iJiekouBuffer) {
                if (empty($jiekouBuffer[$i]['fl']) || $jiekouBuffer[$i]['fl'] == '0.00') {
                    $jiekouBuffer[$i]['fl'] = $jiekouzjBuffer[$iJiekouBuffer['jkid']]['fl'];
                    $jiekouBuffer[$i]['fldefault'] = 0;
                }
                $jiekouBuffer[$i]['ifjkopen'] = $jiekouzjBuffer[$iJiekouBuffer['jkid']]['ifopen'];
            }
        }

        //系统配置
        $open = array();
        foreach ($jiekouBuffer as $i => $iJiekouBuffer) {
            if(empty($jiekouBuffer[$i]['fldefault'])) $jiekouBuffer[$i]['fldefault'] = 0; //为1代表用户自定义费率
            if (!is_numeric($jiekouBuffer[$i]['ifdefaultopen']))
                $jiekouBuffer[$i]['ifdefaultopen'] = $jiekouBuffer[$i]['ifuseropen'];
            if (empty($jiekouBuffer[$i]['ifjkopen']))
                $jiekouBuffer[$i]['ifjkopen'] = 0;

            if ($jiekouBuffer[$i]['ifopen'] == 0) {
                $jiekouBuffer[$i]['ifjkopen'] = 0;
            }

            if ($jiekouBuffer[$i]['ifjkopen'] == 1 && $jiekouBuffer[$i]['ifdefaultopen'] == 1) {
                $open[] = $iJiekouBuffer;
            }

            $jiekouBuffer[$i]['ifshowopen'] = 1; //系统关闭或者用户关闭都为0
            if ($jiekouBuffer[$i]['ifdefaultopen'] == 1) {
                $jiekouBuffer[$i]['ifdefaultopen'] = '开启'; //用户开启
                $jiekouBuffer[$i]['ifdefaultopennum'] = 1;
            } else {
                $jiekouBuffer[$i]['fl'] = '';
                $jiekouBuffer[$i]['ifdefaultopen'] = '<font color="red">关闭</font>';
                $jiekouBuffer[$i]['ifdefaultopennum'] = 0;
                $jiekouBuffer[$i]['ifshowopen'] = 0;
            }
            if ($jiekouBuffer[$i]['ifjkopen'] == 1) {
                $jiekouBuffer[$i]['ifjkopen'] = '开启'; //系统开启
                $jiekouBuffer[$i]['ifjkopennum'] = 1;
            } else {
                $jiekouBuffer[$i]['fl'] = '';
                $jiekouBuffer[$i]['ifjkopen'] = '<font color="red">关闭</font>';
                $jiekouBuffer[$i]['ifjkopennum'] = 0;
                $jiekouBuffer[$i]['ifshowopen'] = 0;
            }

            $jiekouBuffer[$i] = stringChange('formatMoneyByArray', $jiekouBuffer[$i], array(
                'fl'));
            if (empty($jiekouBuffer[$i]['fl'])) {
                $jiekouBuffer[$i]['flnum'] = 0;
                $jiekouBuffer[$i]['fl'] = '-';
            } else {
                $jiekouBuffer[$i]['flnum'] = $jiekouBuffer[$i]['fl'];
                $jiekouBuffer[$i]['fl'].='%';
            }
        }
        if ($have == 1)
            return $open;
        return $jiekouBuffer;
    }

    //处理返回数据为用户可看数据
    public function changeApiResult($buffer) {
        if ($buffer[0] === 1) {
            //正确信息
            return '接口返回正确信息，测试地址：<a href="' . $buffer[1] . '" target="_blank">测试地址<a>';
        } elseif ($buffer[0] === 0) {
            return '接口返回错误信息：' . $buffer[1];
        } else {
            return '接口返回异常信息：' . $buffer;
        }
    }

    //生成二维码
    public function qrcode($request) {
        //验证key
        if ($request['k'] != md5(C('FX_QRCODE_KEY') . $request['url'])) {
            global $publicData;
            $value = $publicData['peizhi']['httpstyle'] . '://' . $_SERVER['HTTP_HOST'];                  //二维码内容
        } else {
            $value = $request['url'];                  //二维码内容
        }

        include(APP_PATH . 'Common/Tool/phpqrcode/phpqrcode.php');
        $errorCorrectionLevel = 'L';    //容错级别
        //$matrixPointSize = 5;           //生成图片大小
        //生成二维码图片
        $matrixPointSize = floor($request['w'] / 37 * 100) / 100 + 0.01;
        $QR = \QRcode::png($value, false, $errorCorrectionLevel, $matrixPointSize, 2);
    }

}
