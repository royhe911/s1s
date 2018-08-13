<?php
namespace app\index\controller;

use think\Controller;
use app\common\model\feigo\ShopPromotion as ShopPromotionModel;

class ShopPromotion extends Controller
{
    public function _empty($id)
    {
        header("Content-Type:text/html;charset=utf-8");
        $id = round($id);
        if (empty($id)) {
            throw exception(lang("11001"), 11001);
        }
    
        $article_info = ShopPromotionModel::get($id);
        if (empty($article_info)) {
            throw exception(lang("11001"), 11001);
        }
        $this->assign('article_info', $article_info);
        return $this->fetch('info');
    }
}
