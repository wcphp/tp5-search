<?php
// +----------------------------------------------------------------------
// | 搜索条件处理
// +----------------------------------------------------------------------
// | Author: kk <weika@wcphp.com>
// +----------------------------------------------------------------------

namespace WcSearch\util;

use think\Db;

class SearchUtil
{
    public $where = [];
    public $whereOr = [];
    public $order = [];
    public $page = '';
    protected static  $obj ='';
    /**
     * 处理搜索条件
     * @param array  $search 搜索条件
     */
    public static  function tidy($searchRule,$data=[])
    {
        self::$obj = new self;
        if(!empty($searchRule) && !empty($data)){
            foreach($searchRule as $key=>$item){
                $key = strtolower($key);
                switch($key){
                    case 'where':
                        self::$obj->tidyWhere($item,$data);
                        break;
                    case 'order':
                        self::$obj->tidyOrder($item,$data);
                        break;
                    case 'page':
                        self::$obj->tidyPage($item,$data);
                        break;
                    default:
                        break;
                }

            }
        }
        return self::$obj;
    }
    /**
     * 整理查询条件
     */
    protected  function tidyWhere($where,$data){
        foreach($where as $k=>$v){
            //逻辑与
            if(is_string($v[0])){
                //有默认值
                if(isset($v[2])){
                    $res = $this->tidySymbol($v[0],$v[1],$data,$v[2]);
                }else{
                    $res = $this->tidySymbol($v[0],$v[1],$data);
                }

                if($res !== false){
                    $this->where[] = array_merge([$k],$res);
                }

                //逻辑或
            }else{
                $res = $this->tidySymbol($v[0][0],$v[1],$data);
                if($res !== false){
                    $this->whereOr[] = array_merge([$k],$res);
                }

            }
        }
    }

    /**
     * 根据不同符号处理查询条件
     * $symbol 符号
     * $param 键值
     * $data 搜索数据
     */
    protected function tidySymbol($symbol,$param,$data,$default=null){
        $res = false;
        $symbol = strtolower($symbol);
        if($symbol == 'time') {
            //区间时间
            if (is_array($param)) {
                $startDate = isset($data[$param[0]]) ? $data[$param[0]] : ""; //update james rh 20171031
                $endDate = isset($data[$param[1]]) ? $data[$param[1]] : "";   //update james rh 20171031
                if (!empty($startDate) && !empty($endDate)) {
                    $startDate = date('Y-m-d 00:00:00', strtotime($startDate));
                    $endDate = date('Y-m-d 23:59:59', strtotime($endDate));
                    $res = $startDate == $endDate ? ['eq', $startDate] : ['between time', [$startDate, $endDate]];
                } elseif (!empty($startDate)) {
                    $startDate = date('Y-m-d 00:00:00', strtotime($startDate));
                    $res = ['egt', $startDate];
                } elseif (!empty($endDate)) {
                    $endDate = date('Y-m-d 23:59:59', strtotime($endDate));
                    $res = ['elt', $endDate];
                }
                //等于这个时间
            } else {
                if (!empty($data[$param])) {
                    return date('Y-m-d H:i:s', strtotime($data['$param']));
                }
            }
           // 指定某一天
        }elseif($symbol == 'day'){
            if(!is_array($param) && ($dateStr =  !empty($data[$param]) ? $data[$param] : (empty($default) ? '' : $default))){
                $startDate = date('Y-m-d 00:00:00', strtotime($dateStr));
                $endDate = date('Y-m-d 23:59:59', strtotime($dateStr));
                $res = ['between time', [$startDate, $endDate]];
            }

        }elseif($symbol == 'like'){
            if(isset($data[$param]) && $data[$param] !==''){
                $value =(string)$data[$param];
                $res = ['like','%'.trim($value).'%'];
            }
        }elseif(in_array($symbol,['eq','=','neq','<>','gt','>','egt','>=','lt','<','elt','<=','in','exp'])){
            if(is_array($param)){//数据库值与展示值映射   例如：展示1  数据库中存的是 10  或者展示是男数据库中存的是 1
                if(!empty($data[$param[0]]) && is_array($param[1])){
                    $key = $data[$param[0]];
                    foreach ($param[1] as $k=>$v){
                        if($key == $k){
                            if($symbol == 'exp'){
                                $v = Db::raw($v);
                            }
                            $res = [$symbol,$v];
                            break;
                        }
                    }
                }
            }else{
                $v = !empty($data[$param]) || (isset($data[$param]) && $data[$param] === 0) ?  $data[$param] : (!is_null($default) ?  $default : null);
                if(!is_null($v)){
                    if($symbol == 'exp'){
                        $v = Db::raw($v);
                    }
                    $res = [$symbol,$v];
                }

            }
        }
        return $res;
    }
    /**
     * 整理排序条件
     */
    protected  function tidyOrder($order,$data){
            //  'order'=>['字段'=>['提交字段','asc']]], asc   desc

        $res = [];
        foreach($order as $key=>$item){
            if(is_array($item) && (empty($item[0]) || empty($data[$item[0]]))&& isset($item[1]) && in_array(strtolower($item[1]),['asc','desc'])) {
                $res = array_merge($res, [$key => strtolower($item[1])]);
            }elseif(is_array($item) && isset($item[0]) && isset($data[$item[0]]) && in_array(strtolower($data[$item[0]]),['asc','desc'])){
                $res = array_merge($res, [$key => strtolower($data[$item[0]])]);
            }elseif(is_string($item) && isset($data[$item]) && in_array(strtolower($data[$item]),['asc','desc'])){
                $res = array_merge($res,[$key=>strtolower($data[$item])]);
            }
        }
        $this->order = $res;
    }
    /**
     * 整理每页
     */
    protected  function tidyPage($page,$data){
        if(isset($page[0]) && is_numeric($page[0])){
            $res['pageNum'] = $page[0];
        }elseif(isset($page[0]) && isset($data[$page[0]])){
            $res['pageNum'] = (int)$data[$page[0]];
        }else{
            $res['pageNum'] =1;
        }

        if(isset($page[1]) && is_numeric($page[1])){
            $res['pageSize'] =$page[1];
        }elseif(isset($page[1]) && isset($data[$page[1]])){
            $res['pageSize'] =  (int)$data[$page[1]];
        }else{
            $res['pageSize'] = 20;
        }
        $this->page = $res;
    }

}