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

                    <div slot="outcoming" slot-scope="text, record">
                        <div v-if="record.is_out">
                            {{ record.name }}
                        </div>
                        <div v-else>
                            <material-flow-select :ref="'materialFlowSelect'+(record.id)" :type="2" :material-id="record.material_id" :width="300" :default-data="record.flow_id" @change="(value) => {flowChange(record,value)}"></material-flow-select>
                        </div>

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
    name: 'applyHandle',
    components: {
        "material-flow-select":  httpVueLoader('/statics/components/material/materialFlowSelect.vue'),
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
                    title: '选择出库记录',
                    dataIndex: 'outcoming',
                    scopedSlots: { customRender: 'outcoming' },
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
                    {id:undefined,material_id:undefined,number:0,remain:0,name:null,flow_id:null},
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
                url: '/api/materialApply/getInfo',
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
                    name:res.data.approval.appr_name,
                    reason:res.data.approval.appr_reason,
                    purpose:res.data.maap_purpose,
                    file_list:res.data.file_list,
                    relation_approval:res.data.relation_approval,
                    process:res.data.approval_process,
                    isApproval:res.data.is_approval
                }

                for (let item of res.data.detail){
                    this.formData.detail.push({
                        material_id:item.maap_material_id,
                        id:item.maap_id,
                        flow_id:item.maap_flow_id,
                        is_out:(item.maap_flow_id > 0) ? true : false,
                        number:item.maap_number,
                        unit:item.mate_unit,
                        name:item.mate_name,
                    })
                }
                this.$forceUpdate();
            }).catch(error => {
                this.$message.error('请求失败');
            });
        },
        flowChange(row,value){
            row.flow_id = value;
            console.log(this.formData.detail);
        },
        submitData(){
            this.loading = true;
            let params = {
                id:this.id,
                detail:JSON.stringify(this.formData.detail),
            };
            axios({
                // 默认请求方式为get
                method: 'post',
                url: '/api/materialApply/handle',
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
                this.$emit('submit');
            }).catch(error => {
                this.$message.error('请求失败');
            });
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
                return false;
            }
            this.initForm();
            this.getDetail(newData);
        },
    },
    computed: {

    }

}
</script>
<style scoped>

</style>

