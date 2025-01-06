@extends('admin.layout')
@section('content')
    <div id="app">
        <a-card>
            <div>
                <a-form layout="inline" >
                    <a-form-item>
                        <a-range-picker
                                :placeholder="['开始时间', '结束时间']"
                                @change="dateChange"
                                :default-value="[defaultDate,defaultDate]"></a-range-picker>
                    </a-form-item>
                    <a-form-item>
                        <a-input v-model="listQuery.name" placeholder="用户/单位名称" style="width: 120px;" />
                    </a-form-item>

                    <a-form-item>
                        <a-input v-model="listQuery.phone" placeholder="手机号" style="width: 120px;" />
                    </a-form-item>
                    <a-form-item>
                        <a-input v-model="listQuery.address" placeholder="地址" style="width: 120px;" />
                    </a-form-item>

                    <a-form-item>
                        <a-button icon="search" v-on:click="handleFilter">查询</a-button>
                    </a-form-item>

                    <a-form-item>
                        <a-button icon="search" @click="exportList">导出</a-button>
                    </a-form-item>
                </a-form>

                <a-table :columns="columns" :data-source="listSource" :loading="listLoading" :row-key="(record, index) => { return index }"
                         :pagination="false" :scroll="{ x: 1000,y: 650}">

                    <div slot="action" slot-scope="text, record">
                        <a v-if="$checkPermission('/api/preInstallation/update')" style="margin-right: 8px" @click="onUpdate(record)">
                            修改
                        </a>

                        <a-popconfirm
                                title="是否确定删除?"
                                ok-text="确认"
                                cancel-text="取消"
                                v-on:confirm="onDel(record)"
                        >
                            <a v-if="$checkPermission('/api/preInstallation/delete')" style="margin-right: 8px">
                                删除
                            </a>
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
                <manufacturer-add ref="manufacturerAdd"
                                  :id="id"
                                  @update="update"
                                  @add="add"
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
                areaList:[],
                listQuery: {
                    start_date:null,
                    end_date:null,
                    address: '',
                    phone: '',
                    name: '',
                },
                listSource: [],
                otherTotal: [],
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
                        dataIndex: 'id',
                        width: 10
                    },
                    {
                        title: '姓名',
                        dataIndex: 'name',
                        width: 20
                    },
                    {
                        title: '电话',
                        dataIndex: 'phone',
                        width: 30
                    },
                    {
                        title: '地址',
                        dataIndex: 'address',
                        width: 40
                    },
                    {
                        title: '手写地址',
                        dataIndex: 'handwritten_address',
                        width: 40
                    },
                    {
                        title: "时间",
                        'dataIndex': 'registration_date',
                        width: 20,
                    },
                    {
                        title: '数量',
                        dataIndex: 'installation_count',
                        width: 10
                    },
                    {
                        title: '操作',
                        width: 20,
                        scopedSlots: { customRender: 'action' },
                    }
                ],
                dialogFormVisible:false,
                defaultDate:undefined,
                stageInfoFormVisible:false,
                arrearsInfoFormVisible:false,
                id:null
            },
            created () {
                this.listQuery.page_size = this.pagination.pageSize;
                this.handleFilter()
            },
            components: {
                "manufacturer-add":  httpVueLoader('/statics/components/preInstallation/preInstallationAdd.vue')
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
                exportList(){
                    let formData = JSON.parse(JSON.stringify(this.listQuery));
                    formData.export = 1;
                    axios({
                        // 默认请求方式为get
                        method: 'post',
                        url: '/api/preInstallation/getList',
                        // 传递参数
                        data: formData,
                        responseType: 'json',
                        headers:{
                            'Content-Type': 'multipart/form-data'
                        }
                    }).then(response => {
                        formData.export = 0;
                        let res = response.data;
                        if(res.code !== 0){
                            this.$message.error(res.message);
                            return false;
                        }else{
                            window.location.href = res.data.url
                        }
                    }).catch(error => {
                        this.$message.error(error);
                    });
                },
                // 获取列表
                getPageList () {
                    this.listLoading = true
                    axios({
                        // 默认请求方式为get
                        method: 'post',
                        url: '/api/preInstallation/getList',
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
                handleChange(value){
                    this.$emit('change',value);
                },
                dateChange(value,arr){
                    this.listQuery.start_date = arr[0];
                    this.listQuery.end_date = arr[1];
                },
                onUpdate(row){
                    this.id = row.id
                    this.status = '更新';
                    this.dialogFormVisible = true;
                },
                onDel(row){
                    axios({
                        // 默认请求方式为get
                        method: 'post',
                        url: '/api/preInstallation/delete',
                        // 传递参数
                        data: {
                            id:row.id
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
                onCreate(){
                    this.status = '添加';
                    this.dialogFormVisible = true;
                },
                stageInfoQuery(){
                    this.id = null;
                    this.stageInfoFormVisible = false;
                    this.handleFilter();
                },
                arrearsInfoQuery(){
                    this.id = null;
                    this.stageInfoFormVisible = false;
                    this.handleFilter();
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
