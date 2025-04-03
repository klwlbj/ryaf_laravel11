<template>
    <a-select v-model="id" class="applySelect" show-search placeholder="请选择分类" :max-tag-count="1"
              :mode="mode" style="width: 300px;" allow-clear @change="handleChange" option-filter-prop="label"
              >
        <a-select-option v-for="(item, key) in list" :key="key" :value="item.maap_id" :label="item.mate_name">
            {{ item.mate_name }}
            <div>
                <span>出库数量：{{ item.maap_number }}</span><span style="margin-left: 10px">申请人：{{item.admin_name}}</span>
            </div>
        </a-select-option>
    </a-select>
</template>

<script>
module.exports = {
    name: 'applySelect',
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
        }
    },
    methods: {
        getList () {
            axios({
                // 默认请求方式为get
                method: 'post',
                url: '/api/materialApply/getSelectList',
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
                this.list = res.data
            }).catch(error => {
                this.$message.error('请求失败');
            });
        },
        handleChange(value){
            let selectedObject = this.list.find(option => option.maap_id === value);
            // console.log('Selected object:', selectedObject);

            this.$emit('change',value,selectedObject);
        },
        clearData(){
            this.id = undefined;
            this.getList();
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
.applySelect > div{
    height: 60px;
}
</style>

