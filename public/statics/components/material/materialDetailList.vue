<template>
    <div>
        <a-table :columns="columns" :data-source="listSource" :loading="listLoading" :row-key="(record, index) => { return index }"
                 :pagination="false">

            <div slot="made_expire_date" slot-scope="text, record">
                <span v-if="record.is_expire == 1"  style="color:red">{{ record.made_expire_date }}</span>
                <span v-else style="color:green">{{ record.made_expire_date }}</span>
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
    name: 'materialDetailList',
    components: {},
    props: {
        id: {
            default:function(){
                return 'default'
            },
        },
    },
    data () {
        return {
            listQuery: {
                id: "",
            },
            listSource: [],
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
                    dataIndex: 'made_id',
                    width: 80
                },
                {
                    title: '入库日期',
                    dataIndex: 'made_date',
                },
                {
                    title: '生产日期',
                    dataIndex: 'made_production_date',
                },
                {
                    title: '质保期',
                    scopedSlots: { customRender: 'made_expire_date' },
                    dataIndex: 'made_expire_date',
                },
            ],
        }
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
        getPageList (id) {
            this.listLoading = true
            this.listQuery.material_id = id;
            axios({
                // 默认请求方式为get
                method: 'post',
                url: '/api/material/getDetailList',
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
    },
    created () {
        if(this.id){
            this.getPageList(this.id);
        }
    },
    watch: {
        id (newData,oldData) {
            if(newData === oldData){
                return false
            }

            if(!newData){
                return false
            }

            this.getPageList(newData);
        }
    },
    computed: {

    }

}
</script>
<style scoped>

</style>

