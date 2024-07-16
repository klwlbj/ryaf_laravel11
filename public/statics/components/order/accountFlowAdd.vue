<template>
    <div>
        <a-form-model :loading="loading" :model="formData" ref="dataForm" :label-col="dialogFormLabelCol" :wrapper-col="dialogFormWrapperCol" :rules="formRules">
            <a-form-model-item label="回款日期" prop="date">
                <a-date-picker @change="dateChange" format="YYYY-MM-DD" :default-value="moment().format('YYYY-MM-DD')"/>
            </a-form-model-item>

            <a-form-model-item label="付款方式" prop="pay_way">
                <a-radio-group v-model="formData.pay_way">
                    <a-radio :value="1">
                        微信
                    </a-radio>
                    <a-radio :value="2">
                        支付宝
                    </a-radio>
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


            <a-form-model-item label="实收款" prop="funds_received">
                <a-input-number v-model="formData.funds_received" :step="0.01"/>
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
    name: 'accountFlowAdd',
    components: {},
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
                date: [{ required: true, message: '请输入回款日期', trigger: 'blur' }],
                funds_received: [{ required: true, message: '请输入回款金额', trigger: 'blur' }],
            },
            loading :false,
            cid:undefined
        }
    },
    methods: {
        moment,
        initForm(){
            this.formData= {
                date:moment().format('YYYY-MM-DD'),
                pay_way:1,
                funds_received:0,
                remark:'',
            };
        },
        submitData(){
            let that = this;
            this.$refs.dataForm.validate((valid) => {
                if (valid) {
                    if(!that.id){
                        this.$message.error('order_id不能为空');
                        return false;
                    }
                    that.formData.order_id = that.id;
                    axios({
                        // 默认请求方式为get
                        method: 'post',
                        url: '/api/order/addAccountFlow',
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
                }else{
                    this.$message.error('表单验证失败');
                }
            })
        },
        dateChange(value,str){
            this.formData.date = str;
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

