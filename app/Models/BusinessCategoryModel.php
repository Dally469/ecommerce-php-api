<?php namespace App\Models;
use CodeIgniter\Model;

class BusinessCategoryModel extends Model
{
    protected $table = "shops_category";
    protected $allowedFields = ["id","title","image_url","status"];
    protected $useTimestamps = true;

}
