@extends('admin.layout')

@section('content')
    <div id="app">
        <a-card>
            <div>
                <a-form layout="inline" >
                    <a-form-item label="地址">
                        <a-input v-model="listQuery.address" placeholder="地址" style="width: 200px;" />
                    </a-form-item>
                    <a-form-item label="用户名/手机号">
                        <a-input v-model="listQuery.user_keyword" placeholder="用户名/手机号" style="width: 200px;" />
                    </a-form-item>
                    <a-form-item label="单位">
                        <a-input v-model="listQuery.place" placeholder="单位" style="width: 200px;" />
                    </a-form-item>
                    <a-form-item label="安装时间">
                        <a-range-picker
                            :placeholder="['开始时间', '结束时间']"
                            @change="dateChange"
                            :default-value="[defaultDate,defaultDate]">

                        </a-range-picker>
                    </a-form-item>
                    <a-form-item label="街道">
                        <node-select type="街道办"></node-select>
                    </a-form-item>

                    <a-form-item label="村委">
                        <node-select type="村委"></node-select>
                    </a-form-item>

                    <a-form-item>
                        <a-button icon="search" v-on:click="handleFilter">查询</a-button>
                    </a-form-item>
                </a-form>

                <a-table :columns="columns" :data-source="listSource" :loading="listLoading" :row-key="(record, index) => { return index }"
                         :pagination="false"  :scroll="{ x: 2800,y: 650}">

                    <div slot="order_place" slot-scope="text, record">
                        <div v-for="item in record.order_place">
                            @{{ item.plac_address }}
                        </div>
                    </div>

                    <div slot="order_is_pay" slot-scope="text, record">
                        <span v-if="record.order_funds_received > 0">是</span>
                        <span v-else>否</span>
                    </div>

                    <div slot="order_not_pay" slot-scope="text, record">
                        <span>@{{ record.order_account_receivable -  record.order_funds_received}}</span>
                    </div>


                    <div slot="order_pay_cycle" slot-scope="text, record">
                        <span v-if="record.order_pay_cycle == 1">一次性付款</span>
                        <span v-if="record.order_pay_cycle > 1">@{{  record.order_pay_cycle }}期</span>
                        <span v-else>未知</span>

                        <span v-if="record.is_debt == 1" style="color: red">(欠款)</span>
                    </div>

                    <div slot="account_flow_list" slot-scope="text, record">
                        <div v-for="item in record.account_flow_list">
                            <span>@{{ item.orac_pay_way }}:</span> <span style="color:red">@{{ item.orac_funds_received }}￥</span> <span>@{{ item.orac_datetime }}</span>
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
                    {
                        title: '安装日期',
                        fixed: 'left',
                        dataIndex: 'order_actual_delivery_date',
                        align:'center',
                        width: 150
                    },
                    {
                        title: '区域场所',
                        fixed: 'left',
                        dataIndex: 'order_node_name',
                        align:'center',
                        width: 150
                    },
                    {
                        title: '单位',
                        fixed: 'left',
                        dataIndex: 'order_user_name',
                        align:'center',
                        width: 100
                    },
                    {
                        title: '联系方式',
                        dataIndex: 'order_user_mobile',
                        align:'center',
                    },
                    {
                        title: '详细地址',
                        scopedSlots: { customRender: 'order_place' },
                        dataIndex: 'order_place',
                        align:'center',
                        width: 300
                    },
                    {
                        title: '安装总数',
                        dataIndex: 'order_device_count',
                        align:'center',
                    },
                    {
                        title: '赠送台数',
                        dataIndex: 'order_amount_given',
                        align:'center',
                    },
                    {
                        title: '备注（完成情况）',
                        dataIndex: 'order_remark',
                        align:'center',
                    },
                    {
                        title: '应收账款',
                        dataIndex: 'order_account_receivable',
                        align:'center',
                    },
                    {
                        title: '是否付款',
                        scopedSlots: { customRender: 'order_is_pay' },
                        align:'center',
                        dataIndex: 'order_is_pay'
                    },
                    {
                        title: '付款金额',
                        dataIndex: 'order_funds_received',
                        align:'center',
                    },
                    {
                        title: '未付金额',
                        scopedSlots: { customRender: 'order_not_pay' },
                        align:'center',
                        dataIndex: 'order_not_pay'
                    },
                    {
                        title: '付款方案',
                        scopedSlots: { customRender: 'order_pay_cycle' },
                        align:'center',
                        dataIndex: 'order_pay_cycle'
                    },
                    {
                        title: '收款路径',
                        scopedSlots: { customRender: 'order_pay_way' },
                        align:'center',
                        dataIndex: 'order_pay_cycle'
                    },
                    {
                        title: '回款时间',
                        scopedSlots: { customRender: 'account_flow_list' },
                        dataIndex: 'account_flow_list',
                        align:'center',
                        width:300
                    },
                ],
                dialogFormVisible:false,
                flowFormVisible:false,
                id:null,
                orderId:null,
                defaultDate:undefined
            },
            created () {
                this.listQuery.page_size = this.pagination.pageSize;
                this.handleFilter()
            },
            components: {
                "node-select":  httpVueLoader('/statics/components/node/nodeSelect.vue'),
            },
            methods: {
                moment,
                paginationChange (current, pageSize) {
                    this.listQuery.page = current;
                    this.pagination.current = current;
                    this.listQuery.page_size = pageSize;
                    this.getPageList()
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
                        url: '/api/installation/summary',
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
                }
            },

        })
    </script>
@endsection
