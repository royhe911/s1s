// 提交资料
console.log('test');
$.validator.setDefaults({
    submitHandler: function() {
        console.log('readyPost')
        var msg = {};
        msg.id = $('#c-id').val();
        // 选择门店
        msg.shop_id = $("#shop_id").val();
        // 活动时间
        msg.time = $("#time").val();
        // 活动名称
        msg.title = $("#c-title").val();
        // 状态
        msg.status = $("input[name='c-status']:checked").val();

        // 活动详情图
        var img_box = $(".ac-img-url-box");
        var img_item_box,img_item,img_name;
        for(var i=0;i<img_box.length ;i++) {
            img_item_box=$(img_box[i])
            img_name = img_item_box.attr("data-name");
            img_item = img_item_box.find(".ac-item-box img");
            if(img_item.length>0) {
                msg.license_img = img_item.attr("data-log");
            }else{
                img_box.find("input[type='file']").focus();
                layer.msg('请上传活动详情图');
                return;
            }
        }

        // 活动描述
        msg.desc = $("#c-desc").val();

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
            "region": {
                required: true
            },
            "circle": {
                required: true
            },
            "shop_id": {
                required: true
            },
            "time": {
                required: true
            },
            "c-title": {
                required: true,
                rangelength: [2, 20],
                isSpaceBeforeAndAfter: true
            },
            "c-desc": {
                required: true,
                rangelength: [2, 20],
                isSpaceBeforeAndAfter: true
            }
        },
        messages: {
            "region": {
                required: "请选择区"
            },
            "circle": {
                required: "请选择商圈"
            },
            "shop_id": {
                required: "请选择门店"
            },
            "time": {
                required: "请添加活动时间"
            },
            "c-title": {
                required: "请输入活动名称",
                rangelength: $.validator.format( "输入活动名称不超过{0}-{1}个字" ),
                isSpaceBeforeAndAfter: "请输入活动名称"
            },
            "c-desc": {
                required: "请输入活动描述",
                rangelength: $.validator.format( "输入活动描述不超过{0}-{1}个字" ),
                isSpaceBeforeAndAfter: "请输入活动描述"
            }
        }
    });
});