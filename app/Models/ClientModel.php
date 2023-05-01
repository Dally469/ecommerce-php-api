<?php namespace App\Models;
use CodeIgniter\Model;

class ClientModel extends Model
{
    protected $table = "clients";
    protected $allowedFields = ["id","names", "phone","email","coupon","photo","status"];
    protected $useTimestamps = true;

    public function checkUser($value, $key = "phone")
    {
        $res = $this->select('clients.*')
            ->where($key, $value)
            ->get();
        return $res->getRow();
    }
 
    public function isPhoneExist($phone){
      $res = $this->select('clients.id,phone')->where("clients.phone", $phone)->get();
      return $res->getRow();
    }
   
   
}
