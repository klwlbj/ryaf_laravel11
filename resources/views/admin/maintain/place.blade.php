@extends('admin.layout')

@section('content')
    <div id="app">
        <a-card>
            <div>
                <a-form layout="inline" >
                    <a-form-model-item label="监控中心" prop="node_id">
                        <node-cascader @change="nodeChange"></node-cascader>
                    </a-form-model-item>
                    <a-form-item label="imei">
                        <a-input v-model="listQuery.imei" placeholder="imei" style="width: 200px;" />
                    </a-form-item>

                    <a-form-item label="iccid">
                        <a-input v-model="listQuery.iccid" placeholder="iccid" style="width: 200px;" />
                    </a-form-item>

                    <a-form-item label="用户名/手机号">
                        <a-input v-model="listQuery.user_keyword" placeholder="用户名/手机号" style="width: 200px;" />
                    </a-form-item>

                    <a-form-item label="设备状态">
                        <a-select v-model="listQuery.online" show-search placeholder="设备状态" :max-tag-count="1"
                                  style="width: 200px;" allow-clear>
                            <a-select-option :value="1">
                                在线
                            </a-select-option>
                            <a-select-option :value="0">
                                离线
                            </a-select-option>
                        </a-select>
                    </a-form-item>

                    <a-form-item label="交付时间">
                        <a-range-picker
                            :placeholder="['开始时间', '结束时间']"
                            @change="dateChange"></a-range-picker>
                    </a-form-item>

                    <a-form-item label="无心跳天数">
                        <a-input-number v-model="listQuery.none_heart_day"/>
                    </a-form-item>

                    <a-form-item label="服务临期天数">
                        <a-input-number v-model="listQuery.expired_day"/>
                    </a-form-item>

                    <a-form-item>
                        <span style="margin-left: 10px"><a-checkbox v-model="listQuery.not_matching">无匹配标准地址</a-checkbox></span>
                        <span style="margin-left: 10px"><a-checkbox v-model="listQuery.not_standard_address">无标准地址</a-checkbox></span>
                    </a-form-item>

                    <a-form-item>
                        <a-button icon="search" v-on:click="handleFilter">查询</a-button>
                    </a-form-item>

                    <a-form-item>
                        <a-button :loading="exportLoading" icon="download" @click="exportList">导出</a-button>
                    </a-form-item>
                </a-form>

                <a-table :columns="columns" :data-source="listSource" :loading="listLoading" :row-key="(record, index) => { return index }"
                         :pagination="false" :scroll="{ y: 650 }">

                    <div slot="userinfo" slot-scope="text, record">
                        <div>@{{record.user_name}}</div>
                        <div>@{{record.user_mobile}}</div>
                    </div>

                    <div slot="count" slot-scope="text, record">
                        <div>@{{record.children_list.length}}</div>
                    </div>

                    <div slot="standard_address" slot-scope="text, record">
                        <div v-if="record.plac_standard_address_not_exist" style="color: red">无匹配地址</div>
                        <div v-else>@{{record.plac_standard_address}}</div>
                    </div>


                    <div slot="action" slot-scope="text, record">
                        <a style="margin-right: 8px" @click="onUpdatePlace(record)">
                            修改单位
                        </a>
                    </div>

                    <div slot="expandedRowRender" slot-scope="parentRow">
                        <a-table v-if="parentRow.children_list.length" size="small" :columns="childColumn" :data-source="parentRow.children_list" :pagination="false">
                            <div slot="smde_online_real" slot-scope="childText,childRecord">
                                <a-tag v-if="childRecord.smde_online_real == 0"  color="red">离线</a-tag>
                                <a-tag v-else color="green">在线</a-tag>
                            </div>

                            <div slot="smde_extra_remark" slot-scope="childText,childRecord">
                                <a-tag v-for="(item,index) in childRecord.smde_extra_remark" :key="index">@{{item}}</a-tag>
                            </div>

                            <div slot="smde_last_heart_beat" slot-scope="childText,childRecord">
                                <div>
                                    时间：@{{childRecord.smde_last_heart_beat}}
                                </div>
                                <div>
                                    电量：@{{childRecord.smde_last_nb_module_battery}}
                                </div>
                                <div>
                                    信号：@{{childRecord.smde_last_signal_intensity}}
                                </div>
                            </div>

                            <div slot="action" slot-scope="childText,childRecord">
                                <a style="margin-right: 8px" @click="setRemark(childRecord)">
                                    填写标注
                                </a>
                            </div>
                        </a-table>
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

            <a-modal :mask-closable="false" v-model="placeFormVisible"
                     title="修改单位"
                     width="1200px" :footer="null">
                <update-place
                    style="height: 600px;overflow: auto"
                    ref="updatePlace"
                    :id="placeId"
                    @update="updatePlaceAfter"
                    @close="placeFormVisible = false;"
                >
                </update-place>
            </a-modal>

            <a-modal :mask-closable="false" v-model="dialogFormVisible"
                     title="填写标注"
                     width="800px" :footer="null">
                <set-remark
                    style="height: 600px;overflow: auto"
                    ref="setRemark"
                    :id="id"
                    @update="update"
                    @close="dialogFormVisible = false;"
                >
                </set-remark>
            </a-modal>

        </a-card>
    </div>
@endsection

@section('script')
    <script src="https://webapi.amap.com/maps?v=1.4.15&key=a345ecce0b145c23156f5e63dfe8fffd"></script>
    <script src="https://webapi.amap.com/ui/1.0/main.js"></script>
    <script>
        Vue.use(httpVueLoader)
        new Vue({
            el: '#app',
            data: {
                listQuery: {
                    imei : '',
                    iccid: '',
                    none_heart_day : '',
                    online : undefined,
                    expired_day : '',
                    node_id : undefined,
                    user_keyword : '',
                    start_date : null,
                    end_date : null,
                    not_matching:false,
                    not_standard_address:false,
                },
                tableKey:1,
                exportLoading:false,
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
                        title: '单位id',
                        dataIndex: 'plac_id',
                    },
                    {
                        title: '监控中心',
                        dataIndex: 'node_name',
                    },
                    {
                        title: '单位名称',
                        dataIndex: 'plac_name',
                        width: 200
                    },
                    {
                        title: '单位地址',
                        dataIndex: 'plac_address',
                        width: 200
                    },
                    {
                        title: '标准地址',
                        dataIndex: 'standard_address',
                        scopedSlots: { customRender: 'standard_address' },
                        width: 200
                    },
                    {
                        title: '用户信息',
                        scopedSlots: { customRender: 'userinfo' },
                        dataIndex: 'userinfo',
                    },
                    {
                        title: '设备数',
                        scopedSlots: { customRender: 'count' },
                        dataIndex: 'count',
                    },
                    {
                        title: '操作',
                        scopedSlots: { customRender: 'action' },
                    }
                ],
                childColumn:[
                    {
                        title: 'imei',
                        dataIndex: 'smde_imei'
                    },
                    {
                        title: '型号',
                        dataIndex: 'smde_model_name'
                    },
                    {
                        title: '最后心跳包信息',
                        dataIndex: 'smde_last_heart_beat',
                        scopedSlots: { customRender: 'smde_last_heart_beat' },
                        width:200
                    },
                    {
                        title: '交付时间',
                        dataIndex: 'order_actual_delivery_date'
                    },
                    {
                        title: '服务期限',
                        dataIndex: 'order_service_date'
                    },
                    {
                        title: '标注',
                        scopedSlots: { customRender: 'smde_extra_remark' },
                        dataIndex: 'smde_extra_remark'
                    },
                    {
                        title: '设备状态',
                        scopedSlots: { customRender: 'smde_online_real' },
                        dataIndex: 'smde_online_real'
                    },
                    {
                        title: '操作',
                        scopedSlots: { customRender: 'action' },
                        // fixed:'right'
                    }
                ],
                dialogFormVisible:false,
                id:null,
                placeFormVisible:false,
                placeId:null,
            },
            created () {
                this.listQuery.page_size = this.pagination.pageSize;
                this.handleFilter()
            },
            components: {
                "node-cascader":  httpVueLoader('/statics/components/node/nodeCascader.vue'),
                "set-remark":  httpVueLoader('/statics/components/maintain/setRemark.vue'),
                "update-place":  httpVueLoader('/statics/components/maintain/updatePlace.vue')
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
                        url: '/api/maintain/placeList',
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
                exportList(){
                    this.exportLoading = true;
                    let formData = JSON.parse(JSON.stringify(this.listQuery));
                    formData.export = 1;
                    axios({
                        // 默认请求方式为get
                        method: 'post',
                        url: '/api/maintain/placeList',
                        // 传递参数
                        data: formData,
                        responseType: 'json',
                        headers:{
                            'Content-Type': 'multipart/form-data'
                        }
                    }).then(response => {
                        this.exportLoading = false;
                        let res = response.data;
                        if(res.code !== 0){
                            this.$message.error(res.message);
                            return false;
                        }
                        window.location.href = res.data.url
                    }).catch(error => {
                        this.$message.error('请求失败');
                    });
                },
                setRemark(row){
                    this.id = row.smde_id
                    this.dialogFormVisible = true;
                },
                update(){
                    this.id = null;
                    this.dialogFormVisible = false;
                    this.getPageList();
                },
                onUpdatePlace(row){
                    this.placeId = row.plac_id
                    this.placeFormVisible = true;
                },
                updatePlaceAfter(){
                    this.placeId = null;
                    this.placeFormVisible = false;
                    this.getPageList();
                },
                dateChange(value,arr){
                    this.listQuery.start_date = arr[0];
                    this.listQuery.end_date = arr[1];
                },
            },

        })
    </script>

    <style>
        /**[aria-hidden=true]{*/
        /*    display: none !important;*/
        /*}*/
    </style>
@endsection
