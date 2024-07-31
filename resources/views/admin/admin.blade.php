@extends('admin.layout')

@section('content')
    <div id="app">
        <a-card>
            <div>
                <a-form layout="inline" >
                    <a-form-item>
                        <department-tree style="width: 200px;" ref="departmentTree" @change="departmentChange"></department-tree>
                    </a-form-item>
                    <a-form-item>
                        <a-input v-model="listQuery.keyword" placeholder="成员" style="width: 200px;" />
                    </a-form-item>
{{--                    <a-form-item>--}}
{{--                        <a-select v-model="listQuery.is_leader" show-search placeholder="是否负责人"--}}
{{--                                  style="width: 200px;" allow-clear>--}}
{{--                            <a-select-option :value="1">--}}
{{--                                是--}}
{{--                            </a-select-option>--}}
{{--                            <a-select-option :value="0">--}}
{{--                                否--}}
{{--                            </a-select-option>--}}
{{--                        </a-select>--}}
{{--                    </a-form-item>--}}
                    <a-form-item>
                        <a-button icon="search" v-on:click="handleFilter">查询</a-button>
                    </a-form-item>
                    <a-form-item>
                        <a-button @click="onCreate" type="primary" icon="edit">添加成员</a-button>
                    </a-form-item>
                </a-form>

                <a-table :columns="columns" :data-source="listSource" :loading="listLoading" :row-key="(record, index) => { return index }"
                         :pagination="false">

                    <div slot="status" slot-scope="text, record">
                        <a-tag v-if="record.admin_enabled == 0"  color="red">禁用</a-tag>
                        <a-tag v-else color="green">启用</a-tag>
                    </div>

                    <div slot="admin_is_leader" slot-scope="text, record">
                        <a-tag v-if="record.admin_is_leader == 0"  color="red">否</a-tag>
                        <a-tag v-else color="green">是</a-tag>
                    </div>

                    <div slot="action" slot-scope="text, record">
                        <a style="margin-right: 8px" @click="onUpdate(record)">
                            修改
                        </a>

                        <a-popconfirm
                            title="是否确定删除商品?"
                            ok-text="确认"
                            cancel-text="取消"
                            v-on:confirm="onDel(record)"
                        >
{{--                            <a style="margin-right: 8px">--}}
{{--                                删除--}}
{{--                            </a>--}}
                        </a-popconfirm>
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
                <admin-add ref="adminAdd"
                           style="height: 600px;overflow: auto"
                                  :id="id"
                                  @update="update"
                                  @add="add"
                                  @close="dialogFormVisible = false;"
                >
                </admin-add>
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
                    department_id : undefined,
                    is_leader : undefined
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
                    {
                        title: 'Id',
                        dataIndex: 'admin_id',
                    },
                    {
                        title: '成员名称',
                        dataIndex: 'admin_name',
                    },
                    {
                        title: '部门',
                        dataIndex: 'admin_department_name'
                    },
                    {
                        title: '账号',
                        dataIndex: 'admin_mobile',
                    },
                    // {
                    //     title: '是否负责人',
                    //     scopedSlots: { customRender: 'admin_is_leader' },
                    //     dataIndex: 'admin_is_leader'
                    // },
                    {
                        title: '状态',
                        scopedSlots: { customRender: 'status' },
                        dataIndex: 'admin_enabled'
                    },
                    {
                        title: '操作',
                        scopedSlots: { customRender: 'action' },
                    }
                ],
                dialogFormVisible:false,
                id:null
            },
            created () {
                this.listQuery.page_size = this.pagination.pageSize;
                this.handleFilter()
            },
            components: {
                "department-tree":  httpVueLoader('/statics/components/department/departmentTree.vue'),
                "admin-add":  httpVueLoader('/statics/components/admin/adminAdd.vue')
            },
            methods: {
                departmentChange(value){
                    this.listQuery.department_id = value;
                },
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
                        url: '/api/admin/getList',
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
                    this.status = '添加';
                    this.dialogFormVisible = true;
                },
                onUpdate(row){
                    this.id = row.admin_id
                    this.status = '更新';
                    this.dialogFormVisible = true;
                },
                onDel(row){
                    axios({
                        // 默认请求方式为get
                        method: 'post',
                        url: '/api/admin/delete',
                        // 传递参数
                        data: {
                            id:row.admin_id
                        },
                        responseType: 'json',
                        headers:{
                            'Content-Type': 'multipart/form-data'
                        }
                    }).then(response => {
                        this.loading = false;
                        let res = response.data;
                        if(res.code !== 0){
                            this.$message.error(res.message);
                            return false;
                        }
                        this.$message.success('删除成功');
                        this.handleFilter();
                    }).catch(error => {
                        this.$message.error('请求失败');
                    });
                },
                add(){
                    this.id = null;
                    this.$message.success('添加成功');
                    this.dialogFormVisible = false;
                    this.getPageList();
                },
                update(){
                    this.id = null;
                    this.$message.success('编辑成功');
                    this.dialogFormVisible = false;
                    this.getPageList();
                }
            },

        })
    </script>
@endsection
