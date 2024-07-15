<template>
    <div>
        <a-form-model :loading="loading" :model="formData" ref="dataForm" :label-col="dialogFormLabelCol" :wrapper-col="dialogFormWrapperCol" :rules="formRules">
            <a-form-model-item label="街道" prop="street_id">
                <a-cascader v-model="formData.street_id" :options="areaList" placeholder="区-街道-村委/居委" />
            </a-form-model-item>

            <a-form-model-item label="详细地址" prop="address">
                <a-input v-model="formData.address" />
            </a-form-model-item>

            <a-form-model-item label="名称" prop="name">
                <a-input v-model="formData.name"/>
            </a-form-model-item>

            <a-form-model-item label="联系方式" prop="phone">
                <a-input-number v-model="formData.phone" size="large" :max="10000000000000000"/>
            </a-form-model-item>

            <a-form-model-item label="客户类型" prop="customer_type">
                <a-radio-group v-model="formData.customer_type">
                    <a-radio :value="1">
                        ToB
                    </a-radio>
                    <a-radio :value="2">
                        ToC
                    </a-radio>
                </a-radio-group>
            </a-form-model-item>

            <a-form-model-item label="预计安装总数" prop="advanced_total_installed">
                <a-input v-model="formData.advanced_total_installed" />
            </a-form-model-item>

            <a-form-model-item label="预付金额" prop="advanced_amount">
                <a-input v-model="formData.advanced_amount" />
            </a-form-model-item>

            <a-form-model-item label="付款方案" prop="payment_type">
                <a-radio-group v-model="formData.payment_type">
                    <a-radio :value="1">
                        预付
                    </a-radio>
                </a-radio-group>
            </a-form-model-item>

            <a-form-model-item label="收款方式" prop="pay_way">
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
                        扫二维码
                    </a-radio>
                </a-radio-group>
            </a-form-model-item>

            <a-form-model-item label="备注" prop="remark">
                <a-textarea
                    v-model="formData.remark"
                    placeholder="厂家备注"
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
            areaList: [],
            dialogFormLabelCol: { span: 4 },
            dialogFormWrapperCol: { span: 14 },
            formRules: {
                name: [{ required: true, message: '请输入名称', trigger: 'blur' }],
                address: [{ required: true, message: '请输入详细地址', trigger: 'blur' }],
                phone: [{ required: true, message: '请输入联系方式', trigger: 'blur' }],
                advanced_total_installed: [{ required: true, message: '请输入预计安装总数', trigger: 'blur' }],
                advanced_amount: [{ required: true, message: '请输入预付金额', trigger: 'blur' }],
            },
            loading :false,
            cid:undefined
        }
    },
    methods: {
        initForm(){
            // 获取区域和街道等
            this.getEnumList()

            this.formData= {
                street_id:[],
                // community:'',
                address:'',
                name:'',
                phone:'',
                remark:'',
                advanced_amount:0,
                advanced_total_installed:0,
                payment_type:1,
                customer_type:1,
                pay_way:1,
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
                    street_id: res.data.area_all_id,
                    name: res.data.name,
                    phone:res.data.phone,
                    address:res.data.address,
                    remark:res.data.remark,
                    advanced_amount:res.data.advanced_amount,
                    advanced_total_installed:res.data.advanced_total_installed,
                    payment_type:res.data.payment_type,
                    customer_type:res.data.customer_type,
                    pay_way:res.data.pay_way,
                }
            }).catch(error => {
                this.$message.error('请求失败');
            });
        }
    },
    created () {
      console.log(this.id)

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

