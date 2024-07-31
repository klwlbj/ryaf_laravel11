@extends('admin.layout')

@section('link')
    <link rel="stylesheet" type="text/css"  href="{{asset('statics/css/node.min.css')}}">
@endsection

@section('content')
    <div id="app" style="text-align: center">
        <div class="orgchart">
            <node-tree :data="data"></node-tree>
        </div>

    </div>
@endsection

@section('script')
    <script>
        Vue.use(httpVueLoader)
        new Vue({
            el: '#app',
            data: {
                data:{
                    name:'石井街道办事处',
                    type:'街道办',
                    children:[
                        {
                            name:'演练专用街道救援站',
                            type:'',
                            children:[
                                {
                                    name:'演练专用村委',
                                    type:'村委',
                                    children:[
                                        {
                                            name:'演练专用街道救援站',
                                            type:'',
                                            children:[
                                                {
                                                    name:'演练专用村委',
                                                    type:'村委',
                                                    children:[

                                                    ]
                                                },
                                            ]
                                        },
                                    ]
                                },
                            ]
                        },
                        {
                            name:'顺丰翠园社区',
                            type:'村委',
                            children:[

                            ]
                        },
                        {
                            name:'石井社区',
                            type:'村委',
                            children:[

                            ]
                        },
                        {
                            name:'金碧新城社区',
                            type:'村委',
                            children:[

                            ]
                        },
                        {
                            name:'庆丰社区',
                            type:'村委',
                            children:[

                            ]
                        },
                        {
                            name:'兴隆社区',
                            type:'村委',
                            children:[

                            ]
                        },
                        {
                            name:'马岗社区',
                            type:'村委',
                            children:[

                            ]
                        },
                        {
                            name:'潭村联社',
                            type:'村委',
                            children:[
                                {
                                    name:'潭村第一经济社',
                                    type:'微型消防站',
                                    children:[

                                    ]
                                },
                                {
                                    name:'潭村第一经济社',
                                    type:'',
                                    children:[

                                    ]
                                },
                            ]
                        },
                        {
                            name:'张村社区',
                            type:'村委',
                            children:[

                            ]
                        },
                        {
                            name:'龙腾社区',
                            type:'村委',
                            children:[

                            ]
                        },
                        {
                            name:'新庄社区',
                            type:'村委',
                            children:[

                            ]
                        },
                        {
                            name:'凰岗社区',
                            type:'村委',
                            children:[

                            ]
                        },
                    ]
                }
            },
            created () {

            },
            components: {
                "node-tree":  httpVueLoader('/statics/components/node/nodeTree.vue'),
            },
            methods: {

            },

        })
    </script>
@endsection
