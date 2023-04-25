<?php

namespace App\Models;

use CodeIgniter\Model;

class ClientShippingAddressModel extends Model {
    protected $table = 'client_addresses';

    protected $primaryKey = 'id';
    protected $allowedFields = ['id','clientId','title','address','latitude','longitude','status'];
    protected $useTimestamps = false;
}
