<template>
    <a-select class="materialFlowSelect" v-model="id" show-search placeholder="请选择出库" :max-tag-count="1"
              :mode="mode" :style="'width:' + width + 'px'" allow-clear @change="handleChange" option-filter-prop="label">
        <a-select-option v-for="(item, key) in list" :key="key" :value="item.mafl_id" :label="item.mafl_material_name" :title="item.mafl_material_name">
            {{ item.mafl_material_name }}
            <div>
                出库时间：{{item.mafl_datetime}}
            </div>
        </a-select-option>
    </a-select>
</template>

<script>
module.exports = {
    name: 'materialFlowSelect',
    components: {},
    props: {
        width: {
            default:function(){
                return 200
            },
        },
        mode: {
            default:function(){
                return 'default'
            },
        },
        type: {
            default:function(){
                return undefined
            },
        },
        materialId: {
            default:function(){
                return undefined
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
            let formData = {};
            if(this.type){
                formData.type = this.categoryId;
            }

            if(this.materialId){
                formData.material_id = this.materialId;
            }

            axios({
                // 默认请求方式为get
                method: 'post',
                url: '/api/materialFlow/getList',
                // 传递参数
                data: formData,
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
                this.list = res.data.list;
            }).catch(error => {
                this.$message.error('请求失败');
            });
        },
        handleChange(value){
            this.$emit('change',value);
        },
        clearData(){
            this.id = undefined;
            this.$emit('change',value);
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

            if(!newData){
                this.id = undefined;
                return false
            }

            this.id = newData;
            // this.$emit('change',newData);
        },
        type (newData,oldData) {
            if(newData === oldData){
                return false
            }

            this.getList();
        },
        materialId (newData,oldData) {
            if(newData === oldData){
                return false
            }

            this.getList();
        },
    },
    computed: {

    }

}
</script>
<style scoped>
.materialFlowSelect > div > div > ul > li{
    height: unset;
}

.materialFlowSelect > div{
    height: 60px;
}
</style>

