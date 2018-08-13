<?php
namespace app\admin\controller;

use think\Db;
use think\Exception;
use think\facade\Config;
use think\facade\Log;

use app\common\controller\Backend;
use app\common\model\feigo\BrandShop;
use app\common\model\feigo\GoodDetailImages;
use app\common\model\feigo\GoodImages;
use app\common\model\feigo\Goods;
use app\common\model\feigo\GoodShop;
use app\common\model\feigo\IndexShops;
use app\common\model\feigo\Regions;
use app\common\model\feigo\ShopAlbum;
use app\common\model\feigo\ShopComment;
use app\common\model\feigo\ShopImport;
use app\common\model\feigo\ShopImportErr;
use app\common\model\feigo\ShopImportLike;
use app\common\model\feigo\ShopImportSuc;
use app\common\model\feigo\ShopPaperwork;
use app\common\model\feigo\Shops;
use app\common\model\feigo\ShopTypeShop;
use PHPExcel_IOFactory;
use PHPExcel;

class Import extends Backend
{
    protected $relationSearch = true;
    protected $layout = 'default2';

    public function index()
    {
        $city_id = $this->city_id;
        $list_rows = $this->cur_limit;

        $import_list = ShopImport::where('region_id_2', $city_id)->paginate($list_rows, false, ['query' => $this->request->get()]);
        if (!empty($import_list)) {
            foreach ($import_list as $k => $v) {
                $import_err_count = ShopImportErr::where('branch_no', $v['branch_no'])->count();
                $import_suc_count = ShopImportSuc::where('branch_no', $v['branch_no'])->count();
                $import_list[$k]['import_err_count'] = $import_err_count;
                $import_list[$k]['import_suc_count'] = $import_suc_count;
            }
        }

        $this->view->assign('list', $import_list);
        return $this->view->fetch();
    }

    /**
     * 导入成功数据
     * @param null $branch_no
     * @return string
     * @throws \think\exception\DbException
     */
    public function import_suc($branch_no = null)
    {
        $city = $this->city_id;
        $list_rows = $this->cur_limit;
        if (empty($branch_no)) $this->error('请求错误');

        $import_suc = (new ShopImportSuc)->alias('si')
            ->join('shops s', 's.id=si.shop_id', 'left')
            ->join('shop_type_shop st', 'st.shop_id=s.id', 'left')
            ->join('shop_types t', 't.id=st.type_id', 'left')
            ->where('si.branch_no', $branch_no)
            ->field('si.*, s.name, s.region_id_3 as s_region_id_3, s.region_id_4 as s_region_id_4, s.address as s_address, s.status, t.name as s_type_name')
            ->paginate($list_rows, false, ['query' => $this->request->get()]);
        $region_list_3 =  Regions::where('parent_id', $city)->column('name', 'id');
        $region_list_4 = [];
        if (!empty($region_list_3)) {
            $region_list_4 = Regions::where('parent_id', 'in', array_keys($region_list_3))->column('name', 'id');
        }

        $this->view->assign('list', $import_suc);
        $this->view->assign('branch_no', $branch_no);
        $this->view->assign('region_list_3', $region_list_3);
        $this->view->assign('region_list_4', $region_list_4);
        return $this->view->fetch();
    }

    /**
     * 删除相似的导入数据 也可以排除
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function delete_like()
    {
        $ids = $this->request->post('ids');
        $sign = $this->request->post('sign');

        if (empty($ids) || empty($sign)) $this->error('请求错误');
        if ($sign == 1) {
            $err = ShopImportLike::where('id', $ids)->where('pid', 0)->find();
            if (empty($err)) $this->error('请求错误');
            if ($err['is_import'] == 1 || $err['is_import'] == 2) {
                $this->error('请求错误');
            }
            ShopImportLike::where('id', $ids)->where('pid', 0)->setField('is_import', 2);

        } else if ($sign == 2) {
            $err = ShopImportLike::where('id', $ids)->where('pid', '<>', 0)->find();
            if (empty($err)) $this->error('请求错误');
            if (empty($shop_id = $err['shop_id'])) $this->error('请求错误');

            $shop = Shops::where('id', $shop_id)->find();
            if (empty($shop)) $this->error('已删除');

            if ($shop['is_user_status'] == 1) $this->error('该门店已被认领，不可删除');

            try {
                // 删除
                Shops::where('id', 'in', $shop_id)->delete();

                // 删除首页推荐
                IndexShops::where('id', 'in', $shop_id)->delete();

                // 删除门店评论
                ShopComment::where('shop_id', 'in', $shop_id)->setField('shop_id', 0);

                // 删除门店法人信息
                ShopPaperwork::where('shop_id', 'in', $shop_id)->delete();

                // 删除门店相册
                ShopAlbum::where('shop_id', 'in', $shop_id)->delete();

                // 删除门店分类关联数据
                ShopTypeShop::where('shop_id', 'in', $shop_id)->delete();

                // 删除门店品牌关联数据
                BrandShop::where('shop_id', 'in', $shop_id)->delete();

                // 删除门店商品数据
                $goods_id = GoodShop::where('shop_id', 'in', $shop_id)->column('good_id');
                GoodShop::where('shop_id', 'in', $shop_id)->delete();
                if ($goods_id){
                    // 删除商品
                    Goods::where('id', 'in', $goods_id)->delete();

                    // 删除图片集
                    GoodImages::where('good_id', 'in', $goods_id)->delete();

                    // 删除商品详情图片
                    GoodDetailImages::where('good_id', 'in', $goods_id)->delete();
                }
                Db::commit();
            } catch (Exception $e) {
                Db::rollback();
                $this->error('删除失败');
            }
        } else { // 排除
            $err = ShopImportLike::where('id', $ids)->where('pid', '<>', 0)->find();
            if (empty($err)) $this->error('请求错误');

            if ($err['is_import'] != 0) $this->error('请求错误');
            ShopImportLike::where('id', $ids)->setField('is_import', 3);
        }
        $this->success('操作成功');
    }

    /**
     * 导入错误数据
     * @param null $branch_no
     * @return string
     * @throws \think\exception\DbException
     */
    public function import_err($branch_no = null)
    {
        if (empty($branch_no)) $this->error('请求错误');
        $list_rows = $this->cur_limit;

        $import_err = ShopImportErr::where('branch_no', $branch_no)
            ->paginate($list_rows, false, ['query' => $this->request->get()]);

        $this->view->assign('list', $import_err);
        $this->view->assign('branch_no', $branch_no);
        return $this->view->fetch();
    }

    /**
     * 导入的相似数据
     * @param null $branch_no
     * @return string
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function import_like($branch_no = null)
    {
        $city = $this->city_id;
        if (empty($branch_no)) $this->error('请求错误');

        $import_like = ShopImportLike::where('branch_no', $branch_no)
            ->where('pid', 0)
            ->select()->toArray();
        $ids = [];
        if (!empty($import_like)) {
            foreach ($import_like as $v) {
                $ids[] = $v['id'];
            }
        }
        $like = (new ShopImportLike)->alias('si')
            ->join('shops s', 's.id=si.shop_id', 'left')
            ->join('shop_type_shop st', 'st.shop_id=s.id', 'left')
            ->join('shop_types t', 't.id=st.type_id', 'left')
            ->where('si.pid', 'in', $ids)
            ->field('si.*, s.name, s.region_id_3 as s_region_id_3, s.region_id_4 as s_region_id_4, s.address as s_address, s.status, s.data_source s_data_source, s.is_user_status, t.name as s_type_name')
            ->select()->toArray();
        foreach ($import_like as $k => $v) {
            foreach ($like as $k2 => $v2) {
                if ($v['id'] == $v2['pid']) {
                    $import_like[$k]['child'][] = $v2;
                }
            }
        }

        $region_list_3 =  Regions::where('parent_id', $city)->column('name', 'id');
        $region_list_4 = [];
        if (!empty($region_list_3)) {
            $region_list_4 = Regions::where('parent_id', 'in', array_keys($region_list_3))->column('name', 'id');
        }

        $this->view->assign('region_list_3', $region_list_3);
        $this->view->assign('region_list_4', $region_list_4);
        $this->view->assign('list', $import_like);
        $this->view->assign('branch_no', $branch_no);
        return $this->view->fetch();
    }

    /**
     * 错误信息
     * @return string
     * @throws \think\exception\DbException
     */
    public function err_info()
    {
        $id = $this->request->get('id');
        if (empty($id) || $id == null) $this->error('请求错误');

        $import_err = ShopImportErr::get($id);
        if (empty($import_err)) $this->error('没有数据');

        if (empty($import_err['err_msg'])) $this->error('没有错误');

        $err = json_decode($import_err['err_msg'], true);

        $this->view->assign('list', $err);
        return $this->view->fetch();
    }

    /**
     * 导出excel
     * @throws \PHPExcel_Exception
     * @throws \PHPExcel_Reader_Exception
     * @throws \PHPExcel_Writer_Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function export_data()
    {
        $ids = $this->request->get('ids');
        $branch_no = $this->request->get('branch_no');
        $w[] = ['id', 'in', $ids];
        if ($ids == null || $ids == '') {
            $w = [];
            $w[] = ['branch_no', '=', $branch_no];
        }

        $import_err = ShopImportErr::where($w)->select()->toArray();
        $shop_info = [];
        foreach ($import_err as $k => $v) {
            $shop_info[] = json_decode($v['shop_info'], true);
        }

        $PHPExcel = new PHPExcel();
        $PHPExcel->removeSheetByIndex();
        $i = 2;
        foreach ($shop_info as $key => $values) {
            $PHPExcel->createSheet();
            $PHPExcel->setActiveSheetIndex(0)
                ->setCellValue('A1', '门店名称')
                ->setCellValue('B1', '图片')
                ->setCellValue('C1', '省')
                ->setCellValue('D1', '市')
                ->setCellValue('E1', '区')
                ->setCellValue('F1', '地址')
                ->setCellValue('G1', '开户行')
                ->setCellValue('H1', '银行帐号')
                ->setCellValue('I1', '联系人')
                ->setCellValue('J1', '联系电话')
                ->setCellValue('K1', '门店座机')
                ->setCellValue('L1', '营业时间')
                ->setCellValue('M1', '主营品牌')
                ->setCellValue('N1', '分类')
                ->setCellValue('O1', '法人名称')
                ->setCellValue('P1', '营业面积')
                ->setCellValue('Q1', '规模');
            unset($values['create_time'], $values['branch_no'], $values['detail_images'], $values['images'], $values['shop_id'], $values['city_id'], $values['member_id'], $values['err'], $values['longitude'], $values['latitude'], $values['brand_ids'], $values['type_id_new'], $values['region_id_11'], $values['region_id_22'], $values['region_id_33'], $values['region_id_44']);
            $PHPExcel->getActiveSheet()->fromArray($values, null, 'A' . $i, true);
            $i++;
        }
        $PHPExcelWriter = PHPExcel_IOFactory::createWriter($PHPExcel, 'Excel2007');
        $filename = $branch_no . '错误数据报表';

        /* 生成到浏览器，提供下载 */
        ob_end_clean();  //清空缓存
        header("Pragma: public");
        header("Expires: 0");
        header("Cache-Control:must-revalidate,post-check=0,pre-check=0");
        header("Content-Type:application/force-download");
        header("Content-Type:application/vnd.ms-execl");
        header("Content-Type:application/octet-stream");
        header("Content-Type:application/download");
        header('Content-Disposition:attachment;filename=' . $filename . '.xlsx');
        header("Content-Transfer-Encoding:binary");
        $PHPExcelWriter->save('php://output');
        exit;
    }

    /**
     * 导入相似里面的数据
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function import_data()
    {
        $ids = $this->request->post('ids');

        if (ShopImportLike::where('id', 'in', $ids)->where('pid|is_import', '<>', 0)->find()) {
            $this->error('请选择正确的导入数据');
        }

        $import_like = ShopImportLike::where('id', 'in', $ids)->where('pid', 0)->select()->toArray();

        if (empty($import_like)) {
            $this->error('没有找到要导入的数据');
        }
        foreach ($import_like as $k => $v) {
            if (empty($v['shop_info'])) continue;
            $shop_info = json_decode($v['shop_info'], true);
            if (empty($shop_info)) continue;

            $time = date('Y-m-d H:i:s', time());
            $res_data = [
                'name' => $shop_info['name'],
                'address' => $shop_info['address'],
                'brand_id' => $shop_info['brand_ids'][0],
                'longitude' => $shop_info['longitude'],
                'latitude' => $shop_info['latitude'],
                'region_id_1' => isset($shop_info['region_id_11']['id']) ? $shop_info['region_id_11']['id'] : '',
                'region_id_2' => isset($shop_info['region_id_22']['id']) ? $shop_info['region_id_22']['id'] : '',
                'region_id_3' => isset($shop_info['region_id_33']['id']) ? $shop_info['region_id_33']['id'] : '',
                'region_id_4' => isset($shop_info['region_id_44']['id']) ? $shop_info['region_id_44']['id'] : '',
                'data_source' => 2,
                'status' => 0,
                'contacts' => $shop_info['contacts'],
                'phone_number' => $shop_info['phone_number'],
                'phone_number1' => $shop_info['phone_number1'],
                'create_time' => $time,
                'preview_image' => $shop_info['images'][0],
                'business_hours' => $shop_info['business_hours'],
                'acreage' => $shop_info['acreage'],
                'scale' => $shop_info['scale'],
            ];
            $connect = Config::pull('database');
            $connect['database'] = 'feigo';
            Db::startTrans();
            try {
                $shop_id = Db::connect($connect)->name('shops')->insertGetId($res_data);
                // 法人信息表
                Db::connect($connect)->name('shop_paperwork')->insert(['name' => $shop_info['legal_person'], 'shop_id' => $shop_id]);
                // 品牌
                $res_data = [];
                foreach ($shop_info['brand_ids'] as $v4) {
                    $res_data[] = ['brand_id' => $v4, 'shop_id' => $shop_id];
                }
                Db::connect($connect)->name('brand_shop')->insertAll($res_data);
                // 分类
                $res_data = ['type_id' => $shop_info['type_id_new'], 'shop_id' => $shop_id];
                Db::connect($connect)->name('shop_type_shop')->insert($res_data);
                // 图片
                $res_data = [];
                foreach ($shop_info['images'] as $v5) {
                    $res_data[] = ['image_url' => $v5, 'shop_id' => $shop_id];
                }
                Db::connect($connect)->name('shop_album')->insertAll($res_data);
                // 成功导入绑定批次号
                $res_data = [
                    'shop_id' => $shop_id,
                    'branch_no' => $v['branch_no'],
                    'shop_name' => $shop_info['name'],
                    'type_name' => $shop_info['type_id'],
                    'region_id_3' => isset($shop_info['region_id_33']['name']) ? $shop_info['region_id_33']['name'] : '',
                    'region_id_4' => isset($shop_info['region_id_44']['name']) ? $shop_info['region_id_44']['name'] : '',
                    'address' => $shop_info['address'],
                ];
                Db::connect($connect)->name('shop_import_suc')->insert($res_data);
                // 相似的修改为已导入
                Db::connect($connect)->name('shop_import_like')->where('id', $v['id'])->setField('is_import', 1);
                Db::commit();
            } catch (Exception $e) {
                Db::rollback();
                Log::info($e->getMessage());
            }
        }
        $this->success('导入成功');
    }
}
