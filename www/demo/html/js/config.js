var qzf_id = '2017100'; //商户号
var qzf_key = 'ZVFjVNoCFluOoYcpzPUtYIIRsZVPilhC'; //商户秘钥
var qzf_wg = 'http://localhost:8001/Pay'; //网关

qzf_id = '2018108'; //商户号
qzf_key = 'fjZoFEAnwmdYykFcxsvmorYxqURXJHWS'; //商户秘钥
qzf_wg = 'http://www.51paybal.com/Pay'; //网关

var notify_url = 'http://localhost:8001/notifyUrl.html'; //异步地址
var back_url = 'http://localhost:8001/backUrl.html'; //同步地址
var attch = 'mytest'; //透传

function http_build_query(data) {
    var httpd = qzf_wg + '?';
    for (var i in data) {
        httpd = httpd + i + '=' + data[i] + '&';
    }
    httpd = httpd.substr(0, httpd.length - 2);
    return httpd;
}

function getNowDate() {
    var date = new Date();
    var year = date.getFullYear() // 年
    var month = date.getMonth() + 1; // 月
    var day = date.getDate(); // 日
    var hour = date.getHours(); // 时
    var minutes = date.getMinutes(); // 分
    var seconds = date.getSeconds() //秒
    // 给一位数数据前面加 “0”
    if (month >= 1 && month <= 9) {
        month = "0" + month;
    }
    if (day >= 0 && day <= 9) {
        day = "0" + day;
    }
    if (hour >= 0 && hour <= 9) {
        hour = "0" + hour;
    }
    if (minutes >= 0 && minutes <= 9) {
        minutes = "0" + minutes;
    }
    if (seconds >= 0 && seconds <= 9) {
        seconds = "0" + seconds;
    }
    var currentdate = year + month + day + hour + minutes + seconds;
    return currentdate;
}