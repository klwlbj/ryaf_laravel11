<template>
    <div>
        <a-form-model :loading="loading" :model="formData" ref="dataForm" :label-col="dialogFormLabelCol" :wrapper-col="dialogFormWrapperCol" :rules="formRules">
            <a-form-model-item label="消耗日期" prop="datetime">
                <a-date-picker @change="dateChange" format="YYYY-MM-DD" v-model:value="formData.date"/>
            </a-form-model-item>

            <a-form-model-item label="消耗数量" prop="number">
                <a-input-number v-model="formData.number" :min="0"/>
            </a-form-model-item>

            <a-form-model-item required label="消耗人" prop="admin_id">
                <admin-select ref="adminSelect" :all="1" @change="adminChange"></admin-select>
            </a-form-model-item>


            <a-form-model-item label="备注" prop="remark">
                <a-textarea
                    v-model="formData.remark"
                    placeholder="备注"
                    :auto-size="{ minRows: 3, maxRows: 5 }"
                />
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
    name: 'consumeAdd',
    components: {
        "admin-select":  httpVueLoader('/statics/components/admin/adminSelect.vue'),
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
                number: [{ required: true, message: '请输入数量', trigger: 'blur' }],
            },
            loading :false,
            admin:{}
        }
    },
    methods: {
        moment,
        initForm(){
            this.formData= {
                number:0,
                date:moment().format("YYYY-MM-DD"),
                remark:'',
                admin_id:'',
            };

        },
        submitData(){
            let that = this;
            console.log(this.formData);
            this.$refs.dataForm.validate((valid) => {
                if (valid) {
                    that.loading = true;
                    that.formData.flow_id = that.id;
                    axios({
                        // 默认请求方式为get
                        method: 'post',
                        url: '/api/materialFlowConsume/addConsumeFlow',
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
        materialChange(value){
            this.formData.material_id = value;
        },
        dateChange(value,str){
            this.formData.date = str;
        },
        adminChange(value){
            this.formData.admin_id = value;
        }
    },
    created () {
        this.admin = JSON.parse(localStorage.getItem("admin"));
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
            // this.getDetail(newData);
        },
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

