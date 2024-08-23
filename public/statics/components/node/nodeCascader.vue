<template>
    <a-cascader v-model="id" :options="list" :field-names="fieldNames" change-on-select placeholder="请选择所属区域" @change="handleChange"/>
</template>

<script>
module.exports = {
    name: 'nodeCascaderSelect',
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
        type: {
            default:function(){
                return undefined
            },
        },
        parentId: {
            default:function(){
                return undefined
            },
        },
    },
    data () {
        return {
            list:[],
            id:undefined,
            fieldNames:{
                label:'node_name',
                value:'node_id',
                children:'children',
            }
        }
    },
    methods: {
        getList () {
            axios({
                // 默认请求方式为get
                method: 'post',
                url: '/api/node/getTreeList',
                // 传递参数
                data: {
                    type:this.type,
                    parent_id:this.parentId,
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
                this.list = res.data
            }).catch(error => {
                this.$message.error('请求失败');
            });
        },
        handleChange(value){
            this.$emit('change',value[value.length - 1]);
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
        },
        parentId (newData,oldData) {
            if(newData === oldData){
                return false
            }

            this.getList();
        }
    },
    computed: {

    }

}
</script>
<style scoped>

</style>

