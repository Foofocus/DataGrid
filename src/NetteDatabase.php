<?php

namespace Foofocus\DataGrid;

use Nette;

class NetteDatabase implements ISource
{
    
    const DEFAULT_START = 0;
    const DEFAULT_SORT_DIRECTION = "DESC";
    const SORT_DIRECTIONS = ["DESC","ASC"];
    const DEFAULT_SORT_COLUMN = 1;
    const DEFAULT_PAGE_LENGTH = 20;
    const DEFAULT_PAGE_LENGTH_SELECT = [10,20,50];
    const TEXT_SEARCH_OPER_STRICT = "=";
        
    private $database;
    
    public $query = null;
    
    private $data = null;
    
    private $offset = self::DEFAULT_START;
    
    private $pageLength = self::DEFAULT_PAGE_LENGTH;
    
    private $order = ["column" => self::DEFAULT_SORT_COLUMN, "direction" => self::DEFAULT_SORT_DIRECTION];
    
    private $recordsTotal = 0;
    
    private $recordsFiltered = 0;
    
    private $column_search = false;
    private $search_string = null;
    private $search_columns_string = [];
    private $where = "";
    
    private $columns = [];
    
    private $whereCondition = "OR";
    private $whereCondition_column = "AND";
    
    private $param_arr = [];
    
    private $where_arr = [];
    
    private $error = null;
    
    public $date_format = "Y-m-d H:i:s";
    public $date_convert_sql = "YYYY-MM-DD HH24:MI:SS";
    public $text_search_oper = "ILIKE";
    private $dbType = "mysql";

    function __construct(\Nette\Database\Context $database)
    {
        
        $this->database = $database;
        $this->setDBType();
        
    }
    
    private function setDBType(){
        
        $dsn = $this->database->getConnection()->getDsn(); 
        $this->dbType = (strpos($dsn, "pgsql") !== false) ? "pgsql" : "mysql";
        
    }
    
    /**
     * 
     * @param string $query
     * @param array $args
     * @return $this
     */
    public function setQuery($query, $args = array()){
        
        $this->query = $query;
        if(!empty($args)){
            foreach($args AS $arg){
                $this->where_arr[] = $arg;
                $this->param_arr[] = $arg;
            }            
        }        
        
        return $this;
        
    }
    
    public function setColumns($columns){
        
        $this->columns = $columns;
        
        return $this;
        
    }
    
    public function setData($data){
        
        $this->data = $data;
        
        return $this;
        
    }
        
    public function setColumnSearch($value = true){
        
        $this->column_search = $value;
       
        return $this;
        
    }
    
    public function setPageLength($value = 20){
        
        if(is_numeric($value)) $this->pageLength = intval($value);
        
        return $this;
        
    }
    
    public function setOrderColumn($column){
        
        $this->order["column"] = $column + 1;
        
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
        
        $this->search_string = $value;
        
        return $this;
        
    }
    
    public function setSearchColumns($columns){
        
        $this->search_columns_string = $columns;
        
        return $this;
        
    }
    
    private function getCount(){
        
        $this->recordsTotal = $this->database->queryArgs("SELECT count(*) AS cou FROM (" . $this->query . ") AS sub", $this->where_arr)->fetchField();        
        
        return $this;
        
    }
    
    private function getFilteredCount(){
        
        $this->recordsFiltered =  $this->database->queryArgs("SELECT count(*) AS cou FROM (" . $this->query . ") AS sub " . $this->getWhere() . "", $this->where_arr)->fetchField();        
        
        return $this;
        
    }
    
    private function prepareColumns(){
        
        $cols = array();
        
        foreach($this->columns AS $k => $v){
            
            $str = "sub." . $v->index;
            $cols[] = $str; 
            
        }
        
        return !empty($cols) ? implode(",",$cols) : "*";
        
    }
    
    private function prepareSearchParam($column, $value){
        
        if(empty($column->search)) return false;
        if($column->type === "integer" AND !is_numeric($value)) return false;
        if($column->type === "date" AND !strtotime($value)) return false;
        
        $colval = "";
        
        if($column->type === "integer"){

            $colval = $value;

        }elseif($column->type === "date"){

            $colval = date($this->date_format, strtotime($value));

        }else{

            $colval = !empty($column->search_strict) ? $value : "%" . $value . "%";

        }
        
        $col = ($column->type === "date" ? "to_date(" . $column->index . "::character varying, '" . $this->date_convert_sql . "')" : ($column->type === "integer" ? $column->index . "::character varying" : $column->index)) . ($column->type === "date" ? " = " : " " . (!empty($column->search_strict) ? self::TEXT_SEARCH_OPER_STRICT : $this->text_search_oper) . " ") . "?"; 
        
        return array($col, $colval);
        
    }
    
    private function prepareSearch(){
        
        $cols = [];
        $cols_column = [];
        if(!empty($this->search_columns_string) AND $this->column_search){
            
            foreach($this->search_columns_string AS $key => $val){

                if($param = $this->prepareSearchParam($this->columns[$key], $val)){
                    list($col, $value) = $param;                
                    $cols_column[] = $col; 
                    $this->where_arr[] = $value;
                    $this->param_arr[] = $value;
                }
                
            }
            
        }elseif($this->search_string){
            
            foreach($this->columns AS $v){

                if($param = $this->prepareSearchParam($v, $this->search_string)){
                    list($col, $value) = $param;                
                    $cols[] = $col; 
                    $this->where_arr[] = $value;
                    $this->param_arr[] = $value;
                }
                
            }
            
        }
        $this->where = (!empty($cols) OR !empty($cols_column)) ? " WHERE " . (!empty($cols) ? implode(" " . $this->whereCondition . " ",$cols) : null) . ((!empty($cols) AND !empty($cols_column)) ? " " . $this->whereCondition . " " : null) . (!empty($cols_column) ? implode(" " . $this->whereCondition_column . " ",$cols_column) : null): "";
        return $this;
        
    }
    
    private function prepareQuery(){
        
        $return =  "SELECT " . $this->prepareColumns() . " FROM (" . $this->query . ") AS sub " . $this->getWhere() . " ORDER BY ? " . $this->order["direction"] . " LIMIT ? OFFSET ?";
        //echo $return; exit;
        return $return;
        
    }
    
    private function getWhere(){
        
        return $this->where;
        
    }
    
    private function getDataFromDatabase(){
        
        $this->getCount();
                
        $query = $this->prepareSearch();
        $query = $this->prepareQuery();
        $this->param_arr[] = $this->order["column"];
        $this->param_arr[] = $this->pageLength;
        $this->param_arr[] = $this->offset;
        
        try{
            
            $this->data = $this->database->queryArgs($query, $this->param_arr)->fetchAll(); 
            $this->getFilteredCount();                
            
        }catch(Nette\Database\DriverException $e){
            
            $this->error = $e->getMessage();
            
        }

        return $this;                
        
    }
    
    private function prepareData(){
        
        $this->getDataFromDatabase();
        
        return $this;
                       
    }

    public function getTable(){
        
        $this->prepareData();
        
        return $this->getResponse();
        
    }
    
    private function getResponse(){
        
        return array("data" => $this->data, "recordsTotal" => $this->recordsTotal, "recordsFiltered" => $this->recordsFiltered, "error" => $this->error);
        
    }
    
}