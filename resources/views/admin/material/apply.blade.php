@extends('admin.layout')

@section('content')
    <div id="app">
        <a-card>
            <div>
                <a-form layout="inline" >
                    <a-form-item>
                        <material-select @change="materialChange"></material-select>
                    </a-form-item>
                    <a-form-item>
                        <a-select v-model="listQuery.status" show-search placeholder="请选择状态" :max-tag-count="1"
                                  style="width: 200px;" allow-clear>
                            <a-select-option :value="0">
                                已撤回
                            </a-select-option>
                            <a-select-option :value="1">
                                待审批
                            </a-select-option>
                            <a-select-option :value="2">
                                待出库
                            </a-select-option>
                            <a-select-option :value="3">
                                出库中
                            </a-select-option>
                            <a-select-option :value="4">
                                已完成
                            </a-select-option>
                            <a-select-option :value="5">
                                已驳回
                            </a-select-option>
                        </a-select>
                    </a-form-item>
                    <a-form-item>
                        <a-button icon="search" @click="handleFilter">查询</a-button>
                    </a-form-item>
                    <a-form-item>
                        <a-button @click="onCreate" type="primary" icon="edit">添加申领</a-button>
                    </a-form-item>

                </a-form>
                <a-table :columns="columns" :data-source="listSource" :loading="listLoading" :row-key="(record, index) => { return index }"
                         :pagination="false" :scroll="{ x: 1500,y: 650 }">

                    <div slot="detail" slot-scope="text, record">
                        <div v-for="(item,index) in record.detail" :key="index">
                            @{{ item.mate_name }} * @{{ item.maap_number }}(@{{ item.mate_unit }})</span>
                        </div>
                    </div>

                    <div slot="status" slot-scope="text, record">
                        <a-tag v-if="record.maap_status == 1">待审批</a-tag>
                        <a-tag v-if="record.maap_status == 0">已撤回</a-tag>
                        <a-tag color="#f50" v-else-if="record.maap_status == 2">待出库</a-tag>
                        <a-tag color="#f50" v-else-if="record.maap_status == 3">出库中</a-tag>
                        <a-tag color="green" v-else-if="record.maap_status == 4">已完成</a-tag>
                        <a-tag color="red" v-else-if="record.maap_status == 5">已驳回</a-tag>
                    </div>

                    <div slot="action" slot-scope="text, record">
                        <div>
                            <a @click="onDetail(record)">
                                详情
                            </a>
                        </div>
                        <div>
                            <a v-if="record.is_update" @click="onUpdate(record)">
                                修改
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
                     width="1000px" :footer="null">
                <apply-detail
                    style="max-height: 600px;overflow: auto"
                    ref="applyDetail"
                    :id="detailId"
                    @close="detailVisible = false;"
                >
                </apply-detail>
            </a-modal>

            <a-modal :mask-closable="false" v-model="dialogFormVisible"
                     :title="dialogStatus"
                     width="1000px" :footer="null">
                <apply-add
                    style="max-height: 600px;overflow: auto"
                            ref="applyAdd"
                             :id="id"
                             @update="update"
                             @add="add"
                             @close="dialogFormVisible = false;"
                >
                </apply-add>
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
                    material_id:'',
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
                        dataIndex: 'maap_id',
                        width: 80
                    },
                    {
                        title: '申购名称',
                        dataIndex: 'appr_name',
                    },
                    {
                        title: '申购事由',
                        dataIndex: 'appr_reason'
                    },
                    {
                        title: '申购详情',
                        scopedSlots: { customRender: 'detail' },
                        dataIndex: 'detail'
                    },
                    {
                        title: '申购总金额',
                        dataIndex: 'maap_total_price'
                    },
                    {
                        title: '申购人',
                        dataIndex: 'admin_name'
                    },
                    {
                        title: '状态',
                        scopedSlots: { customRender: 'status' },
                        dataIndex: 'maap_status'
                    },
                    {
                        title: '提交时间',
                        dataIndex: 'maap_crt_time'
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
                detailId:null
            },
            created () {
                this.listQuery.page_size = this.pagination.pageSize;
                this.handleFilter()
            },
            components: {
                "apply-add":  httpVueLoader('/statics/components/material/applyAdd.vue'),
                "apply-detail":  httpVueLoader('/statics/components/material/applyDetail.vue'),
                "material-select":  httpVueLoader('/statics/components/material/materialSelect.vue'),
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
                        url: '/api/materialApply/getList',
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
                onCreate(){
                    this.dialogStatus = '新增';
                    this.dialogFormVisible = true;
                },
                onUpdate(row){
                    this.id = row.maap_id;
                    this.dialogStatus = '修改';
                    this.dialogFormVisible = true;
                },
                update(){
                    this.id = null;
                    this.$message.success('编辑成功');
                    this.dialogFormVisible = false;
                    this.handleFilter();
                },
                add(){
                    this.id = null;
                    this.$message.success('添加成功');
                    this.dialogFormVisible = false;
                    this.handleFilter();
                },
                onDetail(row){
                    this.detailId = row.maap_id;
                    this.detailVisible = true;
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
                materialChange(value){
                    this.listQuery.material_id = value
                }
            },

        })


    </script>
@endsection
