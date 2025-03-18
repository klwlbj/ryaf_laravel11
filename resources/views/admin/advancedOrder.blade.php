@extends('admin.layout')
@section('content')
<div id="app">
    <a-card>
        <div>
            <a-form layout="inline" >
                <a-form-model-item>
                    <node-cascader @change="nodeChange"></node-cascader>
                </a-form-model-item>
                <a-form-item>
                    <a-input v-model="listQuery.user_keyword" placeholder="用户名/用户手机号" style="width: 200px;" />
                </a-form-item>

                <a-form-item>
                    <a-select v-model="listQuery.status" show-search placeholder="状态" :max-tag-count="1"
                              style="width: 200px;" allow-clear>
                        <a-select-option :value="1">
                            进行中
                        </a-select-option>
                        <a-select-option :value="2">
                            已完成
                        </a-select-option>
                    </a-select>
                </a-form-item>

                <a-form-item>
                    <a-range-picker
                        :placeholder="['预收开始时间', '预收结束时间']"
                        @change="dateChange"
                    ></a-range-picker>
                </a-form-item>

                <a-form-item>
                    <span><a-input v-model="listQuery.address" placeholder="地址" style="width: 200px;" /></span>
                </a-form-item>

                <a-form-item>
                    <span><a-input v-model="listQuery.remark" placeholder="备注" style="width: 120px;" /></span>
                    <span style="margin-left: 10px"><a-checkbox v-model="listQuery.remark_precise">精确匹配</a-checkbox></span>
                </a-form-item>

                <a-form-item>
                    <a-button icon="search" v-on:click="handleFilter">查询</a-button>
                </a-form-item>
{{--                <a-form-item>--}}
{{--                    <a-button icon="search" @click="exportList">导出</a-button>--}}
{{--                </a-form-item>--}}
                <a-form-item>
                    <a-button @click="onCreate" type="primary" icon="edit">添加订单</a-button>
                </a-form-item>
            </a-form>

            <a-table :columns="columns" :data-source="listSource" :loading="listLoading" :row-key="(record, index) => { return index }"
                     :pagination="false" :scroll="{ x: 1500,y: 650}">

                <div slot="user_info" slot-scope="text, record">
                    <div>@{{ record.ador_user_name }}</div>
                    <div>@{{ record.ador_user_mobile }}</div>
                </div>

                <div slot="funds_received" slot-scope="text, record">
                    <span style="color:red">@{{ record.ador_funds_received }}</span>/<span style="color:green">@{{ record.ador_remain_funds }}</span>
                </div>

                <div slot="status" slot-scope="text, record">
                    <a-tag v-if="record.ador_status==1" color="#f50">进行中</a-tag>
                    <a-tag v-else color="#87d068">已完成</a-tag>
                </div>

                <div slot="date" slot-scope="text, record">
                    <span style="color:red">@{{ record.ador_installation_date }}</span>/<span style="color:green">@{{ record.ador_pay_date }}</span>
                </div>

                <div slot="action" slot-scope="text, record">
                    <a style="margin-right: 8px" @click="onUpdate(record)">
                        修改
                    </a>

                    <a v-if="record.ador_status == 1" style="margin-right: 8px" @click="onLinkOrder(record)">
                        关联订单
                    </a>

                    <a-popconfirm
                        v-if="record.ador_status == 1"
                        title="是否确定删除?"
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
            <advanced-order-add ref="advancedOrderAdd"
                 :id="id"
                 @update="update"
                 @add="add"
                 @close="dialogFormVisible = false;"
            >
            </advanced-order-add>
        </a-modal>

        </a-modal>

        <a-modal :mask-closable="false" v-model="linkOrderFormVisible"
                 title="关联订单"
                 width="1200px" :footer="null">
            <advanced-order-link ref="linkOrder"
                                 :id="relationId"
                                 @submit="linkOrderSubmit"
                                 @close="linkOrderFormVisible = false;"
            >
            </advanced-order-link >
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
                user_keyword:'',
                remark:'',
                address:'',
                status:undefined,
                node_id:undefined,
                start_date:null,
                end_date:null,
                remark_precise:true
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
                {
                    title: '预付编号',
                    dataIndex: 'ador_sn',
                },
                {
                    title: '监控中心',
                    dataIndex: 'node_name',
                },
                {
                    title: '用户信息',
                    scopedSlots: { customRender: 'user_info' },
                    dataIndex: 'user_info'
                },
                {
                    title: '地址',
                    dataIndex: 'ador_address',
                    width:200
                },
                {
                    title: '状态',
                    scopedSlots: { customRender: 'status' },
                    dataIndex: 'ador_status'
                },
                {
                    title: '安装日期/支付日期',
                    scopedSlots: { customRender: 'date' },
                    dataIndex: 'date',
                    width:200
                },
                {
                    title: '预付金额/剩余金额（元）',
                    scopedSlots: { customRender: 'funds_received' },
                    dataIndex: 'ador_funds_received',
                    width:200
                },
                {
                    title: '预计安装总数',
                    dataIndex: 'ador_installation_count'
                },
                {
                    title: '收款方式',
                    dataIndex: 'ador_pay_way_msg'
                },
                {
                    title: '备注',
                    dataIndex: 'ador_remark'
                },
                {
                    title: '操作',
                    fixed: 'right',
                    scopedSlots: { customRender: 'action' },
                }
            ],
            dialogFormVisible:false,
            linkOrderFormVisible:false,
            id:null,
            relationId:null,
        },
        created () {
            // 获取区域和街道等
            this.getEnumList()
            this.listQuery.page_size = this.pagination.pageSize;
            this.handleFilter()
        },
        components: {
            "node-cascader":  httpVueLoader('/statics/components/node/nodeCascader.vue'),
            "advanced-order-add":  httpVueLoader('/statics/components/business/advancedOrderAdd.vue'),
            "advanced-order-link":  httpVueLoader('/statics/components/business/advancedOrderLink.vue')
        },
        methods: {
            paginationChange (current, pageSize) {
                this.listQuery.page = current;
                this.pagination.current = current;
                this.listQuery.page_size = pageSize;
                this.getPageList()
            },
            // 获取枚举列表
            getEnumList () {
                axios({
                    // 默认请求方式为get
                    method: 'post',
                    url: '/api/area/getList',
                    // 传递参数
                    responseType: 'json',
                    headers:{
                        'Content-Type': 'multipart/form-data'
                    }
                }).then(response => {
                    let res = response.data;
                    this.areaList = res.data.areaList
                }).catch(error => {
                    this.$message.error('请求失败');
                });
            },
            exportList(){
                let formData = JSON.parse(JSON.stringify(this.listQuery));
                formData.export = 1;
                axios({
                    // 默认请求方式为get
                    method: 'post',
                    url: '/api/advancedOrder/getList',
                    // 传递参数
                    data: formData,
                    responseType: 'json',
                    headers:{
                        'Content-Type': 'multipart/form-data'
                    }
                }).then(response => {
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
                    url: '/api/advancedOrder/getList',
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
            onCreate(){
                this.status = '添加';
                this.dialogFormVisible = true;
            },
            onUpdate(row){
                this.id = row.ador_id
                this.status = '更新';
                this.dialogFormVisible = true;
            },
            onLinkOrder(row){
                this.relationId = row.ador_id
                this.status = '关联订单';
                this.linkOrderFormVisible = true;
            },
            onDel(row){
                axios({
                    // 默认请求方式为get
                    method: 'post',
                    url: '/api/advancedOrder/delete',
                    // 传递参数
                    data: {
                        id:row.ador_id
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
            linkOrderSubmit(){
                this.id = null;
                this.$message.success('关联成功');
                this.linkOrderFormVisible = false;
                this.handleFilter();
            },
            nodeChange(value){
                this.listQuery.node_id = value;
            },
            dateChange(value,arr){
                this.listQuery.start_date = arr[0];
                this.listQuery.end_date = arr[1];
            },
        },

    })


</script>
@endsection
