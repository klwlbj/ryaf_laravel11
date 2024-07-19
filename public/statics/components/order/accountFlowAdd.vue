<template>
    <div>
        <a-form-model :loading="loading" :model="formData" ref="dataForm" :label-col="dialogFormLabelCol" :wrapper-col="dialogFormWrapperCol" :rules="formRules">
            <a-form-model-item label="回款时间" prop="datetime">
                <a-date-picker @change="dateChange" show-time format="YYYY-MM-DD HH:mm:ss" :default-value="moment().format('YYYY-MM-DD HH:mm:ss')"/>
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


            <a-form-model-item label="实收款" prop="funds_received">
                <a-input-number v-model="formData.funds_received" :step="0.01"/>
            </a-form-model-item>

            <a-form-model-item label="付款图" prop="image">
                <a-upload list-type="picture-card"
                          :file-list="imageList"
                          :remove="imageHandleRemove"
                          :before-upload="imageBeforeUpload"
                          @change="imageHandleChange" accept="image/*">

                    <div v-if="imageList.length < 1">
                        <a-icon type="plus" ></a-icon>
                        <div class="ant-upload-text">
                            上传付款图
                        </div>
                    </div>
                </a-upload>
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
            dialogFormLabelCol: { span: 4 },
            dialogFormWrapperCol: { span: 14 },
            formRules: {
                datetime: [{ required: true, message: '请输入回款日期', trigger: 'blur' }],
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
                datetime:moment().format('YYYY-MM-DD HH:mm:ss'),
                pay_way:5,
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

                    let image = [];
                    for(let item of that.imageList){
                        image.push({
                            url:item.url,
                            name:item.name,
                            ext:item.ext,
                        });
                    }

                    that.formData.image_list = JSON.stringify(image);
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
        imageHandleRemove(file){
            let index = this.imageList.indexOf(file);
            let newFileList = this.imageList.slice();
            newFileList.splice(index, 1);
            this.imageList = newFileList;
        },
        imageHandleChange(file){
            if(file.file.status && file.file.status === 'removed'){
                return false;
            }

            const formData = new FormData();
            formData.append('file', file.file);
            formData.append('type', 'order_account_flow');
            // formData.append('oss', 1);

            axios({
                // 默认请求方式为get
                method: 'post',
                url: '/api/upload',
                // 传递参数
                data: formData,
                responseType: 'json',
                headers:{
                    'Content-Type': 'multipart/form-data'
                }
            }).then(response => {
                let res = response.data;
                if(res.code !== 0){
                    this.$message.error(res.message);
                    return false;
                }
                this.imageList[this.imageList.length - 1].url = res.data.url;
                this.imageList[this.imageList.length - 1].name = res.data.name;
                this.imageList[this.imageList.length - 1].ext = res.data.ext;
            })
        },
        imageBeforeUpload(file) {
            const isJpgOrPng = file.type === 'image/jpeg' || file.type === 'image/png' || file.type === 'image/jpg';
            if (!isJpgOrPng) {
                this.$message.error('图片格式非法');
                return false;
            }
            const isLt5M = file.size / 1024 / 1024 < 5;
            if (!isLt5M) {
                this.$message.error('图片不能超过5M!');
                return false;
            }

            this.imageList = [...this.imageList, {
                url:'',
                uid:'-1',
                name: 'image',
                status: 'done',
            }];
            return false;
        },
        dateChange(value,str){
            this.formData.date = str;
        }
    },
    created () {
        this.initForm();
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
        }
    },
    computed: {

    }

}
</script>
<style scoped>

</style>

