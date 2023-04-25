<?php namespace App\Models;
use CodeIgniter\Model;

	class OrderDetailModel extends Model
{
    protected $table = "order_details";
    protected $allowedFields = ["id","order_id","product_id","product_price","quantity"];
    protected $useTimestamps = false;


}
