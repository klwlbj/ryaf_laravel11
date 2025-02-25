@extends('admin.layout')

@section('content')
    <div id="app">
        <a-spin :spinning="allLoading" tip="生成任务中..."/>
        <a-card>
            <div>
                <a-form layout="inline" >
                    <a-form-item label="导入时间">
                        <a-range-picker
                            :placeholder="['开始时间', '结束时间']"
                            @change="dateChange"
                            :default-value="[defaultDate,defaultDate]"></a-range-picker>
                    </a-form-item>

                    <a-form-item>
                        <a-checkbox v-model="listQuery.import_fail">导入失败</a-checkbox></span>
                    </a-form-item>

                    <a-form-item>
                        <a-button icon="search" v-on:click="handleFilter">查询</a-button>
                    </a-form-item>

                    <a-form-item>
                        <a-button icon="upload" v-on:click="importFormVisible=true">导入设备</a-button>
                    </a-form-item>
                </a-form>

                <a-table :columns="columns" :data-source="listSource" :loading="listLoading" :row-key="(record, index) => { return index }"
                         :pagination="false" :scroll="{ y: 650 }">

                    <div slot="database_status" slot-scope="text, record">
                        <a-tag v-if="record.deim_database_status == 0"  color="red">未导入</a-tag>
                        <a-tag v-else color="green">已导入</a-tag>
                    </div>

                    <div slot="aep_status" slot-scope="text, record">
                        <a-tag v-if="record.deim_aep_status == 0"  color="red">未导入</a-tag>
                        <a-tag v-else color="green">已导入</a-tag>
                    </div>

                    <div slot="onenet_status" slot-scope="text, record">
                        <a-tag v-if="record.deim_onenet_status == 0"  color="red">未导入</a-tag>
                        <a-tag v-else color="green">已导入</a-tag>
                    </div>

                    <div slot="status" slot-scope="text, record">
                        <a-tag v-if="record.deim_status == 0"  color="red">未执行</a-tag>
                        <a-tag v-else-if="record.deim_status == 1"  color="#f50">执行中</a-tag>
                        <a-tag v-else color="green">已执行</a-tag>
                    </div>

                    <div slot="action" slot-scope="text, record">
                        <div>
                            <a-popconfirm
                                title="是否确定重新执行?"
                                ok-text="确认"
                                cancel-text="取消"
                                @confirm="onRerun(record)"
                            >
                                <a style="margin-right: 8px">
                                    重新执行
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

            <a-modal :mask-closable="false" v-model="importFormVisible"
                     title="导入数据"
                     width="800px" :footer="null">
                <a-form-model :model="formData" :label-col="labelCol" :wrapper-col="wrapperCol">
                    <a-form-model-item label="设备型号">
                        <a-select v-model="formData.model_name" show-search placeholder="型号" :max-tag-count="1"
                                  style="width: 200px;" allow-clear>
                            <a-select-option v-for="item in modelNameList" :value="item.model">
                                @{{  item.model }}(@{{ item.name }})
                            </a-select-option>
                        </a-select>
                    </a-form-model-item>

                </a-form-model>

                <a-form-model-item :wrapper-col="{ span: 14, offset: 4 }">
                    <a-upload
                        :file-list="fileList"
                        :multiple="false"
                        :before-upload="fileBeforeUpload"
                        @change="(file) => fileHandleChange(file)"
                    >
                        <a-button>
                            <a-icon type="upload" ></a-icon>
                            导入数据
                        </a-button>
                    </a-upload>
                </a-form-model-item>
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
                    imei:'',
                    start_date:undefined,
                    end_date:undefined,
                    import_fail:true
                },
                defaultDate:undefined,
                tableKey:1,
                exportLoading:false,
                listSource: [],
                fileList:[],
                listLoading:false,
                status:'新增',
                allLoading:false,
                pagination: {
                    pageSize: 10,
                    total: 0,
                    current: 1,
                    onChange: this.paginationChange,
                    onShowSizeChange: this.paginationChange,
                },
                modelNameList: [
                    {
                        name:"海曼",
                        model:'HM-618PH-4G'
                    },
                    {
                        name:"源流",
                        model:'YL-IOT-YW03'
                    }
                ],
                columns:[
                    {
                        title: 'imei',
                        dataIndex: 'deim_imei',
                    },
                    {
                        title: '型号',
                        dataIndex: 'deim_model_name',
                    },
                    {
                        title: '数据库导入',
                        scopedSlots: { customRender: 'database_status' },
                        dataIndex: 'deim_database_status',
                    },
                    {
                        title: 'aep导入',
                        scopedSlots: { customRender: 'aep_status' },
                        dataIndex: 'deim_aep_status',
                    },
                    {
                        title: 'onenet导入',
                        scopedSlots: { customRender: 'onenet_status' },
                        dataIndex: 'deim_onenet_status',
                    },
                    {
                        title: '执行状态',
                        scopedSlots: { customRender: 'status' },
                        dataIndex: 'deim_status',
                    },
                    {
                        title: '执行备注',
                        dataIndex: 'deim_remark',
                    },
                    {
                        title: '创建时间',
                        dataIndex: 'deim_crt_time',
                    },
                    {
                        title: '操作',
                        align: 'center',
                        scopedSlots: { customRender: 'action' },
                    }
                ],
                dialogFormVisible:false,
                importFormVisible:false,
                labelCol: { span: 4 },
                wrapperCol: { span: 14 },
                formData: {
                    model_name : undefined,
                },
                id:null
            },
            created () {
                this.listQuery.page_size = this.pagination.pageSize;
                this.handleFilter()
            },
            components: {

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
                        url: '/api/maintain/importList',
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
                        // this.tableKey++;
                        this.listLoading = false
                    }).catch(error => {
                        this.$message.error('请求失败');
                    });
                },
                nodeChange(value){
                    // console.log(value);
                    this.listQuery.node_id = value;
                },
                dateChange(value,arr){
                    this.listQuery.start_date = arr[0];
                    this.listQuery.end_date = arr[1];
                },
                fileHandleChange(file){
                    if(file.file.status && file.file.status === 'removed'){
                        return false;
                    }

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
                            this.$message.error('文件类型不正确');
                            // console.log('文件类型不正确');
                            return;
                        }

                        // 表格的表格范围，可用于判断表头是否数量是否正确
                        var fromTo = '';
                        // 遍历每张表读取
                        for (var sheet in workbook.Sheets) {
                            if (workbook.Sheets.hasOwnProperty(sheet)) {
                                fromTo = workbook.Sheets[sheet]['!ref'];
                                console.log(fromTo);
                                persons = persons.concat(XLSX.utils.sheet_to_json(workbook.Sheets[sheet],{header:1}));
                                break; // 如果只取第一张表，就取消注释这行
                            }
                        }

                        let jsonData = [];
                        for (let item of persons){
                            jsonData.push([
                                item[0],
                            ])
                        }

                        if(!that.formData.model_name){
                            this.$message.error('设备型号不能为空');
                        }

                        axios({
                            // 默认请求方式为get
                            method: 'post',
                            url: '/api/maintain/importDevice',
                            // 传递参数
                            data: {
                                data:JSON.stringify(jsonData),
                                model_name:that.formData.model_name
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
                            that.importFormVisible = false;
                            that.$message.success('导入成功'+res.data.success_count+'个;导入失败'+res.data.errot_count+'个');
                            that.handleFilter();
                        })
                    };

                    fileReader.readAsBinaryString(file.file);
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
                onRerun(row){

                }
            },

        })
    </script>

    <style>
        .ant-table-tbody tr:nth-child(2n){
            background: #f1f1f1;
        }
    </style>
@endsection

