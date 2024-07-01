<template>
    <div>
        <a-form-model :loading="loading" :model="formData" ref="dataForm" :label-col="dialogFormLabelCol" :wrapper-col="dialogFormWrapperCol" :rules="formRules">
            <a-form-model-item label="名称" prop="name">
                <a-input v-model="formData.name" />
            </a-form-model-item>

            <a-form-model-item label="仓库" prop="warehouse_id">
                <warehouse-select ref="warehouseSelect" :default-data="warehouseId" @change="warehouseChange"></warehouse-select>
            </a-form-model-item>

            <a-form-model-item label="厂家" prop="manufacturer_id">
                <manufacturer-select ref="manufacturerSelect" :default-data="manufacturerId" @change="manufacturerChange"></manufacturer-select>
            </a-form-model-item>

            <a-form-model-item label="分类" prop="category_id">
                <category-select ref="categorySelect" :default-data="categoryId" @change="categoryChange"></category-select>
            </a-form-model-item>

            <a-form-model-item label="规格" prop="specification_id">
                <specification-select ref="specificationSelect" :category-id="categoryId" :default-data="specificationId" @change="specificationChange"></specification-select>
            </a-form-model-item>

            <a-form-model-item label="单位" prop="unit">
                <a-input v-model="formData.unit" />
            </a-form-model-item>

            <a-form-model-item label="预警值" prop="warning">
                <a-input-number v-model="formData.warning" :min="0"/>
            </a-form-model-item>

            <a-form-model-item label="物品图" prop="image">
                <a-upload list-type="picture-card"
                          :file-list="imageList"
                          :remove="imageHandleRemove"
                          :before-upload="imageBeforeUpload"
                          @change="imageHandleChange" accept="image/*">

                    <div v-if="imageList.length < 1">
                        <a-icon type="plus" ></a-icon>
                        <div class="ant-upload-text">
                            上传物品图
                        </div>
                    </div>
                </a-upload>
            </a-form-model-item>

            <a-form-model-item label="排序" prop="sort">
                <a-input-number v-model="formData.sort" :min="0" :max="1000" />
            </a-form-model-item>

            <a-form-model-item label="备注" prop="remark">
                <a-textarea
                    v-model="formData.remark"
                    placeholder="备注"
                    :auto-size="{ minRows: 3, maxRows: 5 }"
                />
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
    name: 'materialAdd',
    components: {
        "warehouse-select":  httpVueLoader('/statics/components/material/warehouseSelect.vue'),
        "category-select":  httpVueLoader('/statics/components/material/categorySelect.vue'),
        "manufacturer-select":  httpVueLoader('/statics/components/material/manufacturerSelect.vue'),
        "specification-select":  httpVueLoader('/statics/components/material/specificationSelect.vue')
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
            dialogFormLabelCol: { span: 4 },
            dialogFormWrapperCol: { span: 14 },
            formRules: {
                name: [{ required: true, message: '请输入名称', trigger: 'blur' }],
                unit: [{ required: true, message: '请输入单位', trigger: 'blur' }],
                warehouse_id: [{ required: true, message: '请选择仓库', trigger: 'change' }],
                category_id: [{ required: true, message: '请选择分类', trigger: 'change' }],
                manufacturer_id: [{ required: true, message: '请选择厂家', trigger: 'change' }],
                specification_id: [{ required: true, message: '请选择', trigger: 'change' }],
            },
            loading :false,
            categoryId:undefined,
            manufacturerId:undefined,
            specificationId:undefined,
            warehouseId:2,
        }
    },
    methods: {
        initForm(){
            this.formData= {
                name:'',
                warehouse_id:2,
                category_id:undefined,
                manufacturer_id:undefined,
                specification_id:undefined,
                warning:0,
                unit:'',
                image:'',
                remark:'',
                sort:0,
                status : 1,
            };

            this.imageList = [];

            // this.categoryId = undefined;
            // this.manufacturerId = undefined;
            // this.specificationId = undefined;

            if(this.$refs['warehouseSelect']){
                this.$refs['warehouseSelect'].clearData();
            }

            if(this.$refs['categorySelect']){
                this.$refs['categorySelect'].clearData();
            }

            if(this.$refs['manufacturerSelect']){
                this.$refs['manufacturerSelect'].clearData();
            }

            if(this.$refs['specificationSelect']){
                this.$refs['specificationSelect'].clearData();
            }
        },
        imageHandleRemove(file){
            this.formData.image = '';
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
            formData.append('type', 'material');
            // formData.append('oss', 1);

            axios({
                // 默认请求方式为get
                method: 'post',
                url: '/admin/upload',
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
                this.imageList[0].url = this.getImage(res.data.url);
                this.formData.image = res.data.url;
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
        submitData(){
            let that = this;
            this.$refs.dataForm.validate((valid) => {
                if (valid) {
                    if(that.id){
                        that.formData.id = that.id;
                        axios({
                            // 默认请求方式为get
                            method: 'post',
                            url: '/admin/material/update',
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
                            url: '/admin/material/add',
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
        getImage(url){
            if(url.substr(0,4).toLowerCase() == "http"){
                return url;
            }

            return 'http://' + window.location.hostname + url;
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
                url: '/admin/material/getInfo',
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
                    name: res.data.mate_name,
                    warehouse_id: res.data.mate_warehouse_id,
                    category_id: res.data.mate_category_id,
                    manufacturer_id: res.data.mate_manufacturer_id,
                    specification_id: res.data.mate_specification_id,
                    warning: res.data.mate_warning,
                    unit: res.data.mate_unit,
                    remark: res.data.mate_remark,
                    image: res.data.mate_image,
                    sort: res.data.mate_sort,
                    status: res.data.mate_status,
                }
                this.categoryId = res.data.mate_category_id;
                this.manufacturerId = res.data.mate_manufacturer_id;
                this.specificationId = res.data.mate_specification_id;
                if(res.data.mate_image){
                    this.imageList = [
                        {
                            url: this.getImage(res.data.mate_image),
                            uid:'-1',
                            name: 'image',
                            status: 'done',
                        }
                    ]
                }
            }).catch(error => {
                this.$message.error('请求失败');
            });
        },
        warehouseChange(value){
            this.formData.warehouse_id = value;
        },
        categoryChange(value){
            this.formData.category_id = value;
            this.categoryId = value;
            if(this.$refs['specificationSelect']){
                this.$refs['specificationSelect'].clearData();
            }
            this.formData.specification_id = null;
        },
        manufacturerChange(value){
            this.formData.manufacturer_id = value;
        },
        specificationChange(value){
            this.formData.specification_id = value;
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
.ant-form-item{
    margin: 0 0 15px;
}
</style>

