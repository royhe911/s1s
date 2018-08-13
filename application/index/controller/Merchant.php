<?php

namespace app\index\controller;

use think\Controller;

class Merchant extends Controller
{
   public function activate()
   {
       $template = isMobile() ? 'h5_activate': 'pc_activate';
       return $this->view->fetch($template);
   }
}
