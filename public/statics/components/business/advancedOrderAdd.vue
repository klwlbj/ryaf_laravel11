<template>
    <div>
        <a-form-model :loading="loading" :model="formData" ref="dataForm" :label-col="dialogFormLabelCol" :wrapper-col="dialogFormWrapperCol" :rules="formRules">
            <a-form-model-item label="监控中心" prop="street">
                <node-cascader :default-data="nodeId" @change="nodeChange"></node-cascader>
            </a-form-model-item>

            <a-form-model-item label="安装日期" prop="installation_date">
                <a-date-picker format="YYYY-MM-DD" v-model:value="formData.installation_date"/>
            </a-form-model-item>

            <a-form-model-item label="收款日期" prop="pay_date">
                <a-date-picker format="YYYY-MM-DD" v-model:value="formData.pay_date"/>
            </a-form-model-item>

            <a-form-model-item label="用户名" prop="user_name">
                <a-input v-model="formData.user_name" style="width: 200px;"/>
            </a-form-model-item>

            <a-form-model-item label="联系方式" prop="user_phone">
                <a-input v-model="formData.user_phone" style="width: 200px;"/>
            </a-form-model-item>

            <a-form-model-item label="预计安装总数" prop="installation_count">
                <a-input-number v-model="formData.installation_count"/>
            </a-form-model-item>

            <a-form-model-item label="预付金额" prop="funds_received">
                <a-input-number v-model="formData.funds_received"  :step="0.01"/>
            </a-form-model-item>

            <a-form-model-item label="收款方式" prop="pay_way">
                <a-radio-group v-model="formData.pay_way">
                    <!--                    <a-radio :value="1">-->
                    <!--                        微信-->
                    <!--                    </a-radio>-->
                    <!--                    <a-radio :value="2">-->
                    <!--                        支付宝-->
                    <!--                    </a-radio>-->
                    <a-radio :value="3">
                        银行
                    </a-radio>
                    <a-radio :value="4">
                        现金
                    </a-radio>
                    <a-radio :value="5">
                        二维码
                    </a-radio>
                </a-radio-group>
            </a-form-model-item>

            <a-form-model-item label="备注" prop="remark">
                <a-textarea
                    v-model="formData.remark"
                    placeholder="备注"
                    :auto-size="{ minRows: 3, maxRows: 5 }"
                />
            </a-form-model-item>

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
    name: 'advancedOrderAdd',
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
                funds_received: [{ required: true, message: '请输入预付金额', trigger: 'blur' }],
            },
            loading :false,
            nodeId:undefined
        }
    },
    methods: {
        moment,
        initForm(){
            // 获取区域和街道等
            this.getEnumList()

            this.formData= {
                user_name:'',
                user_phone:'',
                installation_count:0,
                funds_received:0,
                installation_date:moment().format("YYYY-MM-DD"),
                pay_date:moment().format("YYYY-MM-DD"),
                remark:'',
                node_id:undefined,
                pay_way:3,
            };
        },
        // 获取枚举列表
        getEnumList () {
            axios({
                // 默认请求方式为get
                method: 'post',
                url: '/api/area/getList',
                // 传递参数
                responseType: 'json',
                headers:{
                    'Content-Type': 'multipart/form-data'
                }
            }).then(response => {
                let res = response.data;
                this.areaList = res.data.areaList
            }).catch(error => {
                this.$message.error('请求失败');
            });
        },
        submitData(){
            let that = this;
            this.$refs.dataForm.validate((valid) => {
                if (valid) {
                    if(that.id){
                        that.formData.id = that.id;
                        axios({
                            method: 'post',
                            url: '/api/advancedOrder/update',
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
                        axios({
                            method: 'post',
                            url: '/api/advancedOrder/add',
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
                        });
                    }
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
            this.loading = true;
            axios({
                // 默认请求方式为get
                method: 'post',
                url: '/api/advancedOrder/getInfo',
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
                    node_id: res.data.ador_node_id,
                    installation_date: res.data.ador_installation_date,
                    pay_date:res.data.ador_pay_date,
                    user_name:res.data.ador_user_name,
                    user_phone:res.data.ador_user_phone,
                    installation_count:res.data.ador_installation_count,
                    funds_received:res.data.ador_funds_received,
                    pay_way:res.data.ador_pay_way,
                    remark:res.data.ador_remark,
                }

                this.nodeId = res.data.ador_node_arr;
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
            console.log(newData);
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

