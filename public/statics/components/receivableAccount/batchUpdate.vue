<template>
    <div>
        <a-form-model :loading="loading" :model="formData" ref="dataForm" :label-col="dialogFormLabelCol" :wrapper-col="dialogFormWrapperCol" :rules="formRules">
<!--            <a-form-model-item label="监控中心" prop="street">-->
<!--                <node-cascader :default-data="nodeId" @change="nodeChange"></node-cascader>-->
<!--            </a-form-model-item>-->

<!--            <a-form-model-item label="单位/用户" prop="user_name">-->
<!--                <a-input v-model="formData.user_name" />-->
<!--            </a-form-model-item>-->

<!--            <a-form-model-item label="联系方式" prop="user_mobile">-->
<!--                <a-input v-model="formData.user_mobile" />-->
<!--            </a-form-model-item>-->

<!--            <a-form-model-item label="安装总数" prop="installation_count">-->
<!--                <a-input-number v-model="formData.installation_count" />-->
<!--            </a-form-model-item>-->

<!--            <a-form-model-item label="赠送台数" prop="given_count">-->
<!--                <a-input-number v-model="formData.given_count" />-->
<!--            </a-form-model-item>-->

            <a-form-model-item label="设备应收款（单价）" prop="device_funds">
                <a-input-number v-model="formData.device_funds" :step="0.01"/>
            </a-form-model-item>

            <a-form-model-item label="应收款（单价）" prop="account_receivable">
                <a-input-number v-model="formData.account_receivable" :step="0.01"/>
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
    name: 'receivableAccountUpdate',
    components: {
        "node-cascader":  httpVueLoader('/statics/components/node/nodeCascader.vue'),
    },
    props: {
        listQuery: {
            default:function(){
                return {}
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
            nodeId:undefined
        }
    },
    methods: {
        moment,
        initForm(){
            this.formData= {
                // street:'',
                // user_name:'',
                // node_id:'',
                // user_mobile:'',
                installation_count:0,
                given_count:0,
                device_funds:120,
                account_receivable:240
            };
        },
        submitData(){
            let that = this;
            // console.log(this.formData);
            this.$refs.dataForm.validate((valid) => {
                if (valid) {
                    that.formData.list_query = JSON.stringify(that.listQuery);
                    that.loading = true;
                    axios({
                        // 默认请求方式为get
                        method: 'post',
                        url: '/api/receivableAccount/batchUpdate',
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
                        that.$emit('update');
                    }).catch(error => {
                        this.$message.error('请求失败');
                    });
                }else{
                    this.$message.error('表单验证失败');
                }
            })
        },
        nodeChange(value){
            this.formData.node_id = value;
        },
    },
    created () {
        this.initForm();
    },
    watch: {
        listQuery (newData,oldData) {
            if(newData === oldData){
                return false
            }

            if(newData === null){
                this.initForm();

                return false;
            }
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

