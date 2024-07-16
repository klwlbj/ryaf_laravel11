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
                            <a-select-option :value="1">
                                待审批
                            </a-select-option>
                            <a-select-option :value="2">
                                申购中
                            </a-select-option>
                            <a-select-option :value="3">
                                已驳回
                            </a-select-option>
                            <a-select-option :value="4">
                                已完成
                            </a-select-option>
                        </a-select>
                    </a-form-item>
                    <a-form-item>
                        <a-button icon="search" @click="handleFilter">查询</a-button>
                    </a-form-item>
                    <a-form-item>
                        <a-button @click="onCreate" type="primary" icon="edit">添加申购</a-button>
                    </a-form-item>

                </a-form>
                <a-table :columns="columns" :data-source="listSource" :loading="listLoading" :row-key="(record, index) => { return index }"
                         :pagination="false">

                    <div slot="detail" slot-scope="text, record">
                        <div v-for="(item,index) in record.detail" :key="index">
                            @{{ item.mapu_material_name }} * @{{ item.mapu_number }}(@{{ item.mapu_unit }})</span>
                        </div>
                    </div>

                    <div slot="status" slot-scope="text, record">
                        <a-tag v-if="record.mapu_status == 1">申购中</a-tag>
                        <a-tag color="green" v-else-if="record.mapu_status == 2">已完成</a-tag>
                    </div>

                    <div slot="action" slot-scope="text, record">
                        <a style="margin-right: 8px" @click="onUpdate(record)">
                            修改
                        </a>

                        <a-popconfirm
                            title="是否确定删除商品?"
                            ok-text="确认"
                            cancel-text="取消"
                            @confirm="onDel(record)"
                        >
                            <a style="margin-right: 8px">
                                删除
                            </a>
                        </a-popconfirm>

                        <div>
                            <a-popconfirm
                                v-if="record.complete_auth"
                                title="是否确定完成申购?"
                                ok-text="确认"
                                cancel-text="取消"
                                @confirm="onComplete(record)"
                            >
                                <a style="margin-right: 8px">
                                    完成申购
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

            <a-modal :mask-closable="false" v-model="dialogFormVisible"
                     :title="dialogStatus"
                     width="800px" :footer="null">
                <purchase-add
                    style="max-height: 600px;overflow: auto"
                            ref="purchaseAdd"
                             :id="id"
                             @update="update"
                             @add="add"
                             @close="dialogFormVisible = false;"
                >
                </purchase-add>
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
                    category_id:'',
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
                        dataIndex: 'mapu_id',
                        width: 80
                    },
                    {
                        title: '详情',
                        scopedSlots: { customRender: 'detail' },
                        dataIndex: 'detail'
                    },
                    {
                        title: '状态',
                        scopedSlots: { customRender: 'status' },
                        dataIndex: 'mapu_status'
                    },
                    {
                        title: '备注',
                        dataIndex: 'mapu_remark'
                    },
                    {
                        title: '提交时间',
                        dataIndex: 'mapu_crt_time'
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
                "purchase-add":  httpVueLoader('/statics/components/material/purchaseAdd.vue'),
                "material-select":  httpVueLoader('/statics/components/material/materialSelect.vue'),
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
                        url: '/api/materialPurchase/getList',
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
                    this.id = row.mapu_id;
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
                onDel(row){
                    axios({
                        // 默认请求方式为get
                        method: 'post',
                        url: '/api/materialPurchase/delete',
                        // 传递参数
                        data: {
                            id:row.mapu_id
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
                onComplete(row){
                    axios({
                        // 默认请求方式为get
                        method: 'post',
                        url: '/api/materialPurchase/complete',
                        // 传递参数
                        data: {
                            id:row.mapu_id
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
                        this.$message.success('操作成功');
                        this.handleFilter();
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
