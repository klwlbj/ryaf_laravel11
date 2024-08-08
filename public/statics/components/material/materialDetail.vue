<template>
    <div>
        <a-descriptions layout="vertical" :title="detail.mate_name" bordered>
            <a-descriptions-item label="厂家"> {{detail.mate_manufacturer_name}}</a-descriptions-item>
            <a-descriptions-item label="分类">{{detail.mate_category_name}}</a-descriptions-item>
            <a-descriptions-item label="规格">{{detail.mate_specification_name.join(',')}}</a-descriptions-item>
            <a-descriptions-item label="库存">{{detail.mate_number}}</a-descriptions-item>
            <a-descriptions-item label="单位">{{detail.mate_unit}}</a-descriptions-item>
            <a-descriptions-item label="预警值">{{detail.mate_warning}}</a-descriptions-item>
            <a-descriptions-item  :span="3" label="最新入库信息">
                <div v-if="detail.last_in_flow">
                    入库时间：{{detail.last_in_flow.mafl_datetime}} <br>生产日期：{{detail.last_in_flow.mafl_production_date}} <br>质保期：{{detail.last_in_flow.mafl_expire_date}} <br>入库数量：{{detail.last_in_flow.mafl_number}}
                </div>
            </a-descriptions-item>
            <a-descriptions-item  :span="3" label="最新出库信息">
                <div v-if="detail.last_out_flow">
                    出库时间：{{detail.last_out_flow.mafl_datetime}} <br>申请人：{{detail.last_out_flow.mafl_apply_name}} <br>领用人：{{detail.last_out_flow.mafl_receive_name}} <br>用途：{{(detail.last_out_flow.mafl_purpose == 1) ? '销售性质' : '非销售性质'}} <br>出库数量：{{detail.last_out_flow.mafl_number}}
                </div>
            </a-descriptions-item>

            <a-descriptions-item  :span="3" label="临期信息">
                <a-table :columns="columns" :data-source="detail.expire_list" :row-key="(record, index) => { return index }"
                         :pagination="false">
                </a-table>
            </a-descriptions-item>
        </a-descriptions>

    </div>
</template>

<script>
module.exports = {
    name: 'materialDetail',
    components: {},
    props: {
        id: {
            default:function(){
                return 'default'
            },
        },
    },
    data () {
        return {
            listQuery: {
                id: "",
            },
            detail: {},
            columns:[
                {
                    title: 'Id',
                    dataIndex: 'mafl_id',
                },
                {
                    title: '入库时间',
                    dataIndex: 'mafl_datetime',
                },
                {
                    title: '质保期',
                    dataIndex: 'mafl_expire_date',
                },
                {
                    title: '入库数量',
                    dataIndex: 'mafl_number',
                },
                {
                    title: '临期数量',
                    dataIndex: 'expire_count',
                },
            ]
        }
    },
    methods: {
        // 获取列表
        getDetail (id) {
            this.listLoading = true
            this.listQuery.id = id;
            axios({
                // 默认请求方式为get
                method: 'post',
                url: '/api/material/getDetail',
                // 传递参数
                data: this.listQuery,
                responseType: 'json',
                headers:{
                    'Content-Type': 'multipart/form-data'
                }
            }).then(response => {
                this.listLoading = false
                let res = response.data;
                this.detail = res.data
            }).catch(error => {
                this.$message.error('请求失败');
            });
        },
    },
    created () {
        if(this.id){
            this.getDetail(this.id);
        }
    },
    watch: {
        id (newData,oldData) {
            if(newData === oldData){
                return false
            }

            if(!newData){
                return false
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

