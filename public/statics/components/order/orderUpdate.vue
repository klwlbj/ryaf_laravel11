<template>
    <div>
        <a-form-model :loading="loading" :model="formData" ref="dataForm" :label-col="dialogFormLabelCol" :wrapper-col="dialogFormWrapperCol" :rules="formRules">
            <a-form-model-item label="订单编号">
                {{formData.order_iid}}
            </a-form-model-item>

            <a-form-model-item label="应收款" prop="order_account_receivable">
                <a-input-number v-model="formData.order_account_receivable" :step="0.01"/>
            </a-form-model-item>

            <a-form-model-item label="设备费用" prop="order_device_funds">
                <a-input-number v-model="formData.order_device_funds" :step="0.01"/>
            </a-form-model-item>


            <a-form-model-item :wrapper-col="{ span: 14, offset: 4 }">
                <a-button :loading="loading" type="primary" @click="submitData">
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
    name: 'orderUpdate',
    components: {

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
            dialogFormLabelCol: { span: 4 },
            dialogFormWrapperCol: { span: 14 },
            formRules: {

            },
            loading :false,
        }
    },
    methods: {
        moment,
        initForm(){
            this.formData= {
                order_iid:'',
                order_account_receivable:0,
                order_device_funds:0,
            };
        },
        submitData(){
            let that = this;
            // console.log(this.formData);
            this.$refs.dataForm.validate((valid) => {
                if (valid) {
                    that.formData.order_id = this.id;
                    that.loading = true;
                    axios({
                        // 默认请求方式为get
                        method: 'post',
                        url: '/api/order/update',
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
                        that.initForm();
                        that.$emit('submit');
                    }).catch(error => {
                        this.$message.error('请求失败');
                    });
                }else{
                    this.$message.error('表单验证失败');
                }
            })
        },
        getDetail(id){
            if(!id){
                this.$message.error('id不能为空');
                return false;
            }

            let that = this;
            that.loading = true;
            axios({
                // 默认请求方式为get
                method: 'post',
                url: '/api/order/getInfo',
                // 传递参数
                data: {
                    order_id:id
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
                    order_iid : res.data.order_iid,
                    order_account_receivable : res.data.order_account_receivable,
                    order_device_funds : res.data.order_device_funds
                }
            }).catch(error => {
                this.$message.error('请求失败');
            });
        },
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
.ant-form-item{
    margin: 0 0 15px;
}
</style>

