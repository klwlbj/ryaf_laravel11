@extends('admin.layout')

@section('content')
    <div id="app">
        <a-card>
            <div>
                <a-form layout="inline" >
                    <a-form-item label="物品">
                        <material-select @change="materialChange"></material-select>
                    </a-form-item>
                    <a-form-item label="出库日期">
                        <a-range-picker
                            :placeholder="['开始时间', '结束时间']"
                            @change="dateChange"
                            :default-value="[defaultDate,defaultDate]"></a-range-picker>
                    </a-form-item>

                    <a-form-item label="领用人">
                        <receive-select @change="receiveChange"></receive-select>
                    </a-form-item>

                    <a-form-item label="状态">
                        <a-select v-model="listQuery.status" show-search placeholder="请选择状态" :max-tag-count="1"
                                  style="width: 200px;" allow-clear>
                            <a-select-option :value="1">
                                未完成
                            </a-select-option>
                            <a-select-option :value="2">
                                已完成
                            </a-select-option>
                        </a-select>
                    </a-form-item>

                    <a-form-item>
                        <a-button icon="search" @click="handleFilter">查询</a-button>
                    </a-form-item>
                </a-form>
                <a-table :columns="columns" :data-source="listSource" :loading="listLoading" :row-key="(record, index) => { return index }"
                         :pagination="false" :scroll="{y: 650 }">

                    <div slot="mafl_specification_name" slot-scope="text, record">
                        <a-tag v-for="(item,i) in record.mafl_specification_name" :key="i">@{{ item }}</a-tag>
                    </div>

                    <div slot="apply_user" slot-scope="text, record">
                        <span>@{{  record.mafl_apply_user }}/@{{  record.mafl_receive_user }}</span>
                    </div>

                    <div slot="purpose" slot-scope="text, record">
                        <a-tag v-if="record.mafl_type == 2 && record.mafl_purpose == 1"  color="#2db7f5">销售性质</a-tag>
                        <a-tag v-else-if="record.mafl_type == 2 && record.mafl_purpose == 2" color="#2db7f5">非销售性质</a-tag>
                        <span v-else>-</span>
                    </div>

                    <div slot="number" slot-scope="text, record">
                        <span style="color:green">@{{ record.mafl_number }}</span>/<span style="color:red">@{{ record.consume_number }}</span>
                    </div>

                    <div slot="action" slot-scope="text, record">
                        <div v-if="record.consume_status==1">
                            <a style="margin-right: 8px" @click="onAddConsume(record)">
                                添加记录
                            </a>
                        </div>

                        <div>
                            <a style="margin-right: 8px" @click="onGetConsume(record)">
                                查看记录
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

            <a-modal :mask-closable="false" v-model="consumeAddFormVisible"
                     title="填写消耗记录"
                     width="800px" :footer="null">
                <consume-add ref="consumeAdd"
                             :id="flowId"
                             @submit="consumeAddSubmit"
                             @close="consumeAddFormVisible = false;"
                >
                </consume-add>
            </a-modal>

            <a-modal :mask-closable="false" v-model="consumeListFormVisible"
                     title="消耗记录"
                     width="800px" :footer="null">
                <consume-flow ref="consumeFlow"
                             :id="flowListId"
                             @close="consumeListFormVisible = false;"
                >
                </consume-flow>
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
                    status:1,
                    start_date:null,
                    end_date:null,
                    receive_user_id:undefined
                },
                defaultDate:undefined,
                listSource: [],
                listLoading:false,
                dialogStatus:'新增',
                pagination: {
                    pageSize: 10,
                    total: 0,
                    current: 1,
                    onChange: this.paginationChange,
                    onShowSizeChange: this.paginationChange,
                },
                columns:[
                    {
                        title: '物品名称',
                        fixed: 'left',
                        dataIndex: 'mafl_material_name',
                        width: 200,
                        align: 'center'

                    },
                    {
                        title: '规格',
                        scopedSlots: { customRender: 'mafl_specification_name' },
                        dataIndex: 'mafl_specification_name',
                        align: 'center'
                    },
                    {
                        title: '数量/消耗',
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
                dialogFormVisible:false,
                consumeAddFormVisible: false,
                consumeListFormVisible: false,
                id:null,
                flowId:null,
                flowListId:null,
                admin:{}
            },
            created () {
                this.admin = JSON.parse(localStorage.getItem("admin"));
                if(this.admin.admin_id == 44){
                    this.listQuery.status = 1;
                }
                this.listQuery.page_size = this.pagination.pageSize;
                this.handleFilter()
            },
            components: {
                "material-select":  httpVueLoader('/statics/components/material/materialSelect.vue'),
                "consume-add":  httpVueLoader('/statics/components/material/consumeAdd.vue'),
                "consume-flow":  httpVueLoader('/statics/components/material/consumeFlow.vue'),
                "receive-select":  httpVueLoader('/statics/components/admin/adminSelect.vue'),
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
                        url: '/api/materialFlowConsume/getList',
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
                    this.handleFilter();
                },
                materialChange(value){
                    this.listQuery.material_id = value;
                    this.handleFilter();
                },
                receiveChange(value){
                    this.listQuery.receive_user_id = value;
                },
                onAddConsume(row){
                    this.flowId = row.mafl_id;
                    this.consumeAddFormVisible = true;
                },
                onGetConsume(row){
                    this.flowListId = row.mafl_id;
                    this.consumeListFormVisible = true;
                },
                consumeAddSubmit(){
                    this.flowId = null;
                    this.flowListId = null;
                    this.consumeAddFormVisible = false;
                    this.getPageList();
                }
            },

        })


    </script>
@endsection

