<template>
    <div>
        <a-spin tip="加载中" :spinning="loading">
            <a-form-model :loading="loading" :model="formData" ref="dataForm" :label-col="dialogFormLabelCol" :wrapper-col="dialogFormWrapperCol" :rules="formRules">

                <a-descriptions bordered size="small">
                    <a-descriptions-item label="单位类型" >
                        <a-select size="small" v-model="formData.type" show-search placeholder="单位类型" :max-tag-count="1"
                                  style="width: 200px;" allow-clear>
                            <a-select-option v-for="(item,index) in ['点击选择','商铺','客栈','餐饮','酒吧','家庭','聚类市场','仓库','养老院','汗蒸桑拿','出租屋/民宿','小幼儿园','网吧','酒店宾馆','景区']" :value="item" :key="index">
                                {{item}}
                            </a-select-option>

                        </a-select>
                    </a-descriptions-item>
                    <a-descriptions-item label="经营项目"  :span="2">
                        <a-select  size="small" v-model="formData.type2" show-search placeholder="单位类型" :max-tag-count="1"
                                   style="width: 200px;" allow-clear>
                            <a-select-option v-for="(item,index) in ['点击选择','餐饮行业','学校','幼儿园','养老院','网吧','酒吧','酒店','宾馆','招待所','仓储','批发市场','商场','电影院','职工住宅','经营性住宅','工厂','厂房','客运站','办公场所','设备用房','其他类别']" :value="item" :key="index">
                                {{item}}
                            </a-select-option>

                        </a-select>
                    </a-descriptions-item>
                    <a-descriptions-item label="单位地址"  :span="3">
                        <a-input  size="small"  v-model="formData.name" />
                    </a-descriptions-item>
                    <a-descriptions-item label="详细地址"  :span="2">
                        <a-input  size="small"  v-model="formData.address" />
                    </a-descriptions-item>
                    <a-descriptions-item label="经纬度">
                        <span><a-input size="small" style="width: 90px" v-model="formData.lng" /></span>
                        <span style="margin-left: 10px"><a-input size="small" style="width: 90px" v-model="formData.lat" /></span>
                        <span style="margin-left: 10px;cursor: pointer" @click="onMap(formData.lng,formData.lat)"><a-icon type="global"/></span>
                    </a-descriptions-item>
                    <a-descriptions-item label="所属监控中心"  :span="2">
                        <node-cascader :default-data="nodeId" @change="nodeChange"></node-cascader>
                    </a-descriptions-item>
                    <a-descriptions-item label="常驻人数"  :span="1">
                        <a-input-number  size="small" id="inputNumber" v-model="formData.people_count" />
                    </a-descriptions-item>
                    <a-descriptions-item label="常驻人群特征"  :span="3">
                        <span v-for="(item,index) in formData.people_feature" style="margin-left: 5px"><a-switch v-model="item.value" :un-checked-children="item.label" :checked-children="item.label" :key="index" /></span>

                    </a-descriptions-item>
                    <a-descriptions-item label="特殊标签"  :span="3">
                        <span v-for="(item,index) in formData.tags" style="margin-left: 5px"><a-switch v-model="item.value" :un-checked-children="item.label" :checked-children="item.label" :key="index" /></span>
                    </a-descriptions-item>
                </a-descriptions>

                <a-form-model-item style="margin-top: 10px;text-align: center" :wrapper-col="{ span: 14, offset: 4 }">
                    <a-button type="primary" @click="submitData">
                        确认
                    </a-button>
                    <a-button style="margin-left: 10px;" @click="$emit('close')">
                        取消
                    </a-button>
                </a-form-model-item>

            </a-form-model>

            <a-modal :mask-closable="false" v-model="dialogFormVisible"
                     title="地图点选GPS"
                     width="800px" :footer="null">
                <get-gps
                    style="height: 600px;overflow: auto"
                    ref="getGps"
                    :lng="lng"
                    :lat="lat"
                    @update="updateMap"
                    @close="dialogFormVisible = false;"
                >
                </get-gps>
            </a-modal>
        </a-spin>
    </div>
</template>

<script>
module.exports = {
    name: 'maintainUpdatePlace',
    components: {
        "node-cascader":  httpVueLoader('/statics/components/node/nodeCascader.vue'),
        "get-gps":  httpVueLoader('/statics/components/map/getGps.vue'),
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
            dialogFormLabelCol: { span: 2 },
            dialogFormWrapperCol: { span: 14 },
            dialogFormVisible: false,
            formRules: {

            },
            loading :false,
            nodeId:undefined,
            lng:'',
            lat:''
        }
    },
    methods: {
        initForm(){
            this.formData= {
                type : '点击选择',
                type2 : '点击选择',
                name: '',
                address:'',
                lng:'',
                lat:'',
                node_id:undefined,
                people_count:1
            };
        },
        submitData(){
            let that = this;
            let form = JSON.parse(JSON.stringify(this.formData))
            this.$refs.dataForm.validate((valid) => {
                if (valid) {
                    if(that.id){
                        form.id = that.id;
                        form.people_feature = JSON.stringify(form.people_feature);
                        form.tags = JSON.stringify(form.tags);
                        axios({
                            // 默认请求方式为get
                            method: 'post',
                            url: '/api/maintain/updatePlace',
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
                url: '/api/maintain/getPlaceInfo',
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
                    type : res.data.plac_type,
                    type2 : res.data.plac_type2,
                    name: res.data.plac_name,
                    address: res.data.plac_address,
                    lng: res.data.plac_lng,
                    lat: res.data.plac_lat,
                    node_id: res.data.plac_node_id,
                    people_count: res.data.plac_people_count,
                    people_feature: res.data.people_feature,
                    tags: res.data.tags,
                }

                this.nodeId = res.data.plac_node_arr;

            }).catch(error => {
                this.$message.error('请求失败');
            });
        },
        nodeChange(value){
            this.formData.node_id = value;
        },
        onMap(lng,lat){
            this.lng = lng;
            this.lat = lat;
            this.dialogFormVisible = true;
        },
        updateMap(lng,lat){
            this.formData.lng = lng;
            this.formData.lat = lat;
            this.dialogFormVisible = false;
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

