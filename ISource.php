<?php

namespace Foofocus\DataGrid;

interface ISource{
    
    public function setData($data);
    public function setColumns($columns);
    public function setSearchString($string);
    public function setSearchColumns($columns);
    public function setPageLength($length);
    public function setOffset($offset);
    public function setOrderColumn($column);
    public function setOrderDirection($direction);
    public function setColumnSearch($column_search);
    public function getTable();
    
}
