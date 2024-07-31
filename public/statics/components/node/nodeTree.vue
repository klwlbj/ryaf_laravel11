<template>
    <table>
        <tbody>
        <tr>
            <td :colspan="(data.children.length > 0) ? data.children.length * 2 : 2">
                <div class="node focused">
                    <div class="title">
                        {{ data.name }}({{ data.type }})
                    </div>
                    <div class="content">{{data.children.length}}个子节点</div>
                </div>
            </td>
        </tr>
        <tr class="lines">
            <td v-if="data.children.length > 0" :colspan="data.children.length * 2">
                <div class="downLine"></div>
            </td>
        </tr>
        <tr class="lines">
            <td v-for="index in (data.children.length * 2)" :class="{rightLine:(index % 2),leftLine:!(index % 2),topLine:(index!==1) && (index!==(data.children.length * 2))}"></td>
        </tr>
        <tr class="nodes">
            <td v-for="item in data.children" colspan="2">
                <node-tree :data="item"></node-tree>
            </td>
        </tr>
        </tbody>
    </table>
</template>

<script>
module.exports = {
    name: 'nodeTree',
    components: {},
    props: {
        data: {
            default:function(){
                return []
            },
        },
    },
    data () {
        return {
            "node-tree":  httpVueLoader('/statics/components/node/nodeTree.vue'),
        }
    },
    methods: {

    },
    created () {

    },
    watch: {

    },
    computed: {

    }

}
</script>
<style scoped>

</style>

