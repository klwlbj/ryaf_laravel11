<template>
    <div>
        <a-form-model :loading="loading" :model="formData" ref="dataForm" :label-col="dialogFormLabelCol" :wrapper-col="dialogFormWrapperCol" :rules="formRules">
            <a-form-model-item required label="申领名称" prop="name">
                <a-input v-model:value="formData.name" placeholder="填写申购名称" />
            </a-form-model-item>

            <a-form-model-item required label="申领事由" prop="reason">
                <a-textarea v-model="formData.reason" :auto-size="{ minRows: 3, maxRows: 6 }"/>
            </a-form-model-item>

            <a-form-model-item label="申领明细">
                <a-table  :columns="columns" :data-source="formData.detail" :loading="loading"  :row-key="(record, index) => { return index }" :pagination="false">
                    <div slot="id" slot-scope="text, record">
                        <material-select :width="300" :category-id="formData.category_id" :default-data="record.id" @change="(value) => {materialChange(value,record)}"></material-select>
                    </div>

                    <div slot="number" slot-scope="text, record">
                        <a-input-number id="inputNumber" v-model="record.number" @change="getPreInfo"/>
                    </div>

                </a-table>
                <a-button type="primary" block @click="detailAdd">新增</a-button>
            </a-form-model-item>

            <a-form-model-item label="出库总金额" prop="total_price">
                {{ preInfo.total_price }}
            </a-form-model-item>

            <a-form-model-item required label="申领用途" prop="purpose">
                <a-select v-model="formData.purpose" show-search placeholder="请选择" :max-tag-count="1"
                          style="width: 200px;" allow-clear @change="getPreInfo">
                    <a-select-option :value="1">
                        销售性质
                    </a-select-option>
                    <a-select-option :value="2">
                        非销售性质
                    </a-select-option>
                </a-select>
            </a-form-model-item>

            <a-form-model-item label="附件" prop="file">
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

            <a-form-model-item label="关联申购单" prop="relation_id">
                <apply-relation-select :default-data="relationId" @change="applyRelationChange"></apply-relation-select>
            </a-form-model-item>

            <a-form-model-item label="审批流程预览" prop="process">
                <div v-if="preInfo.error" style="color:red">{{preInfo.error_msg}}</div>
                <div v-else>
                    <a-timeline>
                        <a-timeline-item v-for="(item,index) in preInfo.process_list" :key="index" color="gray">
                            <p v-if="item.type === 1">审批人：{{item.admin_name}}</p>
                            <p v-else>抄送人：{{item.admin_name}}</p>
                        </a-timeline-item>
                    </a-timeline>
                </div>
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
    name: 'purchaseAdd',
    components: {
        "material-select":  httpVueLoader('/statics/components/material/materialSelect.vue'),
        "apply-relation-select":  httpVueLoader('/statics/components/material/applyRelationSelect.vue'),
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
                // {
                //     title: '余量',
                //     dataIndex: 'remain',
                // },
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
            fileList:[],
            relationId:null,
            preInfo:{
                'total_price' : 0,
                'process_list' : [],
            },
        }
    },
    methods: {
        initForm(){
            this.formData= {
                detail:[
                    {id:undefined,number:0,remain:0,name:null},
                ],
                relation_id:undefined,
                name:'',
                reason:'',
                purpose:1,
                remark:'',
            };

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

            this.$refs.dataForm.validate((valid) => {
                if (valid) {
                    let params = {
                        detail:JSON.stringify(that.formData.detail),
                        file_list:JSON.stringify(file),
                        name:that.formData.name,
                        reason:that.formData.reason,
                        purpose:that.formData.purpose,
                        relation_id:that.formData.relation_id
                    }
                    if(this.id){
                        params.id = this.id;
                        axios({
                            // 默认请求方式为get
                            method: 'post',
                            url: '/api/materialApply/update',
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
                            url: '/api/materialApply/add',
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
            formData.append('type', 'material_apply');
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
                record.name = res.data.mate_name;
                this.$forceUpdate();
                this.getPreInfo();
            }).catch(error => {
                this.$message.error('请求失败');
            });
        },
        applyRelationChange(value){
            this.formData.relation_id = value;
        },
        getPreInfo(){
            let params = {
                detail:JSON.stringify(this.formData.detail),
                purpose:this.formData.purpose,
            }
            this.loading = true;
            axios({
                // 默认请求方式为get
                method: 'post',
                url: '/api/materialApply/getPreInfo',
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
                this.preInfo = res.data;
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
                url: '/api/materialApply/getInfo',
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
                    name:res.data.approval.appr_name,
                    reason:res.data.approval.appr_reason,
                    purpose:res.data.maap_purpose,
                }

                for (let item of res.data.detail){
                    this.formData.detail.push({
                        id:item.maap_material_id,
                        number:item.maap_number,
                        remain:0,
                        name:null
                    })
                }

                for (let item of res.data.file_list){
                    this.fileList.push({
                        url:item.file_path,
                        name:item.file_name,
                        ext:item.file_ext,
                        uid:'-1',
                        status: 'done',
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

