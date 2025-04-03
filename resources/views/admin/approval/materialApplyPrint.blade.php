<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>审批单</title>
    <link rel="stylesheet" type="text/css"  href="{{asset('statics/css/antd.min.css')}}">
    <script src="{{asset('statics/js/vue.js')}}"></script>
    <script src="{{asset('statics/js/antd.min.js?v=1.7.2')}}"></script>
    <script src="{{asset('statics/js/axios.min.js')}}"></script>
    <style>
        .a4-container {
            width: 700px; /* A4 宽度 */
            margin: 0 auto;
            /*border: 1px solid #ccc;*/
            padding: 20px;
            box-sizing: border-box;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin: 20px 0;
        }
        th, td {
            border: 1px solid #ddd;
            padding: 8px;
            text-align: left;
        }
        th {
            background-color: #f2f2f2;
        }
    </style>
</head>
<body>
<div id="app">
    <a-spin tip="加载中..." :spinning="loading">
        <div class="a4-container" id="contentToConvert" ref="contentToConvert">
            <h1>设备申领</h1>
            <p>审批编号: @{{ data.appr_sn }}</p>
            <p>创建人: @{{ data.admin_name }}</p>
            <p>审批名称: @{{ data.appr_name }}</p>
            <p>事由:</p>
            <p>@{{ data.appr_reason }}</p>
            <p>申请部门: @{{ data.depa_name }}</p>
            <p>申领用途: <span v-if="data.relation_data.maap_purpose == 1">销售用途</span><span v-else>销售用途</span></p>
            <p>申请物资:</p>
            <table>
                <thead>
                <tr>
                    <th width="60%">物品名称</th>
                    <th width="20%">数量</th>
                    <th width="20%">单位</th>
                </tr>
                </thead>
                <tbody>
                <tr v-for="(item, index) in data.relation_data.detail" :key="index">
                    <td>@{{ item.mate_name }}</td>
                    <td>@{{ item.maap_number }}</td>
                    <td>@{{ item.mate_unit }}</td>
                </tr>
                </tbody>
            </table>
            <p>物资总金额: @{{ data.relation_data.maap_total_price }}￥</p>
            <p>关联审批单:</p>
            <table>
                <thead>
                <tr>
                    <th width="30%">审批单编号</th>
                    <th width="30%">审批名称</th>
                    <th width="40%">审批事由</th>
                </tr>
                </thead>
                <tbody>
                <tr v-for="(item, index) in data.relation_approval" :key="index">
                    <td>@{{ item.appr_sn }}</td>
                    <td>@{{ item.appr_name }}</td>
                    <td>@{{ item.appr_reason }}</td>
                </tr>
                </tbody>
            </table>

            <p>附件: </p>
            <p v-for="(item, index) in data.relation_data.file_list" :key="index">@{{item.file_name}}.@{{item.file_ext}}</p>
            <p>备注:</p>
            <p>@{{ data.appr_remark }}</p>
            <p>审批流程:</p>
            <table>
                <thead>
                <tr>
                    <th width="20%">审批人</th>
                    <th width="20%">状态</th>
                    <th width="30%">时间</th>
                    <th width="30%">意见</th>
                </tr>
                </thead>
                <tbody>
                <tr v-for="(item, index) in data.process" :key="index">
                    <td>@{{ item.admin_name }}</td>
                    <td><span v-if="item.appr_type==1">已同意</span><span v-else>已抄送</span></td>
                    <td>@{{ item.appr_complete_date }}</td>
                    <td>@{{ item.appr_remark }}</td>
                </tr>
                </tbody>
            </table>
        </div>
    </a-spin>
</div>

<script>
    new Vue({
        el: '#app',
        data: {
            loading:false,
            data:{
                relation_data:{
                    detail:[],
                    file_list:[],
                },
            },
        },
        created () {
            this.getInfo();
        },
        methods: {
            getInfo(){
                let queryString = window.location.search;
                let urlParams = new URLSearchParams(queryString);
                let id = urlParams.get('id');
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
                        id:id,
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
                    this.data = res.data;
                    this.$forceUpdate();
                }).catch(error => {
                    this.$message.error('请求失败');
                });
            }
        },

    });
</script>
</body>
</html>
