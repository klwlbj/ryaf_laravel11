<template>
    <div style="height: 100%; user-select: none;-moz-user-select: none;-webkit-user-select: none;-ms-user-select: none;">
        <ul>
            <li v-for="parent in list">
                <div class="parent-box" @click="parentClick(parent)" @mouseenter="handelMouse(parent,true)" @mouseleave="handelMouse(parent,false)">
                    <span :class="{enter:parent.enter}" style="width: 5px;height: 100%;position: absolute;left: 0"></span>
                    <span style="float: left">
                        <a-icon style="font-size: 10px" type="read" ></a-icon>
                    </span>

                    <span style="float: left;margin-left: 5px">{{ parent.label }}</span>

                    <span style="float: right">
                        <a-icon v-show="parent.spread" style="color:#134974 !important;" type="caret-up" ></a-icon>
                        <a-icon v-show="!parent.spread" style="color:#134974 !important;" type="caret-down" ></a-icon>
                    </span>

                </div>
                <div v-show="parent.spread" v-if="parent.child" class="child-box">

                    <a v-for="child in parent.child" :href="child.url" :class="{is_current:checkCurrent(child)}">
                        <a-icon style="" type="right" ></a-icon>
                        {{ child.label }}</a>
                </div>
            </li>
        </ul>
    </div>
</template>

<script>
module.exports = {
    name: 'adminMenu',
    components: {},
    props: {
        localUrl: {
            default:function(){
                return 'http://ryaf-laravel11.com'
            },
        },
    },
    data () {
        return {
            list:[
                {
                    id:1,
                    label:'首页',
                    spread: true,
                    child:[
                        {
                            id:2,
                            label:'首页',
                            url:'https://pingansuiyue.crzfxjzn.com/node/index.php?init=1',
                        }
                    ]
                },
                {
                    id:3,
                    label:'进销存',
                    spread: true,
                    child:[
                        {
                            id:4,
                            label:'订单管理',
                            url:this.localUrl +'/order/view',
                        },
                    ]
                },
                {
                    id:3,
                    label:'库存管理',
                    spread: true,
                    child:[
                        {
                            id:4,
                            label:'厂家管理',
                            url:this.localUrl +'/materialManufacturer/view',
                        },
                        {
                            id:4,
                            label:'分类管理',
                            url:this.localUrl +'/materialCategory/view',
                        },
                        {
                            id:5,
                            label:'规格管理',
                            url:this.localUrl +'/materialSpecification/view',
                        },
                        {
                            id:6,
                            label:'物品管理',
                            url:this.localUrl +'/material/view',
                        },
                        {
                            id:6,
                            label:'库存流水',
                            url:this.localUrl +'/materialFlow/view',
                        },
                        {
                            id:6,
                            label:'物品申购',
                            url:this.localUrl +'/materialPurchase/view',
                        },
                    ]
                }
            ]
        }
    },
    methods: {
        parentClick(row){
            row.spread = !row.spread;
        },
        handelMouse(row,status){
            row.enter = status;
            this.$forceUpdate();
        },
        checkCurrent(row){
            if(row.url.search(window.location.pathname) != -1){
                return true;
            }

            return false;
        }
    },
    created () {
        // console.log(this.list);
    },
    watch: {

    },
    computed: {

    }

}
</script>
<style scoped>
ul{
    height:100%;
    padding: 0;
    margin: 0;
    list-style: none;
    background-color: #134974 !important
}

li{
    display: block;
    width: 100%;
    line-height: 45px;
}
.parent-box{
    position: relative;
    height: 45px;
    line-height: 45px;
    text-overflow: ellipsis;
    overflow: hidden;
    white-space: nowrap;
    display: block;
    padding: 0 20px;
    color: #FFFFFF !important;
    background-color: #0A385C !important;
    cursor: pointer;
}
.child-box{
    position: relative;
    z-index: 0;
    top: 0;
    border: none;
    box-shadow: none;
    font-size: 12px;
}
.child-box a{
    display: block;
    padding: 0 20px;
    height: 40px;
    line-height: 40px;
    position: relative;
    text-overflow: ellipsis;
    overflow: hidden;
    white-space: nowrap;
    color: #FFFFFF !important;
    background-color: #134974;
}
.is_current{
    background-color: #009688 !important;
}
.enter{
    background-color: #009688 !important
}
</style>

