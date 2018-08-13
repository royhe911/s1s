<?php
namespace app\index\controller;

use think\Controller;
use think\Exception;
use think\facade\Cache;
use think\facade\Log;
use think\facade\Request;

use app\common\model\feigo\MemberOpen;
use app\common\model\feigo\Members;

class OtherLogin extends Controller
{

    public function wechat_login()
    {
        $oldUrl = Request::param('oldUrl', "");
        
        $redirect_uri = "https://" . config('url_prefix_www') . '.' . config('url_domain_root') . "/other_login/wechat_login_callback?oldUrl={$oldUrl}";
        $url = \Wechat::instance()->get_authorize_url($redirect_uri);
        $this->redirect($url);
    }

    public function wechat_login_callback()
    {
        $oldUrl = Request::param('oldUrl', "homePage");
        Log::info("old_url:{$oldUrl}");
        $uinfo = \Wechat::instance()->get_userinfo_by_authorize();
        Log::info($uinfo);
        $uinfo = json_decode(json_encode($uinfo), true);
        if (empty($uinfo[1]) || empty($uinfo[1]['openid'])) {
            throw new Exception("错误的参数类型！");
        }
        $uinfo = $uinfo[1];
        $oid = $uinfo['openid'];
        isset($uinfo['unionid']) ? $unionid = $uinfo['unionid'] : $unionid = '';
        
        $search_data['open_id'] = $oid;
        $search_data['type'] = 2;
        
        try {
            $url = "http://" . config('url_prefix_www') . '.' . config('url_domain_root') . "/#/transfer?oldUrl={$oldUrl}";
            $member_open = MemberOpen::master()->order('id desc')
                ->where($search_data)
                ->find();
            
            if (empty($member_open)) {
                $member_open = $search_data;
                $member_open['create_time'] = date("Y-m-d H:i:s");
                $member_open['unionid'] = $unionid;
                $member_open = MemberOpen::create($member_open);
            } elseif (! empty($member_open->member_id)) {
                $member_info = Members::get($member_open->member_id);
                if (! empty($member_info) && ! empty($member_info->mobile)) {
                    $token = Members::memberToken($member_info->toArray());
                    $token = urlencode($token);
                    Log::info("token:{$token}");
                    $url = $url . "&token={$token}";
                    $this->redirect($url);
                    return;
                }
            }
            
            $member_info['nickname'] = $uinfo['nickname'];
            $member_info['sex'] = $uinfo['sex'] == '1' ? 1 : 2;
            $member_info['city'] = $uinfo['province'] . "_" . $uinfo['city'];
            $avatar = config("qiniu.img_prefix") . "avatar/" . base_convert(time() * 1000, 10, 36) . "_" . base_convert(microtime(), 10, 36) . uniqid() . ".jpg";
            $upload_result = \Qiniu::instance()->fetchFile($uinfo['headimgurl'], config("qiniu.bucket"), $avatar);
            $member_info['avatar'] = $avatar;
            // $member_info['avatar'] = "upload/image/member/j4du8t6g_189ampggnla85950bb8327817.jpg";
            $member_info['member_open_id'] = $member_open->id;
            
            Cache::init(["prefix" => "api_cache"]);
            Cache::set("wechat_openid_{$search_data['type']}_{$oid}", $member_info, 3600);
            
            $url = $url . "&openid={$oid}";
            $this->redirect($url);
        } catch (Exception $e) {
            throw $e;
        }
        exit();
    }
}
