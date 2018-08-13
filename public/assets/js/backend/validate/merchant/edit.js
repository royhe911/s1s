$.validator.setDefaults({
    submitHandler: function() {
        console.log('readyPost')
        var msg = {};
        msg.ids = $('#c-id').val();
        msg.company_name = $('#c-company_name').val();

        // 提交时未选择城市时候提示
        if ($("#add_city_show").children().length <= 0) {
            // window.scrollTo(0,0);
            return;
        } else {
            msg.city_id = $("#c-region_id").val();
        }

        // 营业执照图片
        var img_box_license = $(".license_img_adress");
        var img_item_box,img_item,img_name;
        for(var i=0;i<img_box_license.length ;i++) {
            img_item_box=$(img_box_license[i]);
            img_name = img_item_box.attr("data-name");
            img_item = img_item_box.find(".ac-item-box img");
            if(img_item.length>0) {
                msg.license_img = img_item.attr("data-log");
            }else{
                img_box_license.find("input[type='file']").focus();
                layer.msg('请上传营业执照图片');
                window.scrollTo(0,0);
                return;
            }
        }

        msg.license_number = $("#c-license_number").val();
        msg.name = $("#c-f_name").val();
        msg.banksite = $("#c-banksite").val();
        msg.bankcard = $("#c-bankcard").val();

        // 法人身份证正面图片
        var img_box_person_img_p = $(".img_box_person_img_p");
        var img_item_box,img_item,img_name;
        for(var i=0;i<img_box_person_img_p.length ;i++) {
            img_item_box=$(img_box_person_img_p[i])
            img_name = img_item_box.attr("data-name");
            img_item = img_item_box.find(".ac-item-box img");
            if(img_item.length>0) {
                msg.person_img_p = img_item.attr("data-log");
            }else{
                img_box_person_img_p.find("input[type='file']").focus();
                layer.msg('请上传法人身份证正面照片');
                return;
            }
        }

        // 法人身份证反面图片
        var img_box_person_img_n = $(".img_box_person_img_n");
        var img_item_box,img_item,img_name;
        for(var i=0;i<img_box_person_img_n.length ;i++) {
            img_item_box=$(img_box_person_img_n[i])
            img_name = img_item_box.attr("data-name");
            img_item = img_item_box.find(".ac-item-box img");
            if(img_item.length>0) {
                msg.person_img_n = img_item.attr("data-log");
            }else{
                img_box_person_img_n.find("input[type='file']").focus();
                layer.msg('请上传法人身份证反面照片');
                return;
            }
        }

        msg.person_id_number = $('#c-person_id_number').val();
        msg.contacts = $("#c-contacts").val();
        msg.mobile = $('#c-mobile').val();
        msg.email = $('#c-email').val();
        msg.company_address = $("#c-company_address").val();
        if ($("#isSet_username").val() == 1) {
            msg.username = $('#c-username').val();
        }

        // 发送数据给后台
        // 禁止表单重复提交
        $("#submit").attr('disabled', 'true');
        $.post('', msg, function (data) {
            console.log("msg", msg);
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
});

// 表单验证规则和提示
$().ready(function(){
    // 在键盘按下并释放及提交后验证提交表单
    $("#edit-form").validate({
        rules: {
            "c-company_name": {
                required: true,
                rangelength: [2,40],
                isSpaceBeforeAndAfter: true
            },
            "c-region_id": {
                required: true
            },
            "c-license_number": {
                isLicenseNumber: true
            },
            "c-f_name": {
                rangelength:[2, 20],
                isHanzi: true
            },
            "c-banksite": {
                rangelength:[4, 20],
                isBanksite: true
            },
            "c-bankcard": {
                isBankcard: true
            },
            "c-person_id_number": {
                required: true,
                isIDcard: true
            },
            "c-contacts": {
                required: true,
                rangelength:[2, 20],
                isContacts: true,
                isSpaceBeforeAndAfter: true
            },
            "c-mobile": {
                required: true,
                isSpaceBeforeAndAfter: true,
                isMobile: true
            },
            "c-email" :{
                required: true,
                isSpaceBeforeAndAfter: true,
                isEmail: true
            },
            "c-company_address": {
                required: true,
                isSpaceBeforeAndAfter: true,
                rangelength:[2, 40],
                isSpecialReg: true
            },
            "c-username": {
                required: true,
                isSpaceBeforeAndAfter: true,
                rangelength: [6, 20],
                isUndefined: true
            }
        },
        messages: {
            "c-company_name": {
                required: "请输入公司名称",
                rangelength: $.validator.format( "输入公司名称不超过{0}-{1}个字" ),
                isSpaceBeforeAndAfter: "请输入公司名称"
            },
            "c-region_id": {
                required: "请选择城市"
            },
            "c-license_number": {
                isLicenseNumber: "营业执照号输入有误",
            },
            "c-f_name": {
                rangelength: $.validator.format( "输入法人名称不超过{0}-{1}个字" ),
                isHanzi: "法人输入有误"
            },
            "c-banksite": {
                rangelength: $.validator.format( "输入开户行名称不超过{0}-{1}个字" ),
                isBanksite: "开户行输入有误"
            },
            "c-bankcard": {
                isBankcard: "银行账户输入有误"
            },
            "c-person_id_number": {
                required: "请输入法人身份证",
                isIDcard: "法人身份证输入有误"
            },
            "c-contacts": {
                required: "请输入联系人",
                rangelength: $.validator.format( "输入联系人不超过{0}-{1}个字" ),
                isContacts: "联系人输入有误",
                isSpaceBeforeAndAfter: "请输入联系人"
            },
            "c-mobile": {
                required: "请输入联系手机",
                isSpaceBeforeAndAfter: "请输入联系手机",
                isMobile: "联系手机输入有误"
            },
            "c-email" :{
                required: "请输入邮箱",
                isSpaceBeforeAndAfter: "请输入邮箱",
                isEmail: "邮箱输入有误"
            },
            "c-company_address": {
                required: "请输入公司地址",
                isSpaceBeforeAndAfter: "请输入公司地址",
                rangelength: $.validator.format( "输入公司地址不超过{0}-{1}个字" ),
                isSpecialReg: "公司地址输入有误"
            },
            "c-username": {
                required: "请输入管理员账号",
                isSpaceBeforeAndAfter: "请输入管理员账号",
                rangelength: $.validator.format( "输入管理员帐号不超过{0}-{1}个字" )
            }
        }
    });
});