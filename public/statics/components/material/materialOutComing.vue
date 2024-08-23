<template>
    <div>
        <a-form-model :loading="loading" :model="formData" ref="dataForm" :label-col="dialogFormLabelCol" :wrapper-col="dialogFormWrapperCol" :rules="formRules">
            <a-form-model-item required label="物品" prop="material_id">
                <material-select  ref="materialSelect" :default-data="materialId" @change="materialChange"></material-select>
            </a-form-model-item>

            <a-form-model-item required label="仓库" prop="warehouse_id">
                <warehouse-select ref="warehouseSelect" :default-data="warehouseId" @change="warehouseChange"></warehouse-select>
            </a-form-model-item>

            <a-form-model-item required label="出库数量" prop="number">
                <a-input-number v-model="formData.number" :min="0"/>
            </a-form-model-item>

            <a-form-model-item required label="出库日期" prop="datetime">
                <a-date-picker show-time @change="dateChange" format="YYYY-MM-DD HH:mm:ss" v-model:value="formData.datetime"/>
            </a-form-model-item>

            <a-form-model-item required label="用途" prop="purpose">
                <a-select v-model="formData.purpose" show-search placeholder="请选择" :max-tag-count="1"
                          style="width: 200px;" allow-clear>
                    <a-select-option :value="1">
                        销售性质
                    </a-select-option>
                    <a-select-option :value="2">
                        非销售性质
                    </a-select-option>
                </a-select>
            </a-form-model-item>

            <a-form-model-item required label="申请人" prop="apply_user_id">
                <apply-user-select ref="applyUserSelect" @change="applyUserChange" :default-data="applyUserId"></apply-user-select>
            </a-form-model-item>

            <a-form-model-item required label="领用人" prop="receive_user_id">
                <admin-select ref="adminSelect" @change="receiveUserChange" :default-data="receiveUserId"></admin-select>
            </a-form-model-item>

            <a-form-model-item label="审批图" prop="file">
                <a-upload
                    :file-list="fileList"
                    :multiple="false"
                    :remove="fileHandleRemove"
                    :before-upload="fileBeforeUpload"
                    @change="fileHandleChange">

                    <a-button>
                        <a-icon type="upload" ></a-icon>
                        上传文件
                    </a-button>
                </a-upload>
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
    name: 'materialOutComing',
    components: {
        "warehouse-select":  httpVueLoader('/statics/components/material/warehouseSelect.vue'),
        "material-select":  httpVueLoader('/statics/components/material/materialSelect.vue'),
        "admin-select":  httpVueLoader('/statics/components/admin/adminSelect.vue'),
        "apply-user-select":  httpVueLoader('/statics/components/admin/adminSelect.vue'),
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
            fileList: [],
            dialogFormLabelCol: { span: 4 },
            dialogFormWrapperCol: { span: 14 },
            formRules: {
                // name: [{ required: true, message: '请输入类型名称', trigger: 'blur' }],
            },
            loading :false,
            materialId:undefined,
            receiveUserId:undefined,
            applyUserId:undefined,
            verifyUserId:undefined,
            warehouseId:2
        }
    },
    methods: {
        moment,
        initForm(){
            this.formData= {
                material_id:'',
                warehouse_id:2,
                number:0,
                datetime:moment().format("YYYY-MM-DD HH:mm:ss"),
                receive_user_id:null,
                apply_user_id:null,
                purpose:undefined,
                approve_image:'',
                verify_user_id:this.admin['department']['depa_leader_id'],
                remark:'',
            };

            this.receiveUserId = undefined;
            this.applyUserId = undefined;
            this.verifyUserId = this.admin['department']['depa_leader_id'];
            this.fileList = [];

            if(this.$refs['applyUserSelect']){
                this.$refs['applyUserSelect'].clearData();
            }

            if(this.$refs['adminSelect']){
                this.$refs['adminSelect'].clearData();
            }

            if(this.$refs['verifyUserSelect']){
                this.$refs['verifyUserSelect'].clearData();
            }
        },
        submitData(){
            let that = this;

            let file = [];
            for(let item of that.fileList){
                file.push({
                    url:item.url,
                    name:item.name,
                    ext:item.ext,
                });
            }
            that.formData.file_list = JSON.stringify(file);

            this.$refs.dataForm.validate((valid) => {
                if (valid) {
                    axios({
                        // 默认请求方式为get
                        method: 'post',
                        url: '/api/materialFlow/outComing',
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
        fileHandleRemove(file){
            // this.formData.file = '';
            let index = this.fileList.indexOf(file);
            let newFileList = this.fileList.slice();
            newFileList.splice(index, 1);
            this.fileList = newFileList;
        },
        fileHandleChange(file){
            if(file.file.status && file.file.status === 'removed'){
                return false;
            }

            const formData = new FormData();
            formData.append('file', file.file);
            formData.append('type', 'material_flow');
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
                this.fileList[this.fileList.length - 1].url = res.data.url;
                this.fileList[this.fileList.length - 1].name = res.data.name;
                this.fileList[this.fileList.length - 1].ext = res.data.ext;
            })
        },
        fileBeforeUpload(file) {
            this.fileList = [...this.fileList, {
                url:'',
                uid:'-1',
                name: 'file',
                status: 'done',
            }];
            return false;
        },
        getImage(url){
            if(url.substr(0,4).toLowerCase() == "http"){
                return url;
            }

            return 'http://' + window.location.hostname + url;
        },
        materialChange(value){
            this.formData.material_id = value;
        },
        dateChange(value,str){
            this.formData.datetime = str;
        },
        receiveUserChange(value){
            this.formData.receive_user_id = value;
        },
        applyUserChange(value){
            this.formData.apply_user_id = value;
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

