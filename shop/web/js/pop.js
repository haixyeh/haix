var openPop;
var closePop;
var setPopContent;

$(document).ready(function(){
    // 遮罩面板控制
    var body = $('body');
    var maskWrap = $('<div class="mask-wrap"></div>');
    var maskMain = $('<div class="mask-main"></div>');
    var maskContent = $('<div class="conten-wrap"></div>');
    var contentMain = $('<div class="content"></div>');
    var closeMaskBtn = $('<button class="mask-close-btn">關閉</button>');

    maskWrap.appendTo(body);
    maskMain.appendTo(maskWrap);
    maskContent.appendTo(maskWrap);
    maskContent.append(closeMaskBtn);
    contentMain.appendTo(maskContent);

    closePop = function(e){
        if (e) e.preventDefault();
        $(maskWrap).hide();
    }
    openPop = function(e) {
        if (e) e.preventDefault();
        $(maskWrap).show();
    }
    setPopContent = function(content) {
        contentMain.html('');
        contentMain.append(content);
    }

    maskMain.bind('click', closePop);
    closeMaskBtn.bind('click', closePop);
})