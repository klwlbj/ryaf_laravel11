@extends('admin.layout')

@section('content')
    <div id="app">
        <a-card>
            <div>
                <a-form layout="inline" >
                    <a-form-item label="交付时间">
                        <a-range-picker
                            :placeholder="['开始时间', '结束时间']"
                            @change="dateChange"
                            :default-value="[defaultStartDate,defaultEndDate]"></a-range-picker>
                    </a-form-item>

                    <a-form-item label="监察天数">
                        <a-input-number v-model="listQuery.check_day"/>
                    </a-form-item>

                    <a-form-item label="交付后无信号天数">
                        <a-input-number v-model="listQuery.heart_day"/>
                    </a-form-item>

                    <a-form-item>
                        <a-button icon="search" v-on:click="handleFilter">查询</a-button>
                    </a-form-item>

                    <a-form-item>
                        <a-button :loading="exportLoading" icon="download" @click="exportList">导出</a-button>
                    </a-form-item>
                </a-form>

                <a-table :columns="columns" :data-source="listSource" :loading="listLoading" :row-key="(record, index) => { return index }"
                         :pagination="false" :scroll="{ y: 650 }">

                    <div slot="userinfo" slot-scope="text, record">
                        <div>@{{record.order_user_name}}</div>
                        <div>@{{record.order_user_mobile}}</div>
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
                    start_date:moment('2024-12-31').format("YYYY-MM-DD"),
                    end_date:moment().format("YYYY-MM-DD"),
                    check_day:10,
                    heart_day:4
                },
                defaultStartDate:moment('2024-12-31').format("YYYY-MM-DD"),
                defaultEndDate:moment().format("YYYY-MM-DD"),
                tableKey:1,
                exportLoading:false,
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
                    {
                        title: 'imei',
                        dataIndex: 'smde_imei',
                    },
                    {
                        title: '监控中心',
                        dataIndex: 'node_name',
                    },
                    {
                        title: '安装地址',
                        dataIndex: 'plac_address',
                    },
                    {
                        title: '用户信息',
                        scopedSlots: { customRender: 'userinfo' },
                        dataIndex: 'userinfo',
                    },
                    {
                        title: '安装人员',
                        dataIndex: 'admin_name',
                    },
                    {
                        title: '交付时间',
                        dataIndex: 'order_actual_delivery_date',
                    },
                    {
                        title: '最后心跳包',
                        dataIndex: 'smde_last_heart_beat',
                    },
                ],
                dialogFormVisible:false,
                id:null
            },
            created () {
                this.listQuery.page_size = this.pagination.pageSize;
                this.handleFilter()
            },
            components: {
                "node-cascader":  httpVueLoader('/statics/components/node/nodeCascader.vue'),
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
                        url: '/api/maintain/installationCheckList',
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
                        // this.tableKey++;
                        this.listLoading = false
                    }).catch(error => {
                        this.$message.error('请求失败');
                    });
                },
                nodeChange(value){
                    // console.log(value);
                    this.listQuery.node_id = value;
                },
                exportList(){
                    this.exportLoading = true;
                    let formData = JSON.parse(JSON.stringify(this.listQuery));
                    formData.export = 1;
                    axios({
                        // 默认请求方式为get
                        method: 'post',
                        url: '/api/maintain/installationCheckList',
                        // 传递参数
                        data: formData,
                        responseType: 'json',
                        headers:{
                            'Content-Type': 'multipart/form-data'
                        }
                    }).then(response => {
                        this.exportLoading = false;
                        let res = response.data;
                        if(res.code !== 0){
                            this.$message.error(res.message);
                            return false;
                        }
                        window.location.href = res.data.url
                    }).catch(error => {
                        this.$message.error('请求失败');
                    });
                },
                dateChange(value,arr){
                    this.listQuery.start_date = arr[0];
                    this.listQuery.end_date = arr[1];
                },
            },

        })
    </script>

    <style>
        .ant-table-tbody tr:nth-child(2n){
            background: #f1f1f1;
        }
    </style>
@endsection

