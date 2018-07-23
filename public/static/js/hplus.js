// Custom scripts
$(document).ready(function () {
    var layer = layui.layer;
    // MetsiMenu
    $('#side-menu').metisMenu();

    // Collapse ibox function
    $('.collapse-link').click( function() {
        var ibox = $(this).closest('div.ibox');
        var button = $(this).find('i');
        var content = ibox.find('div.ibox-content');
        content.slideToggle(200);
        button.toggleClass('fa-chevron-up').toggleClass('fa-chevron-down');
        ibox.toggleClass('').toggleClass('border-bottom');
        setTimeout(function () {
            ibox.resize();
            ibox.find('[id^=map-]').resize();
        }, 50);
    });

    // Close ibox function
    $('.close-link').click( function() {
        var content = $(this).closest('div.ibox');
        content.remove();
    });

    // Small todo handler
    $('.check-link').click( function(){
        var button = $(this).find('i');
        var label = $(this).next('span');
        button.toggleClass('fa-check-square').toggleClass('fa-square-o');
        label.toggleClass('todo-completed');
        return false;
    });

    // Append config box / Only for demo purpose
    // $.get("skin-config.html", function (data) {
    //     $('body').append(data);
    // });

    // minimalize menu
    $('.navbar-minimalize').click(function () {
        $("body").toggleClass("mini-navbar");
        SmoothlyMenu();
    })

    // tooltips
    $('.tooltip-demo').tooltip({
        selector: "[data-toggle=tooltip]",
        container: "body"
    })

    // Move modal to body
    // Fix Bootstrap backdrop issu with animation.css
    $('.modal').appendTo("body")

    // Full height of sidebar
    function fix_height() {
        var heightWithoutNavbar = $("body > #wrapper").height() - 61;
        $(".sidebard-panel").css("min-height", heightWithoutNavbar + "px");
    }
    fix_height();

    // Fixed Sidebar
    // unComment this only whe you have a fixed-sidebar
            //    $(window).bind("load", function() {
            //        if($("body").hasClass('fixed-sidebar')) {
            //            $('.sidebar-collapse').slimScroll({
            //                height: '100%',
            //                railOpacity: 0.9,
            //            });
            //        }
            //    })

    $(window).bind("load resize click scroll", function() {
        if(!$("body").hasClass('body-small')) {
            fix_height();
        }
    })

    $("[data-toggle=popover]")
        .popover();

    $('#side-menu').find('li').removeClass('active');
    $('.nav-second-level').removeClass('in');

    $('#side-menu').find('a[href*="' + GV.current_controller + '"]').parent().addClass('active').parents('.parent-li').addClass('active');
    $('#side-menu').find('a[href*="' + GV.current_controller + '"]').parents('.nav-second-level').addClass('in');


    /**
     * 通用表单提交(AJAX方式)
     */
    $('.submit-btn').on('click', function (data) {
        var from    = $(this).parents('.ajax-from')[0];
        var data    = $(this).parents('.ajax-from').serialize();
        if($(this).attr('accomplish-type')){
            var accomplish_type    = 2 ;
        }else{
            var accomplish_type    = 1;
        }

        $.ajax({
            url: from.action,
            type: from.method,
            data: data,
            success: function (info) {
                alert(info.msg);
                if(info.code == 1  && accomplish_type == 1){
                    window.location.reload();
                }
            }
        });
        return false;
    });

    /**
     * 通用全选
    */
    $('.check-all').on('click', function () {
        $(this).parents('table').find('input[type="checkbox"]').prop('checked', $(this).prop('checked'));
    });

    // 时间控件
    $('.date').datetimepicker({
        language: 'zh-CN',//显示中文
        format: 'yyyy-mm-dd',//显示格式
        minView: "month",//设置只显示到月份
        initialDate: new Date(),//初始化当前日期
        autoclose: true,//选中自动关闭
        todayBtn: true//显示今日按钮
    })

    // 时间控件
    $('.time').datetimepicker({
        language: 'zh-CN',//显示中文
        format: 'hh:ii:ss',//显示格式
        startView:1,
        initialDate: new Date(),//初始化当前日期
        autoclose: true,//选中自动关闭
        todayBtn: true//显示今日按钮
    })
    uploadFile();
    //上传控件
    function uploadFile(number){
        $(".uploadfile").fileinput({
            language: 'zh', //设置语言
            uploadUrl:"/index.php/api/upload/upload", //上传的地址
            allowedFileExtensions: ['jpg', 'gif', 'png'],//接收的文件后缀
            uploadAsync: true, //默认异步上传
            showUpload:true, //是否显示上传按钮
            showRemove :true, //显示移除按钮
            showPreview :true, //是否显示预览
            showCaption:false,//是否显示标题
            browseClass:"btn btn-primary", //按钮样式
            dropZoneEnabled: false,//是否显示拖拽区域
            //minImageWidth: 50, //图片的最小宽度
            //minImageHeight: 50,//图片的最小高度
            //maxImageWidth: 1000,//图片的最大宽度
            //maxImageHeight: 1000,//图片的最大高度
            //maxFileSize:0,//单位为kb，如果为0表示不限制文件大小
            //minFileCount: 0,
            maxFileCount:10, //表示允许同时上传的最大文件个数
            enctype:'multipart/form-data',
            validateInitialCount:true,
            previewFileIcon: "<iclass='glyphicon glyphicon-king'></i>",
            msgFilesTooMany: "选择上传的文件数量({n}) 超过允许的最大数值{m}！",
        }).on("fileuploaded", function (event, data, previewId, index){
        });
    }


});


// For demo purpose - animation css script
function animationHover(element, animation){
    element = $(element);
    element.hover(
        function() {
            element.addClass('animated ' + animation);
        },
        function(){
            //wait for animation to finish before removing classes
            window.setTimeout( function(){
                element.removeClass('animated ' + animation);
            }, 2000);
        });
}

// Minimalize menu when screen is less than 768px
$(function() {
    $(window).bind("load resize", function() {
        if ($(this).width() < 769) {
            $('body').addClass('body-small')
        } else {
            $('body').removeClass('body-small')
        }
    })
})

function SmoothlyMenu() {
    if (!$('body').hasClass('mini-navbar') || $('body').hasClass('body-small')) {
        // Hide menu in order to smoothly turn on when maximize menu
        $('#side-menu').hide();
        // For smoothly turn on menu
        setTimeout(
            function () {
                $('#side-menu').fadeIn(500);
            }, 100);
    } else if ($('body').hasClass('fixed-sidebar')){
        $('#side-menu').hide();
        setTimeout(
            function () {
                $('#side-menu').fadeIn(500);
            }, 300);
    } else {
        // Remove all inline style from jquery fadeIn function to reset menu state
        $('#side-menu').removeAttr('style');
    }
}

function GetQueryString(name)
{
    var reg = new RegExp("(^|&)"+ name +"=([^&]*)(&|$)");
    var r = window.location.search.substr(1).match(reg);
    if(r!=null)return  unescape(r[2]); return null;
}

// Dragable panels
function WinMove() {
    // var element = "[class*=col]";
    // var handle = ".ibox-title";
    // var connect = "[class*=col]";
    // $(element).sortable(
    //     {
    //         handle: handle,
    //         connectWith: connect,
    //         tolerance: 'pointer',
    //         forcePlaceholderSize: true,
    //         opacity: 0.8,
    //     })
    //     .disableSelection();
};

