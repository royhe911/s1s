
// 营业时间-整体显示样式
$(".work_day_title_1").on('click', function(e) {
    $(".work_hour_title_1").removeClass('active');
    $(".work_day_title_1").addClass('active');
    $(".work_day_1").removeClass('hide');
    $(".work_hour_1").addClass('hide');
    
});
$(".work_hour_title_1").on('click', function(e) {
    $(".work_day_title_1").removeClass('active');
    $(".work_hour_title_1").addClass('active');
    $(".work_hour_1").removeClass('hide');
    $(".work_day_1").addClass('hide');
});
// 营业时间-24小时渲染
var b = "";
for (e = 0; 24 > e; e++){
    b += "<option value='" + e + ":00'>" + e + ":00</option>",
    b += "<option value='" + e + ":30'>" + e + ":30</option>";
}
$("#start_hour_1").html(b);
$("#end_hour_1").html(b);

// 周一~周日选择
var work_day_check_all_1 = $('input[name="work_day_check_all_1"]');
var work_day_check_1 = $('input[name="work_day_check_1"]');
var work_day_vals_1 = $('input[name="work_day_vals_1"]');
// 点击整周的切换方法
work_day_check_all_1.on('click', function(e) {
    // 全选的情况
    if (work_day_check_all_1[0].checked == true) {
        for( i = 0; i < work_day_check_1.length; i++){
            work_day_check_1[i].checked = true;
        }

        // 整合周一~周日选择的时间，且写入input
        // 'week' => [1 => 1, 2 => 2, 3 => 3, 4 => 4, 5 => 5]
        // 'week' => [1=>1,2=>2,3=>3,4=>4,5=>5,6=>6,7=>7]
        var work_day_check_1_checked = $('input[name="work_day_check_1"]:checked');
        var work_day_data_1 = " ";
        work_day_check_1_checked.each(function(){
        //由于复选框一般选中的是多个,所以可以循环输出
            // console.log($(this).val());
            
            work_day_data_1 +="," + $(this).val();
            // work_day_vals_1.val($(this).val());
        });
        work_day_data_1 = work_day_data_1.substr(1, work_day_data_1.length);
//      work_day_data_1 += "]";
        work_day_vals_1.val("");
        work_day_vals_1.val(work_day_data_1);

    }
    // 全不选的情况
    if (work_day_check_all_1[0].checked == false) {
        for( i = 0; i < work_day_check_1.length; i++){
            work_day_check_1[i].checked = false;
        }
        work_day_vals_1.val("");
    }
});
// 点击周一到周日单个选项的方法
for (var i = 0; i < work_day_check_1.length; i++) {
    work_day_check_1[i].onclick = function(){
        var work_day_check_1_checked = $('input[name="work_day_check_1"]:checked')
        // console.log($("input[name='work_day_check_1']:checked"));
        if (work_day_check_1_checked.length < 7) {
            work_day_check_all_1[0].checked = false;
        } else if(work_day_check_1_checked.length == 7) {
            work_day_check_all_1[0].checked = true;
        }

        // 整合周一~周日选择的时间，且写入input
        // 'week' => [1 => 1, 2 => 2, 3 => 3, 4 => 4, 5 => 5]
        // 'week' => [1=>1,2=>2,3=>3,4=>4,5=>5,6=>6,7=>7]
        var work_day_check_1_checked = $('input[name="work_day_check_1"]:checked');
        var work_day_data_1 ='';
        work_day_check_1_checked.each(function(){
        //由于复选框一般选中的是多个,所以可以循环输出
            // console.log($(this).val());
            work_day_data_1 +=  "," + $(this).val();
            // work_day_vals_1.val($(this).val());
        });
        work_day_data_1 = work_day_data_1.substr(1, work_day_data_1.length);
//      work_day_data_1 += "]";
        work_day_vals_1.val("");
        work_day_vals_1.val(work_day_data_1);

    }
}


// 24小时  ['week' => [6 => 6, 7 => 7], 'time' => '10:00-24:00']
var work_hour_1 = $('input[name="work_hour_1"]');
var start_hour_1 = $("#start_hour_1");
var end_hour_1 = $("#end_hour_1");
var work_hour_vals_1 = $('input[name="work_hour_vals_1"]');
// console.log("hour_test", start_hour, end_hour);
work_hour_1.on('click', function(e) {
    if (work_hour_1[0].checked == true) {
        start_hour_1.attr('disabled', true);
        end_hour_1.attr('disabled', true);
        // select_disabled
        start_hour_1.addClass('select_disabled');
        end_hour_1.addClass('select_disabled');

        // 把24小时时间写入input
        // 'week' => [1 => 1, 2 => 2, 3 => 3, 4 => 4, 5 => 5]
        var work_hour_data = "0:00-24:00";
        work_hour_vals_1.val("");
        work_hour_vals_1.val(work_hour_data);

    }
    if (work_hour_1[0].checked == false) {
        start_hour_1.attr('disabled', false);
        end_hour_1.attr('disabled', false);
        start_hour_1.removeClass('select_disabled');
        end_hour_1.removeClass('select_disabled');

        // 把24小时时间写入input
        // 'week' => [1 => 1, 2 => 2, 3 => 3, 4 => 4, 5 => 5]
        var work_hour_data = "";
        work_hour_data += start_hour_1.val() + "-" + end_hour_1.val();
        work_hour_vals_1.val("");
        work_hour_vals_1.val(work_hour_data);

    }
});

// 24小时中的开始和结束时间
start_hour_1.on('click', function(e) {

    // 把24小时时间写入input
    // 'week' => [1 => 1, 2 => 2, 3 => 3, 4 => 4, 5 => 5]
    var work_hour_data = "";
    work_hour_data += start_hour_1.val() + "-" + end_hour_1.val();
    work_hour_vals_1.val("");
    work_hour_vals_1.val(work_hour_data);

});

end_hour_1.on('click', function(e) {

    // 把24小时时间写入input
    // 'week' => [1 => 1, 2 => 2, 3 => 3, 4 => 4, 5 => 5]
    var work_hour_data = "";
    work_hour_data += start_hour_1.val() + "-" + end_hour_1.val();
    work_hour_vals_1.val("");
    work_hour_vals_1.val(work_hour_data);

});

// function test(){
//     var work_day_check_1 = $('input[name="work_day_check_1"]:checked');
//     var start_hour_1 = $("#start_hour_1").val();
//     var end_hour_1 = $("#end_hour_1").val();
//     console.log("test", work_day_check_1, start_hour_1, end_hour_1);
// }

// function test(){
//     var business_hours_new = [];
//     var work_day_vals_submit_1 = $("input[name='work_day_vals_1']").val();
//     var work_hour_vals_submit_1 = $("input[name='work_hour_vals_1']").val();
//     business_hours_new.push(work_day_vals_submit_1, work_hour_vals_submit_1);
//     console.log('business_hours_new', business_hours_new);
// }

// ['week' => [1 => 1, 2 => 2, 3 => 3, 4 => 4, 5 => 5], 'time' => '10:00-23:00'],
//                ['week' => [6 => 6, 7 => 7], 'time' => '10:00-24:00'],
// 实际效果
// ["'week' => [1=>1,3=>3,4=>4,5=>5,6=>6,7=>7]", "'time' => '0:00-24:00'"]
