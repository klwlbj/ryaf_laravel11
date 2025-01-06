<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>如约安防烟感报装录入</title>
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
        /*max-width: 400px;*/
        margin: 0 auto 0;
        top: 10px;
        background: #ffffff;
        /*margin-top: calc((100vh - 900px) / 2);*/
    }
    .login-button {
        width: 100%;
    }
</style>
<body style="background:url({{asset('/statics/image/bg.png')}}); background-attachment: fixed; background-repeat: no-repeat; background-size: cover;">
<div id="app">
    <div style="height: 10px"></div>
    <div class="login-form">
        <div style="padding:15px; border-bottom:1px solid #EEEEEE; background-color:#5D87AF; color:#ffffff; font-size:16px;">如约安防烟感报装录入</div>
        <div style="padding:15px;">
            <a-form
                id="components-form-demo-normal-login"
            >
                <a-form-item prop="phone" label="手机号">
                    <a-input
                        v-model="form.phone"
                        placeholder="手机号"
                        @keyup.enter="handleSubmit"
                    >
                        <a-icon slot="prefix" type="user" style="color: rgba(0,0,0,.25)"/>
                    </a-input>
                </a-form-item>
                <a-form-item prop="name" label="姓名">
                    <a-input
                            v-model="form.name"
                            placeholder="姓名"
                            @keyup.enter="handleSubmit"
                    >
                        <a-icon slot="prefix" type="user" style="color: rgba(0,0,0,.25)"/>
                    </a-input>
                </a-form-item>
                <a-form-item prop="number" label="数量">
                    <a-input-number
                            v-model="form.number"
                            placeholder="数量"
                            @keyup.enter="handleSubmit"
                            :min="1" :max="20000"
                    >
                        <a-icon slot="prefix" type="number" style="color: rgba(0,0,0,.25)"/>
                    </a-input-number>
                </a-form-item>
                <a-form-item prop="date" label="安装日期">
                    <a-date-picker @change="dateChange" v-model:value="form.date" format="YYYY-MM-DD" />
                        <a-icon slot="prefix" type="date" style="color: rgba(0,0,0,.25)"/>

                </a-form-item>

                <a-form-item prop="address" label="地址">
                    <div v-for="(item,index) in form.address_list" :key="index">
                        <standard-address-select :default-data="item" :id="item.code" @change="(value) => {addressChange(value,index)}" width="100%">

                        </standard-address-select>
                    </div>
                    <a-icon slot="prefix" type="address" style="color: rgba(0,0,0,.25)"/>

{{--                    <a-button type="link" @click="addressAdd" block>新增地址</a-button>--}}
                </a-form-item>

                <a-form-item prop="handwritten_address" label="手写地址(选填)">
                    <a-input
                            v-model="form.handwritten_address"
                            placeholder="手写地址"
                            @keyup.enter="handleSubmit"
                    >
{{--                        <a-icon slot="prefix" type="address" style="color: rgba(0,0,0,.25)"/>--}}
                    </a-input>
                </a-form-item>
                <a-form-item>
                    <a-button @click="handleSubmit" :loading="loading" type="primary" class="login-button">
                        提交
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
            postform:{},
            form: {
                date: '',
                handwritten_address: '',
                phone: '',
                name: '',
                number: 1,
                address_list:[
                    {
                        code:'',
                        standard_address:'',
                        addr_generic_name:'',
                        addr_room:'',
                        install_location:''
                    }
                ],
            },
            loading:false
        },

        created () {

        },
        components: {
            "standard-address-select":  httpVueLoader('/statics/components/installation/standardAddressSelect.vue'),
        },
        methods: {
            addressAdd(){
                this.form.address_list.push({
                    code:'',
                    standard_address:'',
                    addr_generic_name:'',
                    addr_room:'',
                    install_location:''
                });
            },
            addressChange(value,index){
                this.form.address_list[index] = value;
            },
            handleSubmit(){
                this.loading = true
                this.postform.address_list = JSON.stringify(this.form.address_list);
                this.postform.handwritten_address = this.form.handwritten_address;
                this.postform.phone = this.form.phone;
                this.postform.date = this.form.date;
                this.postform.name = this.form.name;
                this.postform.number = this.form.number;
                axios({
                    // 默认请求方式为get
                    method: 'post',
                    url: '/api/addPreInstallation',
                    // 传递参数
                    data: this.postform,
                    responseType: 'json',
                    headers:{
                        'Content-Type': 'multipart/form-data'
                    }
                }).then(response => {
                    this.loading = false
                    let res = response.data;
                    if(res.code !== 0){
                        this.$message.error(res.message);
                        return false;
                    }
                    this.$message.success('添加成功！5秒后刷新');
                    setTimeout(() => {
                        location.reload();
                    }, 5000); // 5000毫秒（即5秒）
                }).catch(error => {
                    this.$message.error('请求失败');
                });
            },
            dateChange(value,str){
                this.form.date = str;
            },
        },

    })

</script>


</body>
</html>
