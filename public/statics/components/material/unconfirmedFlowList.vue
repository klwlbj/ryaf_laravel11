<template>
    <div>
        <a-table :columns="columns" :data-source="listSource" :loading="listLoading" :row-key="(record, index) => { return index }"
                 :pagination="false" :scroll="{ x: 1500,y:500}">

            <div slot="mafl_specification_name" slot-scope="text, record">
                <a-tag v-for="(item,i) in record.mafl_specification_name" :key="i">{{ item }}</a-tag>
            </div>

            <div slot="type" slot-scope="text, record">
                <a-tag v-if="record.mafl_type == 1"  color="green">入库</a-tag>
                <a-tag v-else color="red">出库</a-tag>
            </div>

            <div slot="price" slot-scope="text, record">
                <div v-if="record.mafl_type == 1">
                    <div>单价（含税）：<span style="color:red">{{record.mafl_price_tax}}</span></div>
                    <div>税率：<span style="color:red">{{record.mafl_tax}}%</span></div>
                    <div>单价（不含税）：<span style="color:red">{{record.mafl_price}}</span></div>
                    <div>发票类型：<span style="color:red">{{record.mafl_invoice_type_msg}}</span></div>
                </div>
            </div>

            <div slot="purpose" slot-scope="text, record">
                <a-tag v-if="record.mafl_type == 2 && record.mafl_purpose == 1"  color="#2db7f5">销售性质</a-tag>
                <a-tag v-else-if="record.mafl_type == 2 && record.mafl_purpose == 2" color="#2db7f5">非销售性质</a-tag>
                <span v-else>-</span>
            </div>

            <div slot="file_list" slot-scope="text, record">
                <div v-for="item in record.file_list">
                    <a :href="item.file_path">
                        {{item.file_name}}
                    </a>
                </div>
                <span></span>
            </div>

            <div slot="apply_user" slot-scope="text, record">
                <span>{{  record.mafl_apply_user }}/{{  record.mafl_receive_user }}</span>
            </div>

            <div slot="number" slot-scope="text, record">
                <span v-if="record.mafl_type == 1"  style="color:green">+ {{ record.mafl_number }}</span>
                <span v-else style="color:red">- {{ record.mafl_number }}</span>
            </div>

            <div slot="action" slot-scope="text, record">
                <div v-if="record.mafl_status == 1 && record.mafl_verify_user_id == admin.admin_id">
                    <a-popconfirm
                        title="是否确认无误?"
                        ok-text="确认"
                        cancel-text="取消"
                        @confirm="onVerify(record)"
                    >
                        <a style="margin-right: 8px">
                            确认无误
                        </a>
                    </a-popconfirm>
                </div>
            </div>
        </a-table>
    </div>
</template>

<script>
module.exports = {
    name: 'materialDetailList',
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
                // status: 1,
                is_all: true,
                order_by_status:1
            },
            listSource: [],
            listLoading: false,
            pagination: {
                pageSize: 10,
                total: 0,
                current: 1,
                onChange: this.paginationChange,
                onShowSizeChange: this.paginationChange,
            },
            columns:[
                {
                    title: '规格',
                    scopedSlots: { customRender: 'mafl_specification_name'},
                    dataIndex: 'mafl_specification_name',
                    align: 'center'
                },
                {
                    title: '类型',
                    scopedSlots: { customRender: 'type' },
                    dataIndex: 'mafl_type',
                    align: 'center'
                },
                {
                    title: '数量',
                    scopedSlots: { customRender: 'number' },
                    dataIndex: 'mafl_number',
                    align: 'center'
                },
                {
                    title: '出/入库时间',
                    dataIndex: 'mafl_datetime',
                    align: 'center'
                },
                {
                    title: '单价',
                    scopedSlots: { customRender: 'price' },
                    dataIndex: 'mafl_price',
                    width: 200,
                    align: 'center'
                },
                {
                    title: '用途',
                    scopedSlots: { customRender: 'purpose' },
                    dataIndex: 'mafl_purpose',
                    align: 'center'
                },
                {
                    title: '申请人/领用人',
                    scopedSlots: { customRender: 'apply_user' },
                    dataIndex: 'mafl_apply_user',
                    align: 'center'
                },
                {
                    title: '附件',
                    scopedSlots: { customRender: 'file_list' },
                    dataIndex: 'file_list',
                    align: 'center'
                },
                {
                    title: '过期时间',
                    dataIndex: 'mafl_expire_date',
                    align: 'center'
                },
                {
                    title: '备注',
                    dataIndex: 'mafl_remark',
                    align: 'center'
                },
                {
                    title: '操作',
                    fixed: 'right',
                    scopedSlots: { customRender: 'action' },
                    align: 'center'
                }
            ],
            admin:{}
        }
    },
    methods: {
        // 获取列表
        getPageList (id) {
            this.listLoading = true
            this.listQuery.material_id = id;
            axios({
                // 默认请求方式为get
                method: 'post',
                url: '/api/materialFlow/getList',
                // 传递参数
                data: this.listQuery,
                responseType: 'json',
                headers:{
                    'Content-Type': 'multipart/form-data'
                }
            }).then(response => {
                let res = response.data;
                this.listSource = res.data.list
                this.pagination.total = res.data.total
                this.listLoading = false
            }).catch(error => {
                this.$message.error('请求失败');
            });
        },
        onVerify(row){
            axios({
                // 默认请求方式为get
                method: 'post',
                url: '/api/materialFlow/verify',
                // 传递参数
                data: {id:row.mafl_id},
                responseType: 'json',
                headers:{
                    'Content-Type': 'multipart/form-data'
                }
            }).then(response => {
                let res = response.data;
                if(res.code != 0){
                    this.$message.error(res.message);
                    return false;
                }

                this.getPageList(this.id);
            }).catch(error => {
                this.$message.error('请求失败');
            });
        }
    },
    created () {
        this.admin = JSON.parse(localStorage.getItem("admin"));
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

