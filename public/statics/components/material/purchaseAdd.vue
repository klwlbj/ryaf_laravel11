<template>
    <div>
        <a-form-model :loading="loading" :model="formData" ref="dataForm" :label-col="dialogFormLabelCol" :wrapper-col="dialogFormWrapperCol" :rules="formRules">
            <a-form-model-item label="分类" prop="category_id">
                <category-select ref="categorySelect" :default-data="categoryId" @change="categoryChange"></category-select>
            </a-form-model-item>

            <a-form-model-item label="申购明细">
                <a-table  :columns="columns" :data-source="formData.detail" :loading="loading"  :row-key="(record, index) => { return index }" :pagination="false">
                    <div slot="id" slot-scope="text, record">
                        <material-select :width="300" :category-id="formData.category_id" :default-data="record.id" @change="(value) => {materialChange(value,record)}"></material-select>
                    </div>

                    <div slot="number" slot-scope="text, record">
                        <a-input-number id="inputNumber" v-model="record.number" />
                    </div>

                </a-table>
                <a-button type="primary" block @click="detailAdd">新增</a-button>
            </a-form-model-item>

            <a-form-model-item required label="申请人" prop="apply_user_id">
                <apply-user-select ref="applyUserSelect" @change="applyUserChange" :default-data="applyUserId"></apply-user-select>
            </a-form-model-item>

            <a-form-model-item label="备注" prop="remark">
                <a-textarea v-model="formData.remark" :auto-size="{ minRows: 2, maxRows: 6 }"/>
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
    name: 'purchaseAdd',
    components: {
        "material-select":  httpVueLoader('/statics/components/material/materialSelect.vue'),
        "apply-user-select":  httpVueLoader('/statics/components/admin/adminSelect.vue'),
        "category-select":  httpVueLoader('/statics/components/material/categorySelect.vue'),
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
            columns:[
                {
                    title: '物品名称',
                    dataIndex: 'id',
                    scopedSlots: { customRender: 'id' },
                    width:100,
                },
                {
                    title: '申请数量',
                    dataIndex: 'number',
                    scopedSlots: { customRender: 'number' },
                },
                {
                    title: '余量',
                    dataIndex: 'remain',
                },
                {
                    title: '单位',
                    dataIndex: 'unit',
                },
            ],
            dialogFormLabelCol: { span: 4 },
            dialogFormWrapperCol: { span: 14 },
            formRules: {

            },
            loading :false,
            materialId:null,
            applyUserId:null,
            categoryId:null
        }
    },
    methods: {
        initForm(){
            this.formData= {
                detail:[
                    {id:undefined,number:0,remain:0,name:null},
                    // {id:undefined,number:0,remain:0,name:null},
                    // {id:undefined,number:0,remain:0,name:null},
                    // {id:undefined,number:0,remain:0,name:null},
                    // {id:undefined,number:0,remain:0,name:null},
                    // {id:undefined,number:0,remain:0,name:null},
                ],
                apply_user_id:null,
                category_id:'',
                remark:'',
            };

            this.categoryId = undefined;
            this.applyUserId = undefined;

            if(this.$refs['categorySelect']){
                this.$refs['categorySelect'].clearData();
            }

            if(this.$refs['applyUserSelect']){
                this.$refs['applyUserSelect'].clearData();
            }
        },
        submitData(){
            let that = this;
            this.$refs.dataForm.validate((valid) => {
                if (valid) {
                    let params = {
                        detail:JSON.stringify(that.formData.detail),
                        apply_user_id:that.formData.apply_user_id,
                        category_id:that.formData.category_id,
                        remark:that.formData.remark
                    }
                    if(this.id){
                        params.id = this.id;
                        axios({
                            // 默认请求方式为get
                            method: 'post',
                            url: '/api/materialPurchase/update',
                            // 传递参数
                            data: params,
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
                            url: '/api/materialPurchase/add',
                            // 传递参数
                            data: params,
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
        materialChange(id,record){
            if(!id){
                return false;
            }
            record.id = id;
            this.loading = true;
            axios({
                // 默认请求方式为get
                method: 'post',
                url: '/api/material/getInfo',
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
                record.remain = res.data.mate_number;
                record.unit = res.data.mate_unit;
                this.$forceUpdate();
            }).catch(error => {
                this.$message.error('请求失败');
            });
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
                url: '/api/materialPurchase/getInfo',
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
                    detail: [],
                    apply_user_id: res.data.mapu_apply_user_id,
                    category_id: res.data.mapu_category_id,
                    remark: res.data.mapu_remark
                }

                this.categoryId = res.data.mapu_category_id;
                this.applyUserId = res.data.mapu_apply_user_id;
                for (let item of res.data.detail){
                    this.formData.detail.push({
                        id:item.mapu_material_id,
                        number:item.mapu_number,
                        remain:0,
                        name:null
                    })
                }
                this.$forceUpdate();
            }).catch(error => {
                this.$message.error('请求失败');
            });
        },
        detailAdd(){
            this.formData.detail.push({id:undefined,number:0,remain:0,name:null})
        },
        applyUserChange(value){
            this.formData.apply_user_id = value;
        },
        categoryChange(value){
            this.formData.category_id = value;
        }
    },
    created () {
        if(this.id){
            this.getDetail(this.id);
        }else{
            this.initForm();
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

