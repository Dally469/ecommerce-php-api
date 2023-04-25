<?php namespace App\Models;
use CodeIgniter\Model;

class StockModel extends Model
{
    protected $table = "general_stock";
    protected $allowedFields = ["id","parentId","shopId", "productId","inStock","emptyCylinder","stockReturn","operator","type"];
    protected $useTimestamps = true;

    public function isProductExist($shop,$product)
	{
		$res = $this->select('general_stock.*,p.productName,p.quantityKg')
        ->join("products p", "p.id=general_stock.productId")
        ->where("general_stock.shopId", $shop)
        ->where("productId", $product)
		->get();
		return $res->getRow();
	}
   
}
