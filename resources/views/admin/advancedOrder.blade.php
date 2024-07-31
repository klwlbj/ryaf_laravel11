@extends('admin.layout')
@section('content')
<div id="app">
    <a-card>
        <div>
            <a-form layout="inline" >
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
                                v-model="listQuery.pay_way"
                                style="width: 120px"
                                :allow-clear="true"
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
            </a-form>

            <a-table :columns="columns" :data-source="listSource" :loading="listLoading" :row-key="(record, index) => { return index }"
                     :pagination="false" :scroll="{ x: 2000,y: 650}">

                <div slot="action" slot-scope="text, record">
                    <a style="margin-right: 8px" @click="onUpdate(record)">
                        修改
                    </a>
                    <a style="margin-right: 8px" @click="onLinkOrder(record)">
                        关联订单
                    </a>

                    <a-popconfirm
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
                 width="800px" :footer="null">
            <advanced-order-link ref="linkOrder"
                                 :id="id"
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
                street_id:[],
                address:'',
                name:'',
                phone:'',
                remark:'',
                payment_type:undefined,
                customer_type:undefined,
                pay_way:undefined,
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
                    dataIndex: 'ador_id',
                    width: 80
                },
                {
                    title: '区',
                    dataIndex: 'district_name',
                    width: 100
                },
                {
                    title: '街道',
                    dataIndex: 'street_name'
                },
                {
                    title: '村委/经济联社/社区',
                    dataIndex: 'community_name'
                },
                {
                    title: '地址',
                    dataIndex: 'address'
                },
                {
                    title: '单位/用户名称',
                    dataIndex: 'name'
                },
                {
                    title: '联系方式',
                    dataIndex: 'phone'
                },
                {
                    title: '客户类型',
                    dataIndex: 'customer_type_name'
                },
                {
                    title: '预计安装总数',
                    dataIndex: 'advanced_total_installed'
                },
                {
                    title: '预付金额（元）',
                    dataIndex: 'advanced_amount'
                },
                {
                    title: '付款方案',
                    dataIndex: 'payment_type_name'
                },
                {
                    title: '收款方式',
                    dataIndex: 'pay_way_name'
                },
                {
                    title: '备注',
                    dataIndex: 'remark'
                },
                {
                    title: '操作',
                    fixed: 'right',
                    scopedSlots: { customRender: 'action' },
                }
            ],
            dialogFormVisible:false,
            linkOrderFormVisible:false,
            id:null
        },
        created () {
            // 获取区域和街道等
            this.getEnumList()
            this.listQuery.page_size = this.pagination.pageSize;
            this.handleFilter()
        },
        components: {
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
                this.id = row.ador_id
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
            }
        },

    })


</script>
@endsection
