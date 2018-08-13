// 提交资料
$.validator.setDefaults({
    submitHandler: function() {
        console.log('readyPost')
        var msg = {};
        msg.ids = $('#c-id').val();
        // 选择门店
        msg.shop_id = $('#c-shop_id').val();
        // 选择品牌
        msg.brand_id= $("#c-brand_id").val();
//      商品平台分类
        msg.product_id=$('#c-product_id').val();
        // 商品分类
        msg.type_id= $("#c-type_id").val();
        // 商品名称
        msg.title = $("#c-title").val();
        //图片校验
        var image = $(".ac-img-container").find("img");
        var image_arr = [],bool=true;
        for(var n=0;n<image.length;n++) {
            var src = $(image[n]).attr("data-log");
            if(typeof(src)!="undefined") {
                image_arr.push(src);
                bool = false;
            }
        }
        if (bool) {
            layer.msg('请上传商品预览图片');
            return false;
        }
        msg.image = JSON.stringify(image_arr);
        // 商品价格
        msg.price = $('#c-price').val();
        // 商品描述
        var desc = UE.getEditor('editor', {}).getContent();
        if(desc=="") {
            layer.msg("请输入商品详情");
            return;
        }else{
            msg.desc=desc
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
            "c-shop_id": {
                required: true
            },
            "c-product_id": {
                required: true,
                isSpaceBeforeAndAfter: true
            },
            "c-type_id": {
                required: true
            },
            "c-title": {
                required: true,
                rangelength: [2, 40],
                isSpaceBeforeAndAfter: true
            },
            "c-price": {
                rangelength: [0, 9],
                isDecimal: true
            }
        },
        messages: {
            "c-shop_id": {
                required: "请选择门店"
            },
           "c-product_id": {
                required: "请输入商品平台分类",                
                isSpaceBeforeAndAfter: "请输入商品平台分类"
            },
            "c-type_id": {
                required: "请选择商品分类"
            },
            "c-title": {
                required: "请输入商品名称",
                rangelength: $.validator.format( "输入商品名称不超过{0}-{1}个字" ),
                isSpaceBeforeAndAfter: "请输入商品名称"
            },
            "c-price": {
                rangelength: $.validator.format( "商品价格输入有误" ),
                isDecimal: "商品价格输入有误"
            }
        }
    });
});