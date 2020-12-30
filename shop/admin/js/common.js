var errorHandle = function(res, login) {
    // 登入時api的錯誤處理
    if (login) {
        if(isloginHandle(res)) {
            return false;
        }
    }
    // 未登入時錯誤處理
    if (!login) {

    }
    // 共用錯誤處理
    if (res.code !== 200) {
        alert(res.message);
    }
};

var isloginHandle = function (res) {
    if (res.code === 1001 || res.code === 1002) {
        alert(res.message);
        // location.reload();
        location.href = '/admin'
        // 是否結束接下來的程式
        return true;
    }
}