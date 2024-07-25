<template>
    <a-tree-select show-search style="width: 100%"
                   tree-default-expand-all
                   :dropdown-style="{ maxHeight: '400px', overflow: 'auto' }" v-model="id"
                   placeholder="请选择" allow-clear tree-node-filter-prop="title" :replace-fields="replaceFields"
                   :tree-data="list" @change="handleChange">
    </a-tree-select>
</template>

<script>
module.exports = {
    name: 'departmentTree',
    components: {},
    props: {
        mode: {
            default:function(){
                return 'default'
            },
        },
        defaultData: {
            default:function(){
                return undefined
            },
        },
    },
    data () {
        return {
            list:[],
            id:undefined,
            replaceFields: {
                children: 'children',
                title: 'depa_name',
                key: 'depa_id',
                value: 'depa_id'
            }
        }
    },
    methods: {
        getList () {
            axios({
                // 默认请求方式为get
                method: 'post',
                url: '/api/department/getTreeList',
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
            }).catch(error => {
                this.$message.error('请求失败');
            });
        },
        handleChange(value){
            // console.log(value)
            this.$emit('change',value);
        },
        clearData(){
            this.id = undefined;
        }
    },
    created () {
        if(this.mode === 'default'){
            this.id = undefined;
        }else{
            this.id = [];
        }
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

