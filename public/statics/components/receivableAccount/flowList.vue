<template>
    <div>
        <div style="color:red">
            应付：{{info.reac_account_receivable}}  实付：{{info.reac_funds_received}}
        </div>
        <a-table :columns="columns" :data-source="listSource" :loading="listLoading" :row-key="(record, index) => { return index }"
                 :pagination="false" :scroll="{ y: 500 }">

            <div slot="reac_status" slot-scope="text, record">
                <span v-if="record.reac_status == 1" style="color:red">待审批</span>
                <span v-else style="color:green">已完成</span>
            </div>

            <div slot="reac_funds_received" slot-scope="text, record">
                <span v-if="record.reac_funds_received < 0" style="color:red">{{ record.reac_funds_received }}</span>
                <span v-else style="color:green">{{ record.reac_funds_received }}</span>
            </div>

        </a-table>
    </div>
</template>

<script>
module.exports = {
    name: 'receivableAccountFlowList',
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
                receivable_id : '',
            },
            listSource: [],
            listLoading:false,
            info : {},
            columns:[
                {
                    title: '回款日期',
                    dataIndex: 'reac_datetime',
                },
                {
                    title: '回款金额',
                    scopedSlots: { customRender: 'reac_funds_received' },
                    dataIndex: 'reac_funds_received',
                },
                {
                    title: '收款方式',
                    dataIndex: 'reac_pay_way_msg',
                },
                {
                    title: '回款类型',
                    dataIndex: 'reac_type_msg',
                },
                {
                    title: '状态',
                    scopedSlots: { customRender: 'reac_status' },
                    dataIndex: 'reac_status',
                },
                {
                    title: '备注',
                    dataIndex: 'reac_remark',
                },
                {
                    title: '操作',
                    scopedSlots: { customRender: 'action' },
                    dataIndex: 'action',
                },
            ],
        }
    },
    methods: {
        // 获取列表
        getPageList (id) {
            this.listLoading = true
            this.listQuery.receivable_id = id;
            axios({
                // 默认请求方式为get
                method: 'post',
                url: '/api/receivableAccount/getFlow',
                // 传递参数
                data: this.listQuery,
                responseType: 'json',
                headers:{
                    'Content-Type': 'multipart/form-data'
                }
            }).then(response => {
                let res = response.data;
                this.listSource = res.data.list
                this.info = res.data.info;
                this.listLoading = false
            }).catch(error => {
                this.$message.error('请求失败');
            });
        },
        onApprove(row){
            this.listLoading = true
            axios({
                // 默认请求方式为get
                method: 'post',
                url: '/api/order/approveAccountFlow',
                // 传递参数
                data: {id : row.orac_id},
                responseType: 'json',
                headers:{
                    'Content-Type': 'multipart/form-data'
                }
            }).then(response => {
                this.listLoading = false;
                let res = response.data;
                if(res.code !== 0){
                    this.$message.error(res.message);
                    return false;
                }
                this.$emit('approve');
                this.getPageList(this.id);
            }).catch(error => {
                this.$message.error('请求失败');
            });
        },
        getImage(url){
            if(url.substr(0,4).toLowerCase() == "http"){
                return url;
            }

            return 'http://' + window.location.hostname + url;
        },
    },
    created () {
        if(this.id){
            this.getPageList(this.id);
        }
    },
    watch: {
        id (newData,oldData) {
            console.log(newData);
            if(newData === oldData){
                return false
            }

            if(!newData){
                return false
            }

            this.getPageList(newData);
        }
    },
    computed: {

    }

}
</script>
<style scoped>

</style>

