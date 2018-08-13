/**
 * Created by Administrator on 2018/1/10.
 */

function get_update_param(param_obj) {
	
    function get_token_url() {
        var re_data;
        $.ajax({
            url: re_obj.token_url,
            type: 'GET',
            async : false,
            success: function (data) {
                var sto_obj = {};
                sto_obj.time = Number(new Date());
                if(data.code==0) {
                    sto_obj.token_string = data.data;
                }
//              console.log(data);
                sessionStorage.qiniu_token = JSON.stringify(sto_obj);
                re_data = data.data;
            },
            error: function () {
                alert("获取数据失败");
                console.log("获取数据失败")
            }
        });
        return re_data;
    }
    var default_obj = {
        num:0,
        string:""
    };
    var update_param = [
        {
            domain: qiniu_domin,
            img_prefix: "upload/image/",
            token_url:url_start_session+"api/get_token",
            interval:1000
        }
    ];
    if(param_obj&&typeof param_obj!="object") {
        default_obj = $.extend(default_obj, param_obj);
    }
    console.log(default_obj.num)
    console.log(11)
    if(default_obj.num>update_param.length) {
        default_obj.num = 0;
    }
    var re_obj = update_param[default_obj.num];
    re_obj.img_prefix += default_obj.string;
    //获取登录信息
    var qiniu_token = sessionStorage.qiniu_token;
    if(qiniu_token) {
        var token=JSON.parse(qiniu_token);
        var token_storage_time = new Date()-token.time;
//      console.log(token_storage_time);
        if(token_storage_time>re_obj.interval) {
            re_obj.token=get_token_url();
        }else{
            re_obj.token = token.token_string;
//          console.log(re_obj.token,1111);
        }
    }else{
        re_obj.token = get_token_url();
//      console.log(re_obj.token)
    }
    console.log('________________________________________________________')
    console.log(re_obj);
    console.log('________________________________________________________')
    return re_obj;
}

function upFileSystem(id,param_obj) {
    var file_upload = Qiniu.uploader({
        disable_statistics_report: true,
        makeLogFunc: 1,
        runtimes: 'html5,flash,html4',
        browse_button: id,
        max_file_size: '10000mb',
        chunk_size: '4mb',
        multi_selection: !(moxie.core.utils.Env.OS.toLowerCase()==="ios"),
        uptoken: $('#file_uptoken_url').val(),
        max_retries: 3,
        domain: $('#file_domain').val(),
        get_new_uptoken: true,
        auto_start: true,
        log_level: 8,
        init: {
            'BeforeChunkUpload':function (up,file) {
//              console.dir(up);
//              console.log("before chunk upload:",file.name);
            },
            'FilesAdded': function(up, files) {
                plupload.each(files, function(file) {
                        var os_file = $("#"+id).children("b");
                        if(file.name.indexOf(".zip")!=-1||file.name.indexOf(".rar")!=-1){
                            isUpload =true;
                            os_file.html("已上传 0%，请稍后").attr("id", file.id);
                        }else {
                            isUpload = false;
                            up.removeFile(file);
                            fun.greyPrompt("只支持ZIP压缩文件上传");
                            return false;
                        }}
                );
            },
            'UploadProgress': function(up, file) {
                $("#" + file.id).html("已上传 "+file.percent+"%，请稍后")
            },
            'FileUploaded': function(up, file, info) {
                var src = "";
                if(typeof (info.response)!="undefined") {
                    src =JSON.parse(info.response).key;
                }
                var progress = $("#" + file.id);
                progress.html(file.name).attr({
                    "data-log-src": src,
                    "data-log-name": file.name,
                    "data-log-size": file.size
                });
            },
            'Error': function(up, err, errTip) {
            },
            'Key': function(up, file) {
                var now = new Date();
                var str = now.getTime();
                var re_key;
                if(file.type.indexOf("image")!=-1) {
                    re_key=obj.img_prefix+str+".image";
                }else{
                    re_key=$('#file_prefix').val()+str+".zip";
                }
                return re_key;
            }
        }
    });
}
function up_img_System(id,param_obj,picture) {
	var pic_num=arguments[1];
    var obj = get_update_param(param_obj);
    var img_uploader= Qiniu.uploader({
        disable_statistics_report: true,
        makeLogFunc: 1,
        runtimes: 'html5,flash,html4',
        browse_button: id,
        max_file_size: '10000mb',
        //flash_swf_url: 'bower_components/plupload/js/Moxie.swf',
        chunk_size: '4mb',
        multi_selection: !(moxie.core.utils.Env.OS.toLowerCase()==="ios"),
        uptoken: obj.token,
        //uptoken_url : 'http://app.yscase.com/qiniu/upload/upload_token.php?1501661704642',
        //unique_names: true,
        //uptoken : '<Your upload token>', //若未指定uptoken_url,则必须指定 uptoken ,uptoken由其他程序生成
        // unique_names: true, // 默认 false，key为文件名。若开启该选项，SDK为自动生成上传成功后的key（文件名）。
        //save_key: 'qqqqqqqqqqq.jpg',   // 默认 false。若在服务端生成uptoken的上传策略中指定了 `sava_key`，则开启，SDK会忽略对key的处理
        max_retries: 3,                     // 上传失败最大重试次数
        // uptoken_func: function(){
        //     var ajax = new XMLHttpRequest();
        //     ajax.open('GET', $('#uptoken_url').val(), false);
        //     ajax.setRequestHeader("If-Modified-Since", "0");
        //     ajax.send();
        //     if (ajax.status === 200) {
        //         var res = JSON.parse(ajax.responseText);
        //         console.log('custom uptoken_func:' + res.uptoken);
        //         return res.uptoken;
        //     } else {
        //         console.log('custom uptoken_func err');
        //         return '';
        //     }
        // },
        //uptoken_url:$('#uptoken_url').val(),
        domain: obj.domain,
        get_new_uptoken: true,
        //save_key: true,
        // x_vars: {
        //     'id': '1234',
        //     'time': function(up, file) {
        //         var time = (new Date()).getTime();
        //         // do something with 'time'
        //         return time;
        //     },
        // },
        auto_start: true,
        log_level: 8,
        init: {
            'BeforeChunkUpload':function (up,file) {
            	console.log(1)
//              console.dir(up);
//              console.log("before chunk upload:",file.name);
            },
            'FilesAdded': function(up, files) {
            	console.log(2)
                plupload.each(files, function(file) {
                        var byte_num = 3;//默认的图片大小
                        var byte = byte_num * 1024 * 1024;
                        if(file.size>byte) {
                            isUpload = false;
                            up.removeFile(file);
                            fun.greyPrompt("您上传的图片大于3M，请重新选择图片上传");
                            return
                        }
                        var imgs = $("#"+id),sup="";
                        var items = imgs.parent();
                        
                        if(file.type=='image/jpeg'||file.type=='image/jpg'||file.type=='image/png'||file.type=='image/bmp'){
                            isUpload =true;
                            var attr = imgs.attr("data-picture");
                            var picture;
                            var is_more_picture = false;
                            var item_length = items.prevAll().length;
                            if(attr) {
                                if(attr==0) {
                                    items.addClass("hidden");
                                    sup = "";
                                    if(item_length>0) {
                                        isUpload = false;
                                        up.removeFile(file);
                                        return;
                                    }
                                }else if(attr==999){
                                    is_more_picture = true;
                                    picture = 997;
                                }else{
                                    alert("待写处理事件");
                                    return;
                                }
                            }else{
                                is_more_picture = true;
                                if(pic_num){
                                	picture = pic_num;
                                }else{
                                	picture=3;
                                }
                            }
                            if(is_more_picture) {
                                if(item_length>(picture+1)) {
                                    isUpload = false;
                                    up.removeFile(file);
                                    return;
                                }else if(item_length>picture) {
                                    items.addClass("hidden");
                                }
                                if(item_length==0) {
                                    sup="<sup></sup>"
                                }else{
                                    sup = "";
                                }
                            }
                            items.before("<div class='formControls l mr-15 ta-imgItem ac-item-box' id='"+file.id+"'><i class='os-radius'></i>"+sup+"<b>0%</b><img class='os-img' src="+public_file+"'/assets/img/loading_02.gif? v = 2.0.13'></div>")
                        }else {
                            isUpload = false;
                            up.removeFile(file);
                            fun.greyPrompt("只支持 jpg、jpeg、png、bmp 格式图片上传");
                            return false;
                        }}
                );
            },
            'BeforeUpload': function(up, file) {
            },
            'UploadProgress': function(up, file) {
                $("#" + file.id).find("b").html(file.percent+"%");
            },
            'UploadComplete': function(file) {
                console.log("上传成功后处理");
            },
            'FileUploaded': function(up, file, info) {
	
                var src = "",logSrc="";
                var id = $("#" + file.id);
                id.find("b").remove();
                if(typeof (info.response)!="undefined") {
                    logSrc = JSON.parse(info.response).key;
                    src = obj.domain+ JSON.parse(info.response).key;
                }
                id.find("img").attr({"src":src,"data-log":logSrc});
            },
            'Error': function(up, err, errTip) {
//              console.log(up);
//              console.log(err);
//              console.log(errTip);
//              console.log('----------上传失败-----------');
            },/*,*/
            'Key': function(up, file) {
            	
            	            	console.log('+++++++++++++++++++++++++++++++++++')
            	            	console.log(up)
            	            	console.log(file)
            	            	
                        console.log('+++++++++++++++++++++++++++++++++++')
                var now = new Date();
                var str = now.getTime();
                var re_key;
                if(file.type.indexOf("image")!=-1) {
                    re_key=obj.img_prefix+str+".image";
                }else{
                    re_key=$('#file_prefix').val()+str+".zip";
                }
                return re_key;
            }
        }
    });
}