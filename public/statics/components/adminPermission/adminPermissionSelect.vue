<template>
    <a-tree
        checkable
        :key="key"
        v-model:selectedKeys="id"
        :default-expand-all="true"
        :tree-data="list"
        :replace-fields="replaceFields"
        @check="handleChange"
    >
    </a-tree>
</template>

<script>
module.exports = {
    name: 'adminPermissionTree',
    components: {},
    props: {
        defaultData: {
            default:function(){
                return undefined
            },
        },
    },
    data () {
        return {
            list:[],
            id:[],
            key:1,
            replaceFields: {
                children: 'children',
                title: 'adpe_name',
                key: 'adpe_id',
                value: 'adpe_id'
            }
        }
    },
    methods: {
        getList () {
            axios({
                // 默认请求方式为get
                method: 'post',
                url: '/api/adminPermission/getTreeList',
                // 传递参数
                data: {
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
                this.list = res.data.list
                this.key+=1;
            }).catch(error => {
                this.$message.error('请求失败');
            });
        },
        handleChange(value){
            this.$emit('change',value);
        },
        setData(value){
            this.id = value;
            this.$emit('change',value);
        },
        clearData(){
            this.id = [];
        }
    },
    created () {
        this.getList();
        if(this.defaultData){
            this.id = this.defaultData;
            this.$emit('change',this.defaultData);
        }
    },
    watch: {
        defaultData (newData,oldData) {
            if(newData === oldData){
                return false
            }

            this.id = newData;
        }
    },
    computed: {

    }

}
</script>
<style scoped>

</style>

