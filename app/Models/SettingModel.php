<?php namespace App\Models;

use CodeIgniter\Model;

class SettingModel extends Model
{
    protected $table = "settings";
    protected $allowedFields = ["id", "shop_id","title","setting", "value", "updated_by"];
    protected $useTimestamps = true;
    /**
     * @param array $args array that contains settings key to be selected
     * @return array key value settings pair
     */
    function getSettings(array $args = [], int $shopId): array
    {
        $settingsBuilder = $this->select('id,shop_id,setting,value');
        if (count($args) != 0) {
            $settingsBuilder->whereIn('setting', $args)->where('shop_id', $shopId);
        }
        $settings = $settingsBuilder->get()
            ->getResultArray();
        return array_column($settings, 'value','setting');
    }

    public function isShopExist($shop, $setting)
	{
		$res = $this->select('settings.*')
			->where("shop_id", $shop)
			->where("setting", $setting)
			->get();
		return $res->getRow();
	}

    function getShopSettings(array $args = []): array
    {
        $settingsBuilder = $this->select('id,shop_id,setting,value');
        if (count($args) != 0) {
            $settingsBuilder->whereIn('setting', $args);
        }
        $settings = $settingsBuilder->get()
            ->getResultArray();
        return array_column($settings, 'value','setting');
    }

}