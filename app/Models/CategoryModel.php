<?php namespace App\Models;
use CodeIgniter\Model;

class CategoryModel extends Model
{
    protected $table = "categories";
    protected $allowedFields = ["id","businessCategoryId","title"];
    protected $useTimestamps = false;

    public function checkCategory($value, $category)
    {
        $res = $this->select('categories.*')
            ->where("businessCategoryId", $value)
            ->where("title", $category)
            ->get();
        return $res->getRow();
    }

}
