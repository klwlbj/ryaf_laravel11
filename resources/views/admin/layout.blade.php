<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>平安穗粤</title>
    <meta name="renderer" content="webkit">
    <meta http-equiv="X-UA-Compatible" content="IE=edge,chrome=1">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, minimum-scale=1.0, maximum-scale=1.0, user-scalable=0">
    <link rel="stylesheet" type="text/css"  href="{{asset('statics/css/antd.min.css')}}"></script>
</head>
<script src="{{asset('statics/js/vue.js')}}"></script>
<script src="{{asset('statics/js/moment.js')}}"></script>
<script src="{{asset('statics/js/httpVueLoader.js')}}"></script>
<script src="{{asset('statics/js/antd.min.js')}}"></script>
<script src="{{asset('statics/js/axios.min.js')}}"></script>
<script src="{{asset('statics/js/cookie.js')}}"></script>
<style>
.sidebar{
    border-radius: 0;
    width: 150px;
    background-color: #134974 !important;
}
.container{
    flex: 1;
    overflow: auto;
}
</style>
<body>
<div style=" display: flex;flex-direction: column;height: 100vh; ">
    <div class="header">
        <div style="height: 50px;background-color: #134974;color: white;display: flex;align-items:center;justify-content:space-between;padding: 0 5px;">
            <span style="font-size: 22px;">平安穗粤 智慧消防平台 - 运营管理后台VER 2.1</span>
            <span id="time" style="font-size: 22px;font-weight: bold"></span>
            <span style="font-size: 16px;font-weight: bold"><span>石井街道办事处，您好。</span>     <a style="margin-left:20px;color: white;text-underline: none" href="">数据大屏</a> <a style="color: white;text-underline: none" href="">退出</a></span>
        </div>
    </div>
    <div style="width: 100%;flex: 1;display: flex;">
        <div id="sidebar" class="sidebar">
            <admin-menu></admin-menu>
        </div>

        <div class="container" style="background-color: #F1F1F1;padding: 10px;">
            @section('content')
            @show
        </div>
    </div>
</div>
<script src="{{asset('statics/js/axios-interceptors.js')}}"></script>
<script>
    var token = "{!! $token ?? '' !!}";

    if(token){
        setCookie('X-Token',token,1);
    }

</script>


<script>
    Vue.use(httpVueLoader)
    new Vue({
        el: '#sidebar',
        data: {

        },
        created () {

        },
        components: {
            "admin-menu":  httpVueLoader('/statics/components/common/menu.vue'),
        },
        methods: {

        },

    })

    setInterval(function (){
        var date = new Date();
        var year = date.getFullYear();
        var month = String(date.getMonth()+1).padStart(2,'0');
        var day = String(date.getDay()).padStart(2,'0');
        document.getElementById('time').innerHTML = year + '-' + month + '-' + day + ' ' +date.toLocaleTimeString();
    }, 1000)


</script>

@section('script')
@show


</body>
</html>
