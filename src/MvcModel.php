<?php

/**
 * @author Chanh Ong
 * @package eJV
 * @since 2.0
 */
namespace MvcLite;

class MvcModel extends MvcCore {

    public function __construct($tname, $id = null) {
        
        parent::__construct();
        // get table name from controller
        $this->meTable = $tname;         
    }

    public function _dbt($opr, $text_or_array) {
        if (!empty($opr) and !empty($text_or_array)) {
            $allowArray = ['select', 'dbrow', 'update', 'delete', 'insert', 'getnextid'];
            if (in_array($opr, $allowArray) and !empty($text_or_array)) { // false if not found
                return $this->db->$opr($this->meTable, $text_or_array);
            }
        }
    }
}
