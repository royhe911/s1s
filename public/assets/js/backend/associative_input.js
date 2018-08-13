/**
 * Created by Administrator on 2018/4/3.
 */
if(typeof fun=="undefined"){
    var fun = {};
}
fun.associative_input=function(element) {
    var check_store_ele = element;
    var attr = check_store_ele.attr("data-link-class");
    var drop_down = check_store_ele.parent().find("." + attr);
    check_store_ele.on("input", function () {
        var os_this = $(this);
        var url = url_start_session + "/shops/find_shop_list";
        var html = "";
        var val = $(this).val();
        $.ajax({
            type: 'POST',
            url: url,
            data: {keyword: val},
            success: function (result) {
                var data = result.data;
                if (result.code == 1 && data.length != 0) {
                    var i = 0;
                    $.each(data, function (index, val) {
                        html += '<span style="padding-left: 15px;" data-store-id="' + index + '" data-index="' + i + '">' + val + '</span>';
                        i++;
                    });
                    drop_down.html(html);
                    var height_num = 0;
                    if (i > 6) {
                        height_num = 6;
                    } else {
                        height_num = i;
                    }
                    var drop_down_height = height_num * 17 + 6;
                    drop_down.addClass("os-dropdown-on").css("height", drop_down_height)
                } else {
                    drop_down.removeClass("os-dropdown-on").css("height", '').empty();
                }
            },
            error: function (msg) {
                //提示层
                console.log(result);
                // layer.msg('服务器忙，请稍后··');
            }
        });
    }).on("keyup", function (e) {
        $(this).removeAttr("data-store-id");
        var os_this = $(this);
        var attr = os_this.attr("data-link-class");
        var drop_down = os_this.parent().find("." + attr);
        var drop_down_child = drop_down.children(), index;
        if (drop_down_child.length > 0) {
            var is_current_html = drop_down.find(".ac-current");
            var height = drop_down.height();
            var padding_top = parseInt(drop_down.css("padding-top"));
            var border_top = parseInt(drop_down.css("border-top"))
            var current_child, top, distance;
            if (e.keyCode == 40) {
                drop_down_child.removeClass("ac-current focus");
                if (is_current_html.length > 0) {
                    index = is_current_html.attr("data-index");
                    index = Number(index) + 1;
                    console.log(drop_down_child.length);
                } else {
                    index = 0;
                }
                current_child = $(drop_down_child[index]);
                if (current_child.length > 0) {
                    top = current_child.position().top;
                    if (top > height) {
                        distance = (index - 5) * 17;
                        drop_down.scrollTop(distance);
                    } else if (top < (padding_top)) {
                        distance = index * 17;
                        drop_down.scrollTop(distance);
                    }
                }
            } else if (e.keyCode == 38) {
                drop_down_child.removeClass("ac-current focus");
                if (is_current_html.length > 0) {
                    index = is_current_html.attr("data-index");
                    index = Number(index) - 1;
                } else {
                    index = drop_down_child.length - 1;
                }
                current_child = $(drop_down_child[index]);
                if (current_child.length > 0) {
                    top = current_child.position().top;
                    top = current_child.position().top;
                    if (top < (padding_top)) {
                        distance = index * 17;
                        drop_down.scrollTop(distance);
                    } else if (top > height) {
                        distance = (index - 5) * 17;
                        drop_down.scrollTop(distance);
                    }
                }
            }else if(e.keyCode==13) {
                $(this).blur();
            }
            if (current_child) {
                current_child.addClass("ac-current focus");
                var text = current_child.html();
                if (text) {
                    os_this.val(text);
                }
            }
        }
    }).on("blur", function () {
        var current_child = drop_down.find(".ac-current");
        if (current_child.length > 0) {
            check_store_ele.val(current_child.html()).attr("data-store-id", current_child.attr("data-store-id"));
            drop_down.removeClass("os-dropdown-on").css("height", '').empty();
        }
    });
    drop_down.on("mouseover", "span", function () {
        $(this).siblings().removeClass("ac-current focus");
        $(this).addClass("ac-current focus");
        var text = $(this).html();
        if (text) {
            check_store_ele.val(text);
        }
    }).on("click","span",function() {
        check_store_ele.val($(this).html()).attr("data-store-id", $(this).attr("data-store-id"));
        drop_down.removeClass("os-dropdown-on").css("height", '').empty();
    })
};