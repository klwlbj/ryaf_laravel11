<template>
    <div>
      <a-table :columns="columns" :data-source="listSource" :loading="loading" :row-key="(record, index) => { return index }">

      </a-table>
    </div>
</template>

<script>
module.exports = {
    name: 'financialArrearsInfo',
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
          title: '1个月内（30天内）',
          dataIndex: 'arrears_1',
          width: 80
        },
        {
          title: '1-2个月内（30-60天内）',
          dataIndex: 'arrears_2',
          width: 80
        },
        {
          title: '2-3个月内（60-90天内）',
          dataIndex: 'arrears_3',
          width: 80
        },
        {
          title: '3-4个月内（90-120天内）',
          dataIndex: 'arrears_4',
          width: 80
        },
        {
          title: '4-5个月内（120-150天内）',
          dataIndex: 'arrears_5',
          width: 80
        },
        {
          title: '5-12个月内（150天至本年内）',
          dataIndex: 'arrears_6',
          width: 80
        },
        {
          title: '12个月以上',
          dataIndex: 'arrears_7',
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
                url: '/api/financialIncome/getArrearsInfo',
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

