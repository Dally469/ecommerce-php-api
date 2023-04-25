<?php namespace App\Models;
use CodeIgniter\Model;

class ShopModel extends Model
{
    protected $table = "shops";

    protected $allowedFields = ["id","zoneId","name", "category","phone","latitude","longitude","status","createdBy"];

    protected $useTimestamps = true;

    public function isShopExist($value, $key = "email")
    {
        $res = $this->select('shops.*')
            ->where($key, $value)
            ->get();
        return $res->getRow();
    }

    public function getNearbShopToRequest($lat, $lng, $range){
        $result =  $this->query("SELECT * FROM (
            SELECT shops.id, shops.name as shop_name, phone as shop_phone,zoneId,z.zoneName,shops.latitude,shops.longitude, 
                (
                    (
                        (
                            acos(
                                sin(( $lat * pi() / 180))
                                *
                                sin(( `shops`.`latitude` * pi() / 180)) + cos(( $lat * pi() /180 ))
                                *
                                cos(( `shops`.`latitude` * pi() / 180)) * cos((( $lng - `shops`.`longitude`) * pi()/180)))
                        ) * 180/pi()
                    ) * 60 * 1.1515 * 1.609344
                )
            as distance FROM shops  LEFT JOIN zones z ON z.id = shops.zoneId WHERE shops.status = 1
        ) shops
        WHERE distance <= $range  ORDER BY distance
        LIMIT 1");

        if ($result->getNumRows() > 0) {
            return $result->getRow();
        }else{
            return false;
        }
    }
}
