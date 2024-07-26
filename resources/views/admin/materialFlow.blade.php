@extends('admin.layout')

@section('content')
    <div id="app">
        <a-card>
            <div>
                <a-form layout="inline" >
                    <a-form-item label="物品">
                        <material-select @change="materialChange"></material-select>
                    </a-form-item>
                    <a-form-item label="类型">
                        <a-select v-model="listQuery.type" show-search placeholder="请选择" :max-tag-count="1"
                                  style="width: 200px;" allow-clear>
                            <a-select-option :value="1">
                                入库
                            </a-select-option>
                            <a-select-option :value="2">
                                出库
                            </a-select-option>
                        </a-select>
                    </a-form-item>
                    <a-form-item>
                        <a-button icon="search" @click="handleFilter">查询</a-button>
                    </a-form-item>
                    <a-form-item>
                        <a-button v-if="$checkPermission('/api/materialFlow/inComing')" @click="onInComing" type="primary">入库</a-button>
                    </a-form-item>

                    <a-form-item>
                        <a-button v-if="$checkPermission('/api/materialFlow/outComing')" @click="onOutComing" type="primary">出库</a-button>
                    </a-form-item>
                </a-form>
                <a-table :columns="columns" :data-source="listSource" :loading="listLoading" :row-key="(record, index) => { return index }"
                         :pagination="false" :scroll="{ x: 1500,y: 650 }">

                    <div slot="type" slot-scope="text, record">
                        <a-tag v-if="record.mafl_type == 1"  color="green">入库</a-tag>
                        <a-tag v-else color="red">出库</a-tag>
                    </div>

                    <div slot="purpose" slot-scope="text, record">
                        <a-tag v-if="record.mafl_type == 2 && record.mafl_purpose == 1"  color="#2db7f5">销售性质</a-tag>
                        <a-tag v-else-if="record.mafl_type == 2 && record.mafl_purpose == 2" color="#2db7f5">非销售性质</a-tag>
                        <span v-else>-</span>
                    </div>

                    <div slot="file_list" slot-scope="text, record">
                        <div v-for="item in record.file_list">
                            <a :href="item.file_path">
                                @{{item.file_name}}
                            </a>
                        </div>
                        <span></span>
                    </div>

                    <div slot="apply_user" slot-scope="text, record">
                        <span>@{{  record.mafl_apply_user }}/@{{  record.mafl_receive_user }}</span>
                    </div>

                    <div slot="number" slot-scope="text, record">
                        <span v-if="record.mafl_type == 1"  style="color:green">+ @{{ record.mafl_number }}</span>
                        <span v-else style="color:red">- @{{ record.mafl_number }}</span>
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

            <a-modal :mask-closable="false" v-model="inComingFormVisible"
                     title="入库"
                     width="800px" :footer="null">
                <material-in-coming ref="inComing"
                                    @submit="inComingSubmit"
                                    @close="inComingFormVisible = false;"
                >
                </material-in-coming>
            </a-modal>

            <a-modal :mask-closable="false" v-model="outComingFormVisible"
                     title="出库"
                     width="800px" :footer="null">
                <material-out-coming ref="outComing"
                                     @submit="outComingSubmit"
                                     @close="outComingFormVisible = false;"
                >
                </material-out-coming>
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
                    // {
                    //     title: 'Id',
                    //     dataIndex: 'mafl_id',
                    //     width: 80
                    // },
                    {
                        title: '物品名称',
                        fixed: 'left',
                        dataIndex: 'mafl_material_name',
                        width: 100
                    },
                    {
                        title: '出库仓库',
                        dataIndex: 'mafl_warehouse_name',
                    },
                    {
                        title: '类型',
                        scopedSlots: { customRender: 'type' },
                        dataIndex: 'mafl_type'
                    },
                    {
                        title: '数量',
                        scopedSlots: { customRender: 'number' },
                        dataIndex: 'mafl_number'
                    },
                    {
                        title: '出/入库时间',
                        dataIndex: 'mafl_datetime'
                    },
                    {
                        title: '用途',
                        scopedSlots: { customRender: 'purpose' },
                        dataIndex: 'mafl_purpose'
                    },
                    {
                        title: '申请人/领用人',
                        scopedSlots: { customRender: 'apply_user' },
                        dataIndex: 'mafl_apply_user'
                    },
                    {
                        title: '附件',
                        scopedSlots: { customRender: 'file_list' },
                        dataIndex: 'file_list'
                    },
                    {
                        title: '过期时间',
                        dataIndex: 'mafl_expire_date'
                    },
                    {
                        title: '备注',
                        dataIndex: 'mafl_remark'
                    }
                ],
                dialogFormVisible:false,
                inComingFormVisible:false,
                outComingFormVisible:false,
                id:null
            },
            created () {
                this.listQuery.page_size = this.pagination.pageSize;
                this.handleFilter()
            },
            components: {
                "material-add":  httpVueLoader('/statics/components/material/materialAdd.vue'),
                "category-select":  httpVueLoader('/statics/components/material/categorySelect.vue'),
                "material-select":  httpVueLoader('/statics/components/material/materialSelect.vue'),
                "material-in-coming":  httpVueLoader('/statics/components/material/materialInComing.vue'),
                "material-out-coming":  httpVueLoader('/statics/components/material/materialOutComing.vue')
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
                        url: '/api/materialFlow/getList',
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
                    this.dialogStatus = '添加';
                    this.dialogFormVisible = true;
                },
                onUpdate(row){
                    this.id = row.mate_id
                    this.dialogStatus = '更新';
                    this.dialogFormVisible = true;
                },
                getImage(url){
                    if(url.substr(0,4).toLowerCase() == "http"){
                        return url;
                    }

                    return 'http://' + window.location.hostname + url;
                },
                onDel(row){
                    axios({
                        // 默认请求方式为get
                        method: 'post',
                        url: '/api/material/delete',
                        // 传递参数
                        data: {
                            id:row.mate_id
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
                },
                onInComing(){
                    // this.incomingId = row.id;
                    this.inComingFormVisible = true;
                },
                onOutComing(){
                    this.outComingFormVisible = true;
                },
                inComingSubmit(){
                    this.$message.success('入库成功');
                    this.inComingFormVisible = false;
                    this.handleFilter();
                },
                outComingSubmit(){
                    this.$message.success('出库成功');
                    this.outComingFormVisible = false;
                    this.handleFilter();
                },
                categoryChange(value){
                    this.listQuery.category_id = value;
                },
                materialChange(value){
                    this.listQuery.material_id = value;
                }
            },

        })


    </script>
@endsection

