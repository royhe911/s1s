/**
 * Created by Administrator on 2018/2/8.
 */
//该文档基于jq的插件 , 由于只有本人使用 并未做严格的防错处理
//fun.error_msg() 这个函数为构造函数

//fun.error_msg_clear()本函数为直接调用函数 ,
//参数一 只允许传入对象 都可以默认不传 ,也可以可自定义传参
//对象的参数  error_attr_name默认读取需要元素的属性值,并展示出来
//对象的参数 element参数有歧义 , element传入不为空字符 ,表示读取祖籍元素 并把需要显示的错误提示放入祖籍元素后面
//对象的参数 type的定义为select div input的输入类型  ,不传默认为input类型
//对象的参数 element_attr需要在显示的对象上的属性

if(typeof fun=="undefined"){
    var fun = {};
}
fun.error_msg=function() {
    var de_array = [];
    var num = 0;
    this.init=function(element,msg,css_obj) {
        num++;
        if(!msg) {
            msg="您的输入有误，请重新输入"
        }
        var de_obj = {
            fontSize:12,
            transition_time:"0.2s",
            transition_type:"linear",
            is_transition:false,
            color:"#fe4952",
            overflow:"hidden",
            height:"1.42em",
            display:'block'
        };
        if(css_obj&&typeof css_obj=="object") {
            de_obj = $.extend(de_obj, css_obj);
        }
        de_obj["-webkit-transition"] = "all "+de_obj.transition_time+" "+de_obj.transition_type;
        de_obj["-moz-transition"]="all "+de_obj.transition_time+" "+de_obj.transition_type;
        de_obj["-ms-transition"]= "all "+de_obj.transition_time+" "+de_obj.transition_type;
        de_obj["-o-transition"]="all "+de_obj.transition_time+" "+de_obj.transition_type;
        de_obj["transition"]= "all "+de_obj.transition_time+" "+de_obj.transition_type;
        delete de_obj.transition_time;
        delete de_obj.transition_type;
        //用变量保存是否需要动态效果的bool
        var is_trans = de_obj.is_transition;
        delete de_obj.is_transition;
        //生成变量new_class
        var new_class;
        if(is_trans) {
            new_class = "h-0";
        }else{
            new_class = "";
        }
        de_array[num] = $('<div class="'+new_class+'">' + msg + '</div>');
        de_array[num].css(de_obj);
        if(!(element&&typeof element=="object")){
            console.error("请传入element");
            return;
        }
        element.after(de_array[num]);
        if(is_trans) {
            setTimeout(function() {
                de_array[num].removeClass("h-0");
            },20)
        }
        return num;
    };
    this.clear=function(clear_num) {
        if(clear_num&&typeof clear_num=="number") {
            de_array[clear_num].remove();
        }else{
            console.error("请确认传入的num为数字");
        }
    };
    this.clearAll=function() {
        for(var i=0;i<num;i++) {
            de_array[i].remove();
        }
    };
};
var error_msg = new fun.error_msg();
fun.error_msg_clear=function(set_obj,error_style_obj) {
    //default_obj错误提示需要传入msg ,默认不定义消息信息
    //type 仅支持三个值 input select div ,不传或者非这三个值 ,默认为input
    var default_set_obj = {
        error_attr_name:"data-msg-empty",
        type:"input",
        element:'',
        parents_class:"ac-event-box",
        element_attr:'data-error-num'
    };
    var default_set_style_obj = {};
    if(error_style_obj&&typeof error_style_obj=="object") {
        default_set_style_obj= $.extend(default_set_style_obj,error_style_obj)
    }
    if(default_set_obj.type!="input"&&default_set_obj.type!="select"&&default_set_obj.type!="div") {
        default_set_obj.type = "input";
    }
    if(default_set_obj.type=="input") {
        default_set_obj.check_class = "ac-input";
        default_set_obj.event = "focus input";
    }else if(default_set_obj.type=="select") {
        default_set_obj.check_class = "ac-select";
        default_set_obj.event = "change";
    }else if(default_set_obj.type=="div") {
        default_set_obj.check_class = "ac-div-box";
        default_set_obj.event = "click";
    }
    if(set_obj&&typeof set_obj=="object") {
        default_set_obj= $.extend(default_set_obj,set_obj)
    }
    if(default_set_obj.type=="input") {
        $.each($("."+default_set_obj.check_class), function (index, val) {
            var os_this;
            if(default_set_obj.element=='') {
                os_this = $(this);
            }else{
                os_this = $(this).parents("."+default_set_obj.parents_class);
            }
            $(val).on(default_set_obj.event, function () {
                var data_error = os_this.attr(default_set_obj.element_attr);
                if (typeof data_error != 'undefined') {
                    os_this.removeAttr(default_set_obj.element_attr);
                    error_msg.clear(Number(data_error));
                }
            }).on("blur", function () {
                if ($.trim($(this).val()) == "") {
                    var data_error = os_this.attr(default_set_obj.element_attr);
                    if (typeof data_error == 'undefined') {
                        var msg = os_this.attr(default_set_obj.error_attr_name);
                        var error_num = error_msg.init(os_this, msg, error_style_obj);
                        os_this.attr(default_set_obj.element_attr, error_num);
                    }
                }
            })
        });
    }else if(default_set_obj.type=="select") {
        $.each($("."+default_set_obj.check_class), function (index, val) {
            $(val).on(default_set_obj.event, function () {
                var os_this;
                if(default_set_obj.element=='') {
                    os_this = $(this);
                }else{
                    os_this = $(this).parents(default_set_obj.parents_class);
                }
                var data_error = os_this.attr(default_set_obj.element_attr);
                if (typeof data_error != 'undefined') {
                    os_this.removeAttr(default_set_obj.element_attr);
                    error_msg.clear(Number(data_error));
                }
            })
        });
    }else if(default_set_obj.type=="div") {
        $.each($("."+default_set_obj.check_class), function (index, val) {
            var os_this;
            if(default_set_obj.element=='') {
                os_this = $(this);
            }else{
                os_this = $(this).parents(default_set_obj.parents_class);
            }
            $(val).on(default_set_obj.event, function () {
                var data_error = $(this).attr(default_set_obj.element_attr);
                if (typeof data_error != 'undefined') {
                    os_this.removeAttr(default_set_obj.element_attr);
                    error_msg.clear(Number(data_error));
                }
            })
        });
    }
};
fun.error_msg_create_input=function(obj,style_obj) {
    //default_obj错误提示需要传入msg ,默认不定义消息信息
    //type
    var default_obj = {
        is_reg: false,
        element:'',
        element_info:'',
        type:"empty",
        str_length:4,
        contain:true,
        element_attr:'data-error-num',
        is_focus:true
    };
    default_obj.reg = new RegExp("[`~!@#$^&*=|':;',/?~！@#￥……&*——|‘；：”“'。，、？\"]");
    // 法人身份证验证
    // default_obj.ID_card_test = /(^\d{15}$)|(^\d{18}$)|(^\d{17}(\d|X|x)$)/;
    //[`~!@#$^&*()=|{}':;',\\[\\].<>/?~！@#￥……&*（）——|{}【】‘；：”“'。，、？\"]
    if(obj&&typeof obj=="object") {
        default_obj= $.extend(default_obj,obj)
    }
    if(default_obj.element=='') {
        console.error("请传入jq包裹的input的Dom");
        return;
    }
    if(default_obj.element_info=='') {
        default_obj.element_info=default_obj.element
    }
    var default_style_obj = {};
    if(style_obj&&typeof style_obj=="object") {
        default_style_obj = $.extend(default_style_obj, style_obj);
    }
    var error_info;
    var content_text = $.trim(default_obj.element.val());
    if(default_obj.type=="empty") {
        if(content_text=="") {
            if(default_obj.is_focus) {
                default_obj.element.focus();
            }
            error_info = default_obj.element_info.attr(default_obj.element_attr);
            if (typeof error_info == 'undefined') {
                default_obj.element_info.attr(default_obj.element_attr, error_msg.init(default_obj.element_info, default_obj.msg, default_style_obj));
            }
            return false;
        }
    }else if(default_obj.type=="distance_limit_long") {
        if(content_text.length>default_obj.str_length) {
            if(default_obj.is_focus) {
                default_obj.element.focus();
            }
            error_info = default_obj.element_info.attr(default_obj.element_attr);
            if (typeof error_info == 'undefined') {
                default_obj.element_info.attr(default_obj.element_attr, error_msg.init(default_obj.element_info, default_obj.msg, default_style_obj));
            }
            return false;
        }
    }else  if(default_obj.type=="distance_limit_short") {
        if(content_text.length<default_obj.str_length) {
            if(default_obj.is_focus) {
                default_obj.element.focus();
            }
            error_info = default_obj.element_info.attr(default_obj.element_attr);
            if (typeof error_info == 'undefined') {
                default_obj.element_info.attr(default_obj.element_attr, error_msg.init(default_obj.element_info, default_obj.msg, default_style_obj));
            }
            return false;
        }
    }else if(default_obj.type=="reg"&&default_obj.is_reg) {
        var reg_bool = default_obj.reg.test(content_text);
        if(!default_obj.contain) {
            reg_bool = !reg_bool;
        }
        if(reg_bool){
            if(default_obj.is_focus) {
                default_obj.element.focus();
            }
            error_info = default_obj.element_info.attr(default_obj.element_attr);
            if (typeof error_info == 'undefined') {
                default_obj.element_info.attr(default_obj.element_attr, error_msg.init(default_obj.element_info,  default_obj.msg, default_style_obj));
            }
            return false;
        }
    }
    return true;
};
fun.error_msg_create_box=function(obj,style_obj) {
    //default_obj错误提示需要传入msg ,默认不定义消息信息
    var default_style_obj = {};
    if(style_obj&&typeof style_obj=="object") {
        default_style_obj = $.extend(default_style_obj, style_obj);
    }
    var default_obj = {
        element:'',
        element_info:'',
        type:"select",
        element_attr:'data-error-num'
    };
    if(obj&&typeof obj=="object") {
        default_obj= $.extend(default_obj,obj)
    }
    if(default_obj.element=='') {
        console.error("请传入jq包裹的Dom");
        return;
    }
    if(default_obj.element_info=='') {
        default_obj.element_info=default_obj.element
    }
    var error_info;
    if(default_obj.type=="select") {
        default_obj.element.focus();
        error_info = default_obj.element_info.attr(default_obj.element_attr);
        if (typeof error_info == 'undefined') {
            default_obj.element_info.attr(default_obj.element_attr, error_msg.init(default_obj.element_info, default_obj.msg, default_style_obj));
        }
    }else if(default_obj.type=="div") {
        default_obj.element.focus();
        error_info = default_obj.element_info.attr(default_obj.element_attr);
        if (typeof error_info == 'undefined') {
            default_obj.element_info.attr(default_obj.element_attr, error_msg.init(default_obj.element_info, default_obj.msg,default_style_obj));
        }
    }
};
fun.error_clear_box=function(obj) {
    var def_obj = {
        check_class: "ac-error-box",
        is_parent_attr:"data-error-parent",
        parent_class:'ac-error-box-parent',
        event_div: "click",
        event_select: "change",
        event_input:"focus input",
        element_attr:'data-error-num'
    };
    if(obj&&typeof obj=="object") {
        def_obj = $.extend(def_obj, obj);
    }
    $.each($("."+def_obj.check_class), function (index, val) {
        var os_event;
        if(val.tagName=="SELECT") {
            os_event = def_obj.event_select;
        }else if(val.tagName=="INPUT"||val.tagNam=="TEXTAREA"){
            os_event = def_obj.event_input;
        }else{
            os_event = def_obj.event_div;
        }
        $(val).on(os_event,function() {
            var parent_attr = $(this).attr(def_obj.is_parent_attr);
            if(parent_attr=="true") {
                var  parent_ele= $(this).parents("." + def_obj.parent_class);
                var num_par = parent_ele.attr(def_obj.element_attr);
                if(typeof num_par!="undefined") {
                    error_msg.clear(Number(num_par));
                    parent_ele.removeAttr(def_obj.element_attr);
                }
            }else{
                var num = $(this).attr(def_obj.element_attr);
                if(typeof num!="undefined") {
                    error_msg.clear(Number(num));
                    $(this).removeAttr(def_obj.element_attr);
                }
            }
        })
    })
};