@extends('admin.layout')

@section('content')
    <div id="app">
        <a-card>
            <div>
                <a-form layout="inline" >
                    <a-form-item>
                        <a-button :loading="exportLoading" icon="download" @click="exportList">导出</a-button>
                    </a-form-item>
                </a-form>

                <a-table :columns="columns" :data-source="listSource" :loading="listLoading" :row-key="(record, index) => { return index }"
                         :pagination="false" :scroll="{ y: 650 }">


                </a-table>

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

                },
                defaultDate:undefined,
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
                        title: '街道',
                        dataIndex: 'node_name',
                        align:'center'
                    },
                    {
                        title: '设备总数',
                        dataIndex: 'count',
                        align:'center'
                    },
                    {
                        title: '24小时在线数',
                        dataIndex: '24_count',
                        align:'center'
                    },
                    {
                        title: '48小时在线数',
                        dataIndex: '48_count',
                        align:'center'
                    },
                    {
                        title: '24小时在线率',
                        dataIndex: '24_rate',
                        align:'center'
                    },
                    {
                        title: '48小时在线率',
                        dataIndex: '48_rate',
                        align:'center'
                    },
                ],
                dialogFormVisible:false,
                id:null
            },
            created () {
                this.handleFilter()
            },
            components: {

            },
            methods: {
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
                        url: '/api/report/online',
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
                exportList(){
                    this.exportLoading = true;
                    let formData = JSON.parse(JSON.stringify(this.listQuery));
                    formData.export = 1;
                    axios({
                        // 默认请求方式为get
                        method: 'post',
                        url: '/api/report/online',
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
            },

        })
    </script>

    <style>
        .ant-table-tbody tr:nth-child(2n){
            background: #f1f1f1;
        }
    </style>
@endsection

