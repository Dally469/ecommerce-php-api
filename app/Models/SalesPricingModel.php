<?php namespace App\Models;
use CodeIgniter\Model;

class SalesPricingModel extends Model
{
    protected $table = "sales_method_price";
    protected $allowedFields = ["id","shop_id","method_id","product_id","price"];
    protected $useTimestamps = true;


    public function isPricingExist($shop, $value)
	{
		$res = $this->select('sales_method_price.*')
			->where("shop_id", $shop)
			->where("method_id", $value)
			->get();
		return $res->getRow();
	}

	public function isPoductPricingExist($product, $value)
	{
		$res = $this->select('sales_method_price.*')
			->where("product_id", $product)
			->where("method_id", $value)
			->get();
		return $res->getRow();
	}

	public function checkSales($shop, $product, $value)
	{
		$res = $this->select('sales_method_price.*')
			->where("shop_id", $shop)
			->where("product_id", $product)
			->where("method_id", $value)
			->get();
		return $res->getRow();
	}
}
