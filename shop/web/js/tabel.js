var setTable = function(tabelSetting) {
    var table = $(this);
    var tHead = $(table).find('thead')[0];
    var tBody = $(table).find('tbody')[0];
    var field = tabelSetting.field;
    var tableData = tabelSetting.tableData;
    var tdContent = tabelSetting.tdContent;
    var setTrData = tabelSetting.setTrData;

    // 標頭
    var thTr = $('<tr></tr>');
    thTr.appendTo(tHead);
    
    Object.keys(field).forEach(function(list) {
        var th = $('<th></th>');
        $(th).text(field[list]);
        $(thTr).append(th);
    });

    tableData.forEach(function(list) {
        var id = list.id ? list.id :  null;
        var props = {
            // id: list.id ? list.id :  null
        };

        // 設定tr屬性
        Object.keys(setTrData).forEach(function(propsName) {
            props[propsName]=list[propsName];
        });

        var tdTr = $('<tr '+ objToStringData(props) +'></tr>');
        tdTr.appendTo(tBody);

        Object.keys(field).forEach(function(listName) {
            var td = $('<td></td>');

            if (tdContent[listName]) {
                var method =tdContent[listName];
                $(td).append(method(tdTr, list[listName], list));
            } else {
                $(td).append(list[listName]);
            }

            $(tdTr).append(td);
        });
    });
};


function objToStringData (obj) {
    var str = '';
    for (var p in obj) {
        if (obj.hasOwnProperty(p)) {
            str += 'data-' + p + '="' + obj[p] + '"';
        }
    }
    return str;
}