<?php namespace App\Models;
use CodeIgniter\Model;

class ProductModel extends Model
{
    protected $table = "products";
    protected $allowedFields = [
        "id",
        "shopId",
        "shopCategoryId",
        "productName",
        "productCategory",
        "productDescription",
        "productPhoto",
        "productQuantity",
        "quantityKg",
        "priceRefilling",
        "priceBuying",
        "productRate",
        "zoneId",
        "isGas",
        "status"
    ];
    protected $useTimestamps = false;
}
