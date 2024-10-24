@extends('admin.layout')

@section('content')
    <div id="app">
        <a-alert
            v-if="expireList.length > 0"
            message="物品临期警告"
            :description="expireStr"
            banner closable></a-alert>
        <a-card>
            <div>
                <a-form layout="inline" >
                    <a-form-item>
                        <a-input v-model="listQuery.keyword" placeholder="物品名称" style="width: 200px;"  @keyup.enter="handleFilter"/>
                    </a-form-item>
                    <a-form-item>
                        <manufacturer-select @change="manufacturerChange"></manufacturer-select>
                    </a-form-item>
                    <a-form-item>
                        <category-select @change="categoryChange"></category-select>
                    </a-form-item>
                    <a-form-item>
                        <a-select v-model="listQuery.is_expire" show-search placeholder="是否临期" :max-tag-count="1"
                                  style="width: 200px;" allow-clear>
                            <a-select-option :value="1">
                                是
                            </a-select-option>
                        </a-select>
                    </a-form-item>
                    <a-form-item>
                        <a-select v-model="listQuery.is_verify" show-search placeholder="是否待确认" :max-tag-count="1"
                                  style="width: 200px;" allow-clear>
                            <a-select-option :value="1">
                                是
                            </a-select-option>
                        </a-select>
                    </a-form-item>
                    <a-form-item>
                        <a-button icon="search" @click="handleFilter">查询</a-button>
                    </a-form-item>
                    <a-form-item>
                        <a-button v-if="$checkPermission('/api/material/getList')" :loading="exportLoading" icon="download" @click="exportList">导出</a-button>
                    </a-form-item>

                    <a-form-item>
                        <a-button v-if="$checkPermission('/api/material/reportExport')" type="primary" icon="bar-chart" @click="onReport">进销存报表</a-button>
                    </a-form-item>

                    <a-form-item>
                        <a-button v-if="$checkPermission('/api/material/add')" @click="onCreate" type="primary" icon="edit">添加物品</a-button>
                    </a-form-item>

                </a-form>
                <a-table :columns="columns" :data-source="listSource" :loading="listLoading" :row-key="(record, index) => { return index }"
                         :pagination="false" :scroll="{ x: 1500,y: 650 }">

                    <div slot="mate_name" slot-scope="text, record">
                        <div style="cursor: pointer" @click="getDetail(record)">
                            <a-tooltip placement="right">
                                <template #title>
                                    <div>厂家：@{{record.mate_manufacturer_name}}</div>
                                    <div>分类：@{{record.mate_category_name}}</div>
                                    <div>规格：@{{record.mate_specification_name.join(',')}}</div>
                                    <div>库存：@{{record.mate_number}}</div>
                                    <div>单位：@{{record.mate_unit}}</div>
                                    <div>预警值：@{{record.mate_warning}}</div>
                                    <div v-if="record.last_in_flow.mafl_number">最后入库数：@{{record.last_in_flow.mafl_number}}</div>
                                    <div v-if="record.last_in_flow.mafl_number">最后入库时间：@{{record.last_in_flow.mafl_datetime}}</div>
                                    <div v-if="record.last_out_flow.mafl_number">最后出库数：@{{record.last_out_flow.mafl_number}}</div>
                                    <div v-if="record.last_out_flow.mafl_number">最后出库申请人：@{{record.last_out_flow.mafl_apply_user_name}}</div>
                                    <div v-if="record.last_out_flow.mafl_number">最后出库时间：@{{record.last_out_flow.mafl_datetime}}</div>
                                    <div v-if="record.expire_count > 0">临期数：@{{record.expire_count}}</div>
                                    <div v-if="record.expire_count > 0">临期时间：@{{record.expire_date}}</div>
                                </template>
                                @{{ record.mate_name }}
                            </a-tooltip>
                        </div>
                        <div style="color:red;cursor: pointer" @click="getUnconfirmedFlow(record)">
                            (待确认出入库：@{{ record.flow_count }})
                        </div>
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

                    <div slot="price" slot-scope="text, record">
                        <div>单价（含税）：<span style="color:red">@{{record.mate_price_tax}}</span></div>
                        <div>税率：<span style="color:red">@{{record.mate_tax}}%</span></div>
                        <div>单价（不含税）：<span style="color:red">@{{record.mate_price}}</span></div>
                        <div>发票类型：<span style="color:red">@{{record.mate_invoice_type_msg}}</span></div>
                    </div>

                    <div slot="action" slot-scope="text, record">
                        <a v-if="$checkPermission('/api/material/update')" style="margin-right: 8px" @click="onUpdate(record)">
                            修改
                        </a>

                        <a-popconfirm
                            v-if="$checkPermission('/api/material/delete')"
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
                            <a v-if="$checkPermission('/api/materialFlow/inComing')" style="margin-right: 8px" @click="onInComing(record)">
                                入库
                            </a>

                            <a v-if="$checkPermission('/api/materialFlow/outComing')" style="margin-right: 8px" @click="onOutComing(record)">
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
                >
                </material-detail-list>
            </a-modal>

            <a-modal :mask-closable="false" v-model="infoFormVisible"
                     :title="infoStatus"
                     width="1000px" :footer="null">
                <material-detail
                    style="height: 600px;overflow: auto"
                    ref="materialDetail"
                    :id="infoId"
                >
                </material-detail>
            </a-modal>

            <a-modal :mask-closable="false" v-model="unconfirmedFlowFormVisible"
                     :title="unconfirmedFlowStatus"
                     v-drag-modal
                     :destroy-on-close="true"
                     width="1200px" :footer="null">
                <unconfirmed-flow-list
                    style="height: 600px;overflow: auto"
                    ref="unconfirmedFlowList"
                    :id="unconfirmedFlowId"
                    @refresh="getPageList"
                >
                </unconfirmed-flow-list>
            </a-modal>




            <a-modal :mask-closable="false" v-model="reportFormVisible"
                     title="进销存报表"
                     width="800px" :footer="null">
                <a-form-model :model="reportForm" :label-col="labelCol" :wrapper-col="wrapperCol">
                    <a-form-model-item label="开始日期">
                        <a-date-picker format="YYYY-MM-DD" value-format="YYYY-MM-DD" v-model:value="reportForm.start_date"/>
                    </a-form-model-item>

                    <a-form-model-item label="结束日期">
                        <a-date-picker format="YYYY-MM-DD" value-format="YYYY-MM-DD" v-model:value="reportForm.end_date"/>
                    </a-form-model-item>
                </a-form-model>

                <a-form-model-item :wrapper-col="{ span: 14, offset: 4 }">
                    <a-button type="primary" :loading="reportLoading" @click="submitReport">
                        生成报表
                    </a-button>
                </a-form-model-item>
            </a-modal>
        </a-card>
    </div>



@endsection
@section('script')
    <script>
        Vue.directive('drag-modal', (el, binding, vnode, oldVnode) => {

            // inserted (el, binding, vnode, oldVnode) {
            Vue.nextTick(() => {
                let { visible, destroyOnClose } = vnode.componentInstance
                // 防止未定义 destroyOnClose 关闭弹窗时dom未被销毁，指令被重复调用
                if (!visible) return
                let modal = el.getElementsByClassName('ant-modal')[0]
                let header = el.getElementsByClassName('ant-modal-header')[0]
                let left = 0
                let top = 0
                // 未定义 destroyOnClose 时，dom未被销毁，关闭弹窗再次打开，弹窗会停留在上一次拖动的位置
                if (!destroyOnClose) {
                    left = modal.left || 0
                    top = modal.top || 0
                }
                // top 初始值为 offsetTop
                top = top || modal.offsetTop
                // 点击title部分拖动
                header.onmousedown = e => {
                    let startX = e.clientX
                    let startY = e.clientY
                    header.left = header.offsetLeft
                    header.top = header.offsetTop
                    el.onmousemove = event => {
                        let endX = event.clientX
                        let endY = event.clientY
                        modal.left = header.left + (endX - startX) + left
                        modal.top = header.top + (endY - startY) + top
                        modal.style.left = modal.left + 'px'
                        modal.style.top = modal.top + 'px'
                    }
                    el.onmouseup = event => {
                        left = modal.left
                        top = modal.top
                        el.onmousemove = null
                        el.onmouseup = null
                        header.releaseCapture && header.releaseCapture()
                    }
                    header.setCapture && header.setCapture()
                }


            })
        })

        Vue.use(httpVueLoader)
        new Vue({
            el: '#app',
            data: {
                listQuery: {
                    keyword: "",
                    manufacturer_id:'',
                    category_id:'',
                    is_expire:undefined,
                    is_verify:undefined
                },
                listSource: [],
                expireList: [],
                listLoading:false,
                exportLoading:false,
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
                        scopedSlots: { customRender: 'mate_name' },
                        dataIndex: 'mate_name',
                        width: 200
                    },
                    {
                        title: '厂家',
                        dataIndex: 'mate_manufacturer_name',
                    },
                    {
                        title: '类别',
                        dataIndex: 'mate_category_name',
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
                        title: '默认单价',
                        scopedSlots: { customRender: 'price' },
                        dataIndex: 'mate_price',
                        width: 200,
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
                        fixed:'right'
                    }
                ],
                dialogFormVisible:false,
                inComingFormVisible:false,
                outComingFormVisible:false,
                detailFormVisible:false,
                infoFormVisible:false,
                unconfirmedFlowFormVisible:false,
                id:null,
                inComingMaterialId:null,
                outComingMaterialId:null,
                detailId:null,
                detailStatus:'',
                infoId:null,
                infoStatus:'',
                unconfirmedFlowId:null,
                unconfirmedFlowStatus:'',
                reportLoading:false,
                reportFormVisible:false,
                labelCol: { span: 4 },
                wrapperCol: { span: 14 },
                reportForm:{
                    start_date : moment().startOf('months').format("YYYY-MM-DD"),
                    end_date: moment().endOf('month').subtract(1, 'days').format("YYYY-MM-DD"),
                }
            },
            created () {
                this.listQuery.page_size = this.pagination.pageSize;
                this.handleFilter()
            },
            computed: {
                expireStr(){
                    let str = '';
                    for(let item of this.expireList){
                        str += (item['mate_name'] + ' 即将过期数：' +  item['expire_count'] + '， 过期时间：' + item['mate_expire_date'] + ';');
                    }

                    return str;
                }
            },
            components: {
                "material-add":  httpVueLoader('/statics/components/material/materialAdd.vue'),
                "material-detail-list":  httpVueLoader('/statics/components/material/materialDetailList.vue'),
                "material-detail":  httpVueLoader('/statics/components/material/materialDetail.vue'),
                "manufacturer-select":  httpVueLoader('/statics/components/material/manufacturerSelect.vue'),
                "category-select":  httpVueLoader('/statics/components/material/categorySelect.vue'),
                "material-in-coming":  httpVueLoader('/statics/components/material/materialInComing.vue'),
                "material-out-coming":  httpVueLoader('/statics/components/material/materialOutComing.vue'),
                "unconfirmed-flow-list":  httpVueLoader('/statics/components/material/unconfirmedFlowList.vue')
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
                    this.exportLoading = true;
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
                        this.listLoading = false;
                        let res = response.data;
                        if(res.code !== 0){
                            this.$message.error(res.message);
                            return false;
                        }
                        this.listSource = res.data.list
                        this.expireList = res.data.expire_list
                        this.pagination.total = res.data.total
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
                getDetail(row){
                    this.infoId = row.mate_id;
                    this.infoStatus = row.mate_name;
                    this.infoFormVisible = true;
                },
                getUnconfirmedFlow(row){
                    this.unconfirmedFlowId = row.mate_id;
                    this.unconfirmedFlowStatus = row.mate_name;
                    this.unconfirmedFlowFormVisible = true;
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
                    this.getPageList();
                },
                update(){
                    this.id = null;
                    this.$message.success('编辑成功');
                    this.dialogFormVisible = false;
                    this.getPageList();
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
                    this.getPageList();
                },
                outComingSubmit(){
                    this.outComingMaterialId = null;
                    this.$message.success('出库成功');
                    this.outComingFormVisible = false;
                    this.getPageList();
                },
                categoryChange(value){
                    this.listQuery.category_id = value;
                    this.handleFilter();
                },
                manufacturerChange(value){
                    this.listQuery.manufacturer_id = value;
                    this.handleFilter();
                },
                onReport(){
                    this.reportFormVisible = true;
                },
                submitReport(){
                    this.reportLoading = true;
                    axios({
                        // 默认请求方式为get
                        method: 'post',
                        url: '/api/material/reportExport',
                        // 传递参数
                        data: this.reportForm,
                        responseType: 'json',
                        headers:{
                            'Content-Type': 'multipart/form-data'
                        }
                    }).then(response => {
                        this.reportLoading = false;
                        let res = response.data;
                        if(res.code !== 0){
                            this.$message.error(res.message);
                            return false;
                        }
                        const today = new Date();
                        const year = today.getFullYear();
                        const month = today.getMonth() + 1;  // 月份从0开始，所以要加1
                        const day = today.getDate();
                        this.downloadFile(res.data.url,'进销存报表'+ year + '-' + month + '-' + day);
                        // window.location.href = res.data.url
                    }).catch(error => {
                        this.$message.error('请求失败');
                    });
                },
                downloadFile(url,name) {
                    const link = document.createElement('a');
                    link.style.display = 'none';
                    // 设置下载地址
                    link.setAttribute('href', url);
                    // 设置文件名
                    link.setAttribute('download', name);
                    document.body.appendChild(link);
                    link.click();
                    document.body.removeChild(link);
                }
            },
        })
    </script>
@endsection
