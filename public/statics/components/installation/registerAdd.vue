<template>
    <div>
        <a-form-model :loading="loading" :model="formData" ref="dataForm" :label-col="dialogFormLabelCol" :wrapper-col="dialogFormWrapperCol" :rules="formRules">
            <a-form-model-item label="预装日期" prop="datetime">
                <a-date-picker show-time format="YYYY-MM-DD HH:mm:ss" v-model:value="formData.datetime"/>
            </a-form-model-item>

            <a-form-model-item label="监控中心" prop="node_id">
                <node-cascader @change="nodeChange"></node-cascader>
            </a-form-model-item>

            <a-form-model-item label="详细地址" prop="address">
                    <div v-for="(item,index) in formData.address_list" :key="index">
                        <div style="color:red;font-weight: bold">地址{{ index+1 }}：</div>
                        <standard-address-select :id="item.code" @change="(value) => {addressChange(value,index)}">

                        </standard-address-select>
                    </div>
                <a-button type="link" @click="addressAdd" block>新增地址</a-button>
            </a-form-model-item>

            <a-form-model-item label="单位/用户名" prop="user_name">
                <a-input v-model="formData.user_name" />
            </a-form-model-item>

            <a-form-model-item label="联系电话" prop="user_phone">
                <a-input v-model="formData.user_phone" />
            </a-form-model-item>

            <a-form-model-item label="客户类型" prop="user_type">
                <a-radio-group v-model="formData.user_type">
                    <a-radio :value="1">
                        2B
                    </a-radio>
                    <a-radio :value="2">
                        2C
                    </a-radio>
                </a-radio-group>
            </a-form-model-item>

            <a-form-model-item label="单价" prop="price">
                <a-input-number v-model="formData.price" :step="0.01" @change="totalPriceChange(formData.price,formData.install_count)"/>
            </a-form-model-item>

            <a-form-model-item label="安装台数" prop="install_count">
                <a-input-number v-model="formData.install_count" @change="totalPriceChange(formData.price,formData.install_count)"/>
            </a-form-model-item>

            <a-form-model-item label="赠送台数" prop="given_count">
                <a-input-number v-model="formData.given_count"/>
            </a-form-model-item>

            <a-form-model-item label="付款方式" prop="pay_way">
                <a-radio-group v-model="formData.pay_way">
                    <!--                    <a-radio :value="1">-->
                    <!--                        微信-->
                    <!--                    </a-radio>-->
                    <!--                    <a-radio :value="2">-->
                    <!--                        支付宝-->
                    <!--                    </a-radio>-->
                    <a-radio :value="3">
                        银行
                    </a-radio>
                    <a-radio :value="4">
                        现金
                    </a-radio>
                    <a-radio :value="5">
                        二维码
                    </a-radio>
                </a-radio-group>
            </a-form-model-item>

            <a-form-model-item label="总金额" prop="total_price">
                <a-input-number v-model="formData.total_price" :step="0.01"/>
            </a-form-model-item>


            <a-form-model-item label="备注" prop="remark">
                <a-textarea
                    v-model="formData.remark"
                    placeholder="备注"
                    :auto-size="{ minRows: 3, maxRows: 5 }"
                />
            </a-form-model-item>

            <a-form-model-item label="已交付数" prop="delivery_count">
                <a-input-number v-model="formData.delivery_count"/>
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
    name: 'installationRegisterAdd',
    components: {
        "standard-address-select":  httpVueLoader('/statics/components/installation/standardAddressSelect.vue'),
        "node-cascader":  httpVueLoader('/statics/components/node/nodeCascader.vue')
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
            imageList: [],
            listImageList: [],
            dialogFormLabelCol: { span: 4 },
            dialogFormWrapperCol: { span: 14 },
            formRules: {
                    user_name: [{ required: true, message: '请输入单位/用户名', trigger: 'blur' }],
                user_phone: [{ required: true, message: '请输入联系方式', trigger: 'blur' }],
            },
            loading :false,
            cid:undefined
        }
    },
    methods: {
        moment,
        initForm(){
            this.formData= {
                datetime:moment().format("YYYY-MM-DD HH:mm:ss"),
                node_id:undefined,
                address_list:[
                    {
                        code:'',
                        standard_address:'',
                        addr_generic_name:'',
                        addr_room:'',
                        install_location:''
                    }
                ],
                user_name : '',
                user_phone : '',
                user_type : 1,
                price : 0,
                install_count:0,
                given_count:0,
                pay_way:3,
                total_price:0,
                remark:'',
                delivery_count:0,
            };
        },
        submitData(){
            let that = this;
            this.$refs.dataForm.validate((valid) => {
                if (valid) {
                    let form = JSON.parse(JSON.stringify(that.formData));
                    form.address_list = JSON.stringify(form.address_list);
                    if(that.id){
                        form.id = that.id;
                        axios({
                            // 默认请求方式为get
                            method: 'post',
                            url: '/api/installationRegister/update',
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
                        axios({
                            // 默认请求方式为get
                            method: 'post',
                            url: '/api/installationRegister/add',
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
                url: '/api/installationRegister/getInfo',
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
                    datetime:res.data.inre_datetime,
                    node_id:res.data.inre_node_id,
                    address_list:res.data.address_list,
                    user_name : res.data.inre_user_name,
                    user_phone : res.data.inre_user_phone,
                    user_type : res.data.inre_user_type,
                    price : res.data.inre_price,
                    install_count:res.data.inre_install_count,
                    given_count:res.data.inre_given_count,
                    pay_way:res.data.inre_pay_way,
                    total_price:res.data.inre_total_price,
                    remark:res.data.inre_remark,
                    delivery_count:res.data.inre_delivery_count,
                }
            }).catch(error => {
                this.$message.error('请求失败');
            });
        },
        addressAdd(){
            this.formData.address_list.push({
                code:'',
                standard_address:'',
                addr_generic_name:'',
                addr_room:'',
                install_location:''
            });
        },
        totalPriceChange(price,count){
            this.formData.total_price = price * count;
        },
        nodeChange(value){
            // console.log(value);
            this.formData.node_id = value;
        },
        addressChange(value,index){
            this.formData.address_list[index] = value;
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

