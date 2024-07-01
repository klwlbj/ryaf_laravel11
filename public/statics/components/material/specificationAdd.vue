<template>
    <div>
        <a-form-model :loading="loading" :model="formData" ref="dataForm" :label-col="dialogFormLabelCol" :wrapper-col="dialogFormWrapperCol" :rules="formRules">
            <a-form-model-item label="名称" prop="name">
                <a-input v-model="formData.name" />
            </a-form-model-item>

            <a-form-model-item label="分类" prop="category_id">
                <category-select ref="categorySelect" :default-data="categoryId" @change="categoryChange"></category-select>
            </a-form-model-item>

            <a-form-model-item label="排序" prop="sort">
                <a-input-number v-model="formData.sort" :min="0" :max="1000" />
            </a-form-model-item>



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
    name: 'specificationAdd',
    components: {
        "category-select":  httpVueLoader('/statics/components/material/categorySelect.vue')
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
                name: [{ required: true, message: '请输入类型名称', trigger: 'blur' }],
            },
            loading :false,
            categoryId:undefined
        }
    },
    methods: {
        initForm(){
            this.formData= {
                name:'',
                category_id:undefined,
                sort:0,
                status : 1,
            };

            if(this.$refs['categorySelect']){
                this.$refs['categorySelect'].clearData();
            }
        },
        submitData(){
            let that = this;
            this.$refs.dataForm.validate((valid) => {
                if (valid) {
                    if(that.id){
                        that.formData.id = that.id;
                        axios({
                            // 默认请求方式为get
                            method: 'post',
                            url: '/admin/materialSpecification/update',
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
                            url: '/admin/materialSpecification/add',
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
                url: '/admin/materialSpecification/getInfo',
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
                    name:res.data.masp_name,
                    category_id:res.data.masp_category_id,
                    sort:res.data.masp_sort,
                    status : res.data.masp_status,
                }
                this.categoryId = res.data.masp_category_id;
            }).catch(error => {
                this.$message.error('请求失败');
            });
        },
        categoryChange(value){
            this.formData.category_id = value;
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

