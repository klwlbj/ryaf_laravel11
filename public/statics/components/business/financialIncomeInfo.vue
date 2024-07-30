<template>
    <div>
      <a-table :columns="columns" :data-source="listSource" :loading="loading" :row-key="(record, index) => { return index }">

      </a-table>
    </div>
</template>

<script>
module.exports = {
    name: 'financialIncomeInfo',
    components: {
    },
    props: {
        id: {
            default:function(){
                return null
            },
        },
      orderProjectType: {
            default:function(){
                return null
            },
        },
    },
  data () {
    return {
      formData: {

      },
      imageList: [],
      listImageList: [],
      areaList: [],
      dialogFormLabelCol: { span: 4 },
      dialogFormWrapperCol: { span: 14 },
      loading :false,
      cid:undefined,
      listSource: [],
      columns:[
        {
          title: '支付时间',
          dataIndex: 'amount',
          width: 100
        },
        {
          title: '支付金额',
          dataIndex: 'date',
          width: 80
        },
      ],
    }
  },
    methods: {
        initForm(){
          this.formData= {
            address:'',
          };
        },
        getDetail(id){
            if(!id){
                this.$message.error('id不能为空');
                return false;
            }
            this.loading = true;
            axios({
                // 默认请求方式为get
                method: 'post',
                url: '/api/financialIncome/getStageInfo',
                // 传递参数
                data: {
                    id:id,
                    order_project_type:this.orderProjectType,
                },
                responseType: 'json',
                headers:{
                    'Content-Type': 'multipart/form-data'
                }
            }).then(response => {
                this.loading = false;
                let res = response.data;
                this.listSource = res.data
                // this.$forceUpdate();
            }).catch(error => {
                this.$message.error('请求失败');
            });
        },

    },
    created () {
        if(this.id) {
          this.getDetail(this.id);
        }
    },
    watch: {
        id (newData,oldData) {
            if(newData === oldData){
                return false
            }

            if(newData === null){
                this.initForm();

                return false;
            }

            this.getDetail(newData);
        }
    },
    computed: {

    }

}
</script>
<style scoped>

</style>

