<?php namespace App\Models;
use CodeIgniter\Model;

	class OrderModel extends Model
{
    protected $table = "orders";
    protected $allowedFields = ["id","referenceNo","clientId","shopId", "addressId","paidAmount","isOrderComfirm","paymentMode","status"];
    protected $useTimestamps = true;

    public function isReferenceExist($value)
	{
		$res = $this->select('orders.*')
			->where("referenceNo", $value)
			->where("status", 1)
			->where("isOrderComfirm", 0)
			->get();
		return $res->getRow();
	}


}
