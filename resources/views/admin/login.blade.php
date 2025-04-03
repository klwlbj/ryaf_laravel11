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
                        @keyup.enter="handleSubmit"
                    >
                        <a-icon slot="prefix" type="user" style="color: rgba(0,0,0,.25)"/>
                    </a-input>
                </a-form-item>
                <a-form-item>
                    <a-input
                        v-model="form.password"
                        type="password"
                        placeholder="密码"
                        @keyup.enter="handleSubmit"
                    >
                        <a-icon slot="prefix" type="lock" style="color: rgba(0,0,0,.25)"/>
                    </a-input>
                </a-form-item>
                <a-form-item>
                    <a-input
                        v-model="form.code"
                        type="password"
                        placeholder="验证码"
                        @keyup.enter="handleSubmit"
                        style="width:150px"
                    >
                        <a-icon slot="prefix" type="lock" style="color: rgba(0,0,0,.25)"/>
                    </a-input>
                    <img style="margin-left: 10px" :src="captchaImage" @click="refreshCaptcha" alt="验证码" style="cursor: pointer;">
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
                password: '',
                code:'',
            },
            captchaInput: '',
            captchaText: '',
            captchaImage: '',
            loading:false
        },
        created () {
            this.refreshCaptcha();
        },
        components: {

        },
        methods: {
            generateCaptcha() {
                const chars = 'ABCDEFGHJKMNPQRSTUVWXYZabcdefghjkmnpqrstuvwxyz23456789';
                let captcha = '';
                for (let i = 0; i < 4; i++) {
                    captcha += chars.charAt(Math.floor(Math.random() * chars.length));
                }
                return captcha;
            },

            // 创建验证码图片
            createCaptchaImage(text) {
                const canvas = document.createElement('canvas');
                const ctx = canvas.getContext('2d');

                canvas.width = 100;
                canvas.height = 40;

                // 绘制背景
                ctx.fillStyle = '#f3f3f3';
                ctx.fillRect(0, 0, canvas.width, canvas.height);

                // 绘制文字
                ctx.font = '24px Arial';
                ctx.fillStyle = '#333';
                ctx.textBaseline = 'middle';
                ctx.textAlign = 'center';
                ctx.fillText(text, canvas.width/2, canvas.height/2);

                // 添加干扰线
                for (let i = 0; i < 3; i++) {
                    ctx.beginPath();
                    ctx.moveTo(Math.random() * canvas.width, Math.random() * canvas.height);
                    ctx.lineTo(Math.random() * canvas.width, Math.random() * canvas.height);
                    ctx.strokeStyle = '#999';
                    ctx.stroke();
                }

                return canvas.toDataURL();
            },

            // 刷新验证码
            refreshCaptcha() {
                this.captchaText = this.generateCaptcha();
                this.captchaImage = this.createCaptchaImage(this.captchaText);
                this.error = '';
            },
            handleSubmit(){
                if (this.form.code.toLowerCase() !== this.captchaText.toLowerCase()) {
                    this.$message.error('验证码有误');
                    this.refreshCaptcha();
                    return false;
                }
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
                    // setCookie('X-Token',res.data.token,1);
                    localStorage.setItem("X-Token",res.data.token);
                    localStorage.setItem("menu",JSON.stringify(res.data.menu));
                    localStorage.setItem("permission",JSON.stringify(res.data.permission));
                    localStorage.setItem("admin",JSON.stringify(res.data.admin));
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
