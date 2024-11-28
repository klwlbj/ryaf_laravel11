@extends('admin.layout')

@section('content')
    <div id="app">
        <a-card>
            <div>
                <a-form layout="inline" >
                    <a-form-model-item label="监控中心" prop="node_id">
                        <node-cascader :default-data="nodeId" @change="nodeChange"></node-cascader>
                    </a-form-model-item>
                    <a-form-item label="imei">
                        <a-input v-model="listQuery.imei" placeholder="imei" style="width: 200px;" />
                    </a-form-item>

                    <a-form-item label="设备状态">
                        <a-select v-model="listQuery.online" show-search placeholder="设备状态" :max-tag-count="1"
                                  style="width: 200px;" allow-clear>
                            <a-select-option :value="1">
                                在线
                            </a-select-option>
                            <a-select-option :value="0">
                                离线
                            </a-select-option>
                        </a-select>
                    </a-form-item>

                    <a-form-item label="无心跳天数">
                        <a-input-number v-model="listQuery.none_heart_day"/>
                    </a-form-item>

                    <a-form-item label="服务临期天数">
                        <a-input-number v-model="listQuery.expired_day"/>
                    </a-form-item>
                    <a-form-item>
                        <a-button icon="search" v-on:click="handleFilter">查询</a-button>
                    </a-form-item>
                </a-form>

                <a-table :columns="columns" :data-source="listSource" :loading="listLoading" :row-key="(record, index) => { return index }"
                         :pagination="false" :key="tableKey" :scroll="{ y: 650 }" :default-expand-all-rows="true">

                    <div slot="userinfo" slot-scope="text, record">
                        <div>@{{record.user_name}}</div>
                        <div>@{{record.user_mobile}}</div>
                    </div>

                    <div slot="count" slot-scope="text, record">
                        <div>@{{record.children_list.length}}</div>
                    </div>


                    <div slot="expandedRowRender" slot-scope="parentRow">
                        <a-table v-if="parentRow.children_list.length" :columns="childColumn" :data-source="parentRow.children_list" :pagination="false">
                            <div slot="smde_online_real" slot-scope="childText,childRecord">
                                <a-tag v-if="childRecord.smde_online_real == 0"  color="red">离线</a-tag>
                                <a-tag v-else color="green">在线</a-tag>
                            </div>
                        </a-table>
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
                    imei:'',
                    none_heart_day:'',
                    online:undefined,
                    expired_day:'',
                    node_id:undefined
                },
                tableKey:1,
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
                        title: '单位id',
                        dataIndex: 'plac_id',
                    },
                    {
                        title: '监控中心',
                        dataIndex: 'node_name',
                    },
                    {
                        title: '单位名称',
                        dataIndex: 'plac_name',
                        width: 200
                    },
                    {
                        title: '单位地址',
                        dataIndex: 'plac_address',
                        width: 200
                    },
                    {
                        title: '用户信息',
                        scopedSlots: { customRender: 'userinfo' },
                        dataIndex: 'userinfo',
                    },
                    {
                        title: '设备数',
                        scopedSlots: { customRender: 'count' },
                        dataIndex: 'count',
                    },
                    {
                        title: '操作',
                        scopedSlots: { customRender: 'action' },
                        // fixed:'right'
                    }
                ],
                childColumn:[
                    {
                        title: 'imei',
                        dataIndex: 'smde_imei'
                    },
                    {
                        title: '信号',
                        dataIndex: 'smde_model_name'
                    },
                    {
                        title: '最后心跳包',
                        dataIndex: 'smde_last_heart_beat'
                    },
                    {
                        title: '服务期限',
                        dataIndex: 'order_service_date'
                    },
                    {
                        title: '设备状态',
                        scopedSlots: { customRender: 'smde_online_real' },
                        dataIndex: 'smde_online_real'
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
                "node-cascader":  httpVueLoader('/statics/components/node/nodeCascader.vue')
            },
            methods: {
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
                        url: '/api/maintain/placeList',
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
                        this.tableKey++;
                        this.listLoading = false
                    }).catch(error => {
                        this.$message.error('请求失败');
                    });
                },
                nodeChange(value){
                    // console.log(value);
                    this.listQuery.node_id = value;
                },
            },

        })
    </script>
@endsection
