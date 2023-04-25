<?php

namespace App\Controllers;
use App\Models\BusinessCategoryModel;
use App\Models\CategoryModel;
use App\Models\ProductModel;
use App\Models\OrderModel;
use App\Models\OrderDetailModel;
use App\Models\ClientShippingAddressModel;
use CodeIgniter\HTTP\Response;
use CodeIgniter\HTTP\ResponseInterface;
use Redis;



class Home extends BaseController
{
    private Redis $redis;
    private $accessData;

    public function __construct()
    {
        helper('cfms');
        $this->redis = new Redis();
        try {
            if ($this->redis->connect("127.0.0.1")) {
                //                $this->redis->setOption(Redis::OPT_SERIALIZER, Redis::SERIALIZER_JSON);
            } else {
                echo lang('redisConnectionError');
                die();
            }
        } catch (\RedisException $e) {
            echo lang('redisConnectionError') . $e->getMessage();
            die();
        }
        session_write_close();
    }
    public function appendHeader()
	{
		if ($this->request->getMethod(true) == "OPTIONS") {
			header('Access-Control-Allow-Origin: *');
			header("Access-Control-Allow-Credentials: true");
			header('Access-Control-Allow-Methods: GET, PUT, POST, DELETE, OPTIONS');
			header('Access-Control-Max-Age: 1000');
			header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token ,    Authorization');

			$this->response->setJSON(array("success", "okay"));
			$this->response->send();
			exit();
		}
		header('Access-Control-Allow-Origin: *');
		header("Access-Control-Allow-Credentials: true");
		header('Access-Control-Allow-Methods: GET, PUT, POST, DELETE, OPTIONS');
		header('Access-Control-Max-Age: 36000');
		header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token ,    Authorization');
	}

    public function _secure($token = null)
    {
        $this->appendHeader();
        if (!isset(apache_request_headers()["Authorization"]) && !isset(apache_request_headers()["authorization"]) && $token == null) {
            $this->response->setStatusCode(401)->setJSON(array("error" => "Access denied", "message" => "You don't have permission to access this resource."))->send();
            exit();
        }
        $t = apache_request_headers()["Authorization"] ?? apache_request_headers()["authorization"];
        $auth = $token == null ? $t : 'Bearer ' . $token;
        if ($auth == null || strlen($auth) < 5) {
            $this->response->setStatusCode(401)->setJSON(array("error" => "Access denied", "message" => "You don't have permission to access this resource."))->send();
            exit();
        } else {
            try {
                if (preg_match("/Bearer\s((.*))/", $auth, $matches)) {
                    if (($decoded = $this->redis->get($matches[1])) !== false) {
                        $this->accessData = json_decode($decoded);
                        //check if it is current active token
                        $activeToken = $this->redis->get("user_" . $this->accessData->uid . '_active_token');
                        if ($activeToken != $matches[1]) {
                            //destroy this token, it is not the current
                            $this->redis->del($matches[1]);
                            $this->response->setStatusCode(401)->setJSON(["error" => "not-active"
                                , "message" => "Your account has be signed in on other computer"])->send();
                            exit();
                        }
                        //update session lifetime
                        $this->redis->expire($matches[1], SESSION_EXPIRATION_TIME);

                    } else {
                        $this->response->setStatusCode(401)->setJSON(array("error" => "Invalid token", "message" => "Invalid authentication."))->send();
                        exit();
                    }
                } else {
                    $this->response->setStatusCode(401)->setJSON(array("error" => "Invalid token", "message" => "Invalid authentication."))->send();
                    exit();
                }
            } catch (\Exception $e) {
                $this->response->setStatusCode(401)->setJSON(array("error" => "Invalid token", "message" => $e->getMessage()))->send();
                exit();
            }
        }
    }

    public function testRedis()
    {
        echo $this->redis->get('token');
        echo "Redis connected <br />";
        echo $this->redis->ping("hello") . "<br />";
        if ($this->redis->set("token", " hello token 1")) {
            echo "Token saved";
        }
    }
    public function index()
    {
        return view('welcome_message');
    }

    

    public function getAllOrders(int $id = 0): Response
	{
		$this->appendHeader();
		$mdl = new OrderModel();
		$result = $mdl->select('orders.*')->limit(6)->orderBy('id', 'asc')->get()->getResultArray();

		return $this->response->setJSON(["data"=>$result]);
	}
}
