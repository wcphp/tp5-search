<?php
//+----------------------------------------------------------------------
// | 场景搜索查询Trait
//+----------------------------------------------------------------------
// | Author: kk <weika@wcphp.com>
//+----------------------------------------------------------------------

namespace WcSearch;
use WcSearch\util\SearchUtil;

trait SearchTrait
{

    //增加查询字段
    protected $addField = [];
    //增加返回增加字段
    protected $addAppendField = [];
    //增加返回隐藏字段
    protected $addHiddenField = [];

    protected  static $searchOption = [];

    //搜索场景
    protected $searchScene = '';

    /**
     * 搜索查询接收参数方法
     * @param array $searchParam 控制器接收的查询参数
     * @param string $searchScene 搜索场景
     * Author: kk <weika@wcphp.com>
     */
    final public function searchQuery($searchCondition,$searchScene=''){
        empty($searchScene) || $this->searchScene = $searchScene;
        self::$searchOption = $this->getOptions();
        $searchData = $this-> _searchQuery(SearchUtil::tidy($this->getSceneSearchRule(),$searchCondition));
        return $searchData;
    }

    /**
     * 搜索查询实现方法
     * @param array $searchParam 控制器接收的查询参数
     * Author: kk <weika@wcphp.com>
     */
    protected function _searchQuery($search)
    {
        //查询条件
        $resList = $this->where($search->where)
            //查询返回字段
            ->field($this->getSearchField())
            ->whereOr($search->whrerOr)
            ->order($search->order)
            ->page($search->page)
            //->fetchSql(true)
            ->paginate();
        $searchData = [];
        if(!$resList->isEmpty()){
            $searchData = $resList->append($this->getSceneSearchAppendField())->hidden($this->getSceneSearchHiddenField())->toArray();
        }
        return $search = ['list'=>$searchData['data'] ?? [],'total'=>$searchData['total'] ?? 0];
    }

    /**
     * 设置搜索场景
     * @param  string  $scene //搜索场景
     * @return $this
     * @throws \Exception
     */
    public  function  setScene($scene = ''){
        $this->searchScene = $scene;
        return $this;
    }

    /**
     * 增加查询字段
     * @param  mixed $fields
     * Author: kk <weika@wcphp.com>
     */
    public function addField($fields=[],$tableStr=''){
        if(!empty($fields)){
            if(!empty($tableStr)) {//加表前缀
                $filed = [];
                foreach($fields as $key=>$item){
                    if(is_int($key)){
                        $filed[$key] = $tableStr.'.'.$item;
                    }else{
                        $key = $tableStr.'.'.$key;
                        $filed[$key] = $item;
                    }
                }
                $fields = $filed;
            }
            $this->addField = array_merge( $this->addField,$fields);
        }
        return $this;
    }


    /**
     * 增加返回增加字段
     * @param  mixed $fields
     * Author: kk <weika@wcphp.com>
     */
    public function addAppendField($fields=[]){
        if(!empty($fields) && is_array($fields)){

            $this->addField = array_merge( $this->addAppendField,$fields);
        }
        return $this;
    }

    /**
     * 增加返回隐藏字段
     * @param  mixed $fields
     * Author: kk <weika@wcphp.com>
     */
    public function addHiddenField($fields=[]){
        if(!empty($fields)&& is_array($fields)){
            $this->addField = array_merge( $this->addHiddenField,$fields);
        }
        return $this;
    }


    /**
     * 获取搜索返回增加字段
     * Author: kk <weika@wcphp.com>
     */
    protected function getSceneSearchAppendField(){
        $sceneAppendField = $this->sceneAppendFieldConf();
        $appendField = [];
        if(!empty($sceneAppendField) && !empty($this->searchScene)){
            $appendField = $sceneAppendField[$this->searchScene] ?? array_shift($sceneAppendField);
        }

        if(!empty($this->addAppendField)){
            $appendField = array_merge($appendField,$this->addAppendField);
        }

        return $appendField;
    }

    /**
     * 获取搜索返回隐藏字段
     * Author: kk <weika@wcphp.com>
     */
    protected function getSceneSearchHiddenField(){
        $sceneHiddenField = $this->sceneHiddenFieldConf();
        $hiddenField = [];
        if(!empty($sceneHiddenField) && !empty($this->searchScene)){
            $hiddenField = $sceneAppendField[$this->searchScene] ?? array_shift($sceneHiddenField);
        }

        if(!empty($this->addAppendField)){
            $hiddenField = array_merge($hiddenField,$this->addHiddenField);
        }
        return $hiddenField;
    }

    /**
     * 根据搜索场景获取搜索查询规则，搜索场景为空，默认返回第一个
     * Author: kk <weika@wcphp.com>
     */
    protected function getSceneSearchRule(){
        if (!empty($searchRuleConfig = $this->sceneSearchRuleConf())){
            if(empty($this->searchScene)){
                return array_shift($searchRuleConfig);
            }elseif(isset($searchRuleConfig[$this->searchScene])){
                return $searchRuleConfig[$this->searchScene];
            }else{
                return [];
            }
        }
    }

    /**
     * 获取搜索查询字段
     * Author: kk <weika@wcphp.com>
     */
    public function getSearchField($searchScene=''){
        $sceneFieldConf = $this->sceneFieldConf();
        $sceneField = [];
        $searchScene = empty($searchScene) ? $this->searchScene : $searchScene;
        if(!empty($sceneFieldConf) && !empty($searchScene)){
            $sceneField = $sceneFieldConf[$searchScene] ?? array_shift($sceneFieldConf);
        }

        //查询是否有连表
        if(!empty($sceneField)){

            if(!empty(self::$searchOption['join'])){
                $tableStr = empty(self::$searchOption['alias']) ? $this->getTable() : array_shift(self::$searchOption['alias']);
                $filed = [];
                foreach($sceneField as $key=>$item){
                    if(is_int($key)){
                        $filed[$key] = $tableStr.'.'.$item;
                    }else{
                        $key = $tableStr.'.'.$key;
                        $filed[$key] = $item;
                    }
                }
                $sceneField = $filed;
            }
        }

        if(!empty($this->addField)){
            $sceneField = array_merge($sceneField,$this->addField);
        }
        return $sceneField;
    }







    /**
     * 场景字段配置
     * Author: kk <weika@wcphp.com>
     */
    protected function sceneFieldConf(){
        /* 格式
         return [
            'userList'=>['id',
                    'uid',
                    'title',
                    'collect_num'=>'collectNum',
                    'like_num'=>'likeNum',
                    'create_time'=>'createTime',
            ]];
         * */
        return [];
    }

    /**
     * 场景返回增加字段配置
     * Author: kk <weika@wcphp.com>
     */
    protected function sceneAppendFieldConf(){
        /* 格式
         return [
            'userList'=>['id','name']];
         * */
        return [];
    }

    /**
     * 场景返回隐藏字段配置
     * Author: kk <weika@wcphp.com>
     */
    protected function sceneHiddenFieldConf(){
        /* 格式
         return [
            'userList'=>['id','name']];
         * */
        return [];
    }

    /**
     * 场景搜索查询规则配置
     * Author: kk <weika@wcphp.com>
     */
    protected function sceneSearchRuleConf(){
        /*return [
            'scene'=>[//搜索场景
                'where'=>[],//筛选条件
                'order'=>['b.create_time'=>['','DESC']],//排序
                'page'=>['pageNum','pageSize']//分页
            ]

        ];*/
        return [];
    }

}