<template>
    <div>
        <a-form-model :loading="loading" :model="formData" ref="dataForm" :label-col="dialogFormLabelCol" :wrapper-col="dialogFormWrapperCol" :rules="formRules">
            <a-form-model-item label="部门" prop="department_id">
                <department-tree ref="departmentTree" :default-data="departmentId" @change="departmentChange"></department-tree>
            </a-form-model-item>

            <a-form-model-item label="名称" prop="name">
                <a-input v-model="formData.name" />
            </a-form-model-item>

            <a-form-model-item label="手机号" prop="mobile">
                <a-input v-model="formData.mobile" />
            </a-form-model-item>

            <a-form-model-item label="密码" prop="password">
                <a-input-password v-model="formData.password"/>
            </a-form-model-item>

            <a-form-model-item label="权限" prop="permission">
                <admin-permission-select ref="adminPermissionSelect" @change="permissionChange"></admin-permission-select>
            </a-form-model-item>

<!--            <a-form-model-item label="是否负责人" prop="is_leader">-->
<!--                <a-switch v-model="isLeader"/>-->
<!--            </a-form-model-item>-->

            <a-form-model-item label="状态" prop="status">
                <a-radio-group v-model="formData.status">
                    <a-radio :value="0">
                        禁用
                    </a-radio>
                    <a-radio :value="1">
                        启用
                    </a-radio>
                </a-radio-group>
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
    name: 'departmentAdd',
    components: {
        "department-tree":  httpVueLoader('/statics/components/department/departmentTree.vue'),
        "admin-permission-select":  httpVueLoader('/statics/components/adminPermission/adminPermissionSelect.vue'),
    },
    props: {
        id: {
            default:function(){
                return null
            },
        },
    },
    data () {
        const validatePhone = (_, value) => {
            if(value === ''){
                return Promise.reject('手机号不能为空');
            }

            const phoneRegex = /^1\d{10}$/;
            // 判断 手机号 是否符合 phoneRegex 正则的校验
            if (value && !phoneRegex.test(value)) {
                // 不符合就返回错误
                return Promise.reject('手机号格式错误');
            } else {
                // 符合就返回成功
                return Promise.resolve();
            }
        };
        return {
            formData: {

            },
            imageList: [],
            listImageList: [],
            dialogFormLabelCol: { span: 4 },
            dialogFormWrapperCol: { span: 14 },
            formRules: {
                name: [{ required: true, message: '请输入成员名称', trigger: 'blur' }],
                mobile: [{required: true,validator: validatePhone, trigger: 'blur' }]
            },
            loading :false,
            // isLeader : false,
            departmentId:undefined,
            permission:[]
        }
    },
    methods: {

        initForm(){
            this.formData= {
                department_id:undefined,
                name:'',
                password:'',
                status:1,
                mobile:'',
                permission:''
                // is_leader:0,
            };

            this.departmentId = undefined;
            if(this.$refs['departmentTree']){
                this.$refs['departmentTree'].clearData();
            }

            if(this.$refs['adminPermissionSelect']){
                this.$refs['adminPermissionSelect'].clearData();
            }
        },
        submitData(){
            let that = this;
            that.formData.is_leader = this.isLeader ? 1 : 0;
            that.formData.permission = this.permission.join(',');
            this.$refs.dataForm.validate((valid) => {
                if (valid) {
                    if(that.id){
                        that.formData.id = that.id;
                        axios({
                            // 默认请求方式为get
                            method: 'post',
                            url: '/api/admin/update',
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
                            // 默认请求方式为get
                            method: 'post',
                            url: '/api/admin/add',
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
                url: '/api/admin/getInfo',
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
                    department_id:res.data.admin_department_id,
                    name:res.data.admin_name,
                    status:res.data.admin_enabled,
                    mobile:res.data.admin_mobile,
                    permission:res.data.permission,
                    // is_leader:res.data.admin_is_leader
                }
                this.isLeader = (res.data.admin_is_leader == 1) ? true : false;
                if(res.data.admin_department_id){
                    this.departmentId = res.data.admin_department_id;
                }

                if(this.$refs['adminPermissionSelect']){
                    this.$refs['adminPermissionSelect'].setData(res.data.permission);
                }

            }).catch(error => {
                this.$message.error('请求失败');
            });
        },
        departmentChange(value){
            this.formData.department_id = value;
        },
        permissionChange(value){
            console.log(value);
            this.permission = value;
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

