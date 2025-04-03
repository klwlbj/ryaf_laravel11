@extends('admin.layout')

@section('content')
    <div id="app">
        <a-card>
            <div>
                <a-form layout="inline" >
                    <a-form-item>
                        <a-select v-model="listQuery.status" show-search placeholder="请选择状态" :max-tag-count="1"
                                  style="width: 200px;" allow-clear>
                            <a-select-option :value="0">
                                已撤回
                            </a-select-option>
                            <a-select-option :value="1">
                                审批中
                            </a-select-option>
                            <a-select-option :value="2">
                                已通过
                            </a-select-option>
                            <a-select-option :value="3">
                                已拒绝
                            </a-select-option>
                        </a-select>
                    </a-form-item>
                    <a-form-item>
                        <a-button icon="search" @click="handleFilter">查询</a-button>
                    </a-form-item>

                </a-form>
                <a-table :columns="columns" :data-source="listSource" :loading="listLoading" :row-key="(record, index) => { return index }"
                         :pagination="false" :scroll="{ x: 1500,y: 650 }">

                    <div slot="type" slot-scope="text, record">
                        <a-tag v-if="record.appr_type == 1" color="#108ee9">物品申领</a-tag>
                    </div>

                    <div slot="status" slot-scope="text, record">
                        <a-tag color="#f50" v-if="record.appr_status == 1">审批中</a-tag>
                        <a-tag v-if="record.appr_status == 0">已撤回</a-tag>
                        <a-tag color="green" v-else-if="record.appr_status == 2">已通过</a-tag>
                        <a-tag color="red" v-else-if="record.appr_status == 3">已拒绝</a-tag>
                    </div>

                    <div slot="process" slot-scope="text, record">
                        <span v-if="record.process_admin_name" style="color: orange">@{{ record.process_admin_name }}审批中</span>
                    </div>

                    <div slot="action" slot-scope="text, record">
                        <div>
                            <a @click="onDetail(record)">
                                详情
                            </a>
                        </div>
                        <div>
                            <a v-if="record.appr_status==2" @click="onPrint(record)">
                                打印页面
                            </a>
                        </div>
                        <div>
                            <a-popconfirm
                                v-if="record.is_cancel"
                                title="是否确定撤回?"
                                ok-text="确认"
                                cancel-text="取消"
                                @confirm="onCancel(record)"
                            >
                                <a style="margin-right: 8px">
                                    撤回
                                </a>
                            </a-popconfirm>
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

            <a-modal :mask-closable="false" v-model="detailVisible"
                     title="详情"
                     width="1000px" :footer="null"
                     @cancel="approvalId=null">
                <material-apply-detail
                    v-if="approvalType == 1"
                    style="max-height: 70vh;overflow: auto"
                    ref="materialApplyDetail"
                    :id="approvalId"
                    @approval="afterApproval"
                    @close="detailVisible = false;approvalId=null"
                >
                </material-apply-detail>
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
                    type:'has_approval',
                    status:undefined,
                },
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
                        title: 'Id',
                        dataIndex: 'appr_id',
                        width: 80
                    },
                    {
                        title: '审批编号',
                        dataIndex: 'appr_sn',
                    },
                    {
                        title: '审批类型',
                        scopedSlots: { customRender: 'type' },
                        dataIndex: 'appr_type',
                    },
                    {
                        title: '审批名称',
                        dataIndex: 'appr_name',
                    },
                    {
                        title: '审批事由',
                        dataIndex: 'appr_reason'
                    },
                    {
                        title: '提审人',
                        dataIndex: 'admin_name'
                    },
                    {
                        title: '状态',
                        scopedSlots: { customRender: 'status' },
                        dataIndex: 'appr_status'
                    },
                    {
                        title: '待审批用户',
                        scopedSlots: { customRender: 'process' },
                        dataIndex: 'appr_process'
                    },
                    {
                        title: '提审时间',
                        dataIndex: 'appr_crt_time'
                    },
                    {
                        title: '操作',
                        scopedSlots: { customRender: 'action' },
                        fixed:'right'
                    }
                ],
                dialogFormVisible:false,
                detailVisible:false,
                id:null,
                approvalId:null,
                approvalType:null
            },
            created () {
                this.listQuery.page_size = this.pagination.pageSize;
                this.handleFilter()
            },
            components: {
                "material-apply-detail":  httpVueLoader('/statics/components/approval/materialApplyDetail.vue'),
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
                        url: '/api/approval/getList',
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
                onDetail(row){
                    this.approvalId = row.appr_id;
                    this.approvalType = row.appr_type;
                    this.detailVisible = true;
                },
                afterApproval(){
                    this.approvalId = null;
                    this.detailVisible = false
                    this.$message.success('审批成功');
                    this.getPageList();
                },
                onCancel(row){
                    this.listLoading = true
                    axios({
                        // 默认请求方式为get
                        method: 'post',
                        url: '/api/approval/cancel',
                        // 传递参数
                        data: {
                            id:row.appr_id
                        },
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
                        this.$message.success('撤回成功');
                        this.getPageList();
                    }).catch(error => {
                        this.$message.error('请求失败');
                    });
                },
                onPrint(row){
                    window.open('/approval/materialApplyPrint?id='+row.appr_id);
                }
            },

        })


    </script>
@endsection
