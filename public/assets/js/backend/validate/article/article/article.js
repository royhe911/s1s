// 提交资料
$.validator.setDefaults({
    submitHandler: function() {
        console.log('readyPost')
        var msg = {};
        msg.ids = $('#c-id').val();
        // 资讯名称
        msg.title = $("#c-title").val();
        // 资讯分类
        msg.type_id = $("#c-type_id").val();
        // 资讯来源
        msg.author = $("#c-author").val();
        // 包含或者是不包含城市
        msg.is_visible = $("#c-is_visible").val();
        // 选择城市
        msg.region_id = $("#c-region_id").val();
        // 资讯预览图
        var img_box = $(".ac-img-url-box");
        var img_item_box, img_item, img_name;
        for (var i = 0; i < img_box.length; i++) {
            img_item_box = $(img_box[i]);
            img_name = img_item_box.attr("data-name");
            img_item = img_item_box.find(".ac-item-box img");
            if (img_item.length > 0) {
                msg.image_url = img_item.attr("data-log");
            } else {
                layer.alert('请上传资讯预览图',{icon: 5});
                return;
            }
        }
        // 状态
        msg.is_show = $("input[name='c-is_show']:checked").val();
        // 资讯简介
        msg.desc = $("#c-desc").val();
        // 咨询内容
        var content = UE.getEditor('editor', {}).getContent();
        if(content=="") {
            layer.alert("请输入资讯内容", {icon: 5});
            return;
        }else{
            msg.content=content
        }
        // 显示端
        msg.position = $('#c-position').val();

        // 发送数据给后台
        // 禁止表单重复提交
        console.log("beforePost", msg);
        $("#submit").attr('disabled', 'true');
        $.post('', msg, function (data) {
            console.log("afterPost", msg);
            // 全部校验完毕后提交等待图
            var layer_id=layer.msg('提交中', {
                icon : 16,
                shade : 0.5,
                time : 0
            });
            if (data.code == 1) {
                layer.msg(data.msg, {
                    time: 2000 //2秒关闭（如果不配置，默认是3秒）
                }, function () {
                    window.parent.location.reload();
                });
            } else {
                layer.msg(data.msg);
                $("#submit").removeAttr('disabled');
            }
        })
    }
})

// 表单验证规则和提示
$().ready(function(){
    // 在键盘按下并释放及提交后验证提交表单
    $("#edit-form").validate({
        rules: {
            "c-title": {
                required: true,
                rangelength: [2, 40],
                isSpaceBeforeAndAfter: true
            },
            "c-type_id": {
                required: true
            },
            "c-author": {
                required: true,
                rangelength: [2, 20],
                isSpaceBeforeAndAfter: true
            },
            // "c-is_visible": {
            //     required: true
            // },
            "c-region_id": {
                required: true
            },
            "c-desc": {
                required: true,
                rangelength: [2, 225],
                isSpaceBeforeAndAfter: true
            }
            // "c-position": {
            //     required: true
            // }
        },
        messages: {
            "c-title": {
                required: "请输入资讯名称",
                rangelength: $.validator.format( "输入资讯名称不超过{0}-{1}个字" ),
                isSpaceBeforeAndAfter: "请输入资讯名称"
            },
            "c-type_id": {
                required: "请选择分类"
            },
            "c-author": {
                required: "请输入资讯来源",
                rangelength: $.validator.format( "输入资讯来源不超过{0}-{1}个字" ),
                isSpaceBeforeAndAfter: "请输入资讯来源"
            },
            // "c-is_visible": {
            //     required: true
            // },
            "c-region_id": {
                required: "请选择城市"
            },
            "c-desc": {
                required: "请输入资讯简介",
                rangelength: $.validator.format( "输入资讯简介不超过{0}-{1}个字" ),
                isSpaceBeforeAndAfter: "请输入资讯简介"
            }
            // "c-position": {
            //     required: "请选择显示端"
            // }
        }
    });
});