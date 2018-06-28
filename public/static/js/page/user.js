/**
 * Created by HeYiwei on 2018/5/30
 */
$(document).ready(function () {
    layui.use('upload', function(){
        var upload = layui.upload;
        //执行实例
        var uploadInst = upload.render({
            elem: '#upload', //绑定元素
            url: "/index.php/api/upload/upload",
            type: 'image',
            ext: 'jpg|png|gif|bmp',
            size: 200, //最大允许上传的文件大小
            data:{
                width:$('#upload').attr('data-width'),
                height:$('#upload').attr('data-height'),
                file_path:$('#upload').attr('data-path')
            },
            done: function (data) {
                if (data.error === 0) {
                    $('.thumb-img').attr('src',data.url);
                    $('.thumb-a').attr('href',data.url);
                    $('.theme').val(data.url);
                } else {
                    layer.msg(data.msg);
                }
            }
        });
    });


    //开卡弹窗显示
    $('.open-card').on('click',function () {
        var id = $(this).attr('data-id');
        $('.voucher').attr('data-id',id);
    })

    //开卡结算
    $('.voucher').on('click',function(){
        var id               = $(this).attr('data-id');
        var rank             = $('select[name=rank]').val();
        var remark           = $('input[name=remark]').val();
        var money            = $('input[name=money]').val();
        var give_money       = $('input[name=give_money]').val();
        var give_integral    = $('input[name=give_integral]').val();
        var validity_time    = $('input[name=validity_time]').val();

        $.ajax({
            url: 'manageMember',
            type: 'post',
            data: {
                id:id,
                rank:rank,
                money:money,
                remark:remark,
                give_money:give_money,
                validity_time:validity_time,
                give_integral:give_integral,
            },
            success: function (info) {
                if (info.code === 1) {
                    alert(info.msg);

                    $("#myModal1").modal('hide');
                }
            }
        });
        return false;

    })

});


