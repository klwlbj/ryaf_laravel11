<template>
    <div>
        <a-form-model :loading="loading" :model="formData" ref="dataForm" :label-col="dialogFormLabelCol" :wrapper-col="dialogFormWrapperCol" :rules="formRules">
            <a-form-model-item label="街道" prop="order_area_id">
                <a-cascader v-model="formData.order_area_id" :options="areaList" placeholder="区-街道-村委/居委" />
            </a-form-model-item>

            <a-form-model-item label="详细地址" prop="order_address">
                <a-input v-model="formData.order_address" />
            </a-form-model-item>

            <a-form-model-item label="名称" prop="order_user_name">
                <a-input v-model="formData.order_user_name"/>
            </a-form-model-item>

            <a-form-model-item label="联系方式" prop="order_phone">
                <a-input-number v-model="formData.order_phone" size="large" :max="10000000000000000"/>
            </a-form-model-item>

          <a-form-model-item label="收款日期" prop="order_actual_delivery_date">
              <a-date-picker show-time format="YYYY-MM-DD HH:mm:ss" :default-value="moment().format('YYYY-MM-DD HH:mm:ss')" placeholder="Select Time" v-model="formData.order_actual_delivery_date"/>
          </a-form-model-item>

          <a-form-model-item label="发生日期" prop="order_prospecter_date">
              <a-date-picker show-time format="YYYY-MM-DD HH:mm:ss" :default-value="moment().format('YYYY-MM-DD HH:mm:ss')" placeholder="Select Time" v-model="formData.order_prospecter_date"/>
          </a-form-model-item>

            <a-form-model-item label="预计安装总数" prop="order_delivery_number">
                <a-input v-model="formData.order_delivery_number" />
            </a-form-model-item>

          <a-form-model-item label="分期数" prop="order_pay_cycle">
                <a-input v-model="formData.order_pay_cycle" />
            </a-form-model-item>

            <a-form-model-item label="应收金额" prop="order_account_receivable">
                <a-input v-model="formData.order_account_receivable" />
            </a-form-model-item>

          <a-form-model-item label="实收金额" prop="order_funds_received">
                <a-input v-model="formData.order_funds_received" />
            </a-form-model-item>

          <a-form-model-item label="收款方式" prop="order_contract_type">
            <a-radio-group v-model="formData.order_contract_type">
              <a-radio :value="1">
                以租代购
              </a-radio>
              <a-radio :value="2">
                移动赠机
              </a-radio>
              <a-radio :value="3">
                异网接入
              </a-radio>
            </a-radio-group>
          </a-form-model-item>

            <a-form-model-item label="收款方式" prop="order_pay_way">
                <a-radio-group v-model="formData.order_pay_way">
                    <a-radio :value="3">
                        对公转账
                    </a-radio>
                    <a-radio :value="4">
                        现金
                    </a-radio>
                    <a-radio :value="5">
                        扫二维码
                    </a-radio>
                </a-radio-group>
            </a-form-model-item>

          <a-form-model-item label="项目类型" prop="order_project_type">
            <a-radio-group v-model="formData.order_project_type">
              <a-radio :value="1">
                智慧用电
              </a-radio>
              <a-radio :value="2">
                智慧燃气
              </a-radio>
              <a-radio :value="3">
                用传装置
              </a-radio>
              <a-radio :value="4">
                消防维保
              </a-radio>
              <a-radio :value="5">
                消防工程
              </a-radio>
              <a-radio :value="6">
                消防站建设
              </a-radio>
              <a-radio :value="7">
                其他
              </a-radio>
            </a-radio-group>
          </a-form-model-item>

            <a-form-model-item label="备注" prop="remark">
                <a-textarea
                    v-model="formData.order_remark"
                    placeholder="备注"
                    :auto-size="{ minRows: 3, maxRows: 5 }"
                />
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
    name: 'otherOrderAdd',
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
            imageList: [],
            listImageList: [],
            areaList: [],
            dialogFormLabelCol: { span: 4 },
            dialogFormWrapperCol: { span: 14 },
            formRules: {
              order_user_name: [{ required: true, message: '请输入单位名称', trigger: 'blur' }],
              order_address: [{ required: true, message: '请输入详细地址', trigger: 'blur' }],
              order_phone: [{ required: true, message: '请输入联系方式', trigger: 'blur' }],
              order_delivery_number: [{ required: true, message: '请输入预计安装总数', trigger: 'blur' }],
              order_account_receivable: [{ required: true, message: '请输入应收金额', trigger: 'blur' }],
            },
            loading :false,
            cid:undefined
        }
    },
    methods: {
      moment,
        initForm(){
            // 获取区域和街道等
            this.getEnumList()

            this.formData= {
              order_area_id:[],
                // community:'',
              order_address:'',
              order_user_name:'',
              order_phone:'',
              order_remark:'',
              order_actual_delivery_date:moment().format("YYYY-MM-DD HH:mm:ss"),
              order_prospecter_date:moment().format("YYYY-MM-DD HH:mm:ss"),
              order_delivery_number:0,
              order_project_type:1,
              order_pay_cycle:1,
              order_pay_way:3,
              order_account_receivable:0,
              order_funds_received:0,
              order_contract_type:1,

            };
        },
        // 获取枚举列表
        getEnumList () {
            axios({
                // 默认请求方式为get
                method: 'post',
                url: '/api/area/getList',
                // 传递参数
                responseType: 'json',
                headers:{
                    'Content-Type': 'multipart/form-data'
                }
            }).then(response => {
                let res = response.data;
                this.areaList = res.data.areaList
            }).catch(error => {
                this.$message.error('请求失败');
            });
        },
        submitData(){
            let that = this;
            this.$refs.dataForm.validate((valid) => {
                if (valid) {
                    if(that.id){
                        that.formData.id = that.id;
                        axios({
                            method: 'post',
                            url: '/api/otherOrder/update',
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
                            that.$emit('update');
                        }).catch(error => {
                            this.$message.error('请求失败');
                        });
                    }else{
                        axios({
                            method: 'post',
                            url: '/api/otherOrder/add',
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
                            that.$emit('add');
                        }).catch(error => {
                            this.$message.error('请求失败');
                        });
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
                url: '/api/otherOrder/getInfo',
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
                  order_area_id:res.data.area_all_id,
                  order_address:res.data.order_address,
                  order_user_name:res.data.order_user_name,
                  order_phone:res.data.order_phone,
                  order_remark:res.data.order_remark,
                  order_actual_delivery_date:res.data.order_actual_delivery_date,
                  order_prospecter_date:res.data.order_prospecter_date,
                  order_delivery_number:res.data.order_delivery_number,
                  order_project_type:res.data.order_project_type,
                  order_pay_cycle:res.data.order_pay_cycle,
                  order_pay_way:res.data.order_pay_way,
                  order_account_receivable:res.data.order_account_receivable,
                  order_funds_received:res.data.order_funds_received,
                  order_contract_type:res.data.order_contract_type,
                }
            }).catch(error => {
                this.$message.error('请求失败');
            });
        }
    },
    created () {
      console.log(this.id)

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

