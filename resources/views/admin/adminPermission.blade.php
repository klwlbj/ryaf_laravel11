@extends('admin.layout')

@section('content')
    <div id="app">
        <a-card>
            <div>
                <a-form layout="inline" >
                    <a-form-item>
                        <a-input v-model="listQuery.keyword" placeholder="权限名称" style="width: 200px;" />
                    </a-form-item>
                    <a-form-item>
                        <a-button icon="search" v-on:click="handleFilter">查询</a-button>
                    </a-form-item>
                    <a-form-item>
                        <a-button @click="onCreate" type="primary" icon="edit">添加权限</a-button>
                    </a-form-item>
                </a-form>

                <a-table :key="key" :columns="columns" :data-source="listSource" :loading="listLoading" :row-key="(record, index) => { return record.adpe_id }"
                         :pagination="false" :indent-size="30" :default-expand-all-rows="true" :scroll="{y: 650}">

                    <div slot="type" slot-scope="text, record">
                        <a-tag v-if="record.adpe_type == 1"  color="#009688">菜单</a-tag>
                        <a-tag v-else color="blue">接口</a-tag>
                    </div>

                    <div slot="status" slot-scope="text, record">
                        <a-tag v-if="record.adpe_status == 0"  color="red">禁用</a-tag>
                        <a-tag v-else color="green">启用</a-tag>
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
                            <a style="margin-right: 8px">
                                删除
                            </a>
                        </a-popconfirm>
                    </div>
                </a-table>
            </div>

            <a-modal :mask-closable="false" v-model="dialogFormVisible"
                     :title="status"
                     width="800px" :footer="null">
                <admin-permission-add ref="adminPermissionAdd"
                                  :id="id"
                                  @update="update"
                                  @add="add"
                                  @close="dialogFormVisible = false;"
                >
                </admin-permission-add>
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
                    //     dataIndex: 'depa_id',
                    //     // width: 80
                    // },
                    {
                        title: '名称',
                        dataIndex: 'adpe_name',
                    },
                    {
                        title: '路由',
                        dataIndex: 'adpe_route',
                    },
                    {
                        title: '类型',
                        scopedSlots: { customRender: 'type' },
                        dataIndex: 'adpe_type'
                    },
                    {
                        title: '状态',
                        scopedSlots: { customRender: 'status' },
                        dataIndex: 'adpe_status'
                    },
                    {
                        title: '排序',
                        dataIndex: 'adpe_sort',
                    },
                    {
                        title: '更新时间',
                        dataIndex: 'adpe_upd_time'
                    },
                    {
                        title: '操作',
                        scopedSlots: { customRender: 'action' },
                    }
                ],
                dialogFormVisible:false,
                id:null,
                key:1,
            },
            created () {
                this.listQuery.page_size = this.pagination.pageSize;
                this.handleFilter()
            },
            components: {
                "admin-permission-add":  httpVueLoader('/statics/components/adminPermission/adminPermissionAdd.vue')
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
                        url: '/api/adminPermission/getTreeList',
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
                        this.key = this.key+1;
                    }).catch(error => {
                        this.$message.error('请求失败');
                    });
                },
                onCreate(){
                    this.status = '添加';
                    this.dialogFormVisible = true;
                },
                onUpdate(row){
                    this.id = row.adpe_id;
                    this.status = '更新';
                    this.dialogFormVisible = true;
                },
                onDel(row){
                    axios({
                        // 默认请求方式为get
                        method: 'post',
                        url: '/api/adminPermission/delete',
                        // 传递参数
                        data: {
                            id:row.adpe_id
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
                    this.handleFilter();
                },
                update(){
                    this.id = null;
                    this.$message.success('编辑成功');
                    this.dialogFormVisible = false;
                    this.handleFilter();
                }
            },

        })
    </script>
@endsection
