<template>
    <div>
        <a-form-model :loading="loading" :model="formData" ref="dataForm" :label-col="dialogFormLabelCol" :wrapper-col="dialogFormWrapperCol" :rules="formRules">
            <a-form-model-item label="备注" prop="remark">
                <a-textarea
                    v-model="formData.remark"
                    placeholder="备注"
                    :auto-size="{ minRows: 3, maxRows: 5 }"
                />
            </a-form-model-item>


            <a-form-model-item :wrapper-col="{ span: 14, offset: 4 }">
                <a-button :loading="loading" type="primary" @click="submitData">
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
    name: 'updateRemark',
    components: {},
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
            dialogFormLabelCol: { span: 4 },
            dialogFormWrapperCol: { span: 14 },
            formRules: {

            },
            loading :false,
        }
    },
    methods: {
        initForm(){
            this.formData= {
                remark:'',
            };
        },
        submitData(){
            let that = this;
            this.$refs.dataForm.validate((valid) => {
                if (valid) {
                    if(that.id){
                        this.loading = true;
                        that.formData.id = that.id;
                        axios({
                            // 默认请求方式为get
                            method: 'post',
                            url: '/api/approval/updateInfo',
                            // 传递参数
                            data: that.formData,
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
                            this.loading = false;
                            this.$message.error('请求失败');
                        });
                    }else{
                        this.$message.error('记录id不能为空');
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
                url: '/api/approval/getInfo',
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
                    remark: res.data.appr_remark,
                }
            }).catch(error => {
                this.$message.error('请求失败');
            });
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

