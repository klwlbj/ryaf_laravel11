@extends('admin.layout')

@section('content')
    <div id="app" style="display: flex;justify-content: center;align-items: center;height: 100%">
        <div style="font-size: 32px;color:rgb(5, 52, 52)">
            如约安防信息化系统
        </div>

    </div>
@endsection

@section('script')
    <script>
        Vue.use(httpVueLoader)
        new Vue({
            el: '#app',
            data: {

            },
            created () {

            },
            components: {

            },
            methods: {

            },

        })
    </script>
@endsection
