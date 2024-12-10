<template>
    <div>
        <a-form layout="inline" >
            <a-form-item label="经度">
                <a-input v-model="formData.lng" placeholder="经度" style="width: 100px;" />
            </a-form-item>

            <a-form-item label="纬度">
                <a-input v-model="formData.lat" placeholder="纬度" style="width: 100px;" />
            </a-form-item>


        </a-form>
        <div id="map" ref="map" style="width: 100%;height: 500px;margin-top: 10px">

        </div>

        <div style="margin-top: 10px;text-align: center">
            <a-button type="primary" @click="submitData">
                确认
            </a-button>
            <a-button style="margin-left: 10px;" @click="$emit('close')">
                取消
            </a-button>
        </div>
    </div>
</template>

<script>
module.exports = {
    name: 'maintainGetGps',
    components: {

    },
    props: {
        lng: {
            default:function(){
                return '113.280637'
            },
        },
        lat: {
            default:function(){
                return '23.125178'
            },
        },
    },
    data () {
        return {
            formData: {
                lng : '',
                lat : ''
            },
            dialogFormLabelCol: { span: 4 },
            dialogFormWrapperCol: { span: 14 },
            formRules: {

            },
            loading :false,
        }
    },
    methods: {
        initMap() {
            let that = this;
            let map = new AMap.Map('map', {
                resizeEnable: true,
                zoom: 16, // 地图显示的缩放级别
                center: [this.lng ? this.lng : '113.280637', this.lat ? this.lat : '23.125178'] // 地图中心点坐标
            });

            map.on('click', function(e) {
                that.formData.lng = e.lnglat.getLng();
                that.formData.lat = e.lnglat.getLat();
            });
        },
        initForm(){
            this.formData= {
                lng : this.lng,
                lat : this.lat
            };
        },
        submitData(){
            this.$emit('update',this.formData.lng,this.formData.lat)
        }

    },
    created () {
        // this.initChart();
    },
    mounted(){
        this.initMap();
        this.initForm();
    },
    watch: {
        lng (newData,oldData) {
            if(newData === oldData){
                return false
            }
            this.formData.lng = newData;
        },
        lat (newData,oldData) {
            if(newData === oldData){
                return false
            }
            this.formData.lat = newData;
        },
    },
    computed: {

    }

}
</script>
<style scoped>

</style>

