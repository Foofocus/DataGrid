<?php

namespace Foofocus\DataGrid;

class ArraySource implements ISource
{
        
    const DEFAULT_START = 0;
    const DEFAULT_SORT_DIRECTION = "DESC";
    const SORT_DIRECTIONS = ["DESC","ASC"];
    const DEFAULT_SORT_COLUMN = 1;
    const DEFAULT_PAGE_LENGTH = 20;
    const DEFAULT_PAGE_LENGTH_SELECT = [10,20,50];
        
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
    
    public $date_convert = "Y-m-d H:i:s";

    function __construct($source)
    {
          $this->setData($source);      
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
        
        $this->search_string = $value;
        
        return $this;
        
    }
    
    public function setSearchColumns($columns){
        
        $this->search_columns_string = $columns;
        
        return $this;
        
    }
        
    private function setCountFromArray(){
        
        $this->recordsTotal = count($this->data);        
        
    }
    
    private function setFilteredCountFromArray(){
        
        $this->recordsFiltered = count($this->data);
        
    }
    
    public function getWhere(){
        
        return $this->where;
        
    }
    
    private function convertToAnonymousColumns(){
        
        foreach($this->data AS $k => $v){

            foreach($v AS $key => $value){

                foreach($this->columns AS $kk => $vv){ 

                    if($vv["name"] != $key) continue;

                    unset($this->data[$k][$key]);
                    $this->data[$k][$kk] = $value;

                }

            }

        }
        
    }
    
    private function filterArray(){
        
        $result = [];
        
        foreach($this->data AS $key => $row){

            foreach($this->columns AS $k => $v){

                if($v->type == "date"){
                    $this->data[$key][$v->index] = date($this->date_convert, strtotime($row[$v->index]));                        
                }

            }                

        }
        
        if(!empty($this->search_columns_string)){
            
            foreach($this->data AS $row){
                foreach($this->search_columns_string AS $key => $val){

                    if(array_key_exists($key, $this->columns)){

                        $v = $this->columns[$key];

                        if(is_string($this->search_columns_string) AND !empty($v->type) AND $v->type === "integer") continue;
                        $is = ($v->type === "integer") ? (($row[$v->index] == $this->search_columns_string) ? true : false) : ((strpos(strtolower($row[$v->index]), strtolower($this->search_columns_string)) !== false) ? true : false); 

                        if($is){
                            $result[] = $row;
                            continue(2);
                        }

                    }

                }
            }
            $this->data = $result;
            
        }elseif($this->search_string){
            foreach($this->data AS $row){
                
                foreach($this->columns AS $k => $v){

                    if(empty($v->search)) continue;
                    if(!is_numeric($this->search_string) AND !empty($v->type) AND ($v->type === "integer")) continue;

                    $is = ($v->type === "integer") ? (($row[$v->index] == $this->search_string) ? true : false) : ((strpos(strtolower($row[$v->index]), strtolower($this->search_string)) !== false) ? true : false); 
                    
                    if($is){
                        $result[] = $row;
                        continue(2);
                    }

                }                
                
            }
            $this->data = $result;
        }
        
        if(count($this->data)){
            $order_column = array_slice($this->columns, $this->order["column"], 1);
            if(!empty($order_column) AND is_array($order_column)){
                
                $order_column = current($order_column);
                $sort_arr = array_column($this->data, $order_column->index);

                array_multisort($sort_arr, ($this->order["direction"] === "ASC" ? SORT_ASC : SORT_DESC), $this->data); 
            
            }
            
        }
        
    }
    
    private function getDataFromArray(){
        
        $this->setCountFromArray();         
        $this->filterArray();        
        $this->setFilteredCountFromArray();
        
        $this->data = array_slice($this->data, $this->offset, $this->pageLength); 

        return $this;
        
    }
    
    public function getTable(){
        
        $this->getDataFromArray();

        return $this->getResponse();
        
    }
    
    private function getResponse(){
        
        return array("data" => $this->data, "recordsTotal" => $this->recordsTotal, "recordsFiltered" => $this->recordsFiltered);
        
    }
    
}