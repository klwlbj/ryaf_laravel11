<template>
    <div>
        <a-form-model :loading="loading" :model="formData" ref="dataForm" :label-col="dialogFormLabelCol" :wrapper-col="dialogFormWrapperCol" :rules="formRules">
            <a-form-model-item label="监控中心" prop="street">
                <node-cascader :default-data="nodeId" @change="nodeChange"></node-cascader>
            </a-form-model-item>

            <a-form-model-item label="单位/用户" prop="user_name">
                <a-input v-model="formData.user_name" />
            </a-form-model-item>

            <a-form-model-item label="联系方式" prop="user_mobile">
                <a-input v-model="formData.user_mobile" />
            </a-form-model-item>

            <a-form-model-item label="安装总数" prop="installation_count">
                <a-input-number v-model="formData.installation_count" />
            </a-form-model-item>

            <a-form-model-item label="赠送台数" prop="given_count">
                <a-input-number v-model="formData.given_count" />
            </a-form-model-item>

            <a-form-model-item label="应收款" prop="account_receivable">
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
            nodeId:undefined
        }
    },
    methods: {
        moment,
        initForm(){
            this.formData= {
                area:'',
                street:'',
                user_name:'',
                node_id:'',
                user_mobile:'',
                installation_count:0,
                given_count:0,
                account_receivable:0
            };
        },
        submitData(){
            let that = this;
            // console.log(this.formData);
            this.$refs.dataForm.validate((valid) => {
                if (valid) {
                    that.formData.receivable_id = this.id;
                    that.loading = true;
                    axios({
                        // 默认请求方式为get
                        method: 'post',
                        url: '/api/receivableAccount/update',
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
                url: '/api/receivableAccount/getInfo',
                // 传递参数
                data: {
                    receivable_id:id
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
                    area:res.data.reac_area,
                    street:res.data.reac_street,
                    user_name:res.data.reac_user_name,
                    user_mobile:res.data.reac_user_mobile,
                    node_id:res.data.reac_node_id,
                    installation_count:res.data.reac_installation_count,
                    given_count:res.data.reac_given_count,
                    account_receivable:res.data.reac_account_receivable ? res.data.reac_account_receivable : 0,
                }

                this.nodeId = res.data.order_node_arr;
            }).catch(error => {
                this.$message.error('请求失败');
            });
        },
        nodeChange(value){
            this.formData.node_id = value;
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

