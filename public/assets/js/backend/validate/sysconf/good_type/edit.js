// 提交资料
$.validator.setDefaults({
    submitHandler: function() {
        console.log('readyPost')
        var msg = {};
        msg.ids = $('#c-id').val();
        // 分类名称
        msg.name = $("#c-name").val();
        // 分类图标
        var img_box = $(".ac-img-url-box");
        var img_item_box,img_item,img_name;
        for(var i=0;i<img_box.length ;i++) {
            img_item_box=$(img_box[i])
            img_name = img_item_box.attr("data-name");
            img_item = img_item_box.find(".ac-item-box img");
            if(img_item.length>0) {
                msg.icon = img_item.attr("data-log");
            }else{
                img_box.find("input[type='file']").focus();
                layer.msg('请上传分类图标');
                return;
            }
        }

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
            "c-name": {
                required: true,
                rangelength: [2, 80],
                isSpaceBeforeAndAfter: true
            }
        },
        messages: {
            "c-name": {
                required: "请输入分类名称",
                rangelength: $.validator.format( "输入分类名称不超过{0}-{1}个字" ),
                isSpaceBeforeAndAfter: "请输入分类名称"
            }
        }
    });
});