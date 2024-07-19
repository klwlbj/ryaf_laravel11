@extends('admin.layout')

@section('content')
    <div id="app">
        <a-card>
            <div>
                <a-form layout="inline" >
                    <a-form-item>
                        <a-input v-model="listQuery.keyword" placeholder="物品名称" style="width: 200px;" />
                    </a-form-item>
                    <a-form-item>
                        <manufacturer-select @change="manufacturerChange"></manufacturer-select>
                    </a-form-item>
                    <a-form-item>
                        <category-select @change="categoryChange"></category-select>
                    </a-form-item>
                    <a-form-item>
                        <a-button icon="search" @click="handleFilter">查询</a-button>
                    </a-form-item>
                    <a-form-item>
                        <a-button icon="search" @click="exportList">导出</a-button>
                    </a-form-item>
                    <a-form-item>
                        <a-button @click="onCreate" type="primary" icon="edit">添加物品</a-button>
                    </a-form-item>

                </a-form>
                <a-table :columns="columns" :data-source="listSource" :loading="listLoading" :row-key="(record, index) => { return index }"
                         :pagination="false">

                    <div slot="is_deliver" slot-scope="text, record">
                        <a-tag v-if="record.mate_is_deliver == 0"  color="red">否</a-tag>
                        <a-tag v-else color="green">是</a-tag>
                    </div>

                    <div slot="mate_specification_name" slot-scope="text, record">
                        <a-tag v-for="(item,i) in record.mate_specification_name" :key="i" color="green">@{{ item }}</a-tag>
                    </div>

                    <div slot="number" slot-scope="text, record">
                        <div style="cursor: pointer" @click="stockDetail(record)">
                            <span v-if="record.mate_warning>record.mate_number" style="color: red;font-weight: bold;">@{{ record.mate_number }}</span>
                            <span v-else>@{{ record.mate_number }}</span>
                            /
                            <span v-if="record.expire_count > 0" style="color: red">@{{ record.expire_count }}</span>
                            <span v-else>@{{ record.expire_count }}</span>
                        </div>

                    </div>

                    <div slot="status" slot-scope="text, record">
                        <a-tag v-if="record.mate_status == 0"  color="red">禁用</a-tag>
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
                            @confirm="onDel(record)"
                        >
                            <a style="margin-right: 8px">
                                删除
                            </a>
                        </a-popconfirm>
                        <div>
                            <a style="margin-right: 8px" @click="onInComing(record)">
                                入库
                            </a>

                            <a style="margin-right: 8px" @click="onOutComing(record)">
                                出库
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

            <a-modal :mask-closable="false" v-model="dialogFormVisible"
                     :title="dialogStatus"
                     width="800px" :footer="null">
                <material-add
                    style="height: 600px;overflow: auto"
                    ref="materialAdd"
                    :id="id"
                    @update="update"
                    @add="add"
                    @close="dialogFormVisible = false;"
                >
                </material-add>
            </a-modal>

            <a-modal :mask-closable="false" v-model="inComingFormVisible"
                     title="入库"
                     width="800px" :footer="null">
                <material-in-coming ref="inComing"
                                    :default-material-id="inComingMaterialId"
                                    @submit="inComingSubmit"
                                    @close="inComingFormVisible = false;"
                >
                </material-in-coming>
            </a-modal>

            <a-modal :mask-closable="false" v-model="outComingFormVisible"
                     title="出库"
                     width="800px" :footer="null">
                <material-out-coming ref="outComing"
                                     :default-material-id="outComingMaterialId"
                                     @submit="outComingSubmit"
                                     @close="outComingFormVisible = false;"
                >
                </material-out-coming>
            </a-modal>

            <a-modal :mask-closable="false" v-model="detailFormVisible"
                     :title="detailStatus"
                     width="800px" :footer="null">
                <material-detail-list
                    style="height: 600px;overflow: auto"
                    ref="materialDetailList"
                    :id="detailId"
                    @close="dialogFormVisible = false;"
                >
                </material-detail-list>
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
                    manufacturer_id:'',
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
                    {
                        title: 'Id',
                        dataIndex: 'mate_id',
                        width: 80
                    },
                    {
                        title: '名称',
                        dataIndex: 'mate_name',
                        width: 100
                    },
                    {
                        title: '是否出库',
                        scopedSlots: { customRender: 'is_deliver' },
                        dataIndex: 'mate_is_deliver'
                    },
                    {
                        title: '厂家',
                        dataIndex: 'mate_manufacturer_name',
                        width: 100
                    },
                    {
                        title: '类别',
                        dataIndex: 'mate_category_name',
                        width: 100
                    },
                    {
                        title: '规格',
                        scopedSlots: { customRender: 'mate_specification_name' },
                        dataIndex: 'mate_specification_name',
                    },
                    {
                        title: '单位',
                        dataIndex: 'mate_unit'
                    },
                    {
                        title: '库存/过期',
                        scopedSlots: { customRender: 'number' },
                        dataIndex: 'mate_number'
                    },
                    {
                        title: '预警值',
                        dataIndex: 'mate_warning'
                    },
                    {
                        title: '排序',
                        dataIndex: 'mate_sort'
                    },
                    {
                        title: '状态',
                        scopedSlots: { customRender: 'status' },
                        dataIndex: 'mate_status'
                    },
                    {
                        title: '更新时间',
                        dataIndex: 'mate_upd_time'
                    },
                    {
                        title: '操作',
                        scopedSlots: { customRender: 'action' },
                    }
                ],
                dialogFormVisible:false,
                inComingFormVisible:false,
                outComingFormVisible:false,
                detailFormVisible:false,
                id:null,
                inComingMaterialId:null,
                outComingMaterialId:null,
                detailId:null,
                detailStatus:'',
            },
            created () {
                this.listQuery.page_size = this.pagination.pageSize;
                this.handleFilter()
            },
            components: {
                "material-add":  httpVueLoader('/statics/components/material/materialAdd.vue'),
                "material-detail-list":  httpVueLoader('/statics/components/material/materialDetailList.vue'),
                "manufacturer-select":  httpVueLoader('/statics/components/material/manufacturerSelect.vue'),
                "category-select":  httpVueLoader('/statics/components/material/categorySelect.vue'),
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
                exportList(){
                    let formData = JSON.parse(JSON.stringify(this.listQuery));
                    formData.export = 1;
                    axios({
                        // 默认请求方式为get
                        method: 'post',
                        url: '/api/material/getList',
                        // 传递参数
                        data: formData,
                        responseType: 'json',
                        headers:{
                            'Content-Type': 'multipart/form-data'
                        }
                    }).then(response => {
                        let res = response.data;
                        window.location.href = res.data.url
                    }).catch(error => {
                        this.$message.error('请求失败');
                    });
                },
                // 获取列表
                getPageList () {
                    this.listLoading = true
                    axios({
                        // 默认请求方式为get
                        method: 'post',
                        url: '/api/material/getList',
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
                stockDetail(row){
                    this.detailId = row.mate_id;
                    this.detailStatus = row.mate_name;
                    this.detailFormVisible = true;
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
                onInComing(row){
                    this.inComingMaterialId = row.mate_id
                    // this.incomingId = row.id;
                    this.inComingFormVisible = true;
                },
                onOutComing(row){
                    this.outComingMaterialId = row.mate_id
                    this.outComingFormVisible = true;
                },
                inComingSubmit(){
                    this.inComingMaterialId = null;
                    this.$message.success('入库成功');
                    this.inComingFormVisible = false;
                    this.handleFilter();
                },
                outComingSubmit(){
                    this.outComingMaterialId = null;
                    this.$message.success('出库成功');
                    this.outComingFormVisible = false;
                    this.handleFilter();
                },
                categoryChange(value){
                    this.listQuery.category_id = value;
                },
                manufacturerChange(value){
                    this.listQuery.manufacturer_id = value;
                }
            },

        })


    </script>
@endsection
