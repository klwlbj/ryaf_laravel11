<template>
    <div>
        <a-form-model :loading="loading" :model="formData" ref="dataForm" :label-col="dialogFormLabelCol" :wrapper-col="dialogFormWrapperCol" :rules="formRules">
          <a-form-item prop="phone" label="手机号">
            <a-input
                v-model="formData.phone"
                placeholder="手机号"
            >
              <a-icon slot="prefix" type="user" style="color: rgba(0,0,0,.25)"/>
            </a-input>
          </a-form-item>
          <a-form-item prop="name" label="姓名">
            <a-input
                v-model="formData.name"
                placeholder="姓名"
            >
              <a-icon slot="prefix" type="user" style="color: rgba(0,0,0,.25)"/>
            </a-input>
          </a-form-item>
          <a-form-item prop="number" label="数量">
            <a-input-number
                v-model="formData.number"
                placeholder="数量"
                :min="1" :max="20000"
            >
              <a-icon slot="prefix" type="number" style="color: rgba(0,0,0,.25)"/>
            </a-input-number>
          </a-form-item>
          <a-form-item prop="date" label="安装日期">
            <a-date-picker @change="dateChange" v-model:value="formData.date" format="YYYY-MM-DD" />
            <a-icon slot="prefix" type="date" style="color: rgba(0,0,0,.25)"/>
          </a-form-item>

          <a-form-item prop="address" label="地址">
            <div v-for="(item,index) in formData.address_list" :key="index">
              <standard-address-select :default-data="item" :id="item.code" @change="(value) => {addressChange(value,index)}" width="100%">

              </standard-address-select>
            </div>
            <a-icon slot="prefix" type="address" style="color: rgba(0,0,0,.25)"/>

          </a-form-item>

          <a-form-item prop="handwritten_address" label="手写地址(选填)">
            <a-input
                v-model="formData.handwritten_address"
                placeholder="手写地址"
            >
            </a-input>
          </a-form-item>

            <a-form-model-item :wrapper-col="{ span: 14, offset: 4 }">
                <a-button type="primary" @click="submitData">
                    确认
                </a-button>
                <a-button style="margin-left: 10px;" @click="$emit('close')">
                    取消
                </a-button>
            </a-form-model-item>

        </a-form-model>
    </div>
</template>

<script>
module.exports = {
    name: 'manufacturerAdd',
    components: {
      "standard-address-select":  httpVueLoader('/statics/components/installation/standardAddressSelect.vue'),
    },
    props: {
        id: {
            default:function(){
                return null
            },
        },
    },
    data () {
        return {
            formData: {

            },
            imageList: [],
            listImageList: [],
            dialogFormLabelCol: { span: 4 },
            dialogFormWrapperCol: { span: 14 },
            formRules: {
                name: [{ required: true, message: '请输入厂家名称', trigger: 'blur' }],
            },
            loading :false,
            cid:undefined
        }
    },
    methods: {
        initForm(){
            this.formData= {
                name:'',
                phone:'',
                number:'',
                date : '',
              address_list:[
                {
                  code:'',
                  standard_address:'',
                  addr_generic_name:'',
                  addr_room:'',
                  install_location:''
                }
              ],
                handwritten_address : '',
            };
        },
        submitData(){
            let that = this;
            this.$refs.dataForm.validate((valid) => {
                if (valid) {
                    if(that.id){
                        that.formData.id = that.id;
                        axios({
                            // 默认请求方式为get
                            method: 'post',
                            url: '/api/preInstallation/update',
                            // 传递参数
                            data: that.formData,
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
                            this.initForm();
                            that.$emit('update');
                        }).catch(error => {
                            this.$message.error('请求失败');
                        });
                    }else{
                        /*axios({
                            // 默认请求方式为get
                            method: 'post',
                            url: '/api/preInstallation/add',
                            // 传递参数
                            data: that.formData,
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
                            this.initForm();
                            that.$emit('add');
                        }).catch(error => {
                            this.$message.error('请求失败');
                        });*/
                    }
                }else{
                    this.$message.error('表单验证失败');
                }
            })
        },
      dateChange(value,str){
        this.formData.date = str;
      },
      addressChange(value,index){
        this.formData.address_list[index] = value;
      },
        getDetail(id){
            if(!id){
                this.$message.error('id不能为空');
                return false;
            }
            this.loading = true;
            axios({
                // 默认请求方式为get
                method: 'post',
                url: '/api/preInstallation/getInfo',
                // 传递参数
                data: {
                    id:id
                },
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
                this.formData = {
                  name:res.data.name,
                  phone:res.data.phone,
                  number:res.data.installation_count,
                  date : res.data.registration_date,
                  address_list:[
                    {
                      code:res.data.address_code,
                      standard_address:res.data.address,
                      addr_generic_name:'',
                      addr_room:'',
                      install_location:''
                    }
                  ],
                  handwritten_address : res.data.handwritten_address,
                }
            }).catch(error => {
                this.$message.error('请求失败');
            });
        }
    },
    created () {
        this.initForm();
        if(this.id){
            this.getDetail(this.id);
        }
    },
    watch: {
        id (newData,oldData) {
            if(newData === oldData){
                return false
            }

            if(newData === null){
                this.initForm();

                return false;
            }

            this.getDetail(newData);
        }
    },
    computed: {

    }

}
</script>
<style scoped>

</style>

