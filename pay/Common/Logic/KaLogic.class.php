<?php
// +----------------------------------------------------------------------
// | easy pay [ pay to easy ]
// +----------------------------------------------------------------------
// | Copyright (c) 2016-2017 All rights reserved.
// +----------------------------------------------------------------------
// | Author: fengxing <QQ:51125330>
// +----------------------------------------------------------------------
namespace Common\Logic;
class KaLogic extends BaseLogic {
    protected $moduleName='银行卡';

    /**
     * 列表
     */
    public function index($request){
        $map=array();
        $data=' 1=1 ';
            //高级查询
            if($request['userid']){
                $map['userid']=$request['userid'];
                $data.=' AND userid ="'.$request['userid'].'" ';
            }
            if($request['ka']){
                $map['ka']=$request['ka'];
                $data.=' AND ka ="'.$request['ka'].'" ';
            }
            if($request['username']){
                $map['username']=$request['username'];
                $data.=' AND username ="'.$request['username'].'" ';
            }
            if(is_numeric($request['ifcheck'])){
                $map['ifcheck']=$request['ifcheck'];
                $data.=' AND ifcheck ="'.$request['ifcheck'].'" ';
            }
        $perpage=C('FX_PERPAGE'); //每页行数
        $zijian=SM('Ka');
        $count = $zijian->selectCount(
            $data,
            'id'
        ); // 查询满足要求的总记录数
        // 进行分页数据查询 注意limit方法的参数要使用Page类的属性
        $page = page($count, $request['p'], $perpage) . ',' . $perpage;

        $list = $zijian->pageData(
            '*',
            $data,
            'id DESC',
            $page
           );

        foreach($list as $i=>$iList){
            $list[$i]['addtime'] = stringChange('formatDateTime', $iList['addtime']);
            $list[$i]['checktime'] = stringChange('formatDateTime', $iList['checktime']);
            $list[$i]['ifcheckname']=$iList['ifcheck']==1?'通过':'未通过';
        }

        $pageList=$this->pageList($count,$perpage,$map);

        $params = array(
            'list'=>$list,
            'page'=>$pageList,
            'pageName' => $this->moduleName.'管理'
        );
        return [1, $params];
    }
    /**
     * 添加
     */
    public function add($request){
        $params = array(
            'act'=>'add',
            'pageName' => '添加'.$this->moduleName
        );
        return [1, $params];
    }
    /**
     * 修改
     */
    public function edit($request){
        if(!$request['id']){
            return [0, '参数错误！'];
        }
        $zijianModel = SM('Ka');
        $row = $zijianModel->findData('*', 'id=' . $request['id']);
        $params = array(
            'edit' => $row,
            'act'=>'edit',
            'pageName' => '修改'.$this->moduleName
        );
        return [1, $params,'Ka/add'];
    }

    /**
     * 保存
     */
    public function save($request){
        $zjid = $request['id']; //获取数据标识
        $act = $request['act']; //获取模板标识
        //判断数据标识
        if (empty ($zjid) && $act == 'edit') {
            return [0,'数据标识不能为空！'];
        }
        if (empty ($act)) {
            return [0,'模板标识不能为空！'];
        }
        $zijian = SM('Ka');
        $data = array();
        $data['userid'] = $request['userid'];
        $data['username'] = $request['username'];
        $data['ka'] = $request['ka'];
        $data['address'] = $request['address'];
        $data['zhihang'] = $request['zhihang'];
        $data['sheng'] = $request['sheng'];
        $data['shi'] = $request['shi'];
        $data['lhh'] = $request['lhh'];
        $data['ifcheck'] = $request['ifcheck'];

        $buffer=SM('User')->findData('*','userid="'.$data['userid'].'"');
        if(!$buffer){
            return [0,'用户名id不存在'];
        }

        if ($act == 'add') {
            $data['addtime'] = time();
            if($zijian->insertData($data)===false){
                return [0,'添加失败'];
            }else{
                //写入日志
                $this->adminLog($this->moduleName,'添加银行卡【'.$data['userid'].'】');
                return [1,'添加成功！',__URL__];
            }
        } elseif ($act == 'edit') {
            $data['id'] = $zjid;
            $buffer = $zijian->findData(
                'userid,checktime',
                'id="'.$data['id'].'"');
            if(!$buffer){
                return [0,'银行卡不存在'];
            }
            if(empty($buffer['checktime']) && $data['ifcheck']==1) $data['checktime']=time();

            if($zijian->updateData(
                $data,
                'id='.$data['id'])===false){
                return [0,'修改失败'];
            }else{
                //写入日志
                $this->adminLog($this->moduleName,'修改银行卡ID为【'.$data['id'].'】的数据');
                return [1,'修改成功！', __URL__];
            }
        }
    }

    /**
     * 删除
     */
    public function delete($request){
        $zijianID = $request['id']; //获取数据标识
        $idArray=explode(',',$zijianID);

        if (!$zijianID) {
            return [0,'数据标识不能为空',__URL__];
        }
        if (SM('Ka')->deleteData(
                'id in ('.implode(',',$idArray).')') === false) {
            return [0,'删除失败'];
        } else {
            //写入日志
            $this->adminLog($this->moduleName,'删除银行卡ID为【'.implode(',',$idArray).'】的数据');
            return [1,'删除成功',__URL__];
        }
    }
}