<?php
/**
 * Created by PhpStorm.
 * User: 1609123282
 * Email: 123456789@qq.com
 * Date: 2020/5/5
 * Time: 11:21 PM
 */
namespace app\model;

class BaseModel
{
    protected $db = null;

    public function __construct($db)
    {
        $this->db = $db;
    }
}