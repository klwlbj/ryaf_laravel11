<template>
    <div>
        <a-form layout="inline" >
            <a-form-item label="订单编号">
                <a-input v-model="listQuery.sn" placeholder="订单编号" style="width: 200px;" />
            </a-form-item>

            <a-form-item label="用户名称/电话">
                <a-input v-model="listQuery.user_keyword" placeholder="用户名称/电话" style="width: 200px;" />
            </a-form-item>

            <a-form-item label="地址">
                <a-input v-model="listQuery.address" placeholder="地址" style="width: 200px;" />
            </a-form-item>

            <a-form-item>
                <a-button icon="search" v-on:click="handleFilter">查询</a-button>
            </a-form-item>
        </a-form>

        <a-table  :columns="columns" :data-source="list" :loading="loading"  :row-key="(record, index) => { return index }"
                  :scroll="{x: 1500,y:500}"
                  :pagination="false">

            <div slot="address" slot-scope="text, record">
                <div v-for="item in record.address">
                    {{ item.reac_address }}
                </div>
            </div>

            <div slot="reac_user_name" slot-scope="text, record">
                <div>{{ record.reac_user_name }}</div>
                <div>{{ record.reac_user_mobile }}</div>
                <div>
                    <span v-if="record.reac_user_type == 1">2B</span>
                    <span v-else>2C</span>
                </div>
            </div>

            <div slot="reac_installation_count" slot-scope="text, record">
                <div>{{ record.reac_installation_count }} / {{ record.reac_given_count }}</div>
            </div>

            <div slot="action" slot-scope="text, record">
                <div>
                    <a-popconfirm
                        title="是否确定关联该订单?"
                        ok-text="确认"
                        cancel-text="取消"
                        @confirm="onLink(record)"
                    >
                        <a style="margin-right: 8px">
                            关联
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
</template>

<script>
module.exports = {
    name: 'advancedOrderLink',
    components: {
    },
    props: {
        id: {
            default:function(){
                return null
            },
        },
    },
    data () {
        return {
            listQuery: {
                sn:'',
                user_keyword:'',
                is_debt:1,
                address:''
            },
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
                    align: 'center',
                    dataIndex: 'reac_user_name',
                    width: 150
                },
                {
                    title: '安装日期',
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
                    title: '安装地址',
                    scopedSlots: { customRender: 'address' },
                    dataIndex: 'address',
                    align: 'center',
                    width: 300
                },
                {
                    title: '安装数量/赠送数量',
                    align: 'center',
                    scopedSlots: { customRender: 'reac_installation_count' },
                    dataIndex: 'reac_installation_count'
                },
                {
                    title: '备注',
                    align: 'center',
                    dataIndex: 'reac_remark'
                },
                {
                    title: '操作',
                    fixed: 'right',
                    align: 'center',
                    scopedSlots: { customRender: 'action' },
                }
            ],
            list:[],
            info:{},
            dialogFormLabelCol: { span: 4 },
            dialogFormWrapperCol: { span: 14 },
            formRules: {

            },
            loading :false,
            materialId:null,
        }
    },
    methods: {
        paginationChange (current, pageSize) {
            this.listQuery.page = current;
            this.pagination.current = current;
            this.listQuery.page_size = pageSize;
            this.getPageList()
        },
        handleFilter(){
            this.listQuery.page = 1
            this.pagination.current = 1;
            this.getPageList()
        },
        getPageList(){
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
                this.list = res.data.list
                this.pagination.total = res.data.total
                this.listLoading = false
            }).catch(error => {
                this.$message.error('请求失败');
            });
        },
        submitData(){
            let that = this;
            this.$refs.dataForm.validate((valid) => {
                if (valid) {
                    let params = {
                        detail:JSON.stringify(that.formData.detail),
                    }
                    // this.id = 2111
                    params.id = this.id;
                    axios({
                        // 默认请求方式为get
                        method: 'post',
                        url: '/api/advancedOrder/link',
                        // 传递参数
                        data: params,
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
                        this.initForm();
                        that.$emit('submit');
                    }).catch(error => {
                        this.$message.error('请求失败');
                    });
                }else{
                    this.$message.error('表单验证失败');
                }

            })
        },
        onLink(row){
            this.loading = true;
            axios({
                // 默认请求方式为get
                method: 'post',
                url: '/api/advancedOrder/link',
                // 传递参数
                data: {
                    advanced_id:this.id,
                    receivable_id:row.reac_id
                },
                responseType: 'json',
                headers:{
                    'Content-Type': 'multipart/form-data'
                }
            }).then(response => {
                this.loading = false;
                let res = response.data;
                // console.log(res)
                if(res.code !== 0){
                    this.$message.error(res.message);
                    return false;
                }
                this.$message.success('绑定成功');
                this.getPageList();
            }).catch(error => {
                this.$message.error('请求失败');
            });
        },
        getDetail(id){
            if(!id){
                this.$message.error('id不能为空');
                return false;
            }
            this.loading = true;
            axios({
                // 默认请求方式为get
                method: 'post',
                url: '/api/advancedOrder/getInfo',
                // 传递参数
                data: {
                    id:id
                },
                responseType: 'json',
                headers:{
                    'Content-Type': 'multipart/form-data'
                }
            }).then(response => {
                this.loading = false;
                let res = response.data;
                // console.log(res)
                if(res.code !== 0){
                    this.$message.error(res.message);
                    return false;
                }

                this.info = res.data;

                this.$forceUpdate();
            }).catch(error => {
                this.$message.error('请求失败');
            });
        },

    },
    created () {
        if(this.id){
            this.getDetail(this.id);
        }
    },
    watch: {
        id (newData,oldData) {
            if(newData === oldData){
                return false
            }

            if(newData === null){
                this.initForm();

                return false;
            }

            this.getDetail(newData);
        }
    },
    computed: {

    }

}
</script>
<style scoped>

</style>

