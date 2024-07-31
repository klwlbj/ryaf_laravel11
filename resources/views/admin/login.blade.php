<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>如约安防</title>
    <meta name="renderer" content="webkit">
    <meta http-equiv="X-UA-Compatible" content="IE=edge,chrome=1">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, minimum-scale=1.0, maximum-scale=1.0, user-scalable=0">
    <link rel="stylesheet" type="text/css"  href="{{asset('statics/css/antd.min.css')}}"></script>
</head>
<script src="{{asset('statics/js/vue.js')}}"></script>
<script src="{{asset('statics/js/moment.js')}}"></script>
<script src="{{asset('statics/js/moment-zh-cn.js')}}"></script>
<script src="{{asset('statics/js/httpVueLoader.js')}}"></script>
<script src="{{asset('statics/js/antd.min.js')}}"></script>
<script src="{{asset('statics/js/axios.min.js')}}"></script>
<script src="{{asset('statics/js/cookie.js')}}"></script>
<style>
    .login-form {
        max-width: 400px;
        margin: 0 auto 0;
        top: 10px;
        background: #ffffff;
        margin-top: calc((100vh - 515px) / 2);
    }
    .login-button {
        width: 100%;
    }
</style>
<body style="background:url({{asset('/statics/image/bg.png')}}); background-attachment: fixed; background-repeat: no-repeat; background-size: cover;">
<div id="app">
    <div style="height: 10px"></div>
    <div class="login-form">
        <div style="padding:15px; border-bottom:1px solid #EEEEEE; background-color:#5D87AF; color:#ffffff; font-size:16px;">如约安防信息化系统登录</div>
        <div style="padding:15px;">
            <a-form
                id="components-form-demo-normal-login"
            >
                <a-form-item>
                    <a-input
                        v-model="form.mobile"
                        placeholder="账号"
                    >
                        <a-icon slot="prefix" type="user" style="color: rgba(0,0,0,.25)"/>
                    </a-input>
                </a-form-item>
                <a-form-item>
                    <a-input
                        v-model="form.password"
                        type="password"
                        placeholder="密码"
                    >
                        <a-icon slot="prefix" type="lock" style="color: rgba(0,0,0,.25)"/>
                    </a-input>
                </a-form-item>
                <a-form-item>
                    <a-button @click="handleSubmit" :loading="loading" type="primary" class="login-button">
                        登录
                    </a-button>
                </a-form-item>
            </a-form>
        </div>

    </div>

</div>
<script src="{{asset('statics/js/axios-interceptors.js')}}"></script>

<script>
    Vue.use(httpVueLoader)

    new Vue({
        el: '#app',
        data: {
            form: {
                username: '',
                password: ''
            },
            loading:false
        },
        created () {

        },
        components: {

        },
        methods: {
            handleSubmit(){
                this.loading = true
                axios({
                    // 默认请求方式为get
                    method: 'post',
                    url: '/api/login',
                    // 传递参数
                    data: this.form,
                    responseType: 'json',
                    headers:{
                        'Content-Type': 'multipart/form-data'
                    }
                }).then(response => {
                    this.loading = false
                    let res = response.data;
                    if(res.code != 0){
                        this.$message.error(res.message);
                        return false;
                    }
                    setCookie('X-Token',res.data.token,1);
                    localStorage.setItem("menu",JSON.stringify(res.data.menu));
                    localStorage.setItem("permission",JSON.stringify(res.data.permission));
                    window.location.href = '/'
                }).catch(error => {
                    this.$message.error('请求失败');
                });
            }
        },

    })

</script>


</body>
</html>
