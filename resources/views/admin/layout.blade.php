<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>如约安防</title>
    <meta name="renderer" content="webkit">
    <meta http-equiv="X-UA-Compatible" content="IE=edge,chrome=1">
    <link rel="icon" href="{{asset('statics/image/icon.jpg')}}" type="image/x-icon"/>
    <meta name="viewport" content="width=device-width, initial-scale=1.0, minimum-scale=1.0, maximum-scale=1.0, user-scalable=0">
    <link rel="stylesheet" type="text/css"  href="{{asset('statics/css/antd.min.css')}}">
    @section('link')
    @show
</head>
<script src="{{asset('statics/js/moment.js')}}"></script>
<script src="{{asset('statics/js/moment-zh-cn.js')}}"></script>
<script src="{{asset('statics/js/vue.js')}}"></script>
<script src="{{asset('statics/js/antd.min.js')}}"></script>
<script src="{{asset('statics/js/httpVueLoader.js')}}"></script>
<script src="{{asset('statics/js/axios.min.js')}}"></script>
<script src="{{asset('statics/js/cookie.js')}}"></script>
<style>
.sidebar{
    border-radius: 0;
    width: 150px;
    background-color: #053434 !important;
}
.container{
    flex: 1;
    overflow: auto;
}
</style>
<body>
<div style=" display: flex;flex-direction: column;height: 100vh; ">
    <div id='header' class="header">
        <div style="height: 50px;background-color: #053434;color: white;display: flex;align-items:center;justify-content:space-between;padding: 0 5px;">
            <span style="font-size: 22px;">如约安防信息化系统后台VER 1.0</span>
            <span id="time" style="font-size: 22px;font-weight: bold"></span>
            <span style="font-size: 16px;font-weight: bold">
                <a-dropdown>
                    <span>{{ $adminInfo['admin_name'] }}，您好。</span>
                    <template #overlay>
                      <a-menu>
                        <a-menu-item>
                          <a href="javascript:;" @click="onRestPassword">修改密码</a>
                        </a-menu-item>
                      </a-menu>
                    </template>
                  </a-dropdown>

                <a style="color: white;text-underline: none" href="javascript:void(0);" onclick="logout()">退出</a>
            </span>
        </div>
        <a-modal
            title="修改密码"
            v-model="visible"
            ok-text="提交"
            @ok="submit"
        >
            <a-form-model :loading="loading" :model="form" ref="form" :label-col="dialogFormLabelCol" :wrapper-col="dialogFormWrapperCol" :rules="formRules">
                <a-form-model-item required label="原密码" prop="password">
                    <a-input-password v-model="form.password" autocomplete="off"/>
                </a-form-model-item>

                <a-form-model-item required label="新密码" prop="new_password">
                    <a-input-password v-model="form.new_password" autocomplete="off"/>
                </a-form-model-item>

                <a-form-model-item required label="确认密码" prop="confirm_password">
                    <a-input-password v-model="form.confirm_password" autocomplete="off"/>
                </a-form-model-item>
            </a-form-model>
        </a-modal>
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
    Vue.prototype.$checkPermission = function(key){
        let permission = JSON.parse(localStorage.getItem("permission"));
        return (permission[key] !== 0);
    }
    moment.locale('zh-cn');
    function logout(){
        deleteCookie('X-Token');
        localStorage.removeItem("menu");
        localStorage.removeItem("permission");
        localStorage.removeItem("admin");
        window.location.href='/login';
    }

</script>



@section('script')
@show

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
    // setInterval(function (){
    //     var date = new Date();
    //     var year = date.getFullYear();
    //     var month = String(date.getMonth()+1).padStart(2,'0');
    //     var day = String(date.getDay()).padStart(2,'0');
    //     document.getElementById('time').innerHTML = year + '-' + month + '-' + day + ' ' +date.toLocaleTimeString();
    // }, 1000)
</script>

<script>
    new Vue({
        el: '#header',
        data: {
            form: {
                password: '',
                new_password: '',
                confirm_password: ''
            },
            dialogFormLabelCol: { span: 4 },
            dialogFormWrapperCol: { span: 14 },
            visible:false,
            loading:false,
            formRules:{

            }
        },
        created () {

        },
        components: {

        },
        methods: {
            onRestPassword(){
                this.visible = true;
            },
            submit(){
                this.loading = true;
                axios({
                    // 默认请求方式为get
                    method: 'post',
                    url: '/api/admin/resetPassword',
                    // 传递参数
                    data: this.form,
                    responseType: 'json',
                    headers:{
                        'Content-Type': 'multipart/form-data'
                    }
                }).then(response => {
                    this.loading = false;
                    let res = response.data;
                    if(res.code !== 0){
                        this.$message.error(res.message);
                        return false;
                    }
                    this.form = {
                        password: '',
                        new_password: '',
                        confirm_password: ''
                    }
                    this.visible = false;
                }).catch(error => {
                    this.$message.error('请求失败');
                });
                console.log(this.form);
            }
        },

    })
</script>

</body>
</html>
