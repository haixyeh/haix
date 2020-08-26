$(function() {

    var usermap = function() {
        var obj = {
            init: function() {
                $.ajax({
                    url: '/user',
                    method: "GET",
                    dataType: "json",
                    contentType: "application/json;charset=utf-8",
                    async: false,
                    success: function(res){
                        $("#userList").find('tbody').html('<tr><>');
                        console.log(res, 'res');
                        res.forEach((item, index) => {
                            $("#userList").find('tbody').append(
                                "<tr>" +
                                "<td>"+ (index + 1) +"</td>" +
                                "<td>"+ item.name +"</td>" +
                                "<td><input class='delete' type='button' value='刪除' data-id='"+ item.id +"'></td>" +
                                "</tr>"
                            );
                        });
                        obj.methodSet();
                    },
                    error: function(xhr, ajaxOptions, thrownError){
                        console.log(xhr.status);
                        console.log(thrownError);
                    }
                });
            },
            methodSet: function() {
                $('#signUp').on('click', function() {
                    $(this).attr('disabled', true);
                    var dataJSON = {
                        account: $('#account').val(),
                        password: $('#password').val()
                    };
                    $.ajax({
                        url: '/user',
                        data: JSON.stringify(dataJSON),
                        type: "POST",
                        dataType: "json",
                        contentType: "application/json;charset=utf-8",
                        success: function(res){
                            console.log(res);
                            if (res.msg) {
                                alert(res.msg);
                            }
                            $(this).attr('disabled', false);
                            location.reload();    
                        },
                        error: function(xhr, ajaxOptions, thrownError){
                            $(this).attr('disabled', false);
                            console.log(xhr.status);
                            console.log(thrownError);
                        }
                    });
                });
            
                $('.delete').on('click', function() {
                    var confirmDel = confirm('是否確定刪除');
                    if (!confirmDel) {
                        return;
                    }
                    $.ajax({
                        url: '/user/' + $(this).data('id'),
                        type: 'DELETE',
                        success: function(res){
                            console.log(res);
                            if (res.msg) {
                                alert(res.msg);
                            }
                            location.reload();
                        },
                        error: function(xhr, ajaxOptions, thrownError){
                            console.log(xhr.status);
                            console.log(thrownError);
                        }
                    });
                });
            }
        }
        return obj;
    };

    var set = new usermap();
    set.init();
}())