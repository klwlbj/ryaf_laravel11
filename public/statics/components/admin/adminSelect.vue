<template>
    <a-select v-model="id" show-search placeholder="请选择用户" :max-tag-count="1"
              :mode="mode" style="width: 200px;" allow-clear @change="handleChange" option-filter-prop="label">
        <a-select-option v-for="(item, key) in list" :key="key" :value="item.admin_id" :label="item.admin_name">
            {{ item.admin_name }}
        </a-select-option>
    </a-select>
</template>

<script>
module.exports = {
    name: 'adminSelect',
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
        all: {
            default:function(){
                return 0
            },
        },
    },
    data () {
        return {
            list:[],
            id:undefined,
        }
    },
    methods: {
        getList () {
            axios({
                // 默认请求方式为get
                method: 'post',
                url: '/api/admin/getAllList',
                // 传递参数
                data: {
                    all:this.all
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
            this.$emit('change',value);
        },
        clearData(){
            this.id = undefined;
        },
        setValue(value){
            this.id = value;
            this.$emit('change',value);
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

