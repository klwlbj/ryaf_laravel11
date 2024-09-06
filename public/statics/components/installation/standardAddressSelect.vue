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
            let selectList = this.list.filter(item => item.MPDM === value);
            let selectData = selectList[0] ? selectList[0] : {}

            let res = {
                code : selectData.MPDM,
                standard_address : selectData.MPDZMC,
                addr_generic_name : '',
                addr_room:'',
                install_location:''
            }

            this.$emit('change',res);
        },
        clearData(){
            this.id = undefined;
        },
        setDefaultValue(data){
            if(!data.code){
                return false;
            }

            this.list = [
                {
                    'MPDM' : data.code,
                    'MPDZMC' : data.standard_address
                }
            ]

            this.id = data.code;
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
            // console.log(this.defaultData);
            this.setDefaultValue(this.defaultData);
            // this.id = this.defaultData;
            // this.$emit('change',this.defaultData);
        }
    },
    watch: {
        defaultData (newData,oldData) {
            if(newData === oldData){
                return false
            }
            // console.log(newData);

            this.setDefaultValue(newData);
        }
    },
    computed: {

    }

}
</script>
<style scoped>

</style>

