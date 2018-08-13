/**
 * Created by Administrator on 2018/3/30.
 */
if(typeof fun=="undefined"){
    var fun = {};
}
fun.get_star_number=function(num){
    var translate_num = Number(num);
    if (!isNaN(translate_num)) {
        var int_num = Math.floor(Number(num));
        var float_num = num - int_num;
        return int_num * 15 + float_num * 10;
    } else {
        console.log('这不是一个数字');
    }
}


