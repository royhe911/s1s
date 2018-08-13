/**
 * Created by Administrator on 2018/3/30.
 */

//page number bind event request data
$("#pagination").on("click", "b", function () {
    var news = {};
    //click prev focus move prev 1 or remove one item or add one item
    var current = $("#pagination .current");
    var max = parseInt($("#pagination .max").attr("data-page"));
    var min = parseInt($("#pagination .min").attr("data-page"));
    var ellipse = $("#pagination .disabled");
    if ($(this).attr("data-page") == "prev") {
        //var min = $("#pagination .min").attr("data-page");
        rightMove()
    }
    //click next focus move next 1 or remove one item or add one item
    if ($(this).attr("data-page") == "next") {
        leftMove()
    }
    //click page this eq current no response
    if ($(this).hasClass("current")) {
        console.log("not  change status ")
    }
    //click page this - current eq 1 or -1 remove one or add one item
    if(!isNaN(parseInt($(this).attr("data-page")))) {
        var result = $(this).attr("data-page") - current.attr("data-page");
        //console.log(result)
    }
    if(result==1) {
        rightMove()
    }
    if(result==-1) {
        leftMove()
    }
    if(result==2) {
        if(current.attr("data-page")>=(max-5)) {
            if(ellipse.children(".ellipseLast").length>0) {
                ellipse.children(".ellipseLast").parent().remove();
            }
        }
        if(current.attr("data-page")<max) {
            current.removeClass("current").parent().next().next().children().addClass("current");
        }
        if(current.attr("data-page")<(max-3)) {
            if(current.attr("data-page")==(max-4)) {
                current.parent().next().next().after($("<li class='lf' data-remove='t'><b class='page-link' data-page=" + (parseInt(current.attr('data-page') )+ 3) + ">" + (parseInt(current.attr('data-page')) + 3) + "</b></li>"));
            }else{
                current.parent().next().next().after($("<li class='lf' data-remove='t'><b class='page-link' data-page=" + (parseInt(current.attr('data-page') )+ 3) + ">" + (parseInt(current.attr('data-page')) + 3) + "</b></li><li class='lf' data-remove='t'><b class='page-link' data-page=" + (parseInt(current.attr('data-page') )+ 4) + ">" + (parseInt(current.attr('data-page')) + 4) + "</b></li>"));
            }
        }
        if(current.attr("data-page")>=(min+2)&&current.attr("data-page")!=max) {
            if(current.attr("data-page")==(min+2)) {
                current.parent().prev().before($("<li class='lf disabled' data-remove='t'><span class='ellipseFirst'>...</span></li>"))
                current.parent().prev().remove();
            }else{
                //console.log(1233666)
                if(ellipse.children(".ellipseFirst").length==0) {
                    current.parent().prev().prev().before($("<li class='lf disabled' data-remove='t'><span class='ellipseFirst'>...</span></li>"))
                }
                current.parent().prev().remove();
                current.parent().prev().remove();
            }
        }
        if(!(current.attr("data-page")==max)) {
            news.page = parseInt(current.attr("data-page"))+2;
            //console.log(news.page);
            //console.log(news);
            //$(window).scrollTop(0);
            get_data(news)
        }
    }

    //click page this - current eq 2 or -2 remove two or add two item
    if(result==-2) {
        if(current.attr("data-page")<=(7-min)) {
            if(ellipse.children(".ellipseFirst").length>0) {
                ellipse.children(".ellipseFirst").parent().remove();
            }
        }
        if(current.attr("data-page")>min) {
            current.removeClass("current").parent().prev().prev().children().addClass("current");
        }
        //add elem
        if(current.attr("data-page")>(5-min)) {
            if(current.attr("data-page")==(6-min)) {
                current.parent().prev().prev().before($("<li class='lf' data-remove='t'><b class='page-link' data-page=" + (parseInt(current.attr('data-page') )-3) + ">" + (parseInt(current.attr('data-page')) - 3) + "</b></li>"));
            }else{
                current.parent().prev().prev().before($("<li class='lf' data-remove='t'><b class='page-link' data-page=" + (parseInt(current.attr('data-page') )-4) + ">" + (parseInt(current.attr('data-page')) - 4) + "</b></li><li class='lf' data-remove='t'><b class='page-link' data-page=" + (parseInt(current.attr('data-page') )-3) + ">" + (parseInt(current.attr('data-page')) - 3) + "</b></li>"));
            }
        }
        if(current.attr("data-page")<(max-1)&&current.attr("data-page")!=min) {
            if(current.attr("data-page")==(max-2)) {
                current.parent().next().after($("<li class='lf disabled' data-remove='t'><span class='ellipseLast'>...</span></li>"))
                current.parent().next().remove();
            }else{
                if(ellipse.children(".ellipseLast").length==0) {
                    current.parent().next().next().after($("<li class='lf disabled' data-remove='t'><span class='ellipseLast'>...</span></li>"))
                }
                current.parent().next().remove();
                current.parent().next().remove();
            }
        }
        if(!(current.attr("data-page")==min)) {
            news.page = parseInt(current.attr("data-page"))-2;
            //console.log(news.page);
            //console.log(news);
            //$(window).scrollTop(0);
            get_data(news)
        }
    }
    //click page this min
    if(result>2&&parseInt($(this).attr("data-page"))==max) {
        current.removeClass("current");
        $(this).addClass("current").parent().siblings("[data-remove='t']").remove();
        $(this).parent().before("<li class='lf disabled' data-remove='t'><span class='ellipseFirst'>...</span></li><li class='lf' data-remove='t'><b class='page-link' data-page=" + (max - 2) + ">" + (max - 2) + "</b></li><li class='lf' data-remove='t'><b class='page-link' data-page=" + (max - 1) + ">" + (max - 1) + "</b></li>");
        news.page = max;
        //$(window).scrollTop(0);
        get_data(news)
    }
    //click page this max
    if(result<-2&&parseInt($(this).attr("data-page"))==min) {
        current.removeClass("current");
        $(this).addClass("current").parent().siblings("[data-remove='t']").remove();
        $(this).parent().after("<li class='lf' data-remove='t'><b class='page-link' data-page='2'>2</b></li><li class='lf' data-remove='t'><b class='page-link' data-page='3'>3</b></li><li class='lf disabled' data-remove='t'><span class='ellipseLast'>...</span></li>");
        news.page = min;
        //$(window).scrollTop(0);
        get_data(news)
    }

    //news.page = $(this).html();
    //request(news,false) //stop get number
    //focus right move one step
    function rightMove() {
        if(current.attr("data-page")>=(max-4)) {
            if(ellipse.children(".ellipseLast").length>0) {
                ellipse.children(".ellipseLast").parent().remove();
            }
        }
        if(current.attr("data-page")<max) {
            current.removeClass("current").parent().next().children().addClass("current");
        }
        if(current.attr("data-page")<(max-3)) {
            current.parent().next().next().after($("<li class='lf' data-remove='t'><b class='page-link' data-page=" + (parseInt(current.attr('data-page') )+ 3) + ">" + (parseInt(current.attr('data-page')) + 3) + "</b></li>"));
        }
        if(current.attr("data-page")>(min+2)&&current.attr("data-page")!=max) {
            if(current.attr("data-page")==(min+3)) {
                current.parent().prev().prev().before($("<li class='lf disabled' data-remove='t'><span class='ellipseFirst'>...</span></li>"))
            }
            current.parent().prev().prev().remove();
        }
        if(!(current.attr("data-page")==max)) {
            news.page = parseInt(current.attr("data-page"))+1;
            //console.log(news.page);
            //console.log(news);
            //$(window).scrollTop(0);
            get_data(news)
        }
    }
    //focus left move one step
    function leftMove() {
        if(current.attr("data-page")<=(6-min)) {
            if(ellipse.children(".ellipseFirst").length>0) {
                ellipse.children(".ellipseFirst").parent().remove();
            }
        }
        if(current.attr("data-page")>min) {
            current.removeClass("current").parent().prev().children().addClass("current");
        }
        //add elem
        if(current.attr("data-page")>(5-min)) {
            current.parent().prev().prev().before($("<li class='lf' data-remove='t'><b class='page-link' data-page=" + (parseInt(current.attr('data-page') )-3) + ">" + (parseInt(current.attr('data-page')) - 3) + "</b></li>"));
        }
        if(current.attr("data-page")<(max-2)&&current.attr("data-page")!=min) {
            if(current.attr("data-page")==(max-3)) {
                current.parent().next().next().after($("<li class='lf disabled' data-remove='t'><span class='ellipseLast'>...</span></li>"))
            }
            current.parent().next().next().remove();
        }
        if(!(current.attr("data-page")==min)) {
            news.page = parseInt(current.attr("data-page"))-1;
            //console.log(news.page);
            //console.log(news);
            //$(window).scrollTop(0);
            get_data(news)
        }
    }
});
