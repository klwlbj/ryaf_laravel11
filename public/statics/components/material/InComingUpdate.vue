<template>
    <div>
        <a-form-model :loading="loading" :model="formData" ref="dataForm" :label-col="dialogFormLabelCol" :wrapper-col="dialogFormWrapperCol" :rules="formRules">
            <a-form-model-item label="物品" prop="material_id">
                {{formData.name}}
            </a-form-model-item>

            <a-form-model-item label="生产日期" prop="production_date">
                <a-date-picker @change="productionDateChange" format="YYYY-MM-DD" v-model:value="formData.production_date"/>
            </a-form-model-item>

            <a-form-model-item label="质保期" prop="expire_date">
                <a-date-picker @change="expireDateChange" format="YYYY-MM-DD" v-model:value="formData.expire_date"/>
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
    name: 'materialInComing',
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
                name:'',
                production_date:moment().format("YYYY-MM-DD"),
                expire_date:moment().add(10, 'years').format("YYYY-MM-DD"),
                remark:'',
            };
        },
        submitData(){
            let that = this;
            console.log(this.formData);
            this.$refs.dataForm.validate((valid) => {
                if (valid) {
                    that.formData.id = this.id;
                    that.loading = true;
                    axios({
                        // 默认请求方式为get
                        method: 'post',
                        url: '/api/materialFlow/inComingUpdate',
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
                    name : res.data.mafl_material_name,
                    production_date : res.data.mafl_production_date,
                    expire_date : res.data.mafl_expire_date,
                    remark : res.data.mafl_remark,
                }
            }).catch(error => {
                this.$message.error('请求失败');
            });
        },
        materialChange(value){
            this.formData.material_id = value;
        },
        productionDateChange(value,str){
            this.formData.production_date = str;
        },
        expireDateChange(value,str){
            this.formData.expire_date = str;
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

