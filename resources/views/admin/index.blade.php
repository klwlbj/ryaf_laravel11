@extends('admin.layout')

@section('content')
    <div id="app">
       欢迎光临
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
