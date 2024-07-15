@extends('admin.layout')
@section('content')
<div id="app">
    <a-card>
        <div>
            {{--<a-form layout="inline" >
                <a-form-item>
                    <a-input v-model="listQuery.name" placeholder="单位/用户名称" style="width: 200px;" />
                </a-form-item>
                <a-form-item>
                    <a-cascader v-model="listQuery.street_id" :options="areaList" placeholder="区域" />
                </a-form-item>
                <a-form-item>
                    <a-input v-model="listQuery.address" placeholder="地址" style="width: 200px;" />
                </a-form-item>
                <a-form-item>
                    <a-input v-model="listQuery.phone" placeholder="联系方式" style="width: 200px;" />
                </a-form-item>
                <a-form-item>
                    <a-space>
                        <a-select
                                ref="select"
                                placeholder="客户类型"
                                v-model="listQuery.customer_type"
                                :allow-clear="true"
                                style="width: 120px"
                                @focus="focus"
                                @change="handleChange"
                        >
                            <a-select-option value="1">toB</a-select-option>
                            <a-select-option value="2">toC</a-select-option>
                        </a-select>
                    </a-space>
                </a-form-item>

                <a-form-item>
                    <a-space>
                        <a-select
                                ref="select"
                                placeholder="付款方案"
                                v-model="listQuery.payment_type"
                                :allow-clear="true"
                                style="width: 120px"
                                @focus="focus"
                                @change="handleChange"
                        >
                            <a-select-option value="1" >预付</a-select-option>
                        </a-select>
                    </a-space>
                </a-form-item>
                <a-form-item>
                    <a-space>
                        <a-select
                                ref="select"
                                placeholder="收款方式"
                                v-model="listQuery.income_type"
                                style="width: 120px"
                                :allow-clear="true"
                                @focus="focus"
                                @change="handleChange"
                        >
                            <a-select-option value="1" >微信</a-select-option>
                            <a-select-option value="2" >支付宝</a-select-option>
                            <a-select-option value="3" >银行</a-select-option>
                            <a-select-option value="4" >现金</a-select-option>
                            <a-select-option value="5" >扫二维码</a-select-option>
                        </a-select>
                    </a-space>
                </a-form-item>

                <a-form-item>
                    <a-input v-model="listQuery.remark" placeholder="备注" style="width: 120px;" />
                </a-form-item>

                <a-form-item>
                    <a-button icon="search" v-on:click="handleFilter">查询</a-button>
                </a-form-item>
                <a-form-item>
                    <a-button @click="onCreate" type="primary" icon="edit">添加订单</a-button>
                </a-form-item>
            </a-form>--}}

            <a-table :columns="columns" :data-source="listSource" :loading="listLoading" :row-key="(record, index) => { return index }"
                     :pagination="false">

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

        <a-modal :mask-closable="false" v-model="stageInfoFormVisible"
                 title="分期详情"
                 width="800px" :footer="null">
            <financial_income_info ref="stageInfo"
                                   :id="id"
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
                // street_id:[],
                // address:'',
                // name:'',
                // phone:'',
                // remark:'',
                // payment_type:undefined,
                // customer_type:undefined,
                // income_type:undefined,
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
                    dataIndex: 'project_type'
                },
                {
                    title: '数量',
                    dataIndex: 'number'
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
                    dataIndex: 'advanced_total_installed'
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
                    title: '操作',
                    scopedSlots: { customRender: 'action' },
                }
            ],
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
            stageInfoQuery(){
                this.id = null;
                this.stageInfoFormVisible = false;
                this.handleFilter();
            },
            arrearsInfoQuery(){
                this.id = null;
                this.stageInfoFormVisible = false;
                this.handleFilter();
            }
        },

    })


</script>
@endsection
