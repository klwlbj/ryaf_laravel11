<template>
    <div>
        <div style="color:red">
            应付：{{orderData.order_account_receivable}}  实付：{{orderData.order_funds_received}}
        </div>
        <a-table :columns="columns" :data-source="listSource" :loading="listLoading" :row-key="(record, index) => { return index }"
                 :pagination="false" :scroll="{ y: 500 }">

            <div slot="orac_status" slot-scope="text, record">
                <span v-if="record.orac_status == 1" style="color:red">待审批</span>
                <span v-else style="color:green">已完成</span>
            </div>

            <div slot="orac_funds_received" slot-scope="text, record">
                <span v-if="record.orac_funds_received < 0" style="color:red">{{ record.orac_funds_received }}</span>
                <span v-else style="color:green">{{ record.orac_funds_received }}</span>
            </div>

            <div slot="action" slot-scope="text, record">
                <a-popconfirm
                    v-if="record.approve_auth"
                    title="是否确定审批回款?"
                    ok-text="确认"
                    cancel-text="取消"
                    v-on:confirm="onApprove(record)"
                >
                    <a style="margin-right: 8px">
                        审批
                    </a>
                </a-popconfirm>
            </div>
        </a-table>
    </div>
</template>

<script>
module.exports = {
    name: 'accountFlowList',
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
                order_id: "",
            },
            listSource: [],
            orderData : {},
            columns:[
                {
                    title: '回款日期',
                    dataIndex: 'orac_date',
                },
                {
                    title: '回款金额',
                    scopedSlots: { customRender: 'orac_funds_received' },
                    dataIndex: 'orac_funds_received',
                },
                {
                    title: '收款方式',
                    dataIndex: 'orac_pay_way_msg',
                },
                {
                    title: '回款类型',
                    dataIndex: 'orac_type_msg',
                },
                {
                    title: '状态',
                    scopedSlots: { customRender: 'orac_status' },
                    dataIndex: 'orac_status',
                },
                {
                    title: '备注',
                    dataIndex: 'orac_remark',
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
            this.listQuery.order_id = id;
            axios({
                // 默认请求方式为get
                method: 'post',
                url: '/api/order/getAccountFlow',
                // 传递参数
                data: this.listQuery,
                responseType: 'json',
                headers:{
                    'Content-Type': 'multipart/form-data'
                }
            }).then(response => {
                let res = response.data;
                this.listSource = res.data.list
                this.orderData = res.data.order_info;
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
        }
    },
    created () {
        if(this.id){
            this.getPageList(this.id);
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

            this.getPageList(newData);
        }
    },
    computed: {

    }

}
</script>
<style scoped>

</style>

