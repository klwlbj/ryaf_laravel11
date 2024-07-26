<template>
    <div>
        <a-form-model :loading="loading" :model="formData" ref="dataForm" :label-col="dialogFormLabelCol" :wrapper-col="dialogFormWrapperCol" :rules="formRules">
            <a-form-model-item label="关联明细">
                <a-table  :columns="columns" :data-source="formData.detail" :loading="loading"  :row-key="(record, index) => { return index }" :pagination="false">
                    <div slot="orderId" slot-scope="text, record">
                        <a-input id="inputOrderId" v-model="record.orderId" />
                    </div>

                </a-table>
                <a-button type="primary" block @click="detailAdd">新增</a-button>
            </a-form-model-item>

            <a-form-model-item :wrapper-col="{ span: 14, offset: 4 }">
                <a-button type="primary" @click="submitData">
                    确认
                </a-button>
                <a-button style="margin-left: 10px;" @click="$emit('close')">
                    取消
                </a-button>
            </a-form-model-item>

        </a-form-model>
    </div>
</template>

<script>
module.exports = {
    name: 'advancedOrderLink',
    components: {
    },
    props: {
        id: {
            default:function(){
                return null
            },
        },
    },
    data () {
        return {
            formData: {

            },
            columns:[
                {
                    title: '订单号',
                    dataIndex: 'orderId',
                    scopedSlots: { customRender: 'orderId' },
                },
            ],
            dialogFormLabelCol: { span: 4 },
            dialogFormWrapperCol: { span: 14 },
            formRules: {

            },
            loading :false,
            materialId:null,
        }
    },
    methods: {
        initForm(){
            this.formData= {
                detail:[
                    {orderId:undefined},
                ],
                remark:'',
            };
        },
        submitData(){
            let that = this;
            this.$refs.dataForm.validate((valid) => {
                if (valid) {
                    let params = {
                        detail:JSON.stringify(that.formData.detail),
                    }
                  // this.id = 2111
                    params.id = this.id;
                    axios({
                        // 默认请求方式为get
                        method: 'post',
                        url: '/api/advancedOrder/link',
                        // 传递参数
                        data: params,
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
                        this.initForm();
                        that.$emit('submit');
                    }).catch(error => {
                        this.$message.error('请求失败');
                    });
                }else{
                    this.$message.error('表单验证失败');
                }

            })
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
                url: '/api/advancedOrder/getLinkInfo',
                // 传递参数
                data: {
                    id:id
                },
                responseType: 'json',
                headers:{
                    'Content-Type': 'multipart/form-data'
                }
            }).then(response => {
                this.loading = false;
                let res = response.data;
                console.log(res)
                if(res.code !== 0){
                    this.$message.error(res.message);
                    return false;
                }
                this.formData = {
                    detail: [],
                }
                for (let item of res.data.detail){
                  console.log(item)
                    this.formData.detail.push({
                        orderId:item,
                    })
                }
              console.log(this.formData)

                this.$forceUpdate();
            }).catch(error => {
                this.$message.error('请求失败');
            });
        },
        detailAdd(){
            this.formData.detail.push({orderId:undefined})
        }
    },
    created () {
      console.log(this.id)

        if(this.id){
            this.getDetail(this.id);
        }else{
            this.initForm();
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

