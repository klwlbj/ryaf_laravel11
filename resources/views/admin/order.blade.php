@extends('admin.layout')

@section('content')
    <div id="app">
        <a-card>
            <div>
                <a-form layout="inline" >
                    <a-form-item>
                        <node-cascader :default-data="nodeId" @change="nodeChange"></node-cascader>
                    </a-form-item>
                    <a-form-item>
                        <a-input v-model="listQuery.keyword" placeholder="订单编号" style="width: 200px;" />
                    </a-form-item>
                    <a-form-item>
                        <a-input v-model="listQuery.address" placeholder="地址" style="width: 200px;" />
                    </a-form-item>
                    <a-form-item>
                        <a-input v-model="listQuery.user_keyword" placeholder="用户名/用户手机号" style="width: 200px;" />
                    </a-form-item>
                    <a-form-item>
                        <a-select v-model="listQuery.is_debt" show-search placeholder="是否欠款" :max-tag-count="1"
                                  style="width: 200px;" allow-clear>
                            <a-select-option :value="1">
                                是
                            </a-select-option>
                            <a-select-option :value="2">
                                否
                            </a-select-option>
                        </a-select>
                    </a-form-item>
                    <a-form-item>
                        <a-range-picker
                            :placeholder="['开始时间', '结束时间']"
                            @change="dateChange"
                            :default-value="[defaultDate,defaultDate]"></a-range-picker>
                        </a-form-item>
                    <a-form-item>
                        <a-button icon="search" v-on:click="handleFilter">查询</a-button>
                    </a-form-item>
                </a-form>

                <a-table :columns="columns" :data-source="listSource" :loading="listLoading" :row-key="(record, index) => { return index }"
                         :pagination="false"  :scroll="{ x: 1800,y: 650}">

                    <div slot="order_place" slot-scope="text, record">
                        <div v-for="item in record.order_place">
                            @{{ item.plac_address }}
                        </div>
                    </div>

                    <div slot="order_user" slot-scope="text, record">
                        <div>@{{ record.order_user_name }}</div>
                        <div>@{{ record.order_user_mobile }}</div>
                    </div>

                    <div slot="order_pay_cycle" slot-scope="text, record">
                        <span v-if="record.order_pay_cycle == 1">一次性付款</span>
                        <span v-else-if="record.order_pay_cycle > 1">@{{  record.order_pay_cycle }}期</span>
                        <span v-else>未知</span>

                        <span v-if="record.is_debt == 1" style="color: red">(欠款)</span>
                    </div>

                    <div slot="action" slot-scope="text, record">
                        <div>
                            <a style="margin-right: 8px" @click="onUpdateOrder(record)">
                                修改订单信息
                            </a>
                        </div>
                        <div>
                            <a style="margin-right: 8px" @click="onAdd(record)">
                                添加收款流水
                            </a>
                        </div>
                        <div>
                            <a style="margin-right: 8px" @click="onFlow(record)">
                                流水明细 <span v-if="record.account_flow_count" style="color: red">(未审批:@{{record.account_flow_count}})</span>
                            </a>
                        </div>
                    </div>
                </a-table>

                <div style="text-align: right;margin-top: 10px">
                    <a-pagination
                        :current="pagination.current"
                        :page-size="pagination.pageSize"
                        :total="pagination.total"
                        @change="paginationChange"
                    ></a-pagination>
                </div>

            </div>

            <a-modal :mask-closable="false" v-model="dialogFormVisible"
                     :title="status"
                     width="800px" :footer="null">
                <account-flow-add ref="accountFlowAdd"
                                  :id="id"
                                  @add="afterAdd"
                                  @close="dialogFormVisible = false;"
                >
                </account-flow-add>
            </a-modal>

            <a-modal :mask-closable="false" v-model="flowFormVisible"
                     title="流水明细"
                     width="1200px" :footer="null">
                <account-flow-list ref="accountFlowList"
                                  :id="orderId"
                                  @approve="afterApprove"
                                  @close="flowFormVisible = false;"
                >
                </account-flow-list>
            </a-modal>

            <a-modal :mask-closable="false" v-model="updateOrderFormVisible"
                     title="流水明细"
                     width="800px" :footer="null">
                <order-update ref="orderUpdate"
                                   :id="updateOrderId"
                                   @submit="afterOrderUpdate"
                                   @close="updateOrderFormVisible = false;"
                >
                </order-update>
            </a-modal>



        </a-card>
    </div>
@endsection

@section('script')
    <script>
        Vue.use(httpVueLoader)
        new Vue({
            el: '#app',
            data: {
                listQuery: {
                    is_debt:undefined,
                    keyword: "",
                    address:'',
                    user_keyword:'',
                    node_id: '',
                    start_date:null,
                    end_date:null,
                },
                listSource: [],
                listLoading:false,
                status:'新增回款流水',
                pagination: {
                    pageSize: 10,
                    total: 0,
                    current: 1,
                    onChange: this.paginationChange,
                    onShowSizeChange: this.paginationChange,
                },
                columns:[
                    // {
                    //     title: 'Id',
                    //     dataIndex: 'order_id',
                    //     width: 80
                    // },
                    {
                        title: '订单编号',
                        fixed: 'left',
                        dataIndex: 'order_iid',
                        width: 100
                    },
                    {
                        title: '监控中心',
                        dataIndex: 'order_node_name'
                    },
                    {
                        title: '客户信息',
                        scopedSlots: { customRender: 'order_user' },
                        dataIndex: 'order_user_name'
                    },
                    {
                        title: '安装地址',
                        scopedSlots: { customRender: 'order_place' },
                        dataIndex: 'order_place',
                        width: 300
                    },
                    {
                        title: '状态',
                        dataIndex: 'order_status'
                    },
                    {
                        title: '付款周期',
                        scopedSlots: { customRender: 'order_pay_cycle' },
                        dataIndex: 'order_pay_cycle'
                    },
                    // {
                    //     title: '设备数',
                    //     dataIndex: 'order_device_count'
                    // },
                    {
                        title: '应收款',
                        dataIndex: 'order_account_receivable'
                    },
                    {
                        title: '实收款',
                        dataIndex: 'order_funds_received'
                    },
                    {
                        title: '合约类型',
                        dataIndex: 'order_contract_type'
                    },
                    {
                        title: '服务时长',
                        dataIndex: 'order_service_month_count'
                    },
                    {
                        title: '交付时间',
                        dataIndex: 'order_actual_delivery_date'
                    },
                    {
                        title: '操作',
                        fixed: 'right',
                        scopedSlots: { customRender: 'action' },
                    }
                ],
                dialogFormVisible:false,
                flowFormVisible:false,
                id:null,
                orderId:null,
                updateOrderId:null,
                updateOrderFormVisible:false,
                defaultDate:undefined
            },
            created () {
                this.listQuery.page_size = this.pagination.pageSize;
                this.handleFilter()
            },
            components: {
                "account-flow-list":  httpVueLoader('/statics/components/order/accountFlowList.vue'),
                "account-flow-add":  httpVueLoader('/statics/components/order/accountFlowAdd.vue'),
                "order-update":  httpVueLoader('/statics/components/order/orderUpdate.vue'),
                "node-cascader":  httpVueLoader('/statics/components/node/nodeCascader.vue')
            },
            methods: {
                moment,
                paginationChange (current, pageSize) {
                    this.listQuery.page = current;
                    this.pagination.current = current;
                    this.listQuery.page_size = pageSize;
                    this.getPageList()
                },
                onAdd(row){
                    this.id = row.order_id;
                    this.dialogFormVisible = true;
                },
                onFlow(row){
                    this.orderId = row.order_id;
                    this.flowFormVisible = true;
                },
                onUpdateOrder(row){
                    this.updateOrderId = row.order_id;
                    this.updateOrderFormVisible = true;
                },
                afterAdd(){
                    this.id = null;
                    this.orderId = null;
                    this.dialogFormVisible = false;
                    this.getPageList();
                },
                afterApprove(){
                    // this.orderId = null;
                    this.getPageList();
                },
                afterOrderUpdate(){
                    this.updateOrderFormVisible = false;
                    this.$message.success('更新成功');
                    this.updateOrderId = null;
                    this.getPageList();
                },
                // 刷新列表
                handleFilter () {
                    this.listQuery.page = 1
                    this.pagination.current = 1;
                    this.getPageList()
                },
                // 获取列表
                getPageList () {
                    this.listLoading = true
                    axios({
                        // 默认请求方式为get
                        method: 'post',
                        url: '/api/order/getList',
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
                dateChange(value,arr){
                    this.listQuery.start_date = arr[0];
                    this.listQuery.end_date = arr[1];
                },
                nodeChange(value){
                    this.listQuery.node_id = value;
                }
            },

        })
    </script>
@endsection
