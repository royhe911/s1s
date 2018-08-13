<?php

namespace app\admin\controller\auth;

use app\admin\model\AuthRule as AuthRuleModel;
use app\common\controller\Backend;
use fast\Tree;
use think\facade\Cache;

/**
 * 规则管理
 *
 * @icon fa fa-list
 * @remark 规则通常对应一个控制器的方法,同时左侧的菜单栏数据也从规则中体现,通常建议通过控制台进行生成规则节点
 */
class Rule extends Backend
{

    protected $model = null;
    protected $rulelist = [];
    protected $multiFields = 'ismenu,status';

    public function initialize()
    {
        parent::initialize();
        $this->model = model('AuthRule');
        // 必须将结果集转换为数组
        $ruleList = $this->model->order('weigh', 'desc')->select()->toArray();
        foreach ($ruleList as $k => &$v)
        {
            $v['title'] = __($v['title']);
            $v['remark'] = __($v['remark']);
        }
        unset($v);
        Tree::instance()->init($ruleList);
        $this->rulelist = Tree::instance()->getTreeList(Tree::instance()->getTreeArray(0), 'title');
        $ruledata = [0 => __('None')];
        foreach ($this->rulelist as $k => &$v)
        {
            if (!$v['ismenu'])
                continue;
            $ruledata[$v['id']] = $v['title'];
        }
        $this->view->assign('ruledata', $ruledata);
    }

    /**
     * 查看
     */
    public function index()
    {
        if ($this->request->isAjax())
        {
            $list = $this->rulelist;
            $total = count($this->rulelist);

            $result = array("total" => $total, "rows" => $list);

            return json($result);
        }
        return $this->view->fetch();
    }

    /**
     * 添加
     */
    public function add()
    {
        if ($this->request->isPost())
        {
            $params = $this->request->post("row/a", [], 'strip_tags');
            if ($params)
            {
                if (!$params['ismenu'] && !$params['pid'])
                {
                    $this->error(__('The non-menu rule must have parent'));
                }
                //这里需要针对name做唯一验证
                $ruleValidate = new \app\admin\validate\AuthRule;
                $ruleValidate->rule([
                    'name' => 'require|format|unique:AuthRule,name,' 
                ]);
                if (!$ruleValidate->check($params)) {
                    $this->error($ruleValidate->getError());
                }
                $result = $this->model->save($params);
                if ($result === FALSE)
                {
                    $this->error($this->model->getError());
                }
                Cache::rm('__menu__');
                $this->success();
            }
            $this->error();
        }
        return $this->view->fetch();
    }

    /**
     * 编辑
     */
    public function edit($ids = NULL)
    {
        $row = $this->model->get(['id' => $ids]);
        if (!$row)
            $this->error(__('No Results were found'));
        if ($this->request->isPost())
        {
            $params = $this->request->post("row/a", [], 'strip_tags');
            if ($params)
            {
                if (!$params['ismenu'] && !$params['pid'])
                {
                    $this->error(__('The non-menu rule must have parent'));
                }
                //这里需要针对name做唯一验证
                $ruleValidate = new \app\admin\validate\AuthRule;
                $ruleValidate->rule([
                    'name' => 'require|format|unique:AuthRule,name,' . $row->id,
                ]);
                if (!$ruleValidate->check($params)) {
                    $this->error($ruleValidate->getError());
                }
                $result = $row->save($params);
                if ($result === FALSE)
                {
                    $this->error($row->getError());
                }
                Cache::rm('__menu__');
                $this->success();
            }
            $this->error();
        }
        $this->view->assign("row", $row);
        return $this->view->fetch();
    }

    /**
     * 删除
     */
    public function del($ids = "")
    {
        if ($ids)
        {
            $delIds = [];
            foreach (explode(',', $ids) as $k => $v)
            {
                $delIds = array_merge($delIds, Tree::instance()->getChildrenIds($v, TRUE));
            }
            $delIds = array_unique($delIds);
            $count = $this->model->where('id', 'in', $delIds)->delete();
            if ($count)
            {
                Cache::rm('__menu__');
                $this->success();
            }
        }
        $this->error();
    }

    /**
     * 自动添加权限规则
     * @throws \ReflectionException
     * @Author: zhuhaijun
     * @Date: 2018/4/13
     */
    public function auto_add()
    {
        $forgetClassList = $this->request->param('forgetClassList');//过滤的类名,暂未实现
        $forgetMethodsList = $this->request->param('forgetMethodsList');//过滤的方法名,暂未实现
        $filePath = $this->request->param('filePath', null);//选择的目录
        $namespace = $this->request->param('namespace', null);//命名空间
        $insert = $this->request->param('insert', 0);//是否直接插入数据库
        $isChildren = $this->request->param('children', 0);//是否只显示已有的数据的子数据

        if (empty($filePath)) $filePath = dirname(__DIR__);
        if (empty($namespace)) $namespace = dirname(__NAMESPACE__);

        $forgetClassList = ['Admin', 'Adminlog', 'Group'];//过滤的类名
        $forgetMethodsList = ['initialize'];//过滤的方法名

        $classInfoArr = $this->getClassList($filePath, null, $namespace, $forgetClassList);
        $methodsArr = $this->getMethodsList($classInfoArr, $forgetMethodsList);

        $sqlDataArr = [];
        foreach ($methodsArr as $value) {
            $sqlDataArr[] = $this->getSqlData($value);
        }

        if (empty($sqlDataArr)) die('没有查到数据！');

        $db = new AuthRuleModel;

        $data = $db->column('name', 'id');

        foreach ($sqlDataArr as $key => &$value) {
            if (array_search($value['name'], $data) !== false) {
                unset($sqlDataArr[$key]);
                continue;
            }
            $_key = array_search($value['pname'], $data);
            if ($_key !== false) {
                $value['pid'] = $_key;
            } else {
                if ($isChildren == 1){
                    unset($sqlDataArr[$key]);
                    continue;
                }
            }
            unset($value['pname']);
        }

        sort($sqlDataArr);

        echo '需要更新的规则数据列表：<br/><br/>';

        dump($sqlDataArr); echo '<br/><br/>';

        if (empty($sqlDataArr)) echo('没有需要更新的规则！<br/><br/>');

        if ($insert != 1) die('请设置插入参数insert为1！否则只能查看需要新增的规则列表！<br/><br/>');

        $bool = $db->insertAll($sqlDataArr);

        die('插入结果：' . ($bool ? '成功！': '失败！') . '<br/><br/>');
    }

    /**
     * 递归获取要查询的类文件数据 (返回类文件信息列表)
     * @param null $filePath 初始文件路径
     * @param null $fileName 文件子文件夹路径
     * @param null $namespace 命名空间
     * @param null $forgetClassList 需要过滤的类名列表
     * @return array|null
     * @Author: zhuhaijun
     * @Date: 2018/4/13
     */
    private function getClassList($filePath = null, $fileName = null, $namespace = null, $forgetClassList = null)
    {
        static $classInfoArr;

        if (!empty($fileName)) {
            $namespace .= "\\$fileName";
            $filePath .= "\\$fileName";
        }


        $fileArr = scandir($filePath);
        $fileArrLength = count($fileArr);


        if ($fileArrLength <= 2) return null;

        if (empty($classInfoArr)) $classInfoArr = [];

        for ($i = 2; $i < $fileArrLength; $i++) {

            $file = $fileArr[$i];

            $className = strstr($file, '.', true);

            if ($className === false) {
                $newFileName = empty($fileName) ? $file : "$fileName\\$file";

                if (is_dir("$filePath\\$file")) $this->getClassList($filePath, $newFileName, $namespace, $forgetClassList);
            } else {
                if (!empty($forgetClassList)) if (array_search($className, $forgetClassList) !== false) continue;

                $classpath = $filePath . "\\$className.php";

                if (!is_file($classpath)) continue;

                $classInfoArr[] = array(
                    'className' => $className,
                    'namespace' => $namespace,
                    'fileName' => $fileName
                );
            }
        }

        return $classInfoArr;
    }

    /**
     * 获取
     * @param array $classList 类名列表
     * @param null $forgetMethodsList 需要过滤的方法名列表
     * @param string $classModify 方法名修饰
     * @return array|null
     * @throws \ReflectionException
     * @Author: zhuhaijun
     * @Date: 2018/4/13
     */
    private function getMethodsList($classList, $forgetMethodsList = null, $classModify = 'public')
    {
        if (!is_array($classList) || empty($classList)) return null;

        static $methodsArr;

        if (empty($methodsArr)) $methodsArr = [];

        foreach ($classList as $value) {
            $className = $value['className'];
            $namespace = $value['namespace'];
            $classNameStr = $namespace . '\\' . $className;

            $class = new \ReflectionClass($classNameStr);
            $methodsObject = $class->getMethods();

            foreach ($methodsObject as $v) {
                if ($v->class !== $classNameStr) continue;

                $methodName = $v->name;

                if (!empty($forgetMethodsList)) if (array_search($methodName, $forgetMethodsList) !== false) continue;

                $methodInfo = new \ReflectionMethod($classNameStr, $methodName);

                if (!empty($classModify)){
                    $classModifyStr = 'is' . ucfirst($classModify);
                    if (!$methodInfo->$classModifyStr()) continue;
                }

                $methodsArr[] = [
                    'fileName' => $value['fileName'],
                    'className' => $className,
                    'methodName' => $methodName,
                    'docComment' => $methodInfo->getDocComment()
                ];
            }

        }

        return $methodsArr;
    }

    /**
     * 单一转化为该环境的插入数据
     * @param null $data
     * @return array|null
     * @Author: zhuhaijun
     * @Date: 2018/4/13
     */
    private function getSqlData($data = null)
    {

        if (!is_array($data) || empty($data)) return null;

        $str_replace = [
            'A' => '_a',
            'B' => '_b',
            'C' => '_c',
            'D' => '_d',
            'E' => '_e',
            'F' => '_f',
            'G' => '_g',
            'H' => '_h',
            'I' => '_i',
            'J' => '_j',
            'K' => '_k',
            'L' => '_l',
            'M' => '_m',
            'N' => '_n',
            'O' => '_o',
            'P' => '_p',
            'Q' => '_q',
            'R' => '_r',
            'S' => '_s',
            'T' => '_t',
            'U' => '_u',
            'V' => '_v',
            'W' => '_w',
            'X' => '_x',
            'Y' => '_y',
            'Z' => '_z',
        ];

        $fileName = $data['fileName'];
        if (empty($fileName)){
            $fileName = '';
        }else{
            if (strstr('/', $fileName)){
                $fileNameArr = explode('/', $fileName);
                foreach ($fileNameArr as &$value) $value = ltrim(strtr($value, $str_replace), '_');
                $fileName = implode('/', $fileNameArr) . '/';
            }else{
                $fileName = ltrim(strtr($fileName, $str_replace), '_') . '/';
            }
        }

        $className = ltrim(strtr($data['className'], $str_replace), '_');

        $pname = $fileName . $className;
        $name = $pname . '/' . $data['methodName'];

        if (empty(trim($data['docComment']))) {
            $title = $name;
        } else {
            $docInfoArr = explode(PHP_EOL, $data['docComment']);
            $title = strstr(trim($docInfoArr[1]), ' ');
        }

        $time = time();
        return array(
            'type' => 'file',
            'pid' => 0,
            'name' => $name,
            'title' => $title,
            'icon' => 'fa fa-circle-o',
            'remark' => $title,
            'ismenu' => 0,
            'createtime' => $time,
            'updatetime' => $time,
            'weigh' => '0',
            'status' => 'normal',
            'pname' => $pname
        );
    }
}
