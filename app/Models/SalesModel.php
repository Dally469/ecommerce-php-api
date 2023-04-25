<?php namespace App\Models;
use CodeIgniter\Model;

class SalesModel extends Model
{
    protected $table = "sales";
    protected $allowedFields = ["id","reference_no","branch_id","customer_id", "product_id","item_qty","paid_amount","discount","sales_method","payment_mode","operator","status","type","credit_duration"];
    protected $useTimestamps = true;


    public function checkProductQuantity($value)
	{
		$res = $this->select('p.id,p.quantity_kg as qty')
            ->join("products p","p.id=sales.product_id")
			->where("p.id", $value)
			->get();
		return $res->getRow();
	}

	public function checkSales($value, $date)
	{
		$res = $this->select('sales.*')
			->where("sales.id", $value)
			->where('DATE_FORMAT(sales.created_at,"%Y-%m-%d")', $date)
			->get();
		return $res->getRow();
	}


}
