@extends('admin.layout')

@section('content')
    <div id="app">
        <a-card>
            <div>
                <a-form layout="inline" >
                    <a-form-item>
                        <a-input v-model="listQuery.keyword" placeholder="订单编号" style="width: 200px;" />
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
                         :pagination="false"  :scroll="{ x: 1500 }">

                    <div slot="order_place" slot-scope="text, record">
                        <div v-for="item in record.order_place">
                            @{{ item.plac_address }}
                        </div>
                    </div>

                    <div slot="order_user" slot-scope="text, record">
                        <div>@{{ record.order_user_name }}</div>
                        <div>@{{ record.order_user_mobile }}</div>
                    </div>

                    <div slot="action" slot-scope="text, record">
                        <a style="margin-right: 8px" @click="onUpdate(record)">
                            添加收款流水
                        </a>
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
                <manufacturer-add ref="manufacturerAdd"
                                  :id="id"
                                  @close="dialogFormVisible = false;"
                >
                </manufacturer-add>
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
                    keyword: "",
                    start_date:null,
                    end_date:null,
                },
                listSource: [],
                listLoading:false,
                status:'新增',
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
                        title: '合约类型',
                        dataIndex: 'order_contract_type'
                    },
                    {
                        title: '服务时长',
                        dataIndex: 'order_service_month_count'
                    },
                    {
                        title: '设备数',
                        dataIndex: 'order_device_count'
                    },
                    {
                        title: '应收款',
                        dataIndex: 'order_account_receivable'
                    },
                    {
                        title: '实收款',
                        dataIndex: 'order_funds_received'
                    },
                    {
                        title: '创建日期',
                        dataIndex: 'order_crt_time'
                    },
                    {
                        title: '操作',
                        fixed: 'right',
                        scopedSlots: { customRender: 'action' },
                    }
                ],
                dialogFormVisible:false,
                id:null,
                defaultDate:undefined
            },
            created () {
                this.listQuery.page_size = this.pagination.pageSize;
                this.handleFilter()
            },
            components: {
                "manufacturer-add":  httpVueLoader('/statics/components/material/manufacturerAdd.vue')
            },
            methods: {
                moment,
                paginationChange (current, pageSize) {
                    this.listQuery.page = current;
                    this.pagination.current = current;
                    this.listQuery.page_size = pageSize;
                    this.getPageList()
                },
                onUpdate(row){

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
                }
            },

        })
    </script>
@endsection
