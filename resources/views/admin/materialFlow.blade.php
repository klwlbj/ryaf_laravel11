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
                                  style="width: 200px;" allow-clear @change="handleFilter">
                            <a-select-option :value="1">
                                入库
                            </a-select-option>
                            <a-select-option :value="2">
                                出库
                            </a-select-option>
                        </a-select>
                    </a-form-item>
                    <a-form-item label="出入库日期">
                        <a-range-picker
                            :placeholder="['开始时间', '结束时间']"
                            @change="dateChange"
                            :default-value="[defaultDate,defaultDate]"></a-range-picker>
                    </a-form-item>

                    <a-form-item label="确认状态">
                        <a-select v-model="listQuery.status" show-search placeholder="请选择" :max-tag-count="1"
                                  style="width: 200px;" allow-clear  @change="handleFilter">
                            <a-select-option :value="1">
                                未确认
                            </a-select-option>
                            <a-select-option :value="2">
                                已确认
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
                         :pagination="false" :scroll="{ x: 1800,y: 650 }">

                    <div slot="mafl_specification_name" slot-scope="text, record">
                        <a-tag v-for="(item,i) in record.mafl_specification_name" :key="i">@{{ item }}</a-tag>
                    </div>

                    <div slot="type" slot-scope="text, record">
                        <a-tag v-if="record.mafl_type == 1"  color="green">入库</a-tag>
                        <a-tag v-else color="red">出库</a-tag>
                    </div>

                    <div slot="status" slot-scope="text, record">
                        <a-tag v-if="record.mafl_status == 1"  color="red">待确认</a-tag>
                        <a-tag v-else color="green">已确认</a-tag>
                    </div>

                    <div slot="price" slot-scope="text, record">
                        <div v-if="record.mafl_type == 1">
                            <div>单价（含税）：<span style="color:red">@{{record.mafl_price_tax}}</span></div>
                            <div>税率：<span style="color:red">@{{record.mafl_tax}}%</span></div>
                            <div>单价（不含税）：<span style="color:red">@{{record.mafl_price}}</span></div>
                            <div>发票类型：<span style="color:red">@{{record.mafl_invoice_type_msg}}</span></div>
                        </div>
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

                    <div slot="action" slot-scope="text, record">
                        <div v-if="record.mafl_status == 1 && record.mafl_verify_user_id == admin.admin_id">
                            <a-popconfirm
                                title="是否确认无误?"
                                ok-text="确认"
                                cancel-text="取消"
                                @confirm="onVerify(record)"
                            >
                                <a style="margin-right: 8px">
                                    确认无误
                                </a>
                            </a-popconfirm>
                        </div>
                        <div v-if="$checkPermission('/api/materialFlow/inComingUpdate') && record.mafl_type==1">
                            <a style="margin-right: 8px" @click="onIncomingUpdate(record)">
                                修改
                            </a>
                        </div>

                        <div v-if="$checkPermission('/api/materialFlow/setPrice') && record.mafl_type==1">
                            <a style="margin-right: 8px" @click="onPrice(record)">
                                填写单价
                            </a>
                        </div>

                        <div v-if="$checkPermission('/api/materialFlow/cancel') && record.mafl_operator_id == admin.admin_id && record.is_last && mafl_status == 1">
                            <a-popconfirm
                                title="是否确认撤销?"
                                ok-text="确认"
                                cancel-text="取消"
                                @confirm="cancel(record)"
                            >
                                <a style="margin-right: 8px">
                                    撤销
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

            <a-modal :mask-closable="false" v-model="inComingUpdateVisible"
                     title="入库信息修改"
                     width="800px" :footer="null">
                <in-coming-update ref="inComingUpdate"
                                  :id="flowId"
                                     @submit="inComingUpdateSubmit"
                                     @close="inComingUpdateVisible = false;"
                >
                </in-coming-update>
            </a-modal>

            <a-modal :mask-closable="false" v-model="setPriceFormVisible"
                     title="设置单价"
                     width="800px" :footer="null">
                <set-flow-price ref="outComing"
                                :id="id"
                                @submit="setPriceSubmit"
                                @close="setPriceFormVisible = false;"
                >
                </set-flow-price>
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
                    status:undefined,
                    start_date:null,
                    end_date:null,
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
                    // {
                    //     title: 'Id',
                    //     dataIndex: 'mafl_id',
                    //     width: 80
                    // },
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
                    // {
                    //     title: '出库仓库',
                    //     dataIndex: 'mafl_warehouse_name',
                    // },
                    {
                        title: '类型',
                        scopedSlots: { customRender: 'type' },
                        dataIndex: 'mafl_type',
                        align: 'center'
                    },
                    {
                        title: '数量',
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
                        title: '单价',
                        scopedSlots: { customRender: 'price' },
                        dataIndex: 'mafl_price',
                        width: 200,
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
                        title: '附件',
                        scopedSlots: { customRender: 'file_list' },
                        dataIndex: 'file_list',
                        align: 'center'
                    },
                    {
                        title: '过期时间',
                        dataIndex: 'mafl_expire_date',
                        align: 'center'
                    },

                    {
                        title: '备注',
                        dataIndex: 'mafl_remark',
                        align: 'center'
                    },
                    {
                        title: '最终确认人',
                        dataIndex: 'mafl_verify_user',
                        align: 'center'
                    },
                    {
                        title: '状态',
                        scopedSlots: { customRender: 'status' },
                        dataIndex: 'mafl_status',
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
                inComingFormVisible:false,
                outComingFormVisible:false,
                setPriceFormVisible:false,
                inComingUpdateVisible:false,
                id:null,
                flowId:null,
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
                "material-add":  httpVueLoader('/statics/components/material/materialAdd.vue'),
                "category-select":  httpVueLoader('/statics/components/material/categorySelect.vue'),
                "material-select":  httpVueLoader('/statics/components/material/materialSelect.vue'),
                "material-in-coming":  httpVueLoader('/statics/components/material/materialInComing.vue'),
                "material-out-coming":  httpVueLoader('/statics/components/material/materialOutComing.vue'),
                "set-flow-price":  httpVueLoader('/statics/components/material/setFlowPrice.vue'),
                "in-coming-update":  httpVueLoader('/statics/components/material/inComingUpdate.vue'),
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
                getImage(url){
                    if(url.substr(0,4).toLowerCase() == "http"){
                        return url;
                    }

                    return 'http://' + window.location.hostname + url;
                },
                onInComing(){
                    // this.incomingId = row.id;
                    this.inComingFormVisible = true;
                },
                onIncomingUpdate(row){
                    this.flowId = row.mafl_id;
                    this.inComingUpdateVisible = true;
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
                    this.handleFilter();
                },
                materialChange(value){
                    this.listQuery.material_id = value;
                    this.handleFilter();
                },
                onVerify(row){
                    axios({
                        // 默认请求方式为get
                        method: 'post',
                        url: '/api/materialFlow/verify',
                        // 传递参数
                        data: {id:row.mafl_id},
                        responseType: 'json',
                        headers:{
                            'Content-Type': 'multipart/form-data'
                        }
                    }).then(response => {
                        let res = response.data;
                        if(res.code != 0){
                            this.$message.error(res.message);
                            return false;
                        }

                        this.getPageList();
                    }).catch(error => {
                        this.$message.error('请求失败');
                    });
                },
                cancel(row){
                    axios({
                        // 默认请求方式为get
                        method: 'post',
                        url: '/api/materialFlow/cancel',
                        // 传递参数
                        data: {id:row.mafl_id},
                        responseType: 'json',
                        headers:{
                            'Content-Type': 'multipart/form-data'
                        }
                    }).then(response => {
                        let res = response.data;
                        if(res.code != 0){
                            this.$message.error(res.message);
                            return false;
                        }

                        this.getPageList();
                    }).catch(error => {
                        this.$message.error('请求失败');
                    });
                },
                onPrice(row){
                    this.id = row.mafl_id;
                    this.setPriceFormVisible = true;
                },
                setPriceSubmit(){
                    this.id = null;
                    this.setPriceFormVisible = false;
                    this.$message.success('修改成功');
                    this.getPageList();
                },
                inComingUpdateSubmit(){
                    this.flowId = null;
                    this.inComingUpdateVisible = false;
                    this.$message.success('修改成功');
                    this.getPageList();
                },
                dateChange(value,arr){
                    this.listQuery.start_date = arr[0];
                    this.listQuery.end_date = arr[1];
                    this.handleFilter();
                },
            },

        })


    </script>
@endsection

