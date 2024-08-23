<template>
    <div>
        <a-form-model :loading="loading" :model="formData" ref="dataForm" :label-col="dialogFormLabelCol" :wrapper-col="dialogFormWrapperCol" :rules="formRules">
            <a-form-model-item label="单价（含税）" prop="price_tax">
                <a-input-number v-model="formData.price_tax" :step="0.01"/>
            </a-form-model-item>


            <a-form-model-item label="税率" prop="tax">
                <a-input-number v-model="formData.tax" :step="0.01" :formatter="(value)=>{ return value + '%'}"/>
            </a-form-model-item>

            <a-form-model-item label="发票类型" prop="invoice_type">
                <a-radio-group v-model="formData.invoice_type">
                    <a-radio :value="0">
                        未确认
                    </a-radio>
                    <a-radio :value="1">
                        专票
                    </a-radio>
                    <a-radio :value="2">
                        普票
                    </a-radio>
                </a-radio-group>
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
    name: 'setFlowPrice',
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
                price_tax: [{ required: true, message: '请输入单价（含税）', trigger: 'blur' }],
                tax: [{ required: true, message: '请输入税率', trigger: 'blur' }],
            },
            loading :false,
            cid:undefined
        }
    },
    methods: {
        initForm(){
            this.formData= {
                price:0,
                tax:0,
                invoice_type:1
            };
        },
        submitData(){
            let that = this;
            this.$refs.dataForm.validate((valid) => {
                if (valid) {
                    if(that.id){
                        this.loading = true;
                        that.formData.id = that.id;
                        axios({
                            // 默认请求方式为get
                            method: 'post',
                            url: '/api/materialFlow/setPrice',
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
                            this.loading = false;
                            this.$message.error('请求失败');
                        });
                    }else{
                        this.$message.error('记录id不能为空');
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
                url: '/api/materialFlow/getInfo',
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
                    price_tax: res.data.mafl_price_tax,
                    tax: res.data.mafl_tax,
                    invoice_type: res.data.mafl_invoice_type,
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

