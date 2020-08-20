$(function() {
    var ruleCount = 0;
    $('#end').hide();

    $('#gameNumber').on('propertychange input', function () {
        var re = /^[0-9]$/g;
        var value = $(this).val();
        if (!re.test(value)) {
            $(this).val(value.replace(/[^0-9]+/, ''));
        }
    });
    $('#start').on('click', function() {
        $(".info").html('');
        $('.game-box-mask').hide();
        $.ajax({
            url: '/work/api/randomnumber.php',
            type: "GET",
            contentType: "application/json;charset=utf-8",
            success: function(res){
                console.log(res);
                if (res.msg) {
                    alert(res.msg);
                }
                $('#start').hide();
                $('#end').show();
            },
            error: function(xhr, ajaxOptions, thrownError){
                console.log(xhr.status);
                console.log(thrownError);
            }
        });
    });
    $('#end').on('click', function() {
        $('.game-box-mask').show();
        $('#end').hide();
        $('#start').show();
    });
    $('#postBtn').on('click',function(){
        if ($('#gameNumber').val().length < 4) {
            alert('請輸入四個字元');
            return;
        }
        var value = $('#gameNumber').val();
        var dataJSON = {
            value: value
        };
        $.ajax({
            url: '/work/api/bullcow.php',
            data: JSON.stringify(dataJSON),
            type: "POST",
            dataType: "json",
            contentType: "application/json;charset=utf-8",
            success: function(res){
                if (res.msg) {
                    alert(res.msg);
                }
                if (res.data.text) {
                    ruleCount += 1;
                    $(".info").append("<div>" + ruleCount + " => " + res.data.text + "</div>");
                    $("#tip").html(res.data.a +"<span class='green'>A</span>" + res.data.b + "<span class='red'>B</span>");
                }
            },
            error: function(xhr, ajaxOptions, thrownError){
                console.log(xhr.status);
                console.log(thrownError);
            }
        });
    });
}())