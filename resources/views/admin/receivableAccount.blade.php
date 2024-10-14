@extends('admin.layout')

@section('content')
    <div id="app">
        <a-spin :spinning="allLoading" tip="导入中..."/>
        <a-card>
            <div>
                <a-form layout="inline" >
                    <a-form-item>
                        <a-select v-model="listQuery.area" show-search placeholder="区域" :max-tag-count="1"
                                  style="width: 200px;" allow-clear>
                            <a-select-option :value="1">
                                是
                            </a-select-option>
                            <a-select-option :value="2">
                                否
                            </a-select-option>
                        </a-select>
                    </a-form-item>

                    <a-form-item>
                        <a-input v-model="listQuery.address" placeholder="地址" style="width: 200px;" />
                    </a-form-item>
                    <a-form-item>
                        <a-input v-model="listQuery.user_keyword" placeholder="用户名/用户手机号" style="width: 200px;" />
                    </a-form-item>
                    <a-form-item>
                        <a-select v-model="listQuery.is_debt" show-search placeholder="是否欠款" :max-tag-count="1"
                                  style="width: 200px;" allow-clear>
                            <a-select-option :value="1">
                                是
                            </a-select-option>
                            <a-select-option :value="2">
                                否
                            </a-select-option>
                        </a-select>
                    </a-form-item>
                    <a-form-item>
                        <a-range-picker
                            :placeholder="['开始时间', '结束时间']"
                            @change="dateChange"
                            :default-value="[defaultDate,defaultDate]"></a-range-picker>
                        </a-form-item>
                    <a-form-item>
                        <a-button icon="search" v-on:click="handleFilter">查询</a-button>
                    </a-form-item>
                    <a-form-item>
                        <a-upload
                            :file-list="fileList"
                            :multiple="false"
                            :before-upload="fileBeforeUpload"
                            @change="fileHandleChange"
                        >

                            <a-button>
                                <a-icon type="upload" ></a-icon>
                                导入数据
                            </a-button>
                        </a-upload>
                    </a-form-item>
                </a-form>

                <a-table :columns="columns" :data-source="listSource" :loading="listLoading" :row-key="(record, index) => { return index }"
                         :pagination="false"  :scroll="{ x: 1800,y: 650}">

                    <div slot="address" slot-scope="text, record">
                        <div v-for="item in record.address">
                            @{{ item.reac_address }}
                        </div>
                    </div>

                    <div slot="reac_user_name" slot-scope="text, record">
                        <div>@{{ record.reac_user_name }}</div>
                        <div>@{{ record.reac_user_mobile }}</div>
                    </div>

                    <div slot="reac_user_type" slot-scope="text, record">
                        <span v-if="record.reac_user_type == 1">2B</span>
                        <span v-else>2C</span>
                    </div>

                    <div slot="reac_pay_cycle" slot-scope="text, record">
                        <span v-if="record.reac_pay_cycle == 1">一次性付款</span>
                        <span v-else-if="record.reac_pay_cycle > 1">@{{  record.reac_pay_cycle }}期</span>
                        <span v-else>未知</span>

                        <span v-if="record.is_debt == 1" style="color: red">(欠款)</span>
                    </div>

                    <div slot="action" slot-scope="text, record">
                        <div>
                            <a style="margin-right: 8px" @click="onUpdate(record)">
                                修改信息
                            </a>
                        </div>
                        <div>
                            <a style="margin-right: 8px" @click="onAdd(record)">
                                添加收款流水
                            </a>
                        </div>
                        <div>
                            <a style="margin-right: 8px" @click="onFlow(record)">
                                流水明细 <span v-if="record.account_flow_count" style="color: red">(未审批:@{{record.account_flow_count}})</span>
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
                     :title="status"
                     width="800px" :footer="null">
                <flow-add ref="accountFlowAdd"
                                  :id="id"
                                  @add="afterAdd"
                                  @close="dialogFormVisible = false;"
                >
                </flow-add>
            </a-modal>

            <a-modal :mask-closable="false" v-model="flowFormVisible"
                     title="流水明细"
                     width="1000px" :footer="null">
                <flow-list ref="flowList"
                                  :id="reacId"
                                  @approve="afterApprove"
                                  @close="flowFormVisible = false;"
                >
                </flow-list>
            </a-modal>

            <a-modal :mask-closable="false" v-model="updateFormVisible"
                     title="编辑"
                     width="800px" :footer="null">
                <update ref="update"
                                   :id="updateId"
                                   @update="afterUpdate"
                                   @close="updateFormVisible = false;"
                >
                </update>
            </a-modal>


            <a-modal v-model="importVisible" width="800px" title="导入结果" @ok="afterImport">
                <div style="height: 600px;overflow: scroll">
                    <div>
                        @{{ importMsg }}
                    </div>

                    <div v-for="item in importErrorArr">
                        @{{ item }}
                    </div>
                </div>
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
                    area:undefined,
                    is_debt:undefined,
                    keyword: "",
                    address:'',
                    user_keyword:'',
                    node_id: '',
                    start_date:null,
                    end_date:null,
                },
                listSource: [],
                listLoading:false,
                allLoading:false,
                status:'新增回款流水',
                pagination: {
                    pageSize: 10,
                    total: 0,
                    current: 1,
                    onChange: this.paginationChange,
                    onShowSizeChange: this.paginationChange,
                },
                columns:[
                    {
                        title: '客户信息',
                        scopedSlots: { customRender: 'reac_user_name' },
                        fixed: 'left',
                        align: 'center',
                        dataIndex: 'reac_user_name',
                        width: 150
                    },
                    {
                        title: '安装日期',
                        fixed: 'left',
                        align: 'center',
                        dataIndex: 'reac_installation_date',
                        width: 150
                    },
                    {
                        title: '区域',
                        align: 'center',
                        dataIndex: 'reac_area'
                    },
                    {
                        title: '合约类型',
                        scopedSlots: { customRender: 'reac_user_type' },
                        align: 'center',
                        dataIndex: 'reac_user_type'
                    },
                    {
                        title: '安装地址',
                        scopedSlots: { customRender: 'address' },
                        dataIndex: 'address',
                        align: 'center',
                        width: 300
                    },
                    {
                        title: '付款周期',
                        scopedSlots: { customRender: 'reac_pay_cycle' },
                        align: 'center',
                        dataIndex: 'reac_pay_cycle'
                    },
                    {
                        title: '安装数量',
                        align: 'center',
                        dataIndex: 'reac_installation_count'
                    },
                    {
                        title: '赠送数量',
                        align: 'center',
                        dataIndex: 'reac_given_count'
                    },
                    {
                        title: '应收款',
                        align: 'center',
                        dataIndex: 'reac_account_receivable'
                    },
                    {
                        title: '实收款',
                        align: 'center',
                        dataIndex: 'reac_funds_received'
                    },
                    {
                        title: '操作',
                        fixed: 'right',
                        align: 'center',
                        scopedSlots: { customRender: 'action' },
                    }
                ],
                dialogFormVisible:false,
                flowFormVisible:false,
                importVisible:false,
                importMsg:'',
                importErrorArr:[],
                id:null,
                reacId:null,
                updateId:null,
                updateFormVisible:false,
                defaultDate:undefined,
                fileList:[],
            },
            created () {
                this.listQuery.page_size = this.pagination.pageSize;
                this.handleFilter();
            },
            components: {
                "flow-list":  httpVueLoader('/statics/components/receivableAccount/flowList.vue'),
                "flow-add":  httpVueLoader('/statics/components/receivableAccount/flowAdd.vue'),
                "update":  httpVueLoader('/statics/components/receivableAccount/update.vue'),
            },
            methods: {
                moment,
                fileHandleChange(file){
                    if(file.file.status && file.file.status === 'removed'){
                        return false;
                    }

                    const formData = new FormData();
                    formData.append('file', file.file);
                    this.allLoading = true;
                    axios({
                        // 默认请求方式为get
                        method: 'post',
                        url: '/api/receivableAccount/import',
                        // 传递参数
                        data: formData,
                        responseType: 'json',
                        headers:{
                            'Content-Type': 'multipart/form-data'
                        }
                    }).then(response => {
                        this.allLoading = false;
                        let res = response.data;
                        if(res.code !== 0){
                            this.$message.error(res.message);
                            return false;
                        }
                        this.importMsg = res.data.success_count + '条数据导入成功 ;' + res.data.error_arr.length + '条数据导入失败 ；';
                        this.importErrorArr = [];
                        if(res.data.error_arr.length > 0){
                            this.importErrorArr = res.data.error_arr;
                        }
                        this.importVisible = true;
                    })
                },
                fileBeforeUpload(file) {
                    // this.fileList = [...this.fileList, {
                    //     url:'',
                    //     uid:'-1',
                    //     name: 'file',
                    //     status: 'done',
                    // }];
                    return false;
                },
                paginationChange (current, pageSize) {
                    this.listQuery.page = current;
                    this.pagination.current = current;
                    this.listQuery.page_size = pageSize;
                    this.getPageList()
                },
                onAdd(row){
                    this.id = row.reac_id;
                    this.dialogFormVisible = true;
                },
                onFlow(row){
                    this.reacId = row.reac_id;
                    this.flowFormVisible = true;
                },
                onUpdate(row){
                    this.updateId = row.reac_id;
                    this.updateFormVisible = true;
                },
                afterAdd(){
                    this.id = null;
                    this.reacId = null;
                    this.dialogFormVisible = false;
                    this.getPageList();
                    // this.$refs['flowList'].getPageList(this.id);
                },
                afterApprove(){
                    // this.orderId = null;
                    this.getPageList();
                },
                afterUpdate(){
                    this.updateFormVisible = false;
                    this.$message.success('更新成功');
                    this.updateOrderId = null;
                    this.getPageList();
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
                        url: '/api/receivableAccount/getList',
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
                },
                nodeChange(value){
                    this.listQuery.node_id = value;
                },
                afterImport(){
                    this.importVisible = false;
                    this.getPageList();
                }
            },

        })
    </script>
@endsection