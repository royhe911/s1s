<?php
namespace app\index\controller;

use think\Controller;
use app\common\model\feigo\Article as ArticleModel;
use app\common\model\feigo\Help;

class Article extends Controller
{
    public function _empty($id)
    {
        header("Content-Type:text/html;charset=utf-8");
        $id = round($id);
        if (empty($id)) {
            throw exception(lang("11001"), 11001);
        }

        $article_info = ArticleModel::get($id);
        if (empty($article_info)) {
            throw exception(lang("11001"), 11001);
        }
        ArticleModel::where('id',$id)->setInc('view_count');
        $article_info['content'] =htmlspecialchars_decode($article_info['content']);
        $this->assign('article_info', $article_info);
        return $this->fetch('info');
    }
    
    public function user(){
        return $this->fetch('user');
    }
    
    public function about(){
        return $this->fetch('about');
    }

    public function help_detail($help_id){
        header("Content-Type:text/html;charset=utf-8");
        $help_id = round($help_id);
        if (empty($help_id)) {
            throw exception(lang("10"), 10);
        }

        $help_info = Help::get($help_id);
        if (empty($help_info)) {
            throw exception(lang("10"), 10);
        }
        Help::where('id',$help_id)->setInc('view_count');
        $help_info['content'] =htmlspecialchars_decode($help_info['content']);
        $this->assign('help_info', $help_info);
        return $this->fetch('help_detail');
    }
}
