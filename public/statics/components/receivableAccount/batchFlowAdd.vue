<template>
    <div>
        <a-form-model :loading="loading" :model="formData" ref="dataForm" :label-col="dialogFormLabelCol" :wrapper-col="dialogFormWrapperCol" :rules="formRules">
            <a-form-model-item label="回款时间" prop="datetime">
                <a-date-picker @change="dateChange" show-time format="YYYY-MM-DD HH:mm:ss" :value="formData.datetime"/>
            </a-form-model-item>

            <a-form-model-item label="付款方式" prop="pay_way">
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

            <a-form-model-item label="录入类型" prop="funds_type">
                <a-radio-group v-model="formData.funds_type" button-style="solid">
                    <a-radio-button :value="1">金额</a-radio-button>
                    <a-radio-button :value="2">百分比（推进到xx百分比）</a-radio-button>
                </a-radio-group>
            </a-form-model-item>

            <a-form-model-item v-show="formData.funds_type === 1" label="实收款" prop="funds_received">
                <a-input-number v-model="formData.funds_received" :step="0.01"/>
            </a-form-model-item>
            <a-form-model-item v-show="formData.funds_type === 2" label="百分比" prop="funds_received">
                <a-input-number v-model="formData.funds_percent" :step="0.01" :formatter="(value)=>{ return value + '%'}"/>
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
    name: 'receivableAccountFlowAdd',
    components: {},
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
            imageList: [],
            dialogFormLabelCol: { span: 4 },
            dialogFormWrapperCol: { span: 14 },
            formRules: {
                datetime: [{ required: true, message: '请输入回款日期', trigger: 'blur' }],
                // funds_received: [{ required: true, message: '请输入回款金额', trigger: 'blur' }],
            },
            loading :false,
            cid:undefined
        }
    },
    methods: {
        moment,
        initForm(){
            this.formData = {
                datetime:moment().format('YYYY-MM-DD HH:mm:ss'),
                pay_way:5,
                funds_type:1,
                funds_received:0,
                funds_percent:0,
                remark:'',
            };
        },
        submitData(){
            let that = this;
            this.$refs.dataForm.validate((valid) => {
                if (valid) {
                    that.formData.list_query = JSON.stringify(that.listQuery);

                    axios({
                        // 默认请求方式为get
                        method: 'post',
                        url: '/api/receivableAccount/batchAddFlow',
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
                        that.$emit('submit');
                    }).catch(error => {
                        this.$message.error('请求失败');
                    });
                }else{
                    this.$message.error('表单验证失败');
                }
            })
        },
        dateChange(value,str){
            this.formData.datetime = str;
        }
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

</style>

