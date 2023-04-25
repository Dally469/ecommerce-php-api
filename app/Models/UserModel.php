<?php
namespace App\Models;

use CodeIgniter\Model;

class UserModel extends Model {
    protected $table = 'users';
    protected $allowedFields = ['id', 'user_id','email', 'password','type','position','last_login','status'];
    protected $useTimestamps = false;

    public function checkUser($value, $key = "users.email"){
        $res = $this->select('users.*,s.id as shop_id,s.shop_name,s.locations,s.phone,s.tin_number,s.createdBy')
			->join("shops s","s.id = users.user_id","left")
            ->where("$key" ,$value)
            ->get();
        return $res->getRow();
    }

    public function isExist($id, $email){
        $res = $this->select('users.*')
            ->where("user_id" ,$id)
            ->where("email" ,$email)
            ->get();
        return $res->getRow();
    }

    public function checkPassword($id, $pass){
        $res = $this->select('users.*')
            ->where("id" ,$id)
            ->where("password" ,$pass)
            ->get();
        return $res->getRow();
    }


}

