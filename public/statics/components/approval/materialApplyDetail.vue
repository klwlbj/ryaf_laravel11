<template>
    <div>
        <a-form-model :loading="loading" :model="formData" ref="dataForm" :label-col="dialogFormLabelCol" :wrapper-col="dialogFormWrapperCol" :rules="formRules">
            <a-form-model-item label="申领名称" prop="name">
                {{ formData.name }}
            </a-form-model-item>

            <a-form-model-item label="申领事由" prop="reason">
                <a-textarea readonly v-model="formData.reason" :auto-size="{ minRows: 3, maxRows: 6 }"/>
            </a-form-model-item>

            <a-form-model-item label="申领明细">
                <a-table  :columns="columns" :data-source="formData.detail" :loading="loading"  :row-key="(record, index) => { return index }" :pagination="false">
                    <div slot="id" slot-scope="text, record">
                        {{record.name}}
                    </div>

                    <div slot="number" slot-scope="text, record">
                        {{record.number}}
                    </div>

                </a-table>
            </a-form-model-item>

            <a-form-model-item label="申领用途" prop="purpose">
                <a-tag v-if="formData.purpose == 1" color="#87d068">销售性质</a-tag>
                <a-tag v-else color="#87d068">非销售性质</a-tag>
            </a-form-model-item>

            <a-form-model-item label="附件" prop="file">
                <div v-for="item in formData.file_list">
                    <a :href="item.file_path">
                        {{item.file_name}}
                    </a>
                </div>
            </a-form-model-item>

            <a-form-model-item label="关联申购单" prop="relation_id">
                <div v-if="formData.relation_approval">
                    <div><span>申请名称：</span><span>{{formData.relation_approval.appr_name}}</span></div>
                    <div><span>申请事由：</span><span>{{formData.relation_approval.appr_reason}}</span></div>
                    <div><span>申请时间：</span><span>{{formData.relation_approval.appr_crt_time}}</span></div>
                </div>
            </a-form-model-item>

            <a-form-model-item label="审批流程" prop="process">
                <div>
                    <a-timeline>
                        <a-timeline-item v-for="(item,index) in formData.process" :key="index" :color="item.appr_status === 2 ? 'orange' : (item.appr_status === 3 ? 'green' : (item.appr_status === 4) ? 'red' : 'gray')">
                            <p v-if="item.appr_type === 1">审批人：{{item.admin_name}}</p>
                            <p v-else>抄送人：{{item.admin_name}}</p>
                            <div v-if="item.appr_status === 3 || item.appr_status === 4 ">
                                <div v-if="item.appr_type === 1">
                                    <p>审批状态：
                                        <span v-if="item.appr_status === 3" style="color:green">已同意</span>
                                        <span v-else style="color:red">已拒绝</span>
                                    </p>
                                    <p>审批意见：{{item.appr_remark}}</p>
                                    <p>审批时间：{{item.appr_complete_date}}</p>
                                </div>
                                <div v-else>
                                    <p>抄送状态：
                                        <span>已抄送</span>
                                    </p>
                                    <p>抄送时间：{{item.appr_complete_date}}</p>
                                </div>
                            </div>



                        </a-timeline-item>
                    </a-timeline>
                </div>

            </a-form-model-item>

            <a-form-model-item v-if="isApproval" label="审批意见" prop="process">
                <a-textarea v-model="remark" :auto-size="{ minRows: 3, maxRows: 6 }"/>
            </a-form-model-item>

            <a-form-model-item v-if="isApproval" :wrapper-col="{ span: 14, offset: 4 }">
                <a-button :loading="loading" type="primary" @click="agree">
                    同意
                </a-button>
                <a-button :loading="loading" style="margin-left: 10px;"  @click="reject">
                    拒绝
                </a-button>
            </a-form-model-item>

        </a-form-model>
    </div>
</template>

<script>
module.exports = {
    name: 'applyDetail',
    components: {

    },
    props: {
        id: {
            default:function(){
                return null
            },
        }
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
                    width:200,
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
            remark:'',
            isApproval:false,
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
        getDetail(id){
            if(!id){
                this.$message.error('id不能为空');
                return false;
            }
            this.loading = true;
            axios({
                // 默认请求方式为get
                method: 'post',
                url: '/api/approval/getInfo',
                // 传递参数
                data: {
                    id:id,
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
                    name:res.data.appr_name,
                    reason:res.data.appr_reason,
                    purpose:res.data.relation_data.maap_purpose,
                    file_list:res.data.relation_data.file_list,
                    relation_approval:res.data.relation_approval,
                    process:res.data.process,
                    // isApproval:res.data.is_approval
                }

                this.isApproval = res.data.is_approval;

                for (let item of res.data.relation_data.detail){
                    this.formData.detail.push({
                        id:item.maap_material_id,
                        number:item.maap_number,
                        unit:item.mate_unit,
                        name:item.mate_name
                    })
                }
                this.$forceUpdate();
            }).catch(error => {
                this.$message.error('请求失败');
            });
        },
        agree(){
            this.loading = true;
            axios({
                // 默认请求方式为get
                method: 'post',
                url: '/api/approval/agree',
                // 传递参数
                data: {
                    id:this.id,
                    remark:this.remark
                },
                responseType: 'json',
                headers:{
                    'Content-Type': 'multipart/form-data'
                }
            }).then(response => {
                this.loading = false;
                let res = response.data;
                if (res.code !== 0) {
                    this.$message.error(res.message);
                    return false;
                }

                this.$emit('approval');
            });

        },
        reject(){
            this.loading = true;
            axios({
                // 默认请求方式为get
                method: 'post',
                url: '/api/approval/reject',
                // 传递参数
                data: {
                    id:this.id,
                    remark:this.remark
                },
                responseType: 'json',
                headers:{
                    'Content-Type': 'multipart/form-data'
                }
            }).then(response => {
                this.loading = false;
                let res = response.data;
                if (res.code !== 0) {
                    this.$message.error(res.message);
                    return false;
                }

                this.$emit('approval');
            });
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
        },
    },
    computed: {

    }

}
</script>
<style scoped>

</style>

