// 提交资料
$.validator.setDefaults({
    submitHandler: function() {
        console.log('readyPost')
        var msg = {};
        msg.ids = $('#c-id').val();
        // 轮播名称
        msg.title = $("#c-title").val();
        // 是否包含
        msg.is_visible = $("#c-is_visible").val();
        // 添加城市
        msg.region_id = $('#c-region_id').val();
        // 位置
        msg.type = $('#c-type').val();
        // 位置
        var device = ',';
        $("input[name='device']:checked").each(function(){
            device += $(this).val()+",";
        })
        msg.device = device;
        // 获取上传轮播图片地址
        var img_box = $(".ac-img-url-box");
        var img_item_box, img_item, img_name;
        for (var i = 0; i < img_box.length; i++) {
            img_item_box = $(img_box[i]);
            img_name = img_item_box.attr("data-name");
            img_item = img_item_box.find(".ac-item-box img");
            if (img_item.length > 0) {
                msg.image_xcx = img_item.attr("data-log");
            } else {
                img_box.find("input[type='file']").focus();
                layer.msg('请上传轮播图片');
                return;
            }
        }
        // 链接地址
        msg.url = $("#c-url").val();
        // 城市排序
        var sort = $('#c-sort').val();
        if (sort == "") {
            msg.sort = 99;
        } else {
            msg.sort = sort;
        }
        // 状态
        msg.is_show = $("input[name='c-is_show']:checked").val();

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
            "c-region_id": {
                required: true
            },
            "c-url": {
                required: true,
                rangelength: [5, 50],
                isSpaceBeforeAndAfter: true
            },
            "c-sort": {
                number:true,
                maxlength: 3
            }
        },
        messages: {
            "c-title": {
                required: "请输入轮播图名称",
                rangelength: $.validator.format( "输入轮播图名称不超过{0}-{1}个字" ),
                isSpaceBeforeAndAfter: "请输入轮播图名称"
            },
            "c-region_id": {
                required: "请选择城市"
            },
            "c-url": {
                required: "请输入链接地址",
                rangelength: $.validator.format( "输入链接地址不超过{0}-{1}个字" ),
                isSpaceBeforeAndAfter: "请输入链接地址"
            },
            "c-sort": {
                number: "请输入数字",
                maxlength: "输入数字不能超过3位"
            }
        }
    });
});