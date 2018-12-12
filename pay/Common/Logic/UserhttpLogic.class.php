<?php

// +----------------------------------------------------------------------
// | easy pay [ pay to easy ]
// +----------------------------------------------------------------------
// | Copyright (c) 2016-2017 All rights reserved.
// +----------------------------------------------------------------------
// | Author: fengxing <QQ:51125330>
// +----------------------------------------------------------------------

namespace Common\Logic;

class UserhttpLogic extends BaseLogic {

    protected $moduleName = '接入域名';
    public $statusArray = array(
        0 => '待审核',
        1 => '审核通过',
        2 => '审核失败'
    );

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
        if ($request['http']) {
            $map['http'] = $request['http'];
            $data.=' AND http ="' . $request['http'] . '" ';
        }
        if ($request['sitename']) {
            $map['sitename'] = $request['sitename'];
            $data.=' AND sitename ="' . $request['sitename'] . '" ';
        }
        if ($request['sitephone']) {
            $map['sitephone'] = $request['sitephone'];
            $data.=' AND sitephone ="' . $request['sitephone'] . '" ';
        }
        if (is_numeric($request['status'])) {
            $map['status'] = $request['status'];
            $data.=' AND status ="' . $request['status'] . '" ';
        }
        $perpage = C('FX_PERPAGE'); //每页行数
        $userHttp = SM('UserHttp');
        $count = $userHttp->selectCount(
                $data, 'id'
        ); // 查询满足要求的总记录数
        // 进行分页数据查询 注意limit方法的参数要使用Page类的属性
        $page = page($count, $request['p'], $perpage) . ',' . $perpage;

        $list = $userHttp->pageData(
                '*', $data, 'id DESC', $page
        );

        foreach ($list as $i => $iList) {
            $list[$i]['addtime'] = stringChange('formatDateTime', $iList['addtime']);
            $list[$i]['checktime'] = stringChange('formatDateTime', $iList['checktime']);
            $list[$i]['statusname'] = $this->statusArray[$iList['status']];
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
        $userHttp = SM('UserHttp');
        $row = $userHttp->findData('*', 'id=' . $request['id']);
        $params = array(
            'edit' => $row,
            'act' => 'edit',
            'pageName' => '修改' . $this->moduleName
        );
        return [1,
            $params,
            'Userhttp/add'];
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
        $data = array();
        $data['userid'] = $request['userid'];
        $data['sitename'] = $request['sitename'];
        $data['sitestyle'] = $request['sitestyle'];
        $data['beian'] = $request['beian'];
        $data['siteadmin'] = $request['siteadmin'];
        $data['sitephone'] = $request['sitephone'];
        $data['siteqq'] = $request['siteqq'];
        $data['status'] = $request['status'];

        $buffer = SM('User')->findData('*', 'userid="' . $data['userid'] . '"');
        if (!$buffer) {
            return [0,
                '用户名id不存在'];
        }

        $userHttp = SM('UserHttp');
        if ($act == 'add') {
            $data['http'] = $request['http'];
            $data['addtime'] = time();
            if ($userHttp->insertData($data) === false) {
                return [0,
                    '添加失败'];
            } else {
                //写入日志
                $this->adminLog($this->moduleName, '添加接入域名【' . $data['http'] . '】');
                return [1,
                    '添加成功！',
                    __URL__];
            }
        } elseif ($act == 'edit') {
            $data['id'] = $zjid;
            $buffer = $userHttp->findData(
                    'userid,checktime', 'id="' . $data['id'] . '"');
            if (!$buffer) {
                return [0,
                    '接入域名不存在'];
            }
            if (empty($buffer['checktime']))
                $data['checktime'] = time();

            if ($userHttp->updateData(
                            $data, 'id=' . $data['id']) === false) {
                return [0,
                    '修改失败'];
            } else {
                //写入日志
                $this->adminLog($this->moduleName, '修改接入域名ID为【' . $data['id'] . '】的数据');
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
        $httpID = $request['id']; //获取数据标识
        $idArray = explode(',', $httpID);

        if (!$httpID) {
            return [0,
                '数据标识不能为空',
                __URL__];
        }
        if (SM('UserHttp')->deleteData(
                        'id in (' . implode(',', $idArray) . ')') === false) {
            return [0,
                '删除失败'];
        } else {
            //写入日志
            $this->adminLog($this->moduleName, '删除接入域名ID为【' . implode(',', $idArray) . '】的数据');
            return [1,
                '删除成功',
                __URL__];
        }
    }

    /**
     * 验证商户来源域名
     * @params int $userid 商户id
     * @params int $useropenhttp 是否免审 1免审
     * @params string|array $http 当前域名或域名数组
     * @params string|array $msg 与当前域名数据类型一致 预设返回信息
     */
    public function checkHttp($userid,$useropenhttp=0,$http='',$msg='') {
        if($useropenhttp==1) return [1,true];
        global $publicData;

        //演示账户跳过
        if($userid==$publicData['peizhi']['bzjuserid']) return [1,true];

        //系统开启
        if($publicData['peizhi']['ifopenuserhttp']==1){
            if(!is_array($http)) $http=array($http);
            if(!is_array($msg)) $msg=array($msg);

            $buffer=SM('UserHttp')->selectData('*','userid="'.$userid.'" and status=1');
            $buffer=stringChange('arrayKey',$buffer,'http');

            foreach($http as $i=>$iHttp){
                if(empty($iHttp)) $iHttp=$_SERVER['HTTP_REFERER'];
                if(empty($iHttp)) continue;

                $httpArr=parse_url($iHttp);
                $host=$httpArr['host'];
                if($httpArr['port']) $host=$host.':'.$httpArr['port'];
                if($buffer[$host]) continue;
                else return [0,$msg[$i]];
            }
        }
        return [1,true];
    }
}
