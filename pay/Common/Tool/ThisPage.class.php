<?php
/**
 * 本类来自TP自带类
 * @date 2014年4月21日
 * @author fengxing
 */
class ThisPage {

    // 分页栏每页显示的页数
    public $rollPage = 5;
    // 页数跳转时要带的参数
    public $parameter  ;
    // 分页URL地址
    public $url     =   '';
    // 默认列表每页显示行数
    public $listRows = 20;
    // 起始行数
    public $firstRow    ;
    // 分页总页面数
    public $totalPages  ;
    // 总行数
    protected $totalRows  ;
    // 当前页数
    protected $nowPage    ;
    // 分页的栏的总页数
    protected $coolPages   ;
    // 分页显示定制
    protected $config  =    array('header'=>'条记录','prev'=>'上一页','next'=>'下一页','first'=>'第一页','last'=>'最后一页','theme'=>' <li><a class="number">%totalRow% %header% %nowPage%/%totalPage% 页</a></li>  %first%  %prePage%  %upPage%  %linkPage%  %downPage%  %nextPage% %end% %goto%');
    // 默认分页变量名
    protected $varPage;
    // [保护变量] 验证是否初始化
    private $ifInit = false;
    //分页链接类型
    private $type ;

    /**
     * 初始化函数
     * @notice 使用该类前必须先调用此函数
     * @access public
     * @param int $totalRows  总的记录数
     * @param int $listRows  每页显示记录数
     * @param array $thirdParam 第三个参数
     * ##第三个参数说明 $thirdParam
     * array | string $parameter分页跳转的参数
     * string $url自定义路径
     * int $type 主题格式 当前:0代表默认主题,1是AAT目前使用主题)
     * ##
     * @return object
     */
    public function __construct($totalRows,$listRows='',$thirdPram){
        if(empty($thirdPram['parameter'])) $thirdPram=array('parameter'=>$thirdPram);
        $thirdPram = array_merge(array('parameter'=>'','url'=>'','type'=>0),$thirdPram);
        extract($thirdPram);
        $this->totalRows    =   $totalRows;
        $this->parameter    =   $parameter;
        $this->varPage      =   C('VAR_PAGE') ? C('VAR_PAGE') : 'p' ;
        if(!empty($listRows)) {
            $this->listRows =   intval($listRows);
        }
        $this->totalPages   =   ceil($this->totalRows/$this->listRows);     //总页数
        $this->coolPages    =   ceil($this->totalPages/$this->rollPage);
        $this->nowPage      =   !empty($_REQUEST[$this->varPage])?intval($_REQUEST[$this->varPage]):1;

        if($_REQUEST['phone']){//手机端特殊情况处理
            if($this->nowPage>0){
                $this->firstRow     =   $this->listRows*($this->nowPage-1);
            }else{
                $this->firstRow     =   $this->listRows*$this->nowPage;
            }
            //手机端增加startNo参数
            $startNo = $_REQUEST['startNo']?$_REQUEST['startNo']-1:0;//【重要】这里需要－1 因为第n道题开始，也要显示第n道题，数据库里要n-1
            $this->firstRow += $startNo;
            if($this->firstRow<0){//如果
                $this->listRows = $startNo;
                $this->firstRow = 0;
            }
        }else{
            if($this->nowPage<1){
                $this->nowPage  =   1;
            }elseif(!empty($this->totalPages) && $this->nowPage>$this->totalPages) {
                $this->nowPage  =   $this->totalPages;
            }
            $this->firstRow     =   $this->listRows*($this->nowPage-1);
        }
        if(!empty($url))    $this->url  =   $url;
        //设置初始化检测变量
        $this->ifInit = true;
        //设置快速分页样式
        $this->type = intval($type);
        $this->quickTheme($type);
        //返回实例化引用
        return $this;
    }

    /**
     * 基于common当前调用规则,设置初始化检测函数
     * @return bool
     */
    private function checkInit(){
        return $this->ifInit;
    }

    /**
     * 快速设置分页主题样式(AAT)
     * @param int $type 分页主题类型
     */
    private function quickTheme($type){
        switch($type){
          case 0://默认分页
            break;
          case 1://AAT
              $this->setConfig('prev','<-');
              $this->setConfig('next','->');
              $this->setConfig('theme','%upPage% %linkPage% %downPage%');
              break;
          case 2://待定义
            break;

          default:
            //
        }
    }

    public function setConfig($name,$value) {
        //初始化检测
        if(!$this->checkInit())
            return false;

        if(isset($this->config[$name])) {
            $this->config[$name]    =   $value;
        }
    }

    /**
     * 分页显示输出
     * @access public
     * @param bool $containAutoGo 是否包含跳转框
     * @return string
     */
    public function show($containAutoGo=true) {
        //初始化检测
        if(!$this->checkInit())
            return false;

        if(0 == $this->totalRows) return '';
        $p              =   $this->varPage;
        $nowCoolPage    =   ceil($this->nowPage/$this->rollPage);

        // 分析分页参数
        if($this->url){
            $depr       =   C('URL_PATHINFO_DEPR');
            $url        =   rtrim(U('/'.$this->url,'',false),$depr).$depr.'__PAGE__';
        }else{
            if($this->parameter && is_string($this->parameter)) {
                parse_str($this->parameter,$parameter);
            }elseif(is_array($this->parameter)){
                $parameter      =   $this->parameter;
            }elseif(empty($this->parameter)){
                unset($_GET[C('VAR_URL_PARAMS')]);
                $var =  !empty($_POST)?$_POST:$_GET;
                if(empty($var)) {
                    $parameter  =   array();
                }else{
                    $parameter  =   $var;
                }
            }
            $parameter[$p]  =   '__PAGE__';
            $url            =   U('',$parameter);
        }
        //上下翻页字符串
        $upRow          =   $this->nowPage-1;
        $downRow        =   $this->nowPage+1;

        //分页html标签样式定义
        if ($upRow>0){
            $upPage     =   "<li><a href='".str_replace('__PAGE__',$upRow,$url)."'>".$this->config['prev']."</a></li>";
        }else{
            $upPage     =   '';
        }

        if ($downRow <= $this->totalPages){
            $downPage   =   "<li><a href='".str_replace('__PAGE__',$downRow,$url)."'>".$this->config['next']."</a></li>";
        }else{
            $downPage   =   '';
        }
        // << < > >>
        if($nowCoolPage == 1){
            $theFirst   =   '';
            $prePage    =   '';
        }else{
            $preRow     =   $this->nowPage-$this->rollPage;
            $prePage    =   "<li><a href='".str_replace('__PAGE__',$preRow,$url)."' >上".$this->rollPage."页</a></li>";
            $theFirst   =   "<li><a href='".str_replace('__PAGE__',1,$url)."' >".$this->config['first']."</a></li>";
        }
        if($nowCoolPage == $this->coolPages){
            $nextPage   =   '';
            $theEnd     =   '';
        }else{
            $nextRow    =   $this->nowPage+$this->rollPage;
            $theEndRow  =   $this->totalPages;
            $nextPage   =   "<li><a href='".str_replace('__PAGE__',$nextRow,$url)."' >下".$this->rollPage."页</a></li>";
            $theEnd     =   "<li><a href='".str_replace('__PAGE__',$theEndRow,$url)."' >".$this->config['last']."</a></li>";
        }
        //跳转框
        $autogo = '';
        if($containAutoGo) {
            if ($this->totalPages > 10) {
                $autogo = '<input name="go_page" id="go_page" size="3" type="text" /><input name="go" onclick="javascript:location.href=(\'' . $url . '\'.replace(\'__PAGE__\',document.getElementById(\'go_page\').value));" type="button" value="go" id="go" />';
            }
        }

        // 1 2 3 4 5
        $linkPage = "";
        for($i=1;$i<=$this->rollPage;$i++){
            $page       =   ($nowCoolPage-1)*$this->rollPage+$i;
            if($page!=$this->nowPage){
                if($page<=$this->totalPages){
                    $linkPage .= "<li><a href='".str_replace('__PAGE__',$page,$url)."'>".$page."</a></li>";
                }else{
                    break;
                }
            }else{
                if($this->totalPages != 1){
                    $linkPage .= "<li><a class='wy_page_current'>".$page."</a></li>";
                }
            }
        }
        $pageStr     =   str_replace(
            array('%header%','%nowPage%','%totalRow%','%totalPage%','%upPage%','%downPage%','%first%','%prePage%','%linkPage%','%nextPage%','%end%','%goto%'),
            array($this->config['header'],$this->nowPage,$this->totalRows,$this->totalPages,$upPage,$downPage,$theFirst,$prePage,$linkPage,$nextPage,$theEnd,$autogo),$this->config['theme']);
        return $pageStr;
    }

}
