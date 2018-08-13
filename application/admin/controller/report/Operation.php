<?php
namespace app\admin\controller\report;

use app\common\model\feigo\ProcessShop;
use app\common\controller\Backend;
use app\common\model\feigo\Regions;
use PHPExcel_IOFactory;
use PHPExcel;

class Operation extends Backend
{
    protected $relationSearch = true;
    protected $layout = 'default2';

    protected $type = [
        1 => '校验', 2 => '修改', 3 => '新增', 4 => '不存在'
    ];

    public function index()
    {
        $list_rows = $this->cur_limit;
        $keyword = request()->get('keyword');
        $region_id_1 = request()->get('region_id_1');
        $region_id_2 = request()->get('region_id_2');
        $type = request()->get('type');
        $time = request()->param('from_time');

        $w = [];
        if ($keyword != null && !empty($keyword)) $w[] = ['mp.nickname|m.mobile', 'like', '%' . $keyword . '%'];
        if ($region_id_1 != null) $w[] = ['p.region_id_1', '=', $region_id_1];
        if ($region_id_2 != null) $w[] = ['p.region_id_2', '=', $region_id_2];
        if ($type > 0) $w[] = ['p.type', '=', $type];
        $str = betweenTime($time, 'p.create_time');
        if (!empty($str)) $w[] = $str;

        $list = (new ProcessShop())->alias('p')
            ->with('region1')
            ->with('region2')
            ->with('region3')
            ->join('member_processor mp', 'mp.member_id=p.member_id')
            ->join('members m', 'm.id=mp.member_id')
            ->where($w)
            ->field('p.*, mp.nickname')
            ->order('p.create_time', 'desc')
            ->group('p.shop_id, p.type')
            ->paginate($list_rows, false, ['query' => $this->request->get()]);

        $region_list_1 = Regions::where('level_type', 1)->column('name', 'id');
        $this->view->assign('list', $list);
        $this->view->assign('type', $this->type);
        $this->view->assign('region_list_1', $region_list_1);
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

        $import_err = (new ProcessShop())->alias('p')
            ->with('region1')
            ->with('region2')
            ->with('region3')
            ->join('member_processor mp', 'mp.member_id=p.member_id')
            ->join('members m', 'm.id=mp.member_id')
            ->where('p.id', 'in', $ids)
            ->field('p.*, mp.nickname')
            ->order('p.create_time', 'desc')
            ->select()->toArray();

        $i = 2;
        $type = $this->type;

        $PHPExcel = new PHPExcel();
        $PHPExcel->removeSheetByIndex();
        $PHPExcel->createSheet();
        $PHPExcel->setActiveSheetIndex(0)
            ->setCellValue('A1', '编号')
            ->setCellValue('B1', '门店名称')
            ->setCellValue('C1', '门店分类')
            ->setCellValue('D1', '省')
            ->setCellValue('E1', '市')
            ->setCellValue('F1', '区')
            ->setCellValue('G1', '门店地址')
            ->setCellValue('H1', '操作类型')
            ->setCellValue('I1', '操作人')
            ->setCellValue('J1', '操作时间');
        foreach ($import_err as $key => $values) {
            $v = [
                'id' => $values['id'],
                'shop_name' => $values['shop_name'],
                'shop_type' => $values['shop_type'],
                'region_name_1' => $values['region1'][0]['name'] ?? '',
                'region_name_2' => $values['region2'][0]['name'] ?? '',
                'region_name_3' => $values['region3'][0]['name'] ?? '',
                'address' => $values['shop_type'],
                'type' => $type[$values['type']] ?? '',
                'nickname' => $values['nickname'],
                'create_time' => $values['create_time'],
            ];
            $PHPExcel->getActiveSheet()->fromArray($v, null, 'A' . $i, true);
            $i++;
        }

        // 设置宽度
        $PHPExcel->getActiveSheet()->getColumnDimension('J')->setWidth(18);
        // 设置居中
        $PHPExcel->getActiveSheet()->getStyle('A1:J' . $i)->getAlignment()->setHorizontal(\PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
        //$PHPExcel->getActiveSheet()->getStyle('A2:D2')->getAlignment()->setHorizontal(\PHPExcel_Style_Alignment::HORIZONTAL_CENTER);

        //设置填充的样式和背景色
        $PHPExcel->getActiveSheet()->getStyle( 'A1:J1')->getFill()->setFillType(\PHPExcel_Style_Fill::FILL_SOLID);
        $PHPExcel->getActiveSheet()->getStyle( 'A1:J1')->getFill()->getStartColor()->setARGB('FF808080');

//            //边框样式
//            $styleArray = [
//                'borders' => [
//                    'allborders' => [
//                        //'style' => PHPExcel_Style_Border::BORDER_THICK,//边框是粗的
//                        'style' => \PHPExcel_Style_Border::BORDER_THIN,//细边框
//                        'color' => array('argb' => '0xCC000000'),
//                    ]
//                ]
//            ];
//            $PHPExcel->getActiveSheet()->getStyle('A1:J' . $i)->applyFromArray($styleArray);

        $PHPExcelWriter = PHPExcel_IOFactory::createWriter($PHPExcel, 'Excel2007');
        $filename =  '校验门店数据明细' . date('YmdHis');

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
}
