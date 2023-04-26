<?php

namespace App\Controllers;
use App\Models\BusinessCategoryModel;
use App\Models\CategoryModel;
use App\Models\ProductModel;
use App\Models\OrderModel;
use App\Models\OrderDetailModel;
use App\Models\ClientShippingAddressModel;
use App\Models\ShopModel;
use App\Models\ClientModel;
use App\Models\StockModel;
use CodeIgniter\HTTP\Response;
use CodeIgniter\HTTP\ResponseInterface;
use Redis;


class App extends BaseController
{
    private Redis $redis;
    private $accessData;

    public function __construct()
    {
        helper('ecommerce');
        $this->redis = new Redis();
        try {
            if ($this->redis->connect("127.0.0.1")) {
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

    public function checkRedis(): Response
    {
     
        return $this->response->setStatusCode(200)->setJSON(
            [ 
                "token"=> $this->redis->get('token'), 
                "message" => $this->redis->ping("Welcome to Ecommerce Api")
            ]
        );
    }

    public function index()
    {
        return $this->response->setJSON(['status' => lang('app.success'), 'message' => lang('app.cfmsApiConfiguredSuccessfully')]);
    }

    function generateReferenceNo($length = 8): string
	{
		$characters = '012345678901234567890123456789012345678901234567890123456789';
		$password = '';
		$characterListLength = mb_strlen($characters, '8bit') - 1;
		for ($i = 1; $i < $length; $i++) {
			try {
				$password .= $characters[random_int(0, $characterListLength)];
			} catch (Exception $e) {
				$password .= '#';
			}
		}
		return $password;
	}

    public function getShopCategories(): Response
	{
		$this->appendHeader();
		$mdl = new BusinessCategoryModel();
		$result = $mdl->select('shops_category.id,shops_category.title,image_url,status,count(c.id) as category')
			->join("categories c", "c.businessCategoryId = shops_category.id", "left")
			->groupBy("shops_category.id")
			->get()->getResultArray();
		return $this->response->setJSON($result);
	}
   

    public function getAllProductByBusinessCategoryId($id): Response
	{
		$this->appendHeader();
		$productModel = new ProductModel();

		$builder = $productModel->select("products.*,products.productCategory as categoryId, c.title as productCategory,s.name as shopName,z.id as zoneId, z.zoneName")
			->join("categories c", "c.id = products.productCategory", "left")
			->join("shops s", "s.id = products.shopId", "left")
			->join("zones z", "z.id = s.zoneId", "left")
			->orderBy('products.id', 'DESC')
			->groupBy('products.id')
			->where("products.shopCategoryId", $id)
			->get()->getResultArray();

		return $this->response->setJSON($builder);
	}

    public function getSimilarProducts($id): Response
	{
		$this->appendHeader();
		$productModel = new ProductModel();

		$builder = $productModel->select("products.*,products.productCategory as categoryId, c.title as productCategory,s.name as shopName,z.id as zoneId, z.zoneName")
			->join("categories c", "c.id = products.productCategory", "left")
			->join("shops s", "s.id = products.shopId", "left")
			->join("zones z", "z.id = s.zoneId", "left")
			->orderBy('products.id', 'DESC')
			->groupBy('products.id')
			->where("products.productCategory", $id)
			->get()->getResultArray();

		return $this->response->setJSON($builder);
	}

    public function saveShippingAddress(): ResponseInterface
    {
        $mdl = new ClientShippingAddressModel();
        $input = json_decode(file_get_contents("php://input"));
        $id = $input->clientId;
        
        try {
            $data = $mdl->save([
                "clientId" => $id,
                "title" => $input->title,
                "address" => $input->address,
                "latitude" => $input->latitude,
                "longitude" => $input->longitude,
                "status" => 1
            ]);

            $result['status'] = 200;
            $result['message'] = "Shipping Address Saved Successfully";
            $result['data'] = $data;
            return $this->response->setJSON($result);

        } catch (\Exception $e) {
            return $this->response->setJSON($e->getMessage());
        }
               
    }

    public function getClientShippingAddress($clientId = 0): ResponseInterface
    {
        $mdl = new ClientShippingAddressModel();
        $result = $mdl->select('client_addresses.*')
            ->where("clientId", $clientId)
            ->orderBy('id', 'DESC')
            ->get()->getResultArray();

        if (!empty($result)) {
            return $this->response->setJSON($result);
        } else {
            return $this->response->setStatusCode(404)->setJSON($result);
        }
    }

    public function getAllCategories(int $id = 0): Response
	{
		$this->appendHeader();
		$mdl = new CategoryModel();
		if ($id != null) {
			$result = $mdl->select('id,title')
				->where("businessCategoryId", $id)
				->orderBy('title', 'asc')
				->get()->getResultArray();
		} else {
			$result = $mdl->select('id,title')->orderBy('title', 'asc')->get()->getResultArray();
		}

		return $this->response->setJSON($result);
	}

    public function clientRequestOrder(): Response
	{
		$this->appendHeader();
		$orderMdl = new OrderModel();
		$orderDetailMdl = new OrderDetailModel();
        $shopModel = new ShopModel();
        $stockMdl = new StockModel();
		$input = json_decode(file_get_contents('php://input'));
        $lists = $input->lists;
        $geoLocationLat = $input->latitude;
        $geoLocationLng = $input->longitude;
        $range = $input->range;
        $referenceCode = $this->generateReferenceNo(11);

        $shop = $shopModel->getNearbShopToRequest($geoLocationLat, $geoLocationLng, $range);
        if (!$shop) {
            return $this->response->setStatusCode(404)->setJSON(array("type" => "error", "message" => "No nearby shop found"));
        }

        try {
            $order = $orderMdl->insert([
                "referenceNo" => $referenceCode,
                "clientId" => $input->clientId,
                "phone" => $input->phone,
                "paidAmount" => $input->amount,
                "shopId" => $shop->id,
                "addressId" => $input->addressId,
                "status" => 0

            ]);
            foreach($lists as $item){
                $product = $stockMdl->isProductExist($shop->id, $item->productId);
                if (empty($product)) {
                    $orderMdl->delete($order);
                    return $this->response->setStatusCode(404)->setJSON(array("type" => "error", "message" => "$shop->shop_name product not found"));
                }

                if ($product->inStock < $item->items) {
                    $orderMdl->delete($order);
                    return $this->response->setJSON(["type" => "error", "message" => "You dont have enough Stock , $product->productName "]);
                } else {
                
                    if ($order > 0) {
                        $orderDetailMdl->save([
                            "order_id" => $order,
                            "product_id" => $item->productId,
                            "product_price" => $item->productPrice,
                            "quantity" => $item->items
        
                        ]);
                    }
                 
                }

            }

            return $this->response->setStatusCode(200)->setJSON(array("type" => "success", "code" => $referenceCode, "message" => "Order Created Successful"));
			
		} catch (\Exception $e) {
			if ($e->getCode() == 1062) {
				return $this->response->setStatusCode(500)->setJSON(array("type" => "error", "message" => "Phone number already registered"));
			}
			if (strpos($e->getMessage(), "Undefined property") !== false) {
				return $this->response->setStatusCode(400)->setJSON(array("error" => "Missing piece", "messages" => "Please make sure all required data is sent" . $e->getMessage()));
			}
			return $this->response->setStatusCode(403)->setJSON(array("error" => "Error occurred", "message" => $e->getMessage()));
		}
	}

    public function trackingOrderByReference($refNo): Response
	{
		$this->appendHeader();
        $input = json_decode(file_get_contents('php://input'));
		$mdl = new OrderModel();
		$detailMdl = new OrderDetailModel();

		$order = $mdl->select('orders.id,referenceNo,orders.clientId,shopId,s.name as shop,s.latitude as sLatitude,s.longitude as sLongitude,
        ca.title as addressTitle,ca.address,ca.latitude as dLatitude,ca.longitude as dLongitude,d.names as driver,d.phone as driverPhone,orders.status')
            ->join("shops s","s.id = orders.shopId", "left")
            ->join("client_addresses ca","ca.id = orders.addressId","left")
            ->join("drivers d","d.id = orders.driverId","left")
			->where("referenceNo", $refNo)
			->get()->getRow();
        $details = $detailMdl->select("order_details.id,p.productName,product_price, quantity")
            ->join("products p","p.id = order_details.product_id")
            ->where("order_id", $order->id)
            ->get()->getResultArray();

		return $this->response->setJSON(['order' => $order, 'orderData' => $details]);
	}
    public function createClientAccount(): Response
	{
		$this->appendHeader();
        $input = json_decode(file_get_contents('php://input'));
		$mdl = new ClientModel();
        if (!isset($input->phone)) {
            return $this->response->setStatusCode(500)->setJSON(['status' => 500, 'message' => 'Invalid data, please try again']);
        }
        if (strlen($input->phone) < 10) {
            return $this->response->setStatusCode(500)->setJSON(['status' => 500, 'message' => 'Invalid phone number']);
        }
        $check = $mdl->isPhoneExist($input->phone);
        if ($check) {
            return $this->response->setStatusCode(500)->setJSON(['status' => 500, 'message' => 'Already exist phone number']);
        }

        $mdl->save([
            "names" => $input->names,
            "phone" => $input->phone,
            "email" => "",
            "coupon" => "",
            "photo" => ""
        ]);
		return $this->response->setStatusCode(200)->setJSON(['status' => 200, 'message' => 'Account successful created']);
	}

    public function approveArrived(): Response
	{
		$this->appendHeader();
        $input = json_decode(file_get_contents('php://input'));
		$mdl = new ClientModel();
        if (!isset($input->phone)) {
            return $this->response->setStatusCode(500)->setJSON(['status' => 500, 'message' => 'Invalid data, please try again']);
        }
        if (strlen($input->phone) < 10) {
            return $this->response->setStatusCode(500)->setJSON(['status' => 500, 'message' => 'Invalid phone number']);
        }
        $check = $mdl->isPhoneExist($input->phone);
        if ($check) {
            return $this->response->setStatusCode(500)->setJSON(['status' => 500, 'message' => 'Already exist phone number']);
        }

        $mdl->save([
            "names" => $input->names,
            "phone" => $input->phone,
            "email" => "",
            "coupon" => "",
            "photo" => ""
        ]);
		return $this->response->setStatusCode(200)->setJSON(['status' => 200, 'message' => 'Account successful created']);
	}

    public function getProductToDelivery($id): Response
	{
		$this->appendHeader();
        $input = json_decode(file_get_contents('php://input'));
		$mdl = new OrderModel();
		$order = $mdl->select('orders.id,referenceNo,orders.clientId,shopId,s.name as shop,s.latitude as sLatitude,s.longitude as sLongitude,
        ca.title as addressTitle,ca.address,ca.latitude as dLatitude,ca.longitude as dLongitude,d.names as driver,d.phone as driverPhone,orders.status')
            ->join("shops s","s.id = orders.shopId", "left")
            ->join("client_addresses ca","ca.id = orders.addressId","left")
            ->join("drivers d","d.id = orders.driverId","left")
			->where("driverId", $id)
			->get()->getResultArray();
       

		return $this->response->setJSON($order);
	}
}
