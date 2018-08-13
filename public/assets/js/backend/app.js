
/**
 * 获取分类（联动）
 * @param objId
 * @param pid
 * @param t
 * @param id
 */
if(typeof fun=="undefined"){
    var fun = {};
}
function get_type (objId, pid, t, id,content) {
    $('#'+objId).empty();
    if (t<1) {
        if(typeof content=="undefined") {
            content = "请选择";
        }
        $('#'+objId).html('<option value="">'+content+'</option>');
        if(pid == 0) return;
    }
    var html = [];
    $.post("/article/article/get_city", {pid: pid}, function (data) {
        html.push('<option value="">请选择</option>');
        if (data.code == 1 && data.data.length>0) {
            var opts = null;
            for (var i=0; i<data.data.length; i++) {
                opts = data.data[i];
                html.push('<option value="'+opts.id+'" '+((id==opts.id)?'selected':'')+'>'+opts.name+'</option>');
            }
        }
        $('#'+objId).html(html.join(''));
    }, 'json');
}


/**
 * 检查浏览器是否支持某属性
 * @param attrName
 * @param attrValue
 * @returns {boolean}
 */
function attr_support(attrName, attrValue) {
    try {
        var element = document.createElement('div');
        if (attrName in element.style) {
            element.style[attrName] = attrValue;
            return element.style[attrName] === attrValue;
        } else {
            return false;
        }
    } catch (e) {
        return false;
    }
}

/**
 * 弹出层
 * @param title 层标题
 * @param url 层链接(opt.type=2|默认)或者HTML内容(opt.type=1)
 * @param opt 选项 {w:WIDTH('800px|80%'),h:HEIGHT('600px|80%'),type:1|2,fn:CALLBACK(回调函数),confirm:BOOL(关闭弹层警告)}
 */
function layer_open(title, url, opt) {
    if (typeof opt === "undefined") opt = {nav: true};
    w = opt.w || "80vw";
    h = opt.h || "80vh";
    // 不支持vh,vw单位时采取js动态获取
    if (!attr_support('height', '10vh')) {
        w = w.replace(/([\d\.]+)(vh|vw)/, function (source, num, unit) {
            return $(window).width() * num / 100 + 'px';
        });
        h = h.replace(/([\d\.]+)(vh|vw)/, function (source, num, unit) {
            return $(window).height() * num / 100 + 'px';
        });
    }
    return layer.open({
        type: opt.type || 2,
        area: [w, h],
        fix: false, // 不固定
        maxmin: true,
        shade: 0.4,
        title: title,
        content: url,
        success: function (layero, index) {
            if (typeof opt.confirm !== "undefined" && opt.confirm === true) {
                layero.find(".layui-layer-close").off("click").on("click", function () {
                    layer.alert('您确定要关闭当前窗口吗？', {
                        btn: ['确定', '取消'] //按钮
                    }, function (i) {
                        layer.close(i);
                        layer.close(index);
                    });
                });
            }
            // 自动添加面包屑导航
            if (true === opt.nav) {
                layer.getChildFrame('#nav-title', index).html($('#nav-title').html() + ' <span class="c-gray en">&gt;</span> ' + $('.layui-layer-title').html());
            }
            if (typeof opt.fn === "function") {
                opt.fn(layero, index);
            }
        }
    });
};

//单选框禁止有选择的插件方法
//传入对象
//对象is_stop是判断值1或者0 ,传后台的
//对象os_this是jq包裹的dom
//对象id是id值 ,传后台的
//is_stop插件开始位置
function is_stop(obj) {
    function get_post_1(default_obj,msg) {
        $.post(default_obj.url, msg, function (data) {
            if (data.code == 1) {
                layer.msg(data.msg, {
                    time: 500 //2秒关闭（如果不配置，默认是3秒）
                }, function () {
                    window.location.reload();
                });
            } else {
                layer.alert(data.msg, {icon: 5});
            }
        });
    }
    console.log(obj.changeState);
    function get_post_2(default_obj,msg) {
        $.post(default_obj.url, msg, function (data) {
            if (data.code == 1) {
                layer.msg(data.msg, {
                    time: 500 //2秒关闭（如果不配置，默认是3秒）
                }, function () {
                    i = os_this.children();
                    // debugger;
                    if (is_stop==1) {
                        i.removeClass("fa-toggle-off");
                        i.addClass("fa-toggle-on");
                        os_this.attr({"data-isStop": 0, "title": default_obj.show_stop});
                    } else {
                        i.removeClass("fa-toggle-on");
                        i.addClass("fa-toggle-off");
                        os_this.attr({"data-isStop": 1, "title": default_obj.show_start});
                    }
                    console.log(typeof changeState);
                    if (typeof changeState == 'function'){
                        changeState(default_obj);
                    }
                });
            } else {
                layer.alert(data.msg, {icon: 5});
            }
        });
    }
    var default_obj = {
        is_stop:0,
        os_this:"",
        id:"",
        info_msg:"请选择要操作的类容",
        url:"",
        show_stop:"禁用",
        show_start:"启用",
        prompt:false,
        prompt_info:"确认该操作吗？"
    };
    if(obj&&typeof obj=="object") {
        default_obj= $.extend(default_obj,obj)
    }else{
        return;
    }
    var os_this = default_obj.os_this;
    var ids = default_obj.id, i;
    var is_stop = default_obj.is_stop;
    var is_show_confirm = false;
    var is_ids = false;
    if(os_this) {
        var msg = {status: default_obj.is_stop};
        if (ids== 0) {
            ids = get_select_all();
            is_ids = true;
        }else{
            if (ids == '') {
                layer.alert(default_obj.info_msg, {icon: 5});
                return false;
            }
        }
        msg.ids = ids;
        if(default_obj.prompt=="all") {
            is_show_confirm = true;
        }else if(default_obj.prompt=="open"&&default_obj.is_stop!=0) {
            is_show_confirm = true;
        }else if(default_obj.prompt=="close"&&default_obj.is_stop==0) {
            is_show_confirm = true;
        }else{
            if(is_ids) {
                get_post_1(default_obj, msg);
            }else{
                get_post_2(default_obj, msg);
            }
        }
        if(is_show_confirm){
            layer.confirm(default_obj.prompt_info, {icon: 3, title:'提示'}, function(){
                if(is_ids) {
                    get_post_1(default_obj, msg);
                }else{
                    get_post_2(default_obj, msg);
                }
            });
        }
    }
}
//is_stop插件结束位置

//input的全选与取消的插件
//par_dom为选择框的总开关
//child_dom为选择框的checkbox的集合
//全选与取消的插件开始的位置
function switch_change(par_dom,child_dom) {
    par_dom.on("click",function () {
        if (this.checked){
            child_dom.each(function(){
                $(this).prop("checked",true);
            });
        } else {
            child_dom.each(function() {
                $(this).prop("checked",false);
            });
        }
    });
    child_dom.on("click",function() {
        if (this.checked){
            for(var i=0;i<child_dom.length;i++) {
                if(!child_dom[i].checked) {
                    return;
                }
            }
            par_dom.prop("checked",true);
        }else{
            par_dom.prop("checked",false);
        }
    })
}
//上一个函数可单独使用
$(function () {
    switch_change($("#btSelectAll"), $("input[name='btSelectItem']:checkbox"));
    $("#btSelectAll,input[name='btSelectItem']:checkbox").change(function () {
        if ($("input[name='btSelectItem']:checkbox").is(':checked')) {
            $('.btn-disabled').removeClass('disabled');
        } else {
            $('.btn-disabled').addClass('disabled');
        }
    });
});
//全选与取消的插件结束的位置

/**
 * 获取选中的ids集合
 * @returns {string}
 */
function get_select_all() {
    var ids = [];
    $("input[name='btSelectItem']:checkbox").each(function(){
        if ($(this)[0].checked) ids.push($(this).val());
    });
    return ids.join(',');
}
//错误弹出框提示
fun.greyPrompt = function (msg) {
    if(!msg) {
        msg="您的操作有误"
    }
    var html = '<div class="dialog" data-greyprompt="modifyPwd">' +
        '<span class="radiusFour">'+msg+'</span>' +
        '</div>';
    $("body").append(html);
    var modifyPwd = $("[data-greyPrompt='modifyPwd']");
    setTimeout(function () {
        modifyPwd.remove();
    }, 2000);
};

//当字符全长度大于文本长度的时候显示影藏显示点点点点
fun.txt_ellipsis=function(element,line_num) {
    var txt = element.attr("data-title");
    if(typeof (txt)=="undefined") {
        txt = "";
    }
    var font_w = parseInt(element.css("font-size")) + parseInt(element.css("letter-spacing"));
    var padding_w=parseInt(element.css("padding-right"))+parseInt(element.css("padding-left"));
    var ele_w = element.width()-padding_w;
    var lh = parseInt(element.css("line-height"));
    var ele_h = line_num * lh;
    var old_html = "",new_html="",new_ele_h="";
    var index = parseInt(element.css("text-indent"));
    var index_num = Math.round(index / font_w);
    var con_font_num =12;
    if(ele_w!=0) {
        con_font_num = Math.floor(ele_w / font_w) * line_num-2-index_num;
    }
    if(txt.length<=con_font_num) {
        element.html(txt);
    }else{
        for(var i=con_font_num;i<txt.length;i++) {
            new_html = txt.substring(0, i) + "...";
            old_html = txt.substring(0, i-1) + "...";
            element.html(new_html);
            new_ele_h = element.height();
            if(new_ele_h>ele_h) {
                element.html(old_html);
                break;
            }
        }
    }
};

fun.inputNumber= function (elem,param) {
    var def_obj = {
        def_obj:0,
        id_minus:false
    };
    if(typeof param=="undefined") {
    }else if(typeof param=="number") {
        def_obj.def_obj = param;
    }else if(typeof param=="object") {
        $.extend(def_obj, param);
    }else if(typeof param=="boolean") {
        def_obj.id_minus = param;
    }else{
        console.error("您传入的参数有误,请确认传入的param为数字,对象,或者boolean")
    }
    var digits = def_obj.def_obj;
    var val = elem.val(),newVal="",boolOne=true,num,minus;
    if(def_obj.id_minus) {
        if(val[0]=="-") {
            minus = "-";
            val = val.substring(1);
            if(val[0]=="-") {
                val = val.substring(1);
            }
            if(val=="") {
                elem.val("-");
                return;
            }
        }
    }else{
        if(val[0]=="-") {
            val = val.substring(1);
        }
    }
    var new_digit = val.length - val.indexOf(".")-1;
    if(isNaN(parseInt(val))) {
        num = 0;
        elem.val("");
    }else{
        if(!(digits==0)) {
            if(val.indexOf(".")!=-1){
                if(val.indexOf(".")==(val.length-1)) {
                    elem.val(val);
                    return val;
                }else if(val[val.length-1]==0) {
                    if(!(new_digit<digits)) {
                        val=val.substring(0, val.length - 1)
                    }
                    elem.val(val);
                    return val;
                }else{
                    for(var i=0;i<val.length;i++) {
                        if(val[i]==".") {
                            if(boolOne) {
                                boolOne = false;
                                newVal = newVal + val[i];
                            }else{
                                elem.val(newVal);
                                num = newVal;
                                return num;
                            }
                        }else{
                            newVal = newVal + val[i];
                        }
                    }
                }
            }
            var temp = Math.pow(10,digits);
            val = parseFloat(val);
            val = val * temp;
            num = Math.floor(val);
            num = num / temp;
        }else{
            num = Math.floor(parseFloat(val));
        }
        if(def_obj.id_minus) {
            num = "-" + num;
        }
        elem.val(num);
    }
    return num;
};

//添加方法选择名字把添加按钮 开始
function add_check_name(obj,element_parent,elements) {
    if(obj&&obj.length!=0) {
        var html = '';
        $.each(obj, function (index,val) {
            if(val.id!=100000) {
                html += '<div class="btn-group dropup" style="border: 1px solid #34c191;padding: 6px 12px;margin-right: 5px;margin-bottom: 10px;border-radius: 4px;">' +
                    '<span>'+val.name+'</span>' +
                    '<i class="iconfont icon-cha1 ac-delete" data-id="'+val.id+'" data-index="'+index+ '" data-parent_id= "' + val.parent_id + '" style="color: #dddddd;margin-left: 10px;cursor: pointer;"></i>' +
                    '</div>'
            }
        });
        element_parent.find(".ac-box").empty().append(html);
        var attr_id;
        if(elements) {
            $.each(elements, function (index,val) {
                attr_id = $(val).attr("data-id");
                if(JSON.stringify(obj).indexOf(':'+attr_id+',')!=-1||JSON.stringify(obj).indexOf('"'+attr_id+'"')!=-1) {
                    $(val).addClass("os-checkbox-focus");
                }
            });
        }
    }else{
        return "传入的对象错误";
    }
}
//添加方法选择名字添加按钮 结束

//select的联动方法 ,开始
function iteration_linkage(obj,judge_obj) {
    var def_obj = {
        cur_id:"",
        spare_id:'',
        cur_element_str: '',
        spare_element_str:'',
        is_replace:false,
        replace_name:"",
        is_fun_1:false,
        spare_fun:''
    };
    if(typeof judge_obj=="object") {
        def_obj=$.extend(def_obj, judge_obj);
    }else{
        console.error("第二个参数错误");
        return;
    }
    if(def_obj.cur_element_str=='') {
        console.error("请在第二个参数输入dom的id");
        return;
    }
    var ele = $("#" + def_obj.cur_element_str);
    if(typeof obj=="object") {
        var option_html = "",val_id;
        if(def_obj.is_replace) {
            ele.empty();
            option_html='<option value="">' + def_obj.replace_name + '</option>'
        }
        $.each(obj, function (index, val) {
            var is_checked,val_id = val.id,cur_ele,spare_ele=[],cur_id,spare_ele_id=[],cur_fun,spare_fun=[];
            if (val_id == def_obj.cur_id) {
                console.log(val.level_type);
                is_checked = 'selected';
                if(def_obj.spare_element_str.length>1) {
                    cur_ele = def_obj.spare_element_str.shift();
                    spare_ele = def_obj.spare_element_str;
                }else if(def_obj.spare_element_str.length==1){
                    cur_ele = def_obj.spare_element_str[0];
                }
                if(def_obj.spare_id.length>1) {
                    cur_id = def_obj.spare_id.shift();
                    spare_ele_id = def_obj.spare_id;
                }else if(def_obj.spare_id.length==1){
                    cur_id = def_obj.spare_id[0];
                }else{
                    cur_id = '';
                }
                if(def_obj.spare_fun.length>1) {
                    cur_fun = def_obj.spare_fun.shift();
                    spare_fun = def_obj.spare_fun;
                }else if(def_obj.spare_fun.length==1){
                    cur_fun = def_obj.spare_fun[0];
                }else{
                    cur_fun = false;
                }
                if(val.child) {
                    iteration_linkage(val.child,{cur_element_str:cur_ele,spare_element_str:spare_ele,cur_id:cur_id,spare_id:spare_ele_id,is_fun_1:cur_fun,spare_fun:cur_fun});
                    if(def_obj.is_fun_1) {
                        show_shops(def_obj.cur_id,val.level_type)
                    }
                }
            } else {
                is_checked = '';
            }
            option_html = option_html + '<option value=' + val_id + ' ' + is_checked + '>' + val.name + '</option>';
        });
        ele.append(option_html);
    }
}
//select的联动方法 ,结束

//公共删除数据方法
function del_data(obj) {
    var def_obj = {
        ids: '',
        url: '',
        is_refresh: true,
        parent_item:'',
        info:"确认删除活动吗？"
    };
    if(typeof obj=="object") {
        $.extend(def_obj, obj);
    }
    var ids = def_obj.ids;
    if(ids=="") {
        console.error("您未传入ids值,请传入ids值");
    }
    if (ids == 0) {
        ids = get_select_all();
    }
    layer.confirm(def_obj.info, {icon: 3, title:'提示'}, function(){
        $.post(def_obj.url, {ids: ids}, function (data) {
            if (data.code == 1) {
                layer.msg(data.msg, {
                    time: 2000 //2秒关闭（如果不配置，默认是3秒）
                }, function () {
                    if(def_obj.is_refresh) {
                        window.parent.location.reload();
                    }else{
                        if(def_obj.parent_item=='') {
                            console.error("请传入您的dom");
                        }else{
                            def_obj.parent_item.remove();
                        }
                    }
                });
            } else {
                layer.alert(data.msg, {icon: 5});
            }
        }, 'json')
    });
}


//失去焦点,调用url排序成功
function sort_edit(obj) {
    var def_obj = {
        cur_class: "ac-sort",
        cur_id:"data-id",
        cur_data_val:"data-val",
        parent_class:"ac-tr",
        event: "blur",
        is_link:false,
        link_attr:"data-link-class",
        is_refresh:false,
        url:''
    };
    if(obj&&typeof obj=="object") {
        def_obj= $.extend(def_obj,obj)
    }
    $("." + def_obj.cur_class).on(def_obj.event, function () {
        var msg = {};
        var os_this = $(this);
        var data_val = $(this).val();
        msg.id= $(this).attr(def_obj.cur_id);
        var data_sort = $(this).attr(def_obj.cur_data_val);
        if(data_val=="") {
            data_val = 1;
            $(this).val(1)
        }
        if (data_val == data_sort) {
            return;
        }
        msg.sort = data_val;
        $.post(def_obj.url, msg, function (data) {
            if (data.code == 1){
                os_this.attr(def_obj.cur_data_val, data_val);
                var all_class = $("." + def_obj.cur_class);
                if(all_class.length>30||def_obj.is_refresh) {
                    layer.msg("编辑成功，一秒后刷新当前页面", {
                        time: 800 //2秒关闭（如果不配置，默认是3秒）
                    }, function () {
                        window.parent.location.reload();
                    });
                }else{
                    var for_val,for_val_num;
                    var cur_dom = os_this.parents("." + def_obj.parent_class);
                    for(var i=-1;i<all_class.length;i++) {
                        for_val = all_class[i];
                        for_val_num = Number($(for_val).attr(def_obj.cur_data_val));
                        if(for_val_num>data_val) {
                            $(for_val).parents("."+def_obj.parent_class).before(cur_dom);
                            if(def_obj.is_link) {
                                var attr_link = cur_dom.attr(def_obj.link_attr);
                                cur_dom.after($("." + attr_link));
                            }
                            break;
                        }
                    }
                    if(i==(all_class.length)) {
                        $(all_class[all_class.length-1]).parents("."+def_obj.parent_class).after(cur_dom);
                        if(def_obj.is_link) {
                            var attr_link_1 = cur_dom.attr(def_obj.link_attr);
                            cur_dom.after($("." + attr_link_1));
                        }
                    }
                }
            } else {
                layer.msg(data.msg, {
                    time: 3000 //2秒关闭（如果不配置，默认是3秒）
                });
            }
        }, 'json')
    });
}


(function($){
    //这是复选框的插件
    //input属性必须为checkout
    //input 必须有父元素框
    //父元素只能有一个子元素input
    //父元素必须有mk-checkbox,的class值
    //父元素的属性data-check的值为input选中值
    //父元素的属性data-nocheck的值为input非选中值
    //父元素的属性data-left-name的值为选择框左边的值
    //父元素的属性data-nocheck的值为选择框右边的值
    if(typeof($.fn.lc_switch) != 'undefined') {return false;} // prevent dmultiple scripts inits
    $.fn.lc_switch = function() {
        $.fn.lcs_on = function() {
            var $wrap = $(this).parents('.mk-checkbox');
            var attr = $wrap.attr("data-check");
            if(typeof attr=="undefined") {
                attr = 0;
            }
            $wrap.find('input').val(attr);
            $wrap.find('.lcs_switch').removeClass('lcs_off').addClass('lcs_on');
            return true;
        };
        // set to OFF
        $.fn.lcs_off = function() {
            var $wrap = $(this).parents('.mk-checkbox');
            var attr = $wrap.attr("data-nocheck");
            if(typeof attr=="undefined") {
                attr = 1;
            }
            $wrap.find('input').val(attr);
            $wrap.find('.lcs_switch').removeClass('lcs_on').addClass('lcs_off');
        }
        // construct
        return this.each(function(){
            if($(this).parent().hasClass("mk-checkbox")) {
                var parent = $(this).parent();
                var attr = parent.attr("data-check");
                if(typeof attr=="undefined") {
                    attr = 0;
                }
                $(this).val(attr);
                var on_text = parent.attr("data-left-name");
                var off_text = parent.attr("data-right-name");
                var ckd_on_txt = (typeof(on_text) == 'undefined') ? 'ON' : on_text;
                var ckd_off_txt = (typeof(off_text) == 'undefined') ? 'OFF' : off_text;
                // labels structure
                var on_label = '<div class="lcs_label lcs_label_on">' + ckd_on_txt + '</div>';
                var off_label = '<div class="lcs_label lcs_label_off">' + ckd_off_txt + '</div>';
                // default states
                var disabled= ($(this).is(':disabled')) ? true: false;
                var active = ($(this).is(':checked')) ? true : false;
                var status_classes = '';
                status_classes += (active) ? ' lcs_on' : ' lcs_off';
                if(!disabled) {status_classes += ' mk-check';}
                var structure ='<div class="lcs_switch '+status_classes+'">' +
                    '<div class="lcs_cursor"></div>' +
                    on_label + off_label +
                    '</div>';
                if($(this).attr('type') == 'checkbox') {
                    var this_parent = $(this).parent();
                    this_parent.append(structure);
                    this_parent.find('.lcs_switch').addClass('lcs_checkbox_switch');
                }
            }
        });
    };
    // handlers
    $(document).ready(function() {
        // on click
        $(".mk-checkbox").delegate('.mk-check', 'click tap', function(e) {
            if( $(this).hasClass('lcs_on') ) {
                if( !$(this).hasClass('lcs_radio_switch') ) { // not for radio
                    $(this).lcs_off();
                }
            } else {
                $(this).lcs_on();
            }
        });
        // on checkbox status change
        $(document).delegate('.mk-checkbox input', 'change', function() {
            if( $(this).is(':checked') ) {
                $(this).lcs_on();
            } else {
                $(this).lcs_off();
            }
        });
    });
})(jQuery);