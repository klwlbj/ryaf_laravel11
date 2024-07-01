<template>
    <a-select v-model="id" show-search placeholder="请选择" :max-tag-count="1"
              :mode="mode" style="width: 200px;" allow-clear @change="handleChange" option-filter-prop="label">
        <a-select-option v-for="(item, key) in list" :key="key" :value="item.masp_id" :label="item.masp_name">
            {{ item.masp_name }}
        </a-select-option>
    </a-select>
</template>

<script>
module.exports = {
    name: 'specificationSelect',
    components: {},
    props: {
        mode: {
            default:function(){
                return 'default'
            },
        },
        categoryId: {
            default:function(){
                return null
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
        getList (categoryId) {
            axios({
                // 默认请求方式为get
                method: 'post',
                url: '/admin/materialSpecification/getAllList',
                // 传递参数
                data: {
                    category_id:categoryId
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
        }
    },
    created () {
        if(this.mode === 'default'){
            this.id = undefined;
        }else{
            this.id = [];
        }
        if(this.categoryId){
            this.getList(this.categoryId);
        }
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
        categoryId(newData,oldData){
            if(newData === oldData){
                return false
            }

            if(!newData){
                this.list = [];
                return false;
            }

            this.getList(newData);
        }
    },
    computed: {

    }

}
</script>
<style scoped>

</style>

