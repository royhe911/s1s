/**
 * Created by Administrator on 2018/1/16.
 */
/**
 * Created by Administrator on 2018/1/2.
 */
if(typeof fun=="undefined"){
    var fun = {};
}
fun.module_prompt=function(array) {
    var default_array = {
        top: 100,
        width: 400,
        background:"rgba(0,0,0,0.3)"
    };
    if(array&&typeof array=="object") {
        default_array = $.extend(default_array, array);
    }
    var parent_ele,content;
    this.init=function(html,element) {
        parent_ele = $('<div></div>');
        parent_ele.css({
            position: "fixed",
            left: 0,
            right: 0,
            top: 0,
            bottom: 0,
            zIndex: 16000,
            background:default_array.background
        });
        content = $('<div></div>');
        content.css({
            position: "relative",
            margin: "0 auto",
            top: default_array.top,
            width: default_array.width
        });
        content.append(html);
        content.appendTo(parent_ele);
        if(element&&typeof element=="object") {
            parent_ele.appendTo(element)
        }else{
            parent_ele.appendTo($("body"));
        }
    };
    this.add_title=function(title_text,title_array,img_array) {
        var default_array = {
            height: 35,
            lineHeight:"35px",
            fontSize:16,
            background:"#dddddd",
            color:"#282828",
            border:"1px solid #dddddd",
            position:"relative",
            textAlign:"left",
            paddingLeft:10
        };
        if(title_array&&typeof title_array=="object") {
            default_array = $.extend(default_array, title_array);
        }
        if(!title_text) {
            title_text = "";
        }
        var default_img_array = {
            "-webkit-border-radius": "50%",
            "-moz-border-radius": "50%",
            "border-radius": "50%",
            width: 15,
            height:15,
            position:"absolute",
            top:10,
            right:10,
            cursor:"pointer",
            x_url_type:"white",
            x_url_bg:"red"
        };
        if(img_array&&typeof img_array=="object") {
            default_img_array = $.extend(default_img_array, img_array);
        }
        var title=$('<div>'+title_text+'</div>');
        var img_url;
        title.css(default_array);
        //控制关闭按钮图标的类别并删除该类别
        if(default_img_array.x_url_type=="black") {
            img_url = "data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAB4AAAAeCAMAAAAM7l6QAAAARVBMVEUAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAADc6ur3AAAAFnRSTlMAzRnspDkDGBPk1KbGw66UhzRjOxsHR0YlUwAAAG1JREFUKM/lz0cOwCAMRFFIo4WW4vsfNZEG2BhOwKwsPXnxxey7nKrnERPjQFIV3cgwfiTJpeh6C7bldwXducK1gA78dE35tCcyeVzniKzuE4os+vq6Zo2+vu61j+2Fwvl/hJY+xsm2XuWDmHwf8zoEWDtE89UAAAAASUVORK5CYII=";
        }else{
            img_url = "data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAB4AAAAeCAMAAAAM7l6QAAAAOVBMVEUAAAD///////////////////////////////////////////////////////////////////////8KOjVvAAAAEnRSTlMAzRil7DkDE+TUxsOulIc0YwdX3W6KAAAAZUlEQVQoz+XPSw6AIAxFUShoQX7a/S9WEpEOWlbAG73kjK7ZfbeD/56lCs5kYaijIBhs96H+MLqzqo6GVfGLVQ4TUWjrOkcUUZVRFGefor6h1Zx7Qffn05WXrtwnuMbZCymbzfcCR4UDevHG2dwAAAAASUVORK5CYII=";
        }
        delete default_img_array.x_url_type;

        //控制img_box的背景颜色并删除该类别
        if(default_img_array.x_url_bg=="red") {
            default_img_array.background = "#ff4951";
        }else{
            default_img_array.background = default_img_array.x_url_bg;
        }
        delete default_img_array.x_url_bg;

        var img_box = $('<i>' +
            '<img style="display: block;max-width: 100%;max-height: 100%;" src="'+img_url+'"/>' +
            '</i>');
        img_box.css(default_img_array);
        img_box.appendTo(title);
        img_box.on("click", function () {
            parent_ele.remove();
        });
        content.prepend(title);
    };
    this.clear=function(html) {
        parent_ele.remove();
    };
}