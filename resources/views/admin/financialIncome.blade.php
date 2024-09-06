@extends('admin.layout')
@section('content')
<div id="app">
    <a-card>
        <div>
            <a-form layout="inline" >

                <a-form-item>
                    <a-space>
                        <a-select
                                ref="select"
                                placeholder="项目类型"
                                v-model="listQuery.order_project_type"
                                style="width: 120px"
                                @change="handleChange"
                        >
                            <a-select-option :value="0">烟感</a-select-option>
                            <a-select-option :value="1">智慧用电</a-select-option>
                            <a-select-option :value="2">智慧燃气</a-select-option>
                            <a-select-option :value="3">用传装置</a-select-option>
                            <a-select-option :value="4">消防维保</a-select-option>
                            <a-select-option :value="5">消防工程</a-select-option>
                            <a-select-option :value="6">消防站建设</a-select-option>
                            <a-select-option :value="7">其他</a-select-option>
                        </a-select>
                    </a-space>
                </a-form-item>
                <a-form-item>
                    <a-range-picker
                            :placeholder="['开始时间', '结束时间']"
                            @change="dateChange"
                            :default-value="[defaultDate,defaultDate]"></a-range-picker>
                </a-form-item>
                <a-form-item>
                    <a-input v-model="listQuery.address" placeholder="地址" style="width: 120px;" />
                </a-form-item>

                <a-form-item>
                    <a-space>
                        <a-select
                                ref="select"
                                placeholder="欠款账龄"
                                v-model="listQuery.arrears_duration"
                                :allow-clear="true"
                                style="width: 120px"
                                @change="handleChange"
                        >
                            <a-select-option value="0">未逾期</a-select-option>
                            <a-select-option value="1">1个月内</a-select-option>
                            <a-select-option value="2">1-2个月内</a-select-option>
                            <a-select-option value="3">2-3个月内</a-select-option>
                            <a-select-option value="4">3-4个月</a-select-option>
                            <a-select-option value="5">4-5个月内</a-select-option>
                            <a-select-option value="6">5-12个月内</a-select-option>
                        </a-select>
                    </a-space>
                </a-form-item>

                <a-form-item>
                    <a-button icon="search" v-on:click="handleFilter">查询</a-button>
                </a-form-item>
            </a-form>

            <a-table :columns="columns" :data-source="listSource" :loading="listLoading" :row-key="(record, index) => { return index }"
                     :pagination="false" :scroll="{ x: 2000,y: 650}">

                <div slot="action" slot-scope="text, record">
                    <a style="margin-right: 8px" @click="onStageInfo(record)">
                        分期详情
                    </a>

                    <a style="margin-right: 8px" @click="onArrearsInfo(record)">
                        欠款账龄
                    </a>
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
            <other-order-add ref="otherOrderAdd"
                                :id="id"
                                @update="update"
                                @add="add"
                                @close="dialogFormVisible = false;"
            >
            </other-order-add>
        </a-modal>

        <a-modal :mask-closable="false" v-model="stageInfoFormVisible"
                 title="分期详情"
                 width="800px" :footer="null">
            <financial_income_info ref="stageInfo"
                                   :id="id"
                                   :order-project-type="listQuery.order_project_type"
                                 @submit="stageInfoQuery"
                                 @close="stageInfoFormVisible = false;"
            >
            </financial_income_info >
        </a-modal>

        <a-modal :mask-closable="false" v-model="arrearsInfoFormVisible"
                 title="欠款账龄"
                 width="800px" :footer="null">
            <financial_arrears_info ref="arrearsInfo"
                                   :id="id"
                                   :order-project-type="listQuery.order_project_type"
                                 @submit="arrearsInfoQuery"
                                 @close="arrearsInfoFormVisible = false;"
            >
            </financial_arrears_info >
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
                start_date:null,
                end_date:null,
                address: '',
                order_project_type: 0,
                arrears_duration: undefined,
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
                    title: 'Id',
                    dataIndex: 'order_iid',
                    width: 80
                },
                {
                    title: '单位',
                    dataIndex: 'order_user_name',
                    width: 100
                },
                {
                    title: '发生日期',
                    dataIndex: 'order_prospecter_date',
                },
                {
                    title: '项目类型',
                    dataIndex: 'order_project_type'
                },
                {
                    title: '数量',
                    dataIndex: 'install_number'
                },
                {
                    title: '分期数',
                    dataIndex: 'order_pay_cycle'
                },
                {
                    title: '收款类型',
                    dataIndex: 'order_contract_type'
                },
                {
                    title: '应收金额',
                    dataIndex: 'order_account_receivable'
                },
                {
                    title: '收款日期',
                    dataIndex: 'order_actual_delivery_date'
                },
                {
                    title: '收款方式',
                    dataIndex: 'income_type'
                },
                {
                    title: '实收金额',
                    dataIndex: 'order_funds_received'
                },
                {
                    title: '未收款金额（欠款金额）',
                    dataIndex: 'order_account_outstanding'
                },
                {
                    title: '是否逾期',
                    dataIndex: 'is_overdue'
                },
                {
                    title: '操作',
                    fixed: 'right',
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
            // 获取区域和街道等
            // this.getEnumList()
            this.listQuery.page_size = this.pagination.pageSize;
            this.handleFilter()
        },
        components: {
          "other-order-add":  httpVueLoader('/statics/components/business/otherOrderAdd.vue'),
          "financial_income_info":  httpVueLoader('/statics/components/business/financialIncomeInfo.vue'),
          "financial_arrears_info":  httpVueLoader('/statics/components/business/financialArrearsInfo.vue'),
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
                    url: '/api/financialIncome/getList',
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
            onStageInfo(row){
                this.id = row.order_id
                this.status = '详情';
                this.stageInfoFormVisible = true;
            },
            onArrearsInfo(row){
                this.id = row.order_id
                this.status = '欠款账龄';
                this.arrearsInfoFormVisible = true;
            },
            onDel(row){
                axios({
                    // 默认请求方式为get
                    method: 'post',
                    url: '/api/advancedOrder/delete',
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
