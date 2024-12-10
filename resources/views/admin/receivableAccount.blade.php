@extends('admin.layout')

@section('content')
    <div id="app">
        <a-spin :spinning="allLoading" tip="导入中..."/>
        <a-card>
            <div>
                <a-form layout="inline" >
                    <a-form-model-item>
                        <node-cascader @change="nodeChange"></node-cascader>
                    </a-form-model-item>

                    <a-form-item>
                        <a-input v-model="listQuery.address" placeholder="地址" style="width: 200px;" />
                    </a-form-item>

                    <a-form-item>
                        <span><a-input v-model="listQuery.remark" placeholder="备注" style="width: 200px;" /></span>
                        <span style="margin-left: 10px"><a-checkbox v-model="listQuery.remark_precise">备注精确匹配</a-checkbox></span>
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
                        <a-checkbox v-model="listQuery.has_received">实收大于0</a-checkbox>
                    </a-form-item>
                    <a-form-item>
                        <a-range-picker
                            :placeholder="['安装开始时间', '安装结束时间']"
                            @change="dateChange"
                            :default-value="[defaultDate,defaultDate]"></a-range-picker>
                    </a-form-item>
                    <a-form-item>
                        <a-range-picker
                            :placeholder="['收款开始时间', '收款结束时间']"
                            @change="flowDateChange"
                            :default-value="[defaultDate,defaultDate]"></a-range-picker>
                    </a-form-item>
                    <a-form-item>
                        <a-button icon="search" v-on:click="handleFilter">查询</a-button>
                    </a-form-item>

                    <a-form-item>
                        <a-button type="primary" icon="sync" v-on:click="syncFormVisible = true">同步订单数据</a-button>
                    </a-form-item>

                    <a-form-item>
                        <a-button type="primary" v-on:click="batchAddFlowFormVisible = true">批量添加回款</a-button>
                    </a-form-item>

                    <a-form-item>
                        <a-button icon="download" v-on:click="downloadFile('AccountsReceivableTemplate.xlsx')">下载导入模板</a-button>
                    </a-form-item>

                    <a-form-item v-if="$checkPermission('/api/receivableAccount/import')">
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

                <div style="color:red">
                    <span>记录数：@{{statistics.count}}</span> <span style="margin-left: 10px">应收：@{{ statistics.account_receivable }}</span> <span style="margin-left: 10px">实收：@{{ statistics.funds_received }}</span>
                </div>

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
                        <div  v-if="$checkPermission('/api/receivableAccount/update')">
                            <a style="margin-right: 8px" @click="onUpdate(record)">
                                修改信息
                            </a>
                        </div>
                        <div v-if="$checkPermission('/api/receivableAccount/addFlow')">
                            <a style="margin-right: 8px" @click="onAdd(record)">
                                添加收款流水
                            </a>
                        </div>
                        <div>
                            <a style="margin-right: 8px" @click="onFlow(record)">
                                流水明细 <span v-if="record.account_flow_count" style="color: red">(未审批:@{{record.account_flow_count}})</span>
                            </a>
                        </div>

                        <div>
                            <a-popconfirm
                                v-if="$checkPermission('/api/receivableAccount/delete')"
                                title="是否确定删除记录?"
                                ok-text="确认"
                                cancel-text="取消"
                                @confirm="onDel(record)"
                            >
                                <a style="margin-right: 8px">
                                    删除
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

            <a-modal :mask-closable="false" v-model="syncFormVisible"
                     title="同步订单"
                     width="800px" :footer="null">
                <a-form-model :model="syncForm" :label-col="labelCol" :wrapper-col="wrapperCol">
                    <a-form-model-item label="开始日期">
                        <a-date-picker format="YYYY-MM-DD" value-format="YYYY-MM-DD" v-model:value="syncForm.start_date"/>
                    </a-form-model-item>

                    <a-form-model-item label="结束日期">
                        <a-date-picker format="YYYY-MM-DD" value-format="YYYY-MM-DD" v-model:value="syncForm.end_date"/>
                    </a-form-model-item>
                </a-form-model>

                <a-form-model-item :wrapper-col="{ span: 14, offset: 4 }">
                    <a-button type="primary" :loading="syncLoading" @click="submitSync">
                        同步数据
                    </a-button>
                </a-form-model-item>
            </a-modal>


            <a-modal :mask-closable="false" v-model="batchAddFlowFormVisible"
                     title="批量添加回款"
                     width="800px" :footer="null">
                <batch-flow-add
                    ref="batchFlowAdd"
                    :list-query="listQuery"
                        @submit="afterBatchFlowAdd"
                        @close="batchAddFlowFormVisible = false;"
                >
                </batch-flow-add>
            </a-modal>


            <a-modal v-model="importVisible" width="800px" title="导入结果" @ok="afterImport">
                <div style="max-height: 600px;overflow-y: auto">
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
    <script src="{{asset('statics/js/xlsx.min.js')}}"></script>
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
                    flow_start_date:null,
                    flow_end_date:null,
                    remark:"",
                    remark_precise: true,
                    has_received:false
                },
                listSource: [],
                areaArr : [],
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
                        title: '监控中心',
                        align: 'center',
                        dataIndex: 'node_name'
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
                        width: 400
                    },
                    {
                        title: '备注',
                        align: 'center',
                        dataIndex: 'reac_remark'
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
                statistics:{
                    count:0,
                    account_receivable:0,
                    funds_received:0,
                },
                id:null,
                reacId:null,
                updateId:null,
                updateFormVisible:false,
                defaultDate:undefined,
                fileList:[],
                syncFormVisible:false,
                syncLoading:false,
                batchAddFlowFormVisible:false,
                batchAddFlowLoading:false,
                labelCol: { span: 4 },
                wrapperCol: { span: 14 },
                syncForm:{
                    start_date : moment().format("YYYY-MM-DD"),
                    end_date: moment().format("YYYY-MM-DD"),
                }
            },
            created () {
                this.listQuery.page_size = this.pagination.pageSize;
                this.handleFilter();
            },
            components: {
                "node-cascader":  httpVueLoader('/statics/components/node/nodeCascader.vue'),
                "flow-list":  httpVueLoader('/statics/components/receivableAccount/flowList.vue'),
                "batch-flow-add":  httpVueLoader('/statics/components/receivableAccount/batchFlowAdd.vue'),
                "flow-add":  httpVueLoader('/statics/components/receivableAccount/flowAdd.vue'),
                "update":  httpVueLoader('/statics/components/receivableAccount/update.vue'),
            },
            methods: {
                moment,
                downloadFile(fileName) {
                    const fileUrl = '/' + fileName; // 文件的URL地址
                    const link = document.createElement('a');
                    link.href = fileUrl;
                    link.setAttribute('download', fileName);
                    link.click();
                },
                fileHandleChange(file){
                    if(file.file.status && file.file.status === 'removed'){
                        return false;
                    }

                    const formData = new FormData();
                    formData.append('file', file.file);
                    this.allLoading = true;
                    let that = this;
                    let fileReader = new FileReader();
                    fileReader.onload = function(ev) {
                        try {
                            var data = ev.target.result,
                                workbook = XLSX.read(data, {
                                    type: 'binary'
                                }), // 以二进制流方式读取得到整份excel表格对象
                                persons = []; // 存储获取到的数据
                        } catch (e) {
                            console.log('文件类型不正确');
                            return;
                        }

                        // 表格的表格范围，可用于判断表头是否数量是否正确
                        var fromTo = '';
                        // 遍历每张表读取
                        for (var sheet in workbook.Sheets) {
                            if (workbook.Sheets.hasOwnProperty(sheet)) {
                                fromTo = workbook.Sheets[sheet]['!ref'];
                                console.log(fromTo);
                                persons = persons.concat(XLSX.utils.sheet_to_json(workbook.Sheets[sheet]));
                                break; // 如果只取第一张表，就取消注释这行
                            }
                        }

                        let jsonData = [];
                        for (let item of persons){
                            jsonData.push([
                                item['订单编号'],
                                item['地区'],
                                item['安装日期'],
                                item['客户类型'],
                                item['区域场所'],
                                item['单位'],
                                item['联系方式'],
                                item['地址'],
                                item['安装总数'],
                                item['赠送台数'],
                                item['备注（完成情况）'],
                                item['应收账款'],
                                item['付款金额'],
                                item['付款方案'],
                                item['收款路径'],
                                item['回款时间'],
                            ])
                        }
                        // console.log(jsonData);
                        axios({
                            // 默认请求方式为get
                            method: 'post',
                            url: '/api/receivableAccount/import',
                            // 传递参数
                            data: {
                                data:JSON.stringify(jsonData)
                            },
                            responseType: 'json',
                            headers:{
                                'Content-Type': 'multipart/form-data'
                            }
                        }).then(response => {
                            that.allLoading = false;
                            let res = response.data;
                            if(res.code !== 0){
                                that.$message.error(res.message);
                                return false;
                            }
                            that.importMsg = res.data.success_count + '条数据导入成功 ;' + res.data.error_arr.length + '条数据导入失败 ；';
                            that.importErrorArr = [];
                            if(res.data.error_arr.length > 0){
                                that.importErrorArr = res.data.error_arr;
                            }
                            that.importVisible = true;
                        })
                    };

                    fileReader.readAsBinaryString(file.file);
                    // axios({
                    //     // 默认请求方式为get
                    //     method: 'post',
                    //     url: '/api/receivableAccount/import',
                    //     // 传递参数
                    //     data: formData,
                    //     responseType: 'json',
                    //     headers:{
                    //         'Content-Type': 'multipart/form-data'
                    //     }
                    // }).then(response => {
                    //     this.allLoading = false;
                    //     let res = response.data;
                    //     if(res.code !== 0){
                    //         this.$message.error(res.message);
                    //         return false;
                    //     }
                    //     this.importMsg = res.data.success_count + '条数据导入成功 ;' + res.data.error_arr.length + '条数据导入失败 ；';
                    //     this.importErrorArr = [];
                    //     if(res.data.error_arr.length > 0){
                    //         this.importErrorArr = res.data.error_arr;
                    //     }
                    //     this.importVisible = true;
                    // })
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
                onDel(row){
                    this.listLoading = true
                    axios({
                        // 默认请求方式为get
                        method: 'post',
                        url: '/api/receivableAccount/delete',
                        // 传递参数
                        data: {
                            receivable_id:row.reac_id
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
                        this.$message.success('删除成功');
                        this.getPageList();
                    }).catch(error => {
                        this.$message.error('请求失败');
                    });
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
                        this.statistics = res.data.statistics
                        this.areaArr = res.data.area
                        this.listLoading = false
                    }).catch(error => {
                        this.$message.error('请求失败');
                    });
                },
                dateChange(value,arr){
                    this.listQuery.start_date = arr[0];
                    this.listQuery.end_date = arr[1];
                },
                flowDateChange(value,arr){
                    this.listQuery.flow_start_date = arr[0];
                    this.listQuery.flow_end_date = arr[1];
                },
                nodeChange(value){
                    this.listQuery.node_id = value;
                },
                afterImport(){
                    this.importVisible = false;
                    this.getPageList();
                },
                submitSync(){
                    this.syncLoading = true;
                    axios({
                        // 默认请求方式为get
                        method: 'post',
                        url: '/api/receivableAccount/syncOrder',
                        // 传递参数
                        data: this.syncForm,
                        responseType: 'json',
                        headers:{
                            'Content-Type': 'multipart/form-data'
                        }
                    }).then(response => {
                        this.syncLoading = false;
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
                        this.syncFormVisible = false;
                        this.importVisible = true;
                        this.$message.success('同步成功');
                    }).catch(error => {
                        this.$message.error('请求失败');
                    });
                },
                afterBatchFlowAdd(){
                    this.batchAddFlowFormVisible = false;
                    this.reacId = null;
                    this.$message.success('操作成功');
                    this.getPageList();
                },
            },

        })
    </script>
@endsection
