<template>
    <a-select
        v-model="id"
        show-search
        placeholder="输入关键字"
        style="width: 400px"
        :default-active-first-option="false"
        :show-arrow="false"
        :filter-option="false"
        :not-found-content="null"
        @search="getList"
        @change="handleChange"
    >
        <a-select-option v-for="(item, key) in list" :key="key" :value="item.MPDM" :label="item.MPDZMC">
            {{ item.MPDZMC }}
        </a-select-option>
    </a-select>
</template>

<script>
module.exports = {
    name: 'standardAddressSelect',
    components: {},
    props: {
        index: {
            default:function(){
                return 0
            },
        },
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
        getList (keyword) {
            if(!keyword){
                return keyword;
            }
            axios({
                // 默认请求方式为get
                method: 'post',
                url: '/api/address/getStandardAddress',
                // 传递参数
                data: {
                    keyword:keyword
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
            let selectData = this.list.filter(item => item.MPDM === value);

            let res = {
                code : selectData.MPDM,
                standard_address : selectData.MPDZMC,
                addr_generic_name : '',
                addr_room:'',
                install_location:''
            }

            this.$emit('change',res,this.index);
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

