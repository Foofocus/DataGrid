<?php

namespace Foofocus\DataGrid;

use Nette;

class DataGrid extends Nette\Application\UI\Control
{
        
    const DEFAULT_OFFSET = 0;
    const DEFAULT_SORT_DIRECTION = "DESC";
    const SORT_DIRECTIONS = ["DESC","ASC"];
    const DEFAULT_SORT_COLUMN = 0;
    const DEFAULT_PAGE_LENGTH = 20;
    const DEFAULT_PAGE_LENGTH_SELECT = [10,20,50];
    const DEFAULT_TEMPLATE_PATH = __DIR__ . "/templates/@default.latte";
    
    private $name = "dataTablesTable";
    
    private $draw = 1;
    
    private $source;
    
    private $that;
    
    protected $columns = [];
    
    private $preparedColumns = [];
    
    private $anonymous_columns = true;
    
    private $templatePath = self::DEFAULT_TEMPLATE_PATH;

    public $date_format = "Y-m-d H:i:s";
    
    public $column_search = false;
    
    public $searchString = null;
    public $showFooter = false;
    public $searchColumns = [];
    public $async = true;
    public $paging = true;
    public $searching = true;
    public $ordering = true;
    public $offset = self::DEFAULT_OFFSET;
    public $pageLength = self::DEFAULT_PAGE_LENGTH;
    public $pageLengthSelect = self::DEFAULT_PAGE_LENGTH_SELECT;
    public $order = ["column" => self::DEFAULT_SORT_COLUMN, "direction" => self::DEFAULT_SORT_DIRECTION];
    public $responsive = true;
    
    private $ajaxParams = array();
    
    function __construct($that, $name)
    {
        
        $this->name = $name;
        $this->that = $that;
        
    }
    
    public function setDataSource($source){
        
        $this->source = $source;
        return $this;
        
    }
    
    /**
     * set responsivity of grid
     * @param boolean $value
     * @return $this
     */
    public function setResponsivity($value){
        
        $this->responsive = $value;
        return $this;
        
    }
    
    private function getLastInsertedColumn(){
        
        return end($this->columns);
        
    }
    
    public function setRenderer($data){
        
        $column = $this->getLastInsertedColumn();
        $column->renderer = $data;

        return $this;
        
    }
    
    public function setReplace($data = array()){
        
        $column = $this->getLastInsertedColumn();
        $column->replace = $data;

        return $this;
        
    }
    
    public function setSortable(){
        
        $column = $this->getLastInsertedColumn();
        $column->sortable = true;
        
        return $this;                
        
    }
    
    /**
     * Add element by ID 
     * @param array $filter key - name of parametr, value - ID of element
     * @return $this
     */
    public function addAjaxParams($filter = array()){
        
        foreach($filter AS $key => $val){
            
            $this->ajaxParams[$key] = $val;
            
        }
        
        return $this;                
        
    }    
    
    public function addClass($name){
        
        $column = $this->getLastInsertedColumn();
        if(empty($column->classes)) $column->classes = [];
        $column->classes[] = $name;
        
        return $this;                
        
    }
    
    public function addAttribute($name, $value){
        
        $column = $this->getLastInsertedColumn();
        if(empty($column->attributes)) $column->attributes = [];
        $column->attributes[$name] = $value;
        
        return $this;                
        
    }
    
    public function addAjax($value = true){
        
        $column = $this->getLastInsertedColumn();
        $column->ajax = $value;
        
        return $this;                
        
    }
    
    public function addConfirm($value){
        
        $column = $this->getLastInsertedColumn();
        $column->confirm = $value;
        
        return $this;                
        
    }
    
    public function setColumnSearch($value = true){
        
        $this->column_search = $value;
        
        return $this;
        
    }
    
    public function setPageLength($value = 20){
        
        $this->pageLength = $value;
        
        return $this;
        
    }
    
    public function setPageLengthSelect($value = array()){
        
        $this->pageLengthSelect = $value;
        
        return $this;
        
    }
    
    public function setOrderColumn($column){
        
        $this->order["column"] = $column;
        
        return $this;
        
    }
    
    public function setOrderDirection($value){
        
        $this->order["direction"] = in_array(strtoupper($value),self::SORT_DIRECTIONS) ? strtoupper($value) : self::DEFAULT_SORT_DIRECTION;
        
        return $this;
        
    }
    
    public function setOffset($value){
        
        if(is_numeric($value)) $this->offset = intval($value);
        
        return $this;
        
    }
    
    public function setSearchString($value){
        
        $this->searchString = $value;
        
    }
    
    public function setSearchColumn($column, $value){
        
        foreach($this->getColumns() AS $col){
            
            if($col->index === $column){
                
                if($col->type === "link" AND $col->value !== null) $column = $col->value;
                
            }
            
        }
        
        $this->searchColumns[$column] = $value;
        
        return $column;
        
    }
    
    public function setDraw($value){
        
        $this->draw = intval($value);
        
    }
    
    public function showFooter($value = true){
        
        $this->showFooter = $value;
        
    }
    
    public function setColumnText($index, $name, $sortable = false, $searchable = false){
        
        $column = new \stdClass();
        $column->index = $index;
        $column->name = $name;
        $column->sortable = $sortable;
        $column->search = $searchable;
        $column->type = "text";
        
        $this->columns[] = $column;
        
        return $this;
        
    }
    
    public function setColumnNumber($index, $name, $sortable = false, $searchable = false){
        
        $column = new \stdClass();
        $column->index = $index;
        $column->name = $name;
        $column->sortable = $sortable;
        $column->search = $searchable;
        $column->type = "integer";
        
        $this->columns[] = $column;
        
        return $this;
        
    }
    
    /**
     * 
     * @param string $index
     * @param string $name
     * @param boolean $sortable
     * @param boolean $searchable
     * @param string $format format like "Y-m-d H:i:s"
     * @return $this
     */
    public function setColumnDateTime($index, $name, $sortable = false, $searchable = false, $format = null){
        
        $column = new \stdClass();
        $column->index = $index;
        $column->name = $name;
        $column->sortable = $sortable;
        $column->search = $searchable;
        $column->type = "date";
        $column->format = ($format !== null) ? $format : $this->date_format;
        
        $this->columns[] = $column;
        
        return $this;
        
    }
    
    /**
     * 
     * @param string $index name of index (unique)
     * @param string $name name of grid column
     * @param string $link link (i.e. :module:presenterName:default)
     * @param string $value name of column how the link will appear
     * @param array $params additional column params of url like key => column_name or static params like key => :value
     * @param boolean $sortable
     * @param boolean $searchable
     * @return $this
     */
    public function setColumnLink($index, $name, $link, $value = null, $params = array(), $sortable = false, $searchable = false){
        
        $column = new \stdClass();
        $column->index = $index;
        $column->name = $name;
        $column->sortable = $sortable;
        $column->search = $searchable;
        $column->value = $value;
        $column->link = $link;
        $column->params = $params;
        $column->type = "link";
        
        $this->columns[] = $column;
        
        return $this;
        
    }
    
    public function setTemplatePath($path){
        
        $this->templatePath = $path;
        
    }
    
    private function getTemplatePath(){
        
        return $this->templatePath;
        
    }
    
    private function getColumns(){
        
        return $this->columns;
        
    }
    
    private function prepareDataColumns(){
        
        $return = [];
        
        $columns = $this->getColumns();
        foreach($columns AS $column){
            
            if($column->type === "link"){
                
                if($column->value !== null AND !array_key_exists($column->value, $return)){
                    $return[$column->value] = new \stdClass ();
                    $return[$column->value]->index = $column->value;
                    $return[$column->value]->search = $column->search;
                    $return[$column->value]->sortable = $column->sortable;
                    $return[$column->value]->type = $column->type;
                }
                
                foreach($column->params AS $param){
                    if(!array_key_exists($param, $return) AND substr($param,0,1) !== ":"){
                        $return[$param] = new \stdClass ();
                        $return[$param]->index = $param;
                        $return[$param]->search = $column->search;
                        $return[$param]->sortable = $column->sortable;
                        $return[$param]->type = $column->type;
                    }                    
                    
                }
                
            }else{
                
                if(!array_key_exists($column->index, $return)){
                    $return[$column->index] = new \stdClass ();
                    $return[$column->index]->index = $column->index;
                    $return[$column->index]->search = $column->search;
                    $return[$column->index]->sortable = $column->sortable;
                    $return[$column->index]->type = $column->type;
                }                
                
            }
            
        }
        
        $this->preparedColumns = $return;
        
        return $return;
        
    }
    
    private function convertToAnonymousColumns($data){
        
        foreach($data AS $k => $v){

            foreach($v AS $key => $value){

                foreach($this->columns AS $kk => $vv){ 

                    if($vv->index != $key) continue;

                    unset($data[$k][$key]);
                    $data[$k][$kk] = $value;

                }

            }

        }
        
        return $data;
        
    }
    
    private function getOffset(){
        
        return $this->offset;
        
    }
    
    public function render(){
        
        foreach($this->getColumns() AS $column){
            
            if(!empty($column->search)) $this->searching = true;
            
        }        
        
        $template = $this->template;
        $template->columns = $this->getColumns();
        $template->name = $this->name;
        $template->column_search = $this->column_search;
        $template->offset = $this->getOffset();
        $template->pageLength = $this->pageLength;
        $template->pageLengthSelect = $this->pageLengthSelect;
        $template->async = $this->async;
        $template->paging = $this->paging;
        $template->searching = $this->searching;
        $template->ordering = $this->ordering;
        $template->order = $this->order;
        $template->showFooter = $this->showFooter;
        $template->defaultTemplatePath = self::DEFAULT_TEMPLATE_PATH;
        $template->ajaxParams = $this->ajaxParams;
        $template->responsive = $this->responsive;
        
        $template->setFile($this->getTemplatePath());
        $template->render();
        
    }
    
    private function renderLinkColumn($column, $data){
        
        $params = [];
        if(strpos($column->link, "this") === false){
            foreach($column->params AS $key => $val){
                
                $params[$key] = array_key_exists($val, $this->preparedColumns) ? $data[$val] : ((substr($val,0,1) === ":") ? substr($val,1) : $val);

            }
        }
        $return = array("link" => (strpos($column->link, ":") === false) ? $this->that->link($column->link, $params) : $this->that->presenter->link($column->link, $params), "value" => (!$column->value ? $column->name : $data[$column->value]));
        
        return $return;        
        
    }
    
    private function renderDateTimeColumn($column, $data){
        
        return date($column->format, strtotime($data[$column->index]));
        
    }
    
    private function renderIntegerColumn($column, $data){
        
        return intval($data[$column->index]);
        
    }
    
    private function renderTextColumn($column, $data){
        
        return $data[$column->index];
        
    }
    
    private function processRenderer($renderer, $data){
        
        if(is_callable($renderer)){
            
            return call_user_func($renderer, $data);
            
        }elseif(is_string($renderer)){
            
            return $renderer;
            
        }else{
            
            throw new Exception("Unable to render column");
            
        }                
        
    }
    
    private function renderColumn($column, $data){
        
        $data = is_array($data) ? (object) $data : $data;
        return $this->processRenderer($column->renderer, $data);
        
    }
    
    private function renderReplace($column, $data){

        if(!empty($column->replace) AND is_array($column->replace)){
            
            foreach($column->replace AS $k => $val){

                if($data[$column->index] == $k) return $this->processRenderer($val, $data);
                
            }
            
        }
        
        return $data[$column->index];
        
    }
    
    private function fillDataRow($data){
        
        $result = [];

        foreach($this->getColumns() AS $key => $column){
            
            try{
            
                if(isset($column->renderer)){    
                    $result[$column->index] = $this->renderColumn($column, $data);                
                }elseif(isset($column->replace)){
                    $result[$column->index] = $this->renderReplace($column, $data);
                }elseif($column->type === "link"){
                    $result[$column->index] = $this->renderLinkColumn($column, $data);
                }elseif($column->type === "text"){
                    $result[$column->index] = $this->renderTextColumn($column, $data);
                }elseif($column->type === "date"){
                    $result[$column->index] = $this->renderDateTimeColumn($column, $data);
                }elseif($column->type === "integer"){
                    $result[$column->index] = $this->renderIntegerColumn($column, $data);
                }   

                // set default value to null if there is empty value
                if(!$result[$column->index]) $result[$column->index] = null;
            
            }catch(Exception $e){
                
                $result[$column->index] = null;
                
            }
            
        }
        
        
        return $result;
        
    }
    
    public function getParams(){
        
        $params = $this->presenter->getParameters();
        if(!empty($params["length"]) AND is_numeric($params["length"])) $this->setPageLength(intval($params["length"]));
        if(!empty($params["start"]) AND is_numeric($params["start"])) $this->setOffset(intval($params["start"]));
        if(!empty($params["order"][0]["column"]) AND is_numeric($params["order"][0]["column"])) $this->setOrderColumn(intval($params["order"][0]["column"]));
        if(!empty($params["order"][0]["dir"]) AND is_string($params["order"][0]["dir"])) $this->setOrderDirection($params["order"][0]["dir"]);
        if(!empty($params["search"]["value"]) AND is_string($params["search"]["value"])) $this->setSearchString($params["search"]["value"]);
        foreach($params["columns"] AS $key => $column){
            
                if(!empty($column["search"]["value"])) $this->setSearchColumn($column["name"],$column["search"]["value"]); 
                
        }

        if(!empty($params["draw"]) AND is_numeric($params["draw"])) $this->setDraw(intval($params["draw"]));
        
    }
    
    public function handleGetPage($page){
        
        $success = false;
        $data = array("data" => array());
        
        $this->getParams();
        $this->prepareDataColumns();
                
        if($this->source instanceof NetteDatabase){ 

            $source = $this->source;
                        
        }elseif(is_array($this->source)){
            
            $source = new \Foofocus\DataGrid\ArraySource($this->source);

        }
        
        $source->setColumns($this->preparedColumns)
                ->setSearchString($this->searchString)
                ->setSearchColumns($this->searchColumns)
                ->setPageLength($this->pageLength)
                ->setOffset($this->offset)
                ->setOrderColumn($this->order["column"])
                ->setOrderDirection($this->order["direction"])
                ->setColumnSearch($this->column_search);
        
        $data = $source->getTable();
        
        if(!empty($data["data"])){
            
            foreach($data["data"] AS $k => $v){

               $data["data"][$k] = $this->fillDataRow($v);
                
            }
            
            if($this->anonymous_columns) $data["data"] = $this->convertToAnonymousColumns($data["data"]);
        
             $success = true;                                    
            
        }
        
        $data["draw"] = $this->draw;
                        
        
        //\Tracy\Debugger::barDump($data);
        $this->presenter->sendResponse(new \Nette\Application\Responses\JsonResponse(array_merge(array("success" => $success), $data)));
        
        return $this;
        
    }
    
    
}