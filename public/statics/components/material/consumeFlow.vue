<template>
    <div>
        <a-table :columns="columns" :data-source="listSource" :loading="listLoading" :row-key="(record, index) => { return index }"
                 :pagination="false">

            <div slot="expire_date" slot-scope="text, record">
                <span v-if="record.is_expire == 1"  style="color:red">{{ record.expire_date }}</span>
                <span v-else style="color:green">{{ record.expire_date }}</span>
            </div>
        </a-table>
    </div>
</template>

<script>
module.exports = {
    name: 'consumeFlow',
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

            },
            listSource: [],
            listLoading: false,
            columns:[
                {
                    title: 'ID',
                    dataIndex: 'mafl_id',
                },
                {
                    title: '消耗日期',
                    dataIndex: 'mafl_date',
                },
                {
                    title: '消耗数量',
                    dataIndex: 'mafl_number',
                },
                {
                    title: '使用人',
                    dataIndex: 'admin_name',
                }
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
            this.listQuery.flow_id = id;
            axios({
                // 默认请求方式为get
                method: 'post',
                url: '/api/materialFlowConsume/getConsumeList',
                // 传递参数
                data: this.listQuery,
                responseType: 'json',
                headers:{
                    'Content-Type': 'multipart/form-data'
                }
            }).then(response => {
                this.listLoading = false
                let res = response.data;
                this.listSource = res.data
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

