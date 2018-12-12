<?php

// +----------------------------------------------------------------------
// | easy pay [ pay to easy ]
// +----------------------------------------------------------------------
// | Copyright (c) 2016-2017 All rights reserved.
// +----------------------------------------------------------------------
// | Author: fengxing <QQ:51125330>
// +----------------------------------------------------------------------

namespace Common\Logic;

class HttpLogic extends BaseLogic {

    protected $moduleName = '域名';
    public $zt = array(
        0 => '否',
        1 => '是'
    );

    /**
     * 列表
     */
    public function index($request) {
        $map = array();
        $data = ' 1=1 ';

        $http = SM('Http');

        $list = $http->selectData(
                '*', $data, 'id desc'
        );

        foreach ($list as $i => $iList) {
            $list[$i]['addtime'] = stringChange('formatDateTime', $iList['addtime']);
            $list[$i]['ifdefaultname'] = $this->zt[$list[$i]['ifdefault']];
            $list[$i]['ifdefaulthiddenname'] = $this->zt[$list[$i]['ifdefaulthidden']];
            $list[$i]['lockedname'] = $list[$i]['locked']=='1'?'锁定':'正常';
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
        $http = SM('Http');
        $row = $http->findData('*', 'id=' . $request['id']);
        $params = array(
            'edit' => $row,
            'act' => 'edit',
            'pageName' => '修改' . $this->moduleName
        );
        return [1,
            $params,
            'Http/add'];
    }

    /**
     * 保存
     */
    public function save($request) {
        $httpID = $request['id']; //获取数据标识
        $act = $request['act']; //获取模板标识
        //判断数据标识
        if (empty($httpID) && $act == 'edit') {
            return [0,
                '数据标识不能为空！'];
        }
        if (empty($act)) {
            return [0,
                '模板标识不能为空！'];
        }
        $http = SM('Http');
        $data = array();
        $data['httpname'] = $request['httpname'];
        $data['http'] = $request['http'];
        $data['ifdefault'] = $request['ifdefault'];
        $data['hiddenhttp'] = $request['hiddenhttp'];
        $data['ifdefaulthidden'] = $request['ifdefaulthidden'];
        $data['locked'] = $request['locked'];

        if(empty($data['httpname'])){
            return [0,'域名名称不能为空。'];
        }
        if(empty($data['http'])){
            return [0,'域名不能为空。'];
        }
        if(!strstr($data['http'],'http://') && !strstr($data['http'],'https://')){
            return [0,'域名格式不正确。'];
        }

        if ($act == 'add') {
            $data['addtime'] = time();
            //检查名称重复
            $buffer = $http->selectData(
                    'id', 'http="' . $data['http'] . '"');
            if ($buffer) {
                return [0,
                    '网址重复请更换'];
            }
            if (($httpID=$http->insertData($data)) === false) {
                return [0,
                    '添加失败'];
            } else {
                $this->checkDefault('ifdefault',$data['ifdefault'],$httpID);
                $this->checkDefault('ifdefaulthidden',$data['ifdefaulthidden'],$httpID);
                //写入日志
                $this->adminLog($this->moduleName, '添加域名【' . $data['http'] . '】');
                return [1,
                    '添加成功！',
                    __URL__];
            }
        } elseif ($act == 'edit') {
            $data['id'] = $httpID;
            $buffer = $http->selectData(
                    'id,httpname', 'id="' . $data['id'] . '"');
            if (!$buffer) {
                return [0,
                    '当前修改域名不存在'];
            }
            $buffer = $http->selectData(
                    'id,httpname,http', 'http="' . $data['http'] . '" && id!="' . $data['id'] . '"');
            if ($buffer) {
                return [0,
                    '域名重复'];
            }
            if ($http->updateData(
                            $data, 'id=' . $data['id']) === false) {
                return [0,
                    '修改失败'];
            } else {
                $this->checkDefault('ifdefault',$data['ifdefault'],$httpID);
                $this->checkDefault('ifdefaulthidden',$data['ifdefaulthidden'],$httpID);
                //写入日志
                $this->adminLog($this->moduleName, '修改域名id为【' . $httpID . '】的数据');
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
        if (SM('Http')->deleteData(
                        'id in (' . implode(',', $idArray) . ')') === false) {
            return [0,
                '删除失败'];
        } else {
            //写入日志
            $this->adminLog($this->moduleName, '删除域名ID为【' . implode(',', $idArray) . '】的数据');
            return [1,
                '删除成功',
                __URL__];
        }
    }

    /**
     * 设置默认
     * @params string $field 字段名称
     * @params int $status 状态0或1
     * @params int $id 当前id
     * @return bool
     */
    public function checkDefault($field,$status,$id) {
        if($status==0){
            return true;
        }

        //获取当前字段对应的默认值
        $buffer=SM('Http')->findData('*',$field.'=1 and id!='.$id);

        if($buffer && $status==1){
            //取消所有默认
            return SM('Http')->updateData([$field=>0],'id!='.$id);
        }

        return true;
    }

    /**
     * 获取用户的可用域名
     */
    public function getApiHttp($userid) {
        $buffer=SM('Http')->selectData('*','locked=0');
        $httpBuffer=  stringChange('arrayKey',$buffer,'id');

        $userBuffer=SM('User')->findData('*','userid='.$userid);
        if($userBuffer['httpid']>0){
            $http=$httpBuffer[$userBuffer['httpid']]['http'];
            if($http) return [1,$http];
        }

        //默认域名
        $defaultHttp='';
        $buffer=SM('Http')->findData('*','ifdefault=1 and locked=0');
        if($buffer) $defaultHttp=$buffer['http'];

        global $publicData;
        if(empty($defaultHttp)){
            $defaultHttp=$publicData['peizhi']['httpstyle'].'://'.$_SERVER['HTTP_HOST'];
        }

        //获取接口绑定域名
        $jiekouBuffer=SM('Jiekou')->selectData('*','ifopen=1');
        if(empty($jiekouBuffer)) return [1,$defaultHttp];

        //获取接口对应账户域名
//        $jiekoupeizhiBuffer=SM('Jiekoupeizhi')->selectData('pzid,httpid','1=1');
//        $jiekoupeizhiBuffer=  stringChange('arrayKey',$jiekoupeizhiBuffer,'pzid');
//        $jiekouzjBuffer=SM('Jiekouzj')->selectData('zjid,pzid,jkid','ifopen=1 and ifchoose=1');
//        $jiekouzjBuffer=  stringChange('arrayKey',$jiekouzjBuffer,'jkid');

        foreach($jiekouBuffer as $i=>$iJiekouBuffer){
            //轮询的http 配置域名
            if($iJiekouBuffer['ifround']){
                if($iJiekouBuffer['roundhttpid']){
                    $jiekouBuffer[$i]['thishttp']=$httpBuffer[$iJiekouBuffer['roundhttpid']]['http'];
                    if($jiekouBuffer[$i]['thishttp']) continue;
                }

                //轮询的http 没有配置域名
                $jiekouBuffer[$i]['thishttp']=$defaultHttp;
                continue;
            }

            //如果开启故障切换则使用默认域名
            if($publicData['peizhi']['changeapitime']){
                $jiekouBuffer[$i]['thishttp']=$defaultHttp;
                continue;
            }

            //配置接口http
            if($iJiekouBuffer['httpid']){
                $jiekouBuffer[$i]['thishttp']=$httpBuffer[$iJiekouBuffer['httpid']]['http'];
                if($jiekouBuffer[$i]['thishttp']) continue;
            }

            //获取接口对应账户的
//            $jkid=$iJiekouBuffer['jkid'];
//            $pzid=$jiekouzjBuffer[$jkid]['pzid'];
//            if($jiekoupeizhiBuffer[$pzid]['httpid']){
//                $jiekouBuffer[$i]['thishttp']=$httpBuffer[$jiekoupeizhiBuffer[$pzid]['httpid']]['http'];
//                if($jiekouBuffer[$i]['thishttp']) continue;
//            }

            $jiekouBuffer[$i]['thishttp']=$defaultHttp;
        }

        //对个接口的域名进行汇总，如果仅有一个域名则输入域名，如果有多个输出数组
        $httpstr='';
        foreach($jiekouBuffer as $i=>$iJiekouBuffer){
            if($httpstr=='') $httpstr=$iJiekouBuffer['thishttp'];
            elseif($httpstr!=$iJiekouBuffer['thishttp']){
                return [1,$jiekouBuffer];
            }
        }

        return [1,$jiekouBuffer[0]['thishttp']];
    }

    /**
     * 获取用户的可用查询等其他域名
     */
    public function getApiHttpOther($userid) {
        $buffer=SM('Http')->selectData('*','locked=0');
        $httpBuffer=  stringChange('arrayKey',$buffer,'id');

        $userBuffer=SM('User')->findData('*','userid='.$userid);
        if($userBuffer['httpid']>0){
            $http=$httpBuffer[$userBuffer['httpid']]['http'];
            if($http) return [1,$http];
        }

        //默认域名
        $defaultHttp='';
        $buffer=SM('Http')->findData('*','ifdefault=1 and locked=0');
        if($buffer) $defaultHttp=$buffer['http'];

        if(empty($defaultHttp)){
            global $publicData;
            $defaultHttp=$publicData['peizhi']['httpstyle'].'://'.$_SERVER['HTTP_HOST'];
        }
        return [1,$defaultHttp];
    }
}
