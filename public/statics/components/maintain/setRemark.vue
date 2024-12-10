<template>
    <div>
        <a-form-model :loading="loading" :model="formData" ref="dataForm" :label-col="dialogFormLabelCol" :wrapper-col="dialogFormWrapperCol" :rules="formRules">
            <a-form-model-item label="标注" prop="extra_remark">
                <div v-for="(item) in formData.extra_remark">
                    <a-input v-model="item.value" />
                </div>

                <a-button type="primary" block @click="remarkAdd">新增标注</a-button>
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
    name: 'maintainSetRemark',
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
                extra_remark : [
                    ''
                ]
            },
            imageList: [],
            listImageList: [],
            dialogFormLabelCol: { span: 4 },
            dialogFormWrapperCol: { span: 14 },
            formRules: {

            },
            loading :false,
            // isLeader : false,
            permission:[]
        }
    },
    methods: {

        initForm(){
            this.formData= {
                extra_remark : []
            };
        },
        submitData(){
            let that = this;
            let form = {
                extra_remark : JSON.stringify(this.formData.extra_remark)
            }
            this.$refs.dataForm.validate((valid) => {
                if (valid) {
                    if(that.id){
                        form.id = that.id;
                        axios({
                            // 默认请求方式为get
                            method: 'post',
                            url: '/api/maintain/setRemark',
                            // 传递参数
                            data: form,
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
                            that.$emit('update');
                        }).catch(error => {
                            this.$message.error('请求失败');
                        });
                    }else{
                        this.$message.error('id不能为空');
                    }
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
                url: '/api/maintain/getRemarkInfo',
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
                if(res.code !== 0){
                    this.$message.error(res.message);
                    return false;
                }
                this.formData = {
                    extra_remark:res.data.extra_remark,
                }

            }).catch(error => {
                this.$message.error('请求失败');
            });
        },
        remarkAdd(){
            this.formData.extra_remark.push({
                value:'',
            })
        }
    },
    created () {
        this.initForm();
        if(this.id){
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

