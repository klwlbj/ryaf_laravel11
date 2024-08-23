<template>
    <div>
        <a-form-model :loading="loading" :model="formData" ref="dataForm" :label-col="dialogFormLabelCol" :wrapper-col="dialogFormWrapperCol" :rules="formRules">
            <a-form-model-item label="物品" prop="material_id">
                <material-select  ref="materialSelect" :default-data="materialId" @change="materialChange"></material-select>
            </a-form-model-item>

            <a-form-model-item label="仓库" prop="warehouse_id">
                <warehouse-select ref="warehouseSelect" :default-data="warehouseId" @change="warehouseChange"></warehouse-select>
            </a-form-model-item>

            <a-form-model-item label="入库数量" prop="number">
                <a-input-number v-model="formData.number" :min="0"/>
            </a-form-model-item>

            <a-form-model-item label="生产日期" prop="production_date">
                <a-date-picker @change="productionDateChange" format="YYYY-MM-DD" v-model:value="formData.production_date"/>
            </a-form-model-item>

            <a-form-model-item label="质保期" prop="expire_date">
                <a-date-picker @change="expireDateChange" format="YYYY-MM-DD" v-model:value="formData.expire_date"/>
            </a-form-model-item>

            <a-form-model-item label="入库时间" prop="datetime">
                <a-date-picker @change="dateChange" show-time format="YYYY-MM-DD HH:mm:ss" v-model:value="formData.datetime"/>
            </a-form-model-item>

            <a-form-model-item label="最终确认人" prop="verify_user_id">
                <verify-user-select ref="verifyUserSelect" @change="verifyUserChange" :default-data="verifyUserId"></verify-user-select>
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
        "warehouse-select":  httpVueLoader('/statics/components/material/warehouseSelect.vue'),
        "material-select":  httpVueLoader('/statics/components/material/materialSelect.vue'),
        "verify-user-select":  httpVueLoader('/statics/components/admin/adminSelect.vue'),
    },
    props: {
        id: {
            default:function(){
                return null
            },
        },
        defaultMaterialId: {
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
                material_id: [{ required: true, message: '请选择物品', trigger: 'change' }],
                number: [{ required: true, message: '请输入数量', trigger: 'blur' }],
            },
            loading :false,
            materialId:undefined,
            warehouseId:2,
            verifyUserId:undefined,
            admin:{}
        }
    },
    methods: {
        moment,
        initForm(){
            this.formData= {
                material_id:'',
                warehouse_id:2,
                number:0,
                production_date:moment().format("YYYY-MM-DD"),
                expire_date:moment().add(10, 'years').format("YYYY-MM-DD"),
                datetime:moment().format("YYYY-MM-DD HH:mm:ss"),
                remark:'',
                verify_user_id:this.admin['department']['depa_leader_id'],
            };

            this.verifyUserId = this.admin['department']['depa_leader_id'];

            if(this.$refs['verifyUserSelect']){
                this.$refs['verifyUserSelect'].clearData();
            }
        },
        submitData(){
            let that = this;
            console.log(this.formData);
            this.$refs.dataForm.validate((valid) => {
                if (valid) {
                    that.loading = true;
                    axios({
                        // 默认请求方式为get
                        method: 'post',
                        url: '/api/materialFlow/inComing',
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
        productionDateChange(value,str){
            this.formData.production_date = str;
        },
        expireDateChange(value,str){
            this.formData.expire_date = str;
        },
        dateChange(value,str){
            this.formData.datetime = str;
        },
        warehouseChange(value){
            this.formData.warehouse_id = value;
        },
        verifyUserChange(value){
            this.formData.verify_user_id = value;
        }
    },
    created () {
        this.admin = JSON.parse(localStorage.getItem("admin"));
        this.initForm();
        if(this.defaultMaterialId){
            this.materialId = this.defaultMaterialId;
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
            // this.getDetail(newData);
        },
        defaultMaterialId (newData,oldData) {
            if(newData === oldData){
                return false
            }

            this.materialId = newData;
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

