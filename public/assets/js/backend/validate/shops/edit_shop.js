// 提交资料
$.validator.setDefaults({
    submitHandler: function() {
        var msg = {};
        // 不验证
        msg.id = $("#type_id").val();
        // 门店名称
        msg.name = $("#store_name").val();
        // 选择地区
        msg.region_id_3 = $("#region_id_3").attr('data-region_id_3');
        // 选择商圈
        msg.region_id_4 = $("#region_id_4").val();
        // 详细地址
        msg.address = $("#address").val();
        //门牌号
        msg.doorplate  = $("#doorplate").val();
        // 百度拾取
        msg.coordinate = $("#coordinate").val();
        // 选择品牌(英文逗号隔开)
        msg.brand = $('#brand').val();
        // 选择分类
        var id_list=JSON.parse($('#types_data').val());
        var types_data;
        for(var i=0;i<id_list.length;i++){
        	if(i==0){
        		types_data=id_list[i].id;
        	}else{
        		types_data=types_data+','+id_list[i].id;
        	}
        }
        msg.type_ids=types_data;
         // 营业面积
        msg.acreage = $("#acreage").val();
        // 规模(人数)
        msg.scale = $("#scale").val();
        // 营业类型
        msg.business_type = $('#business_type').val();
        // 联系人
        msg.contacts = $("#contacts").val();
        // 联系手机
        msg.phone_number = $("#phone_number").val();
        // 联系电话
//      var code=$('#code').val();
//      var num=$("#phone_number1").val();
        msg.phone_number1 = $('#tel').val();
		// 营业状态
        msg.business_status=$("#status").val();
        
        
		// 营业时间   
		var business_hours_new = [];
		       // 第1个营业时间
       var start_hour_submit_1 = $("#start_hour_1").val();
       var end_hour_submit_1 = $("#end_hour_1").val();
       var business_hours_new_1 = [];
       var work_day_vals_submit_1 = $("input[name='work_day_vals_1']").val();
       var work_hour_vals_submit_1 = $("input[name='work_hour_vals_1']").val();
              
       if(work_day_vals_submit_1 && work_day_vals_submit_1!=',' && work_hour_vals_submit_1){
       	  business_hours_new_1.push(work_day_vals_submit_1, work_hour_vals_submit_1);
       }else if((work_day_vals_submit_1=="" || work_day_vals_submit_1==',') && work_hour_vals_submit_1==""){
       	  business_hours_new_1=[];
       	   console.log(business_hours_new_1.length)
       }else{
       	  layer.msg('请选择正确的营业时间');
          return false;
       }
//     
    //             第2个营业时间
       var start_hour_submit_2 = $("#start_hour_2").val();
       var end_hour_submit_2 = $("#end_hour_2").val();
       var business_hours_new_2 = [];
       var work_day_vals_submit_2 = $("input[name='work_day_vals_2']").val();
       var work_hour_vals_submit_2 = $("input[name='work_hour_vals_2']").val();
       if(work_day_vals_submit_2 && work_day_vals_submit_2!=',' && work_hour_vals_submit_2){
       	  business_hours_new_2.push(work_day_vals_submit_2, work_hour_vals_submit_2);
       }else if((work_day_vals_submit_2=="" || work_day_vals_submit_2==',') && work_hour_vals_submit_2==""){
       	  business_hours_new_2=[]
       }else{
       	  layer.msg('请选择正确的营业时间');
          return false;
       }
       
       if(business_hours_new_1.length>0 && business_hours_new_2.length==0){
       	   business_hours_new.push(business_hours_new_1);
       }else if(business_hours_new_1.length==0 && business_hours_new_2.length>0){
       	    business_hours_new.push(business_hours_new_2);
       }else if(business_hours_new_1.length>0 && business_hours_new_2.length>0){
       	   business_hours_new.push(business_hours_new_1,business_hours_new_2);
       }else{
       	   business_hours_new=[];
       }
       msg.business_hours_new = JSON.stringify(business_hours_new);		             
       
        // 门店介绍
        msg.desc = $("#desc").val();                          
        // 获取门店预览图地址
        var imageList = $("#imgList").find("img");
        var image_arr,bool=true;
        var image;
        var images;
        var preview_image=[];
        for(var n=0;n<imageList.length;n++) {
            var src = $(imageList[n]).attr("data-log");            
            if(typeof(src)!="undefined") {
            	if(n==0){
            		images=src;
            		image=src;
                	bool = false;
            	}else{
            		images=images+','+src;
            	}                
            }
        }
        if (bool) {
            layer.msg('请上传商品预览图片');
            return false;
        }
        msg.preview_image=image;
        msg.preview_images=images;
        
       
        // 验证法人
        msg.true_name = $("#truename").val();
        // 法人身份证
        msg.person_id_number = $("#person_id_number").val();
        
        // 获取法人身份证正面照
        img_box = $(".ac-img-url-box_person_img_p");
        for(i=0;i<img_box.length ;i++) {
            img_item_box = $(img_box[i]);
            img_name = img_item_box.attr("data-name");
            img_item = img_item_box.find(".ac-item-box img");
            if(img_item.length>0) {
                msg.person_img_p = img_item.attr("data-log");
            }
        }

        // 获取法人身份证反面照
        img_box = $(".ac-img-url-box_person_img_n");
        for(i=0;i<img_box.length ;i++) {
            img_item_box = $(img_box[i]);
            img_name = img_item_box.attr("data-name");
            img_item = img_item_box.find(".ac-item-box img");
            if(img_item.length>0) {
                msg.person_img_n = img_item.attr("data-log");
            }
        }
        // 获取法人手持身份证照
        img_box = $(".ac-img-url-box_person_img_s");
        for(i=0;i<img_box.length ;i++) {
            img_item_box = $(img_box[i]);
            img_name = img_item_box.attr("data-name");
            img_item = img_item_box.find(".ac-item-box img");
            if(img_item.length>0) {
                msg.person_img_s = img_item.attr("data-log");
            }
        }
        
//      营业类型
        msg.license_type=$('input[name="license_type"]:checked').val();
//      营业证名称
        msg.license_name=$('#license_name').val();
         // 营业执照号
        msg.license_number = $("#license_number").val();
        // 获取营业执照照片地址
        img_box = $(".ac-img-url-box_businessLicense");
        for(i=0;i<img_box.length ;i++) {
            img_item_box = $(img_box[i]);
            img_name = img_item_box.attr("data-name");
            img_item = img_item_box.find(".ac-item-box img");
            if(img_item.length>0) {
                msg.license_img = img_item.attr("data-log");
            }
        }
        // 开户行
        msg.banksite = $("#banksite").val();
        // 银行账户
        msg.bankcard = $("#bankcard").val();        
        
        
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
    $("#add_form_cc").validate({
        rules: {
            "store_name": {
                required: true,
                isSpaceBeforeAndAfter: true,
                rangelength: [2, 40]
            },
            // "region_id_3": {
            //     required: true
            // },
            // "region_id_4": {
                // required: true,
                // rangelength: [2, 40],
                // isSpaceBeforeAndAfter: true
            // },
            "address": {
                required: true
                // isSpaceBeforeAndAfter: true,
                // rangelength: [2, 40]
            },
//         "coordinate": {
//             required: true
//         },
            "brand": {
                isChineseComma: true
            },
            "types_data": {
                required: true
            },
            "license_number": {
                isLicenseNumber: true
            },
            "truename": {
                rangelength: [2, 20]
            },
            "banksite": {
                rangelength: [4, 20],
                isBanksite: true
            },
            "bankcard": {
                isBankcard: true
            },
            "person_id_number": {
                isIDcard_notRequired: true
            },
            "acreage": {
                isUnsignedInt: true
            },
            "scale": {
                rangelength: [1, 10],
                isUnsignedInt: true
            },
            // "business_type": {
            //     // required: true
            // },
            "contacts": {
                required: true,
                isSpaceBeforeAndAfter: true,
                rangelength: [2, 20],
                isHanzi: true
            },
            "phone_number": {
                required: true,
                isSpaceBeforeAndAfter: true,
                isMobile: true
            },
            "tel":{
            	isAllTelNum: true           	
            },
//          "phone_number1": {
//              isTel: true
//          },
//          
//          "work_day_vals_1": {
//              required: true
//          },
//          "work_hour_vals_1": {
//              required: true
//          },
            "desc": {
                rangelength: [2, 120]
            }
        },
        messages: {
            "store_name": {
                required: "请输入门店名称",
                isSpaceBeforeAndAfter: "请输入门店名称",
                rangelength: $.validator.format("输入门店名称不超过{0}-{1}个字")
            },
            // "region_id_3": {
            //     required: true,
            //     isSpaceBeforeAndAfter: true,
            //     rangelength: [2, 40]
            // },
            // "region_id_4": {
                // required: true,
                // rangelength: [2, 40],
                // isSpaceBeforeAndAfter: true
            // },
            "address": {
                required: "请选择详细地址"
                // isSpaceBeforeAndAfter: "请选择详细地址",
                // rangelength: $.validator.format("输入详细地址不超过{0}-{1}个字")
            },
            // "coordinate": {
            //     required: true
            // },
            "brand": {
                isChineseComma: "请用英文逗号隔开品牌"
            },
            "types_data": {
                required: "请添加品牌分类"
            },
            "license_number": {
                isLicenseNumber: "营业执照号输入有误"
            },
            "truename": {
                rangelength: $.validator.format("输入法人名称不超过{0}-{1}个字")
            },
            "banksite": {
                rangelength: $.validator.format("输入开户行名称不超过{0}-{1}个字"),
                isBanksite: $.validator.format("开户行输入有误")
            },
            "bankcard": {
                isBankcard: $.validator.format("银行账户输入有误")
            },
            "person_id_number": {
                isIDcard_notRequired: $.validator.format("法人身份证输入有误")
            },
            "acreage": {
                isUnsignedInt: $.validator.format("请输入正整数")
            },
            "scale": {
                rangelength: $.validator.format("输入规模名称不超过{0}-{1}个数字"),
                isUnsignedInt: "请输入正整数"
            },
            // "business_type": {
            //     // required: true
            // },
            "contacts": {
                required: "请输入联系人",
                isSpaceBeforeAndAfter: "请输入联系人",
                rangelength: $.validator.format("输入联系人不超过{0}-{1}个字"),
                isHanzi: "请输入汉字"
            },
            "phone_number": {
                required: "请输入联系手机",
                isSpaceBeforeAndAfter: "请输入联系手机",
                isMobile: "联系手机输入有误"
            },
            "tel": {           	
                isAllTelNum: "请输入正确的座机号"
            },
//          "phone_number1": {
//              isTel: "座机号码为3-4位区号加7-8位数字"
//          },
//          "work_day_vals_1": {
//              required: "请选择营业日"
//          },
//          "work_hour_vals_1": {
//              required: "请选择营业时段"
//          },
            "desc": {
                rangelength: $.validator.format("输入门店介绍不超过{0}-{1}个字")
            }
        }
    });
});