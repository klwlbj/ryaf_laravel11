<template>
    <a-select v-model="id"  class="relationSelect" show-search placeholder="请选择关联申领单" :max-tag-count="3"
              :mode="mode" style="width: 300px;" allow-clear @change="handleChange" option-filter-prop="label">
        <a-select-option v-for="(item, key) in list" :key="key" :value="item.id" :label="item.name">
            {{ item.name }}
            <div>
                {{item.sn}}
            </div>
        </a-select-option>
    </a-select>
</template>

<script>
module.exports = {
    name: 'applyRelationSelect',
    components: {},
    props: {
        mode: {
            default:function(){
                return 'multiple'
            },
        },
        defaultData: {
            default:function(){
                return undefined
            },
        },
        materialIds: {
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
                url: '/api/materialApply/getRelationList',
                // 传递参数
                data: {
                    'material_ids' : this.materialIds
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
        materialIds (newData,oldData) {
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
.relationSelect > div > div > ul > li{
    height: unset;
}
</style>

