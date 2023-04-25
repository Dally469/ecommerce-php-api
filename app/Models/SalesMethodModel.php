<?php namespace App\Models;
use CodeIgniter\Model;

class SalesMethodModel extends Model
{
    protected $table = "sales_method";
    protected $allowedFields = ["id","title"];
    protected $useTimestamps = false;
}
